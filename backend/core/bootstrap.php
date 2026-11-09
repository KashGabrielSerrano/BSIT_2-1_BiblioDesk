<?php
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
    if (!defined('APP_BASE_URL')) {
        define('APP_BASE_URL', '');
    }
    if (!defined('DRIVE_LINK_ALLOWED_HOSTS')) {
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


function db(): mysqli {
    static $con = null;
    if ($con === null) {
        $con = connection();
        $con->set_charset('utf8mb4');
        $mode = (string)($con->query('SELECT @@SESSION.sql_mode')->fetch_row()[0] ?? '');
        if (stripos($mode, 'STRICT_TRANS_TABLES') === false) {
            $mode = trim($mode . ',STRICT_TRANS_TABLES', ',');
            $stmt = $con->prepare('SET SESSION sql_mode = ?');
            $stmt->bind_param('s', $mode);
            $stmt->execute();
            $stmt->close();
        }
    }
    return $con;
}

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
        remember_return_to();
        header('Location: login.php');
        exit();
    }
    return $user;
}

/** For pages: only the given roles may open the page; everyone else goes to the login page. */
function require_role_page(array $roles): array {
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        if (!$user) {
            remember_return_to();
        }
        header('Location: login.php');
        exit();
    }
    return $user;
}

/**
 * Remember the page a logged-out visitor asked for (e.g. a scanned book QR code), so login can
 * send them back to it instead of the dashboard. Only plain GET requests for a page in this
 * folder are remembered (no full URLs, so it can never redirect to another site).
 */
function remember_return_to(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }
    $target = basename((string)parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
    $query  = (string)($_SERVER['QUERY_STRING'] ?? '');
    $candidate = $target . ($query !== '' ? '?' . $query : '');
    if (is_safe_return_to($candidate)) {
        $_SESSION['return_to'] = $candidate;
    }
}

function is_safe_return_to(string $path): bool {
    return (bool)preg_match('/^[A-Za-z0-9_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$/', $path)
        && !preg_match('/^(login|registration|login_controller|registration_controller|stream_pdf)\.php/', $path);
}

/** Where to go right after logging in: the remembered page (once), otherwise the role's dashboard. */
function after_login_redirect(?string $role): string {
    $target = $_SESSION['return_to'] ?? '';
    unset($_SESSION['return_to']);
    return is_string($target) && is_safe_return_to($target) ? $target : dashboard_for_role($role);
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


function db_one(string $sql, string $types = '', array $params = []): ?array {
    return db_all(db(), $sql, $types, $params)[0] ?? null;
}


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


function gv(array $src, string $key): string {
    $v = $src[$key] ?? '';
    return is_scalar($v) ? trim((string)$v) : '';
}


function like_term(string $s): string {
    return '%' . addcslashes($s, '%_\\') . '%';
}


function dec(SystemOperators $so, $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $out = $so->decrypt($value);
    return is_string($out) ? $out : '';
}


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


function start_user_session(array $user): void {
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['user_id']    = $user['User_ID'];
    $_SESSION['email']      = so()->decrypt($user['Email']);
    $_SESSION['role']       = $user['Role'];
    $_SESSION['student_id'] = !empty($user['Student_ID']) ? so()->decrypt($user['Student_ID']) : null;
}


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

/* ========================================================================== */
/* 8. PROFILE (decrypted details of the logged-in user)                       */
/* ========================================================================== */

const DEPARTMENT_LABELS = [
    'BSIT' => 'BS Information Technology',
    'BSCS' => 'BS Computer Science',
    'BSIS' => 'BS Information Systems',
    'BSBA' => 'BS Business Administration',
    'BSA'  => 'BS Accountancy',
];


function current_profile(): ?array {
    static $profile = null;
    if ($profile !== null) {
        return $profile;
    }
    $user = current_user();
    if (!$user) {
        return null;
    }
    $row = db_one(
        "SELECT Full_Name, Student_ID, Department, Birthday, Email, Role, Account_Status, Created_At
           FROM user_table WHERE User_ID = ?",
        'i',
        [$user['user_id']]
    );
    $so = so();

    $full   = trim(dec($so, $row['Full_Name'] ?? ''));
    $parts  = $full === '' ? [] : preg_split('/\s+/', $full);
    $suffix = '';
    if (count($parts) > 2 && preg_match('/^(Jr\.?|Sr\.?|I{1,3}|IV|V)$/i', end($parts))) {
        $suffix = array_pop($parts);
    }
    $last  = count($parts) > 1 ? array_pop($parts) : '';
    $first = implode(' ', $parts);

    $sid  = dec($so, $row['Student_ID'] ?? '');
    $dept = dec($so, $row['Department'] ?? '');

    $birthday = '';
    $bday_raw = dec($so, $row['Birthday'] ?? '');
    if ($bday_raw !== '' && ($d = DateTime::createFromFormat('m-d-Y', $bday_raw))) {
        $birthday = $d->format('Y-m-d');
    }

    return $profile = [
        'full_name'         => $full !== '' ? $full : 'BiblioDesk User',
        'first_name'        => $first,
        'last_name'         => $last,
        'suffix'            => $suffix,
        'student_id'        => $sid,
        'student_id_masked' => $sid !== '' ? mb_substr($sid, 0, 5) . str_repeat('*', max(0, mb_strlen($sid) - 5)) : '',
        'department'        => $dept,
        'department_label'  => DEPARTMENT_LABELS[$dept] ?? $dept,
        'email'             => dec($so, $row['Email'] ?? ''),
        'birthday'          => $birthday,
        'role'              => $row['Role'],
        'status'            => $row['Account_Status'],
        'created_at'        => $row['Created_At'],
    ];
}

/** Delete this browser's remember-me token from the DB and the cookie (used by logout). */
function forget_remember_token(): void {
    if (!empty($_COOKIE['remember_me'])) {
        db_exec("DELETE FROM user_tokens WHERE token_hash = ?", 's', [hash('sha256', (string)$_COOKIE['remember_me'])]);
        clear_remember_cookie();
    }
}
