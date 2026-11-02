<?php
/**
 * stream_pdf.php
 * Fetches the PDF from Supabase Storage, stamps a "Property of BiblioDesk"
 * label + QR code on page 1 with FPDI, and returns the raw bytes to PDF.js.
 *
 * Called ONLY via fetch() POST from read_book.php (see notes there).
 * NOTE: no closing "?>" tag on purpose - a trailing newline after it would be
 * sent before the PDF bytes and corrupt the file.
 */

// ---------------------------------------------------------------------------
// 0. Output hygiene - nothing may reach the client except the final PDF bytes
// ---------------------------------------------------------------------------
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();                                   // swallow notices / BOMs / whitespace from includes
ini_set('display_errors', '0');               // warnings must never be mixed into binary output
ini_set('zlib.output_compression', 'Off');    // gzip would invalidate our Content-Length
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');           // stop mod_deflate from re-encoding the body
}

require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/fpdf.php";
require_once __DIR__ . "/helpers/fpdi/src/autoload.php";

use setasign\Fpdi\Fpdi;

// Set to true once curl.cainfo / openssl.cafile is configured in php.ini (XAMPP).
const VERIFY_SSL = false;

// Temp files are always removed, even if we exit() early or an error is thrown.
$GLOBALS['__tmp_files'] = [];
register_shutdown_function(function () {
    foreach ($GLOBALS['__tmp_files'] as $f) {
        if (is_string($f) && file_exists($f)) {
            @unlink($f);
        }
    }
});

function fail(int $code, string $message): void
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    header("Content-Type: text/plain; charset=utf-8");
    header("Cache-Control: no-store");
    echo $message;
    exit;
}

// The session is needed to validate the token. (config.php may already start it.)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ---------------------------------------------------------------------------
// 1. Request + token validation (POST only, so download managers cannot replay it)
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fail(405, "Method not allowed.");
}

$token = $_POST['token'] ?? '';
if (!is_string($token) || $token === '' || !isset($_SESSION['image_tokens'][$token])) {
    fail(403, "Invalid or missing access token.");
}

$token_data = $_SESSION['image_tokens'][$token];
$user_id    = $_SESSION['user_id'] ?? null;

// Single-use: burn the token immediately, whatever happens next.
unset($_SESSION['image_tokens'][$token]);

if ($token_data['user_id'] !== $user_id || time() > $token_data['expires_at']) {
    fail(403, "Token authorization failed or expired.");
}

$book_id = intval($token_data['book_id']);

// Release the session lock now - the remote fetches below can take seconds and
// would otherwise block every other request from this user.
session_write_close();

$con  = connection();
$stmt = $con->prepare("SELECT Drive_Link FROM book_table WHERE Book_ID = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book || empty($book['Drive_Link'])) {
    fail(404, "Book record missing.");
}

$supabase_url = trim($book['Drive_Link']);
if (strtolower((string) parse_url($supabase_url, PHP_URL_SCHEME)) !== 'https') {
    fail(500, "Invalid storage URL.");
}

// ---------------------------------------------------------------------------
// 2. Remote fetch helper
// ---------------------------------------------------------------------------
function fetchRemoteData(string $url)
{
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_SSL_VERIFYPEER => VERIFY_SSL,
            CURLOPT_SSL_VERIFYHOST => VERIFY_SSL ? 2 : 0,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($data !== false && $code >= 200 && $code < 300) {
            return $data;
        }
    }

    $opts = [
        "http" => [
            "method"          => "GET",
            "header"          => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
            "follow_location" => 1,
            "max_redirects"   => 5,
            "timeout"         => 30,
        ],
        "ssl" => [
            "verify_peer"       => VERIFY_SSL,
            "verify_peer_name"  => VERIFY_SSL,
            "allow_self_signed" => !VERIFY_SSL,
        ],
    ];
    return @file_get_contents($url, false, stream_context_create($opts));
}

// ---------------------------------------------------------------------------
// 3. Download the PDF from Supabase + generate QR code
// ---------------------------------------------------------------------------
$pdf_binary = fetchRemoteData($supabase_url);
if ($pdf_binary === false || substr($pdf_binary, 0, 4) !== '%PDF') {
    fail(502, "Failed to retrieve document stream from Supabase.");
}

$temp_pdf = tempnam(sys_get_temp_dir(), 'pdf_orig_');
$GLOBALS['__tmp_files'][] = $temp_pdf;
file_put_contents($temp_pdf, $pdf_binary);
unset($pdf_binary);

$scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'];
$target_url = "{$scheme}://{$host}/read_book.php?id={$book_id}";

$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($target_url);
$qr_binary  = fetchRemoteData($qr_api_url);
if ($qr_binary === false) {
    fail(502, "Failed to generate QR code stream.");
}

$temp_qr_base = tempnam(sys_get_temp_dir(), 'qr_img_');
$temp_qr      = $temp_qr_base . '.png';
$GLOBALS['__tmp_files'][] = $temp_qr_base;   // tempnam() creates this file too - clean it up
$GLOBALS['__tmp_files'][] = $temp_qr;
file_put_contents($temp_qr, $qr_binary);
unset($qr_binary);

/**
 * Check if image is dark based on average brightness.
 */
function isImageDark(string $image_path): bool
{
    if (!extension_loaded('gd') || !file_exists($image_path)) {
        return false;
    }

    $gd_img = @imagecreatefrompng($image_path);
    if (!$gd_img) {
        return false;
    }
    if (!imageistruecolor($gd_img)) {
        imagepalettetotruecolor($gd_img);   // imagecolorat() returns a palette index otherwise
    }

    $total_brightness = 0;
    $sample_count     = 0;
    $w = imagesx($gd_img);
    $h = imagesy($gd_img);

    for ($x = 0; $x < $w; $x += 5) {
        for ($y = 0; $y < $h; $y += 5) {
            $rgb = imagecolorat($gd_img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $total_brightness += (0.299 * $r + 0.587 * $g + 0.114 * $b);
            $sample_count++;
        }
    }
    imagedestroy($gd_img);

    if ($sample_count === 0) {
        return false;
    }
    return (($total_brightness / $sample_count) < 128);
}

// ---------------------------------------------------------------------------
// 4. Overlay QR + label on page 1 with FPDI
// ---------------------------------------------------------------------------
$pdf_out = null;

try {
    $is_dark_qr = isImageDark($temp_qr);
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(false);

    $pageCount = $pdf->setSourceFile($temp_pdf);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size       = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);

        // Overlay QR Code and property label on Page 1 only
        if ($pageNo === 1) {
            $qrWidth  = 25;  // mm
            $qrHeight = 25;  // mm
            $margin   = 10;  // mm from right/bottom edges

            $xPos     = $size['width']  - $qrWidth  - $margin;
            $yPos     = $size['height'] - $qrHeight - $margin;
            $textYPos = $yPos - 5; // 5mm above QR code top edge

            $pdf->SetFont('Arial', 'B', 8);
            if ($is_dark_qr) {
                $pdf->SetTextColor(255, 255, 255);
            } else {
                $pdf->SetTextColor(80, 80, 80);
            }

            $pdf->SetXY($xPos - 5, $textYPos);
            $pdf->Cell($qrWidth + 10, 4, 'Property of BiblioDesk', 0, 0, 'C');
            $pdf->Image($temp_qr, $xPos, $yPos, $qrWidth, $qrHeight);
        }
    }

    $pdf_out = $pdf->Output('S');   // returns the PDF as a string

} catch (\Throwable $e) {           // Throwable also catches PHP 8 TypeErrors etc.
    error_log("stream_pdf.php watermark error (book {$book_id}): " . $e->getMessage());
    fail(500, "Error processing PDF watermarking: " . $e->getMessage());
}

// ---------------------------------------------------------------------------
// 5. Send the bytes - discard ALL buffered junk first, then headers, then body
// ---------------------------------------------------------------------------
if (!is_string($pdf_out) || $pdf_out === '') {
    fail(500, "Failed to generate watermarked document.");
}

// Base64 text: download managers (IDM) only capture binary/downloadable MIME
// types, so a text/plain body is ignored. read_book.php decodes it in JS.
$payload = base64_encode($pdf_out);
unset($pdf_out);

while (ob_get_level()) {
    ob_end_clean();
}

http_response_code(200);
header("Content-Type: text/plain; charset=utf-8");
header("X-Content-Type-Options: nosniff");
header("Content-Length: " . strlen($payload));
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

echo $payload;
exit;