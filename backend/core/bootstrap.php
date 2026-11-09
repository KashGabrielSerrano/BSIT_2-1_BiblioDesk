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

/**
 * The visitor's IP address (login lockout, OTP limits, logs). Only REMOTE_ADDR is trusted: headers
 * like X-Forwarded-For can be faked by any client, which would let them dodge the lockout (bug #5).
 * If the host sits behind a proxy/CDN that sets a trusted header, name it in config.php, e.g.
 *   define('TRUSTED_PROXY_IP_HEADER', 'HTTP_CF_CONNECTING_IP');
 * (dev/ip_check.php shows what the live host actually sends.)
 */
function client_ip(): string {
    if (defined('TRUSTED_PROXY_IP_HEADER') && TRUSTED_PROXY_IP_HEADER !== '' && !empty($_SERVER[TRUSTED_PROXY_IP_HEADER])) {
        $first = trim(explode(',', (string)$_SERVER[TRUSTED_PROXY_IP_HEADER])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

/** A 6-digit one-time code from a cryptographically secure generator (bug #6: was mt_rand). */
function generate_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
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

/* ========================================================================== */
/* 9. NOTIFICATIONS & ACTIVITY LOG                                            */
/* ========================================================================== */


const APP_TIMEZONE = 'Asia/Manila';

/** Send one notification (the bell). $link: a page to open on click (null for none). */
function notify(int $user_id, string $type, string $title, string $message = '', ?string $link = null): void {
    db_exec(
        "INSERT INTO notification_table (User_ID, Type, Title, Message, Link) VALUES (?, ?, ?, ?, ?)",
        'issss',
        [$user_id, mb_substr($type, 0, 40), mb_substr($title, 0, 120), mb_substr($message, 0, 255), $link]
    );
}


function notify_roles(array $roles, string $type, string $title, string $message = '', ?string $link = null, int $except_user = 0): int {
    $roles = array_values(array_intersect($roles, ['Student', 'Librarian', 'Admin']));
    if (!$roles) {
        return 0;
    }
    $marks = implode(',', array_fill(0, count($roles), '?'));
    $rows  = db_all(db(), "SELECT User_ID FROM user_table WHERE Account_Status = 'Active' AND Role IN ($marks)",
                    str_repeat('s', count($roles)), $roles);
    $count = 0;
    foreach ($rows as $r) {
        if ((int)$r['User_ID'] !== $except_user) {
            notify((int)$r['User_ID'], $type, $title, $message, $link);
            $count++;
        }
    }
    return $count;
}

/** Record who did what (role changes, approvals, settings...). Shown on adminlog.php later. */
function log_activity(string $action, ?string $target_type = null, ?int $target_id = null, string $details = ''): void {
    $actor = current_user()['user_id'] ?? null;
    db_exec(
        "INSERT INTO activity_log (Actor_ID, Action, Target_Type, Target_ID, Details) VALUES (?, ?, ?, ?, ?)",
        'issis',
        [$actor, mb_substr($action, 0, 60), $target_type, $target_id, mb_substr($details, 0, 500)]
    );
}

/** UTC 'Y-m-d H:i:s' from the DB -> local date string, e.g. 'Nov 09, 2026' or 'Y-m-d'. */
function local_date(?string $utc, string $format = 'M d, Y'): string {
    if (!$utc) {
        return '';
    }
    $d = new DateTime($utc, new DateTimeZone('UTC'));
    return $d->setTimezone(new DateTimeZone(APP_TIMEZONE))->format($format);
}

/* ========================================================================== */
/* 10. GLOBAL SYSTEM SETTINGS (system_settings table, Admin-editable — A2)    */
/* ========================================================================== */

const SETTINGS_SPEC = [
    'max_borrows'           => ['Max active borrows per student', 'int', 1, 20, 3, 'How many physical books a student can have borrowed at once.', 'Borrowing'],
    'loan_days'             => ['Loan period (days)', 'int', 1, 60, 7, 'Due date = the day the book is borrowed + this many days.', 'Borrowing'],
    'max_reservations'      => ['Max active reservations per student', 'int', 0, 10, 2, 'Reservations are only possible when no copy is available.', 'Reservations'],
    'reservation_hold_days' => ['Pickup hold (days)', 'int', 1, 14, 2, 'How long a reserved copy is held once it is ready for pickup.', 'Reservations'],
    'fine_per_day'          => ['Overdue fine per day (₱)', 'money', 0, 1000, 5.00, 'Charged for each day a borrowed book is late.', 'Fines'],
    'grace_days'            => ['Grace period (days)', 'int', 0, 30, 0, 'Days late before the overdue fine starts.', 'Fines'],
    'max_fine_per_loan'     => ['Max overdue fine per loan (₱)', 'money', 0, 100000, 200.00, 'The overdue fine for one loan never goes above this.', 'Fines'],
    'lost_book_fee'         => ['Lost book fee (₱)', 'money', 0, 100000, 500.00, 'Fixed fine when a borrowed copy is marked as lost.', 'Fines'],
    'block_if_unpaid_fines' => ['Block borrowing with unpaid fines', 'bool', 0, 1, 1, 'Students with unpaid fines cannot borrow or reserve.', 'Fines'],
];


function settings_all(bool $refresh = false): array {
    static $cache = null;
    if ($cache !== null && !$refresh) {
        return $cache;
    }
    $stored = [];
    foreach (db_all(db(), "SELECT Setting_Key, Setting_Value FROM system_settings") as $r) {
        $stored[$r['Setting_Key']] = $r['Setting_Value'];
    }
    $cache = [];
    foreach (SETTINGS_SPEC as $key => $spec) {
        $parsed = isset($stored[$key]) ? parse_setting($key, $stored[$key]) : null;
        $cache[$key] = $parsed ?? $spec[4];
    }
    return $cache;
}

function get_setting(string $key) {
    return settings_all()[$key] ?? null;
}


function parse_setting(string $key, $raw) {
    if (!isset(SETTINGS_SPEC[$key]) || !is_scalar($raw)) {
        return null;
    }
    [, $type, $min, $max] = SETTINGS_SPEC[$key];
    $raw = trim((string)$raw);
    if ($type === 'bool') {
        return in_array($raw, ['1', '0'], true) ? (int)$raw : null;
    }
    if ($type === 'int') {
        if (!preg_match('/^\d+$/', $raw)) return null;
        $v = (int)$raw;
    } else {
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $raw)) return null;
        $v = round((float)$raw, 2);
    }
    return ($v >= $min && $v <= $max) ? $v : null;
}


function forget_remember_token(): void {
    if (!empty($_COOKIE['remember_me'])) {
        db_exec("DELETE FROM user_tokens WHERE token_hash = ?", 's', [hash('sha256', (string)$_COOKIE['remember_me'])]);
        clear_remember_cookie();
    }
}
