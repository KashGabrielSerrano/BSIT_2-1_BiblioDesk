<?php
session_start();
header("Content-Type: application/json");

$book_id = intval($_POST['book_id'] ?? 0);
$page    = intval($_POST['page'] ?? 1);
$user_id = $_SESSION['user_id'] ?? null;

if (!$book_id || !$user_id) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized request."]);
    exit();
}

// Generate an ephemeral random token
$token = bin2hex(random_bytes(16));

if (!isset($_SESSION['image_tokens'])) {
    $_SESSION['image_tokens'] = [];
}

// Store token details (valid for 5 seconds)
$_SESSION['image_tokens'][$token] = [
    'user_id'    => $user_id,
    'book_id'    => $book_id,
    'page'       => $page,
    'expires_at' => time() + 5
];

echo json_encode([
    'success' => true,
    'token'   => $token,
    'page'    => $page
]);