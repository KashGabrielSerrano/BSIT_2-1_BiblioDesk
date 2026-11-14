<?php
/**
 * backend/connection/config.example.php
 * TEMPLATE ONLY. Copy to config.php (git-ignored) and fill in real values. NEVER commit config.php.
 * Every value below is a placeholder; none of them work as they are.
 *
 * This file starts the session and the CSRF token, holds the secrets and environment settings, and
 * defines connection(). backend/core/bootstrap.php loads it.
 */

// Enforce secure session cookie parameters BEFORE starting session
if (session_status() === PHP_SESSION_NONE) {
    $is_secure = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Global CSRF Token Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Supabase Storage (server-side only; this file is gitignored, never echo these to the browser) ---
// Dashboard > Project Settings > API: the project URL and the "service_role" key (NOT the anon key).
if (!defined('SUPABASE_URL'))                define('SUPABASE_URL', 'https://YOUR-PROJECT-ID.supabase.co');
if (!defined('SUPABASE_SERVICE_KEY'))        define('SUPABASE_SERVICE_KEY', 'YOUR_SERVICE_ROLE_KEY');
// Names of the storage buckets you created (they must exist and be public)
if (!defined('SUPABASE_BUCKET_COVERS'))      define('SUPABASE_BUCKET_COVERS', 'book-covers');
if (!defined('SUPABASE_BUCKET_PDFS'))        define('SUPABASE_BUCKET_PDFS', 'pdf-books');
if (!defined('SUPABASE_BUCKET_PROFILEPICS')) define('SUPABASE_BUCKET_PROFILEPICS', 'profile-pictures');

// Book link secret (read_book.php?b=<token>). Keep private; changing it invalidates shared links/QR codes.
// Must be exactly 64 hex characters. Generate one with:  php -r "echo bin2hex(random_bytes(32));"
if (!defined('BOOK_LINK_KEY'))               define('BOOK_LINK_KEY', 'GENERATE_A_64_CHARACTER_HEX_KEY');

// Public address of the backend folder, used in share links / QR codes.
// Local XAMPP: http://localhost/YOUR_FOLDER/backend/    Hosted: https://your-domain.example/backend/
if (!defined('APP_BASE_URL'))                define('APP_BASE_URL', 'https://your-domain.example/backend/');

// --- Optional ---
// Hosts allowed for book PDF links. Defaults to the host of SUPABASE_URL when unset.
// if (!defined('DRIVE_LINK_ALLOWED_HOSTS')) define('DRIVE_LINK_ALLOWED_HOSTS', ['YOUR-PROJECT-ID.supabase.co']);

function connection() {
    date_default_timezone_set('UTC');

    // Local XAMPP defaults. On a host such as InfinityFree use the MySQL host name, database name,
    // user and password shown in its control panel (the host is NOT "localhost" there).
    $host   = "localhost";
    $user   = "root";
    $pass   = "";
    $dbname = "finals_project_db";

    $con = new mysqli($host, $user, $pass, $dbname);
    if ($con->connect_error) {
        die("Database connection failed: " . $con->connect_error);
    }

    $con->query("SET time_zone = '+00:00';");

    return $con;
}
