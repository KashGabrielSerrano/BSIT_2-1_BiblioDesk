<?php
/**
 * stream_pdf.php
 * Fetches the book's PDF from Supabase Storage and returns it (base64 text) to PDF.js
 * in read_book.php. The PDF itself is passed through untouched.
 *
 * Called ONLY via fetch() POST from read_book.php with a single-use, 60-second token.
 * NOTE: no closing "?>" tag on purpose - a trailing newline after it would be
 * sent before the payload and could corrupt it.
 */

// ---------------------------------------------------------------------------
// 0. Output hygiene - nothing may reach the client except the final payload
// ---------------------------------------------------------------------------
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();                                   // swallow notices / BOMs / whitespace from includes
ini_set('display_errors', '0');               // warnings must never be mixed into the output
ini_set('zlib.output_compression', 'Off');    // gzip would invalidate our Content-Length
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');           // stop mod_deflate from re-encoding the body
}

require_once __DIR__ . "/connection/config.php";

// Set to true once curl.cainfo / openssl.cafile is configured in php.ini (XAMPP).
const VERIFY_SSL = false;

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

// Release the session lock now - the remote fetch below can take seconds and
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
// 3. Download the PDF from Supabase
// ---------------------------------------------------------------------------
$pdf_binary = fetchRemoteData($supabase_url);
if ($pdf_binary === false || substr($pdf_binary, 0, 4) !== '%PDF') {
    fail(502, "Failed to retrieve document stream from Supabase.");
}

// ---------------------------------------------------------------------------
// 4. Send the bytes - discard ALL buffered junk first, then headers, then body
// ---------------------------------------------------------------------------
// Base64 text: download managers (IDM) only capture binary/downloadable MIME
// types, so a text/plain body is ignored. read_book.php decodes it in JS.
$payload = base64_encode($pdf_binary);
unset($pdf_binary);

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
