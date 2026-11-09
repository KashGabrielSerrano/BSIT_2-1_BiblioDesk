<?php
/**
 * backend/connection/config.example.php
 * Copy to config.php (git-ignored) and fill in real values. NEVER commit config.php.
 *
 * Only secrets and environment settings belong here. The session, CSRF token and all helper
 * functions are set up by backend/core/bootstrap.php, which loads this file.
 */

// --- Supabase Storage (server-side only; never echo these to the browser) ---
if (!defined('SUPABASE_URL'))            define('SUPABASE_URL', 'https://YOUR-PROJECT-ID.supabase.co');
if (!defined('SUPABASE_SERVICE_KEY'))    define('SUPABASE_SERVICE_KEY', 'YOUR_SERVICE_ROLE_KEY');
if (!defined('SUPABASE_BUCKET_COVERS')) define('SUPABASE_BUCKET_COVERS', 'book-covers');
if (!defined('SUPABASE_BUCKET_PDFS'))    define('SUPABASE_BUCKET_PDFS', 'YOUR_PDF_BUCKET');

// --- Optional ---
// Public URL of the backend folder; used for share links / QR codes. Leave unset locally.
// if (!defined('APP_BASE_URL')) define('APP_BASE_URL', 'https://your-domain.example/backend/');
// Hosts allowed for book PDF links. Defaults to the host of SUPABASE_URL when unset.
// if (!defined('DRIVE_LINK_ALLOWED_HOSTS')) define('DRIVE_LINK_ALLOWED_HOSTS', ['YOUR-PROJECT-ID.supabase.co']);

function connection() {
    date_default_timezone_set('UTC');

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
