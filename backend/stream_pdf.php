<?php
require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/fpdf.php";
require_once __DIR__ . "/helpers/fpdi/src/autoload.php";

$con = connection();
use setasign\Fpdi\Fpdi;

while (ob_get_level()) {
    ob_end_clean();
}

$token = $_GET['token'] ?? '';
if (empty($token) || !isset($_SESSION['image_tokens'][$token])) {
    http_response_code(403);
    exit("Invalid or missing access token.");
}

$token_data = $_SESSION['image_tokens'][$token];
$user_id    = $_SESSION['user_id'] ?? null;

if ($token_data['user_id'] !== $user_id || time() > $token_data['expires_at']) {
    unset($_SESSION['image_tokens'][$token]);
    http_response_code(403);
    exit("Token authorization failed or expired.");
}

unset($_SESSION['image_tokens'][$token]);
$book_id = intval($token_data['book_id']);

$stmt = $con->prepare("SELECT Drive_Link FROM book_table WHERE Book_ID = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book || empty($book['Drive_Link'])) {
    http_response_code(404);
    exit("Book record missing.");
}

$supabase_url = trim($book['Drive_Link']);

$opts = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
        "follow_location" => 1,
        "max_redirects" => 5,
        "timeout" => 30
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
        "allow_self_signed" => true
    ]
];

$context = stream_context_create($opts);
$pdf_binary = @file_get_contents($supabase_url, false, $context);

if ($pdf_binary === false || substr($pdf_binary, 0, 4) !== '%PDF') {
    http_response_code(500);
    exit("Failed to retrieve document stream.");
}

$temp_pdf = tempnam(sys_get_temp_dir(), 'pdf_orig_');
file_put_contents($temp_pdf, $pdf_binary);

$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$target_url = "{$scheme}://{$host}/read_book.php?id={$book_id}";

$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($target_url);
$qr_binary = @file_get_contents($qr_api_url, false, $context);

if ($qr_binary === false) {
    @unlink($temp_pdf);
    http_response_code(500);
    exit("Failed to generate QR code stream.");
}

$temp_qr = tempnam(sys_get_temp_dir(), 'qr_img_') . '.png';
file_put_contents($temp_qr, $qr_binary);

 /**
 * Check if image is dark based on average brightness.
 *
 * @param string $image_path Path to PNG or JPEG file.
 * @return bool True if dark, false if light or unreadable.
 */
function isImageDark(string $image_path): bool {
    if (!extension_loaded('gd') || !file_exists($image_path)) {
        return false;
    }

    $gd_img = @imagecreatefrompng($image_path);
    if (!$gd_img) {
        return false;
    }

    $total_brightness = 0;
    $sample_count = 0;
    $w = imagesx($gd_img);
    $h = imagesy($gd_img);

    for ($x = 0; $x < $w; $x += 5) {
        for ($y = 0; $y < $h; $y += 5) {
            $rgb = imagecolorat($gd_img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $brightness = (0.299 * $r + 0.587 * $g + 0.114 * $b);
            $total_brightness += $brightness;
            $sample_count++;
        }
    }
    if ($sample_count === 0) {
        return false;
    }

    return (($total_brightness / $sample_count) < 128);
}

try {
    $is_dark_qr = isImageDark($temp_qr);
    $pdf = new Fpdi();
    $pdf->SetAutoPageBreak(false);
    
    $pageCount = $pdf->setSourceFile($temp_pdf);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size = $pdf->getTemplateSize($templateId);


        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);

        // Overlay QR Code and Label on Page 1 only. THIS IS IN MM.
        if ($pageNo === 1) {
            $qrWidth = 25;  // mm
            $qrHeight = 25; // mm
            $margin = 10;   // mm from right/bottom edges

            $xPos = $size['width'] - $qrWidth - $margin;
            $yPos = $size['height'] - $qrHeight - $margin;
            $textYPos = $yPos - 5; // 5mm above QR code top edge

            $pdf->SetFont('Arial', 'B', 8);
            if ($is_dark_qr) {
                $pdf->SetTextColor(255, 255, 255); // White text if QR background is dark
            } else {
                $pdf->SetTextColor(80, 80, 80);     // Dark gray text for standard QR
            }

            $pdf->SetXY($xPos - 5, $textYPos);
            $pdf->Cell($qrWidth + 10, 4, 'Property of BiblioDesk', 0, 0, 'C');
            $pdf->Image($temp_qr, $xPos, $yPos, $qrWidth, $qrHeight);
        }
    }

    @unlink($temp_pdf);
    @unlink($temp_qr);

    header("Content-Type: application/pdf");
    header("Content-Disposition: inline; filename=\"secure_document.pdf\"");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");

    $pdf->Output('I', 'secure_document.pdf');
    exit();

} catch (\Exception $e) {
    @unlink($temp_pdf);
    @unlink($temp_qr);
    http_response_code(500);
    exit("Error processing PDF watermarking: " . $e->getMessage());
}