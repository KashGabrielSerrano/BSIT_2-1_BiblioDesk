<?php

const BOOK_LINK_PARAM  = 'b';
const BOOK_LINK_CIPHER = 'aes-256-gcm';
const BOOK_LINK_LENGTH = 43; 

function book_link_keys(): array {
    static $keys = null;
    if ($keys !== null) {
        return $keys;
    }
    $secret = defined('BOOK_LINK_KEY') ? (string)BOOK_LINK_KEY : '';
    if (!preg_match('/^[0-9a-f]{64}$/i', $secret)) {
        throw new RuntimeException('BOOK_LINK_KEY is missing or invalid in backend/connection/config.php (64 characters).');
    }
    $root = hash('sha256', 'bibliodesk/book-link/v1|' . strtolower($secret), true);
    return $keys = [
        'enc' => hash_hmac('sha256', 'enc', $root, true),
        'iv'  => hash_hmac('sha256', 'iv', $root, true),
    ];
}

function book_token(int $book_id): string {
    $keys = book_link_keys();
    $iv   = substr(hash_hmac('sha256', pack('N', $book_id), $keys['iv'], true), 0, 12);
    $tag  = '';
    $ct   = openssl_encrypt(pack('N', $book_id), BOOK_LINK_CIPHER, $keys['enc'], OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ct === false || strlen($tag) !== 16) {
        throw new RuntimeException('Could not create a book link token (is the PHP openssl extension enabled?).');
    }
    return rtrim(strtr(base64_encode($iv . $ct . $tag), '+/', '-_'), '=');
}

function book_id_from_token(string $token): ?int {
    if (!preg_match('/^[A-Za-z0-9_-]{' . BOOK_LINK_LENGTH . '}$/', $token)) {
        return null;
    }
    $raw = base64_decode(strtr($token, '-_', '+/') . '=', true);
    if ($raw === false || strlen($raw) !== 32) {
        return null;
    }
    $keys  = book_link_keys();
    $plain = openssl_decrypt(substr($raw, 12, 4), BOOK_LINK_CIPHER, $keys['enc'], OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 16, 16));
    if ($plain === false || strlen($plain) !== 4) {
        return null;                       
    }
    $id = (int)unpack('N', $plain)[1];
    return $id > 0 && hash_equals(book_token($id), $token) ? $id : null;
}

function book_id_param(array $src, string $key = 'book_id'): int {
    $v = $src[$key] ?? '';
    return is_string($v) ? (book_id_from_token(trim($v)) ?? 0) : 0;
}
