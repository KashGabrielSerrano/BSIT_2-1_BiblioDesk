<?php
session_start();
require_once "connection/config.php";
$con = connection();

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

header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"secure_document.pdf\"");
header("Content-Length: " . strlen($pdf_binary));
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

echo $pdf_binary;
exit();