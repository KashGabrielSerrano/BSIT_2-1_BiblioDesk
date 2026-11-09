<?php
/**
 * backend/core/bootstrap.php
 * THE shared file. Every page and controller that needs the session, the database or a helper
 * starts with:   require_once __DIR__ . '/core/bootstrap.php';   (adjust the path from subfolders)
 *
 * What it does on include (in this order — the order matters):
 *   1. Starts the session with hardened cookie settings (BEFORE config.php, so they always apply)
 *   2. Loads connection/config.php (DB credentials, Supabase keys) and SystemOperators
 *   3. Makes sure a CSRF token exists
 * Everything else is a function you call when you need it. Nothing is printed by this file.
 *
 * Sections:  1. Session & config   2. Shared objects (db, so)   3. JSON responses
 *            4. Security guards     5. DB helpers                 6. Input & encryption helpers
 *            7. Login session & remember-me cookie
 *
 * NOTE: connection/config.php and helpers/SystemOperators.php are git-ignored (they hold secrets).
 *       This file works with the existing live copies unchanged.
 */

/* ========================================================================== */
/* 1. SESSION & CONFIG                                                        */
/* ========================================================================== */

if (!defined('BIBLIODESK_CORE')) {
    define('BIBLIODESK_CORE', true);

    date_default_timezone_set('UTC');

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    require_once __DIR__ . '/../connection/config.php';
    require_once __DIR__ . '/../helpers/SystemOperators.php';

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Optional config values: config.php may define them; otherwise sensible defaults.
    if (!defined('APP_BASE_URL')) {
        // Public base URL of the backend folder, used for share links / QR codes.
        // Set it in config.php on the live server, e.g. define('APP_BASE_URL', 'https://example.com/backend/');
        define('APP_BASE_URL', '');
    }
    if (!defined('DRIVE_LINK_ALLOWED_HOSTS')) {
        // Only files on our own Supabase project may be linked as a book PDF.
        $supabase_host = defined('SUPABASE_URL') ? parse_url(SUPABASE_URL, PHP_URL_HOST) : null;
        define('DRIVE_LINK_ALLOWED_HOSTS', is_string($supabase_host) && $supabase_host !== '' ? [$supabase_host] : []);
    }
}

function is_https(): bool {
    return isset($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) === 'on';
}

/* ========================================================================== */
/* 2. SHARED OBJECTS                                                          */
/* ========================================================================== */

/** The one database connection for this request (created on first use). */
function db(): mysqli {
    static $con = null;
    if ($con === null) {
        $con = connection();
        $con->set_charset('utf8mb4');
    }
    return $con;
}

/** The one SystemOperators instance (encryption, OTP, email, client IP). */
function so(): SystemOperators {
    static $so = null;
    return $so ??= new SystemOperators();
}

function client_ip(): string {
    return so()->getClientIP();
}

/* ========================================================================== */
/* 3. JSON RESPONSES (controllers)                                            */
/* ========================================================================== */

/**
 * Send {"success", "message", ...extra} and stop.
 * $status: HTTP status code; null keeps whatever was already set (default 200).
 * Any stray output buffered before this call (PHP notices, BOMs) is discarded.
 */
function sendResponse(bool $success, string $message, array $extra = [], ?int $status = null): void {
    if (ob_get_level() > 0 && ob_get_length()) {
        ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        if ($status !== null) {
            http_response_code($status);
        }
    }
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit();
}

/** On localhost only, the real exception text for debugging. Remote requests get nothing. */
function local_debug(Throwable $e): array {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip !== '127.0.0.1' && $ip !== '::1') {
        return [];
    }
    return ['debug' => get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine()];
}

/* ========================================================================== */
/* 4. SECURITY GUARDS                                                         */
/* ========================================================================== */

/** Reject a POST whose CSRF token (form field or X-CSRF-Token header) does not match the session. */
function require_csrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($submitted) || $submitted === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        error_log('Security Warning: Potential CSRF attempt blocked from IP ' . client_ip());
        sendResponse(false, 'Your session has expired or the request could not be verified. Please reload the page.', [], 403);
    }
}

/**
 * The logged-in user, re-checked against the database on every request:
 * ['user_id' => int, 'role' => 'Student'|'Librarian'|'Admin', 'status' => 'Active'].
 * Returns null when nobody is logged in. If the account was deleted, suspended or is no longer
 * Active, the session is ended on the spot (so suspension takes effect immediately).
 * The session role is kept in sync with the DB (so a role change applies on the next request).
 */
function current_user(): ?array {
    static $checked = false, $user = null;
    if ($checked) {
        return $user;
    }
    $checked = true;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $id = (int)$_SESSION['user_id'];
    $row = db_one("SELECT User_ID, Role, Account_Status FROM user_table WHERE User_ID = ? LIMIT 1", 'i', [$id]);

    if (!$row || $row['Account_Status'] !== 'Active') {
        end_user_session();
        return null;
    }

    $_SESSION['role'] = $row['Role'];
    $user = ['user_id' => (int)$row['User_ID'], 'role' => $row['Role'], 'status' => $row['Account_Status']];
    return $user;
}

/** For pages: send logged-out (or no-longer-active) visitors to the login page. */
function require_login_page(): array {
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit();
    }
    return $user;
}

/** For pages: only the given roles may open the page; everyone else goes to the login page. */
function require_role_page(array $roles): array {
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        header('Location: login.php');
        exit();
    }
    return $user;
}

/** For controllers: 401 JSON when not logged in. */
function require_login_api(string $message = 'Unauthorized session. Please log in.'): array {
    $user = current_user();
    if (!$user) {
        sendResponse(false, $message, [], 401);
    }
    return $user;
}

/** For controllers: 403 JSON unless the user has one of the roles. */
function require_role_api(array $roles, string $message = 'Access denied.'): array {
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        sendResponse(false, $message, [], 403);
    }
    return $user;
}

/** Where each role lands after logging in. */
function dashboard_for_role(?string $role): string {
    return in_array($role, ['Librarian', 'Admin'], true) ? 'librarymanagement.php' : 'userdb_overview.php';
}

/* ========================================================================== */
/* 5. DB HELPERS (prepared statements only)                                   */
/* ========================================================================== */

/** Run a prepared SELECT and return all rows as associative arrays. */
function db_all(mysqli $con, string $sql, string $types = '', array $params = []): array {
    $stmt = $con->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** First row of a prepared SELECT (or null), on the shared connection. */
function db_one(string $sql, string $types = '', array $params = []): ?array {
    return db_all(db(), $sql, $types, $params)[0] ?? null;
}

/** Run a prepared INSERT / UPDATE / DELETE on the shared connection. Returns affected rows. */
function db_exec(string $sql, string $types = '', array $params = []): int {
    $stmt = db()->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

/* ========================================================================== */
/* 6. INPUT & ENCRYPTION HELPERS                                              */
/* ========================================================================== */

/** Scalar-string reader for $_GET / $_POST (arrays and nulls become ''). */
function gv(array $src, string $key): string {
    $v = $src[$key] ?? '';
    return is_scalar($v) ? trim((string)$v) : '';
}

/** Escape LIKE wildcards so user input is matched literally. */
function like_term(string $s): string {
    return '%' . addcslashes($s, '%_\\') . '%';
}

/** Decrypt a SystemOperators-encrypted field; never returns false/null. */
function dec(SystemOperators $so, $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $out = $so->decrypt($value);
    return is_string($out) ? $out : '';
}

/** Encrypt a sensitive field via SystemOperators before it is bound to SQL. */
function enc(SystemOperators $so, $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $out = $so->encrypt($value);
    return is_string($out) ? $out : '';
}

/* ========================================================================== */
/* 7. LOGIN SESSION & REMEMBER-ME COOKIE                                      */
/* ========================================================================== */

const REMEMBER_ME_DAYS = 30;

/** Fill the session after a successful login. $user needs User_ID, Email, Role, Student_ID (encrypted). */
function start_user_session(array $user): void {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['user_id']    = $user['User_ID'];
    $_SESSION['email']      = so()->decrypt($user['Email']);
    $_SESSION['role']       = $user['Role'];
    $_SESSION['student_id'] = !empty($user['Student_ID']) ? so()->decrypt($user['Student_ID']) : null;
}

/** Log out: forget the session completely and delete the session cookie. */
function end_user_session(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/** Create a remember-me token for the user (stored hashed) and send the cookie. */
function issue_remember_token(int $user_id): void {
    $token      = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + REMEMBER_ME_DAYS * 86400);
    db_exec(
        "INSERT INTO user_tokens (user_id, token_hash, user_agent, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)",
        'issss',
        [$user_id, hash('sha256', $token), $_SERVER['HTTP_USER_AGENT'] ?? '', client_ip(), $expires_at]
    );
    set_remember_cookie($token);
}

function set_remember_cookie(string $token): void {
    setcookie('remember_me', $token, [
        'expires'  => time() + REMEMBER_ME_DAYS * 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function clear_remember_cookie(): void {
    setcookie('remember_me', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => '',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Delete this browser's remember-me token from the DB and the cookie (used by logout). */
function forget_remember_token(): void {
    if (!empty($_COOKIE['remember_me'])) {
        db_exec("DELETE FROM user_tokens WHERE token_hash = ?", 's', [hash('sha256', (string)$_COOKIE['remember_me'])]);
        clear_remember_cookie();
    }
}
