<?php
/* ========================================================================== */
/* 1. BOOTSTRAP, HEADERS & DEPENDENCIES                                       */
/* ========================================================================== */

ob_start();
session_start();

require_once __DIR__ . '/connection/config.php';
require_once __DIR__ . '/helpers/SystemOperators.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

const DRIVE_LINK_ALLOWED_HOSTS = [];

const REPORT_STATUSES  = ['New', 'Reviewing', 'Forwarded', 'Resolved'];
const ACCOUNT_STATUSES = ['Active', 'Suspended', 'Pending'];

// sendResponse() is declared in section 2; top-level functions are hoisted, so it is callable here.
set_exception_handler(function (Throwable $e): void {
    error_log('librarymanagement_controller bootstrap error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    sendResponse(false, 'A server error occurred. Please try again.', [], 500);
});

$con = connection();
if (!($con instanceof mysqli)) {
    sendResponse(false, 'Database connection failed.', [], 500);
}
$con->set_charset('utf8mb4');

$so      = new SystemOperators();
$user_ip = $so->getClientIP();

/* ========================================================================== */
/* 2. SHARED HELPER FUNCTIONS                                                 */
/* ========================================================================== */

/* ----- response ---------------------------------------------------------- */

function sendResponse(bool $success, string $message, array $extra = [], int $status = 200): void {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($status);
    echo json_encode(
        array_merge(['success' => $success, 'message' => $message], $extra),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit();
}

/* ----- query helpers ----------------------------------------------------- */

/** Scalar-string reader for $_GET / $_POST (arrays and nulls become ''). */
function gv(array $src, string $key): string {
    $v = $src[$key] ?? '';
    return is_scalar($v) ? trim((string)$v) : '';
}

/** Run a prepared SELECT and return all rows. */
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

function table_exists(mysqli $con, string $table): bool {
    $rows = db_all(
        $con,
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1",
        's',
        [$table]
    );
    return !empty($rows);
}

/** Escape LIKE wildcards so user input is matched literally. */
function like_term(string $s): string {
    return '%' . addcslashes($s, '%_\\') . '%';
}

/* ----- encryption wrappers ----------------------------------------------- */

/** Decrypt a SystemOperators-encrypted field; so it never returns false/null. */
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

/* ----- book input readers & validation ----------------------------------- */

function drive_host_allowed(string $host): bool {
    if (empty(DRIVE_LINK_ALLOWED_HOSTS)) {
        return true;
    }
    $host = strtolower($host);
    foreach (DRIVE_LINK_ALLOWED_HOSTS as $allowed) {
        $allowed = strtolower($allowed);
        if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
            return true;
        }
    }
    return false;
}

function valid_cover(string $v): bool {
    if (preg_match('#^https?://#i', $v)) {
        return filter_var($v, FILTER_VALIDATE_URL) !== false;
    }
    // Relative path (the user library prefixes "../"). No scheme/colon, no "..".
    return (bool)preg_match('#^(?!.*\.\.)[A-Za-z0-9_\-./ ]+$#', $v);
}

/** @return array{0: array, 1: ?string} [data, error] */
function read_book_input(): array {
    $d = [
        'title'       => gv($_POST, 'title'),
        'author'      => gv($_POST, 'author'),
        'isbn'        => gv($_POST, 'isbn'),
        'publisher'   => gv($_POST, 'publisher'),
        'category'    => gv($_POST, 'category'),
        'description' => gv($_POST, 'description'),
        'cover_image' => gv($_POST, 'cover_image'),
        'drive_link'  => gv($_POST, 'drive_link'),
        'is_digital'  => gv($_POST, 'is_digital') ?: 'Yes',
    ];

    if ($d['title'] === '')       return [$d, 'Title is required.'];
    if ($d['author'] === '')      return [$d, 'Author is required.'];
    if ($d['description'] === '') return [$d, 'Description is required.'];

    $limits = ['title' => 255, 'author' => 255, 'isbn' => 20, 'publisher' => 150, 'category' => 100, 'cover_image' => 255, 'drive_link' => 255];
    foreach ($limits as $field => $max) {
        if (mb_strlen($d[$field]) > $max) {
            return [$d, ucfirst(str_replace('_', ' ', $field)) . " must be {$max} characters or fewer."];
        }
    }
    if (mb_strlen($d['description']) > 5000) {
        return [$d, 'Description must be 5,000 characters or fewer.'];
    }

    if (!in_array($d['is_digital'], ['Yes', 'No'], true)) {
        return [$d, 'Invalid digital access value.'];
    }
    if ($d['isbn'] !== '' && !preg_match('/^[0-9Xx\- ]{1,20}$/', $d['isbn'])) {
        return [$d, 'ISBN may only contain digits, hyphens, spaces and X.'];
    }
    if ($d['cover_image'] !== '' && !valid_cover($d['cover_image'])) {
        return [$d, 'Cover image must be an http(s) URL or a relative file path.'];
    }

    if ($d['drive_link'] !== '') {
        $host = parse_url($d['drive_link'], PHP_URL_HOST);
        if (!preg_match('#^https://#i', $d['drive_link'])
            || filter_var($d['drive_link'], FILTER_VALIDATE_URL) === false
            || !is_string($host)
            || !drive_host_allowed($host)) {
            return [$d, 'PDF link must be a valid https URL (Supabase Storage public URL).'];
        }
    } elseif ($d['is_digital'] === 'Yes') {
        return [$d, 'A PDF link is required for digital books.'];
    }

    // Optional columns: empty string -> NULL (ISBN has a UNIQUE index, so '' would collide).
    foreach (['isbn', 'publisher', 'category', 'cover_image', 'drive_link'] as $nullable) {
        if ($d[$nullable] === '') {
            $d[$nullable] = null;
        }
    }
    return [$d, null];
}

/* ----- row mappers ------------------------------------------------------- */

function book_row(array $r): array {
    return [
        'book_id'     => (int)$r['Book_ID'],
        'title'       => $r['Title'],
        'author'      => $r['Author'],
        'isbn'        => $r['ISBN'] ?? '',
        'publisher'   => $r['Publisher'] ?? '',
        'category'    => $r['Category'] ?? '',
        'description' => $r['Description'] ?? '',
        'cover_image' => $r['Cover_Image'] ?? '',
        'drive_link'  => $r['Drive_Link'] ?? '',
        'is_digital'  => $r['Is_digital'],
    ];
}

/* ========================================================================== */
/* 3. SECURITY GATEWAY & ROUTER (executes BEFORE the handlers below)          */
/* ========================================================================== */

try {
    /* ---- Action whitelists ---- */
    $read_actions  = ['get_catalog', 'get_book', 'get_accounts', 'get_reports'];
    $write_actions = ['add_book', 'update_book', 'delete_book', 'update_account_status'];

    /* ---- Retrieve & validate action ---- */
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $action = is_string($action) ? $action : '';

    if (!in_array($action, array_merge($read_actions, $write_actions), true)) {
        sendResponse(false, 'Invalid action.', [], 400);
    }

    /* ---- HTTP method check (GET for reads, POST for writes) ---- */
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($action, $write_actions, true) && $method !== 'POST') {
        sendResponse(false, 'This action requires POST.', [], 405);
    }
    if (in_array($action, $read_actions, true) && $method !== 'GET') {
        sendResponse(false, 'This action requires GET.', [], 405);
    }

    /* ---- Role authorization ----
     * DEV BYPASS: role lock is disabled so the page can be tested directly in the browser.
     * Re-enable before deployment!!!  Roles in the session are 'Student' | 'Librarian' | 'Admin'.
     *
     * if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Librarian', 'Admin'], true)) {
     *     sendResponse(false, 'Librarian access required. Please log in.', [], 403);
     * }
     */

    /* ---- CSRF validation for every state-changing (POST) request ---- */
    if ($method === 'POST') {
        $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($submitted) || $submitted === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
            error_log('Security Warning: Potential CSRF attempt blocked from IP ' . $user_ip);
            sendResponse(false, 'Your session has expired or the request could not be verified. Please reload the page.', [], 403);
        }
    }

    /* ---- Dispatch ---- */
    switch ($action) {
        case 'get_catalog':           handle_get_catalog($con); break;
        case 'get_book':              handle_get_book($con); break;
        case 'add_book':              handle_add_book($con); break;
        case 'update_book':           handle_update_book($con); break;
        case 'delete_book':           handle_delete_book($con); break;
        case 'get_accounts':          handle_get_accounts($con, $so); break;
        case 'update_account_status': handle_update_account_status($con); break;
        case 'get_reports':           handle_get_reports($con, $so); break;
    }
} catch (Throwable $e) {
    error_log('librarymanagement_controller error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    sendResponse(false, 'A server error occurred. Please try again.', [], 500);
}

/* ========================================================================== */
/* 4. ACTION HANDLERS                                                         */
/*    Only declarations below this line; nothing executes after the router.   */
/* ========================================================================== */

/* ----- Catalog handlers -------------------------------------------------- */

function handle_get_catalog(mysqli $con): void {
    $search = gv($_GET, 'search');
    $order  = gv($_GET, 'sort') === 'oldest' ? 'ASC' : 'DESC'; // whitelisted, safe to interpolate

    $where = '';
    $types = '';
    $params = [];
    if ($search !== '') {
        $like   = like_term($search);
        $where  = 'WHERE (Title LIKE ? OR Author LIKE ? OR ISBN LIKE ? OR Category LIKE ?)';
        $types  = 'ssss';
        $params = [$like, $like, $like, $like];
    }

    $rows = db_all(
        $con,
        "SELECT Book_ID, Title, Author, ISBN, Publisher, Category, Cover_Image, Drive_Link, Is_digital
           FROM book_table {$where}
          ORDER BY Book_ID {$order}
          LIMIT 500",
        $types,
        $params
    );

    sendResponse(true, 'Catalog loaded.', [
        'books' => array_map('book_row', $rows),
        'count' => count($rows),
    ]);
}

function handle_get_book(mysqli $con): void {
    $book_id = (int)gv($_GET, 'book_id');
    if ($book_id <= 0) {
        sendResponse(false, 'Invalid book ID.', [], 400);
    }
    $rows = db_all($con, 'SELECT * FROM book_table WHERE Book_ID = ?', 'i', [$book_id]);
    if (!$rows) {
        sendResponse(false, 'Book not found.', [], 404);
    }
    sendResponse(true, 'Book loaded.', ['book' => book_row($rows[0])]);
}

function handle_add_book(mysqli $con): void {
    [$d, $err] = read_book_input();
    if ($err !== null) {
        sendResponse(false, $err, [], 422);
    }

    try {
        $stmt = $con->prepare(
            'INSERT INTO book_table (Title, Description, Cover_Image, Drive_Link, Author, ISBN, Publisher, Category, Is_digital)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssssss',
            $d['title'], $d['description'], $d['cover_image'], $d['drive_link'],
            $d['author'], $d['isbn'], $d['publisher'], $d['category'], $d['is_digital']
        );
        $stmt->execute();
        $new_id = $con->insert_id;
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1062) {
            sendResponse(false, 'A book with this ISBN already exists.', [], 409);
        }
        throw $e;
    }

    sendResponse(true, 'Book added to the catalog.', ['book_id' => $new_id], 201);
}

function handle_update_book(mysqli $con): void {
    $book_id = (int)gv($_POST, 'book_id');
    if ($book_id <= 0) {
        sendResponse(false, 'Invalid book ID.', [], 400);
    }
    if (!db_all($con, 'SELECT 1 FROM book_table WHERE Book_ID = ?', 'i', [$book_id])) {
        sendResponse(false, 'Book not found.', [], 404);
    }

    [$d, $err] = read_book_input();
    if ($err !== null) {
        sendResponse(false, $err, [], 422);
    }

    try {
        $stmt = $con->prepare(
            'UPDATE book_table
                SET Title = ?, Description = ?, Cover_Image = ?, Drive_Link = ?, Author = ?,
                    ISBN = ?, Publisher = ?, Category = ?, Is_digital = ?
              WHERE Book_ID = ?'
        );
        $stmt->bind_param(
            'sssssssssi',
            $d['title'], $d['description'], $d['cover_image'], $d['drive_link'],
            $d['author'], $d['isbn'], $d['publisher'], $d['category'], $d['is_digital'], $book_id
        );
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        if ((int)$e->getCode() === 1062) {
            sendResponse(false, 'Another book already uses this ISBN.', [], 409);
        }
        throw $e;
    }

    sendResponse(true, 'Book updated.');
}

function handle_delete_book(mysqli $con): void {
    $book_id = (int)gv($_POST, 'book_id');
    if ($book_id <= 0) {
        sendResponse(false, 'Invalid book ID.', [], 400);
    }

    // NOTE: FK constraints CASCADE to bookmarks, favorites, reading progress/history,
    // reservations, transactions and fines for this book.
    $stmt = $con->prepare('DELETE FROM book_table WHERE Book_ID = ?');
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    if ($deleted === 0) {
        sendResponse(false, 'Book not found.', [], 404);
    }
    sendResponse(true, 'Book deleted.');
}

/* ----- Account handlers (Librarian scope = Student accounts only) -------- */

function handle_get_accounts(mysqli $con, SystemOperators $so): void {
    $tab    = gv($_GET, 'tab') === 'pending' ? 'pending' : 'users';
    $status = gv($_GET, 'status');
    $search = mb_strtolower(gv($_GET, 'search'));

    // Tab badge counts (independent of search/filter)
    $counts = ['users' => 0, 'pending' => 0];
    foreach (db_all($con, "SELECT Account_Status, COUNT(*) AS c FROM user_table WHERE Role = 'Student' GROUP BY Account_Status") as $r) {
        $counts[$r['Account_Status'] === 'Pending' ? 'pending' : 'users'] += (int)$r['c'];
    }

    $types  = '';
    $params = [];
    if ($tab === 'pending') {
        $statusSql = "Account_Status = 'Pending'";
    } else {
        $statusSql = "Account_Status IN ('Active','Suspended')";
        if (in_array($status, ['Active', 'Suspended'], true)) {
            $statusSql = 'Account_Status = ?';
            $types     = 's';
            $params    = [$status];
        }
    }

    $rows = db_all(
        $con,
        "SELECT User_ID, Full_Name, Student_ID, Email, Department, Account_Status, Created_At
           FROM user_table
          WHERE Role = 'Student' AND {$statusSql}
          ORDER BY Created_At DESC, User_ID DESC
          LIMIT 1000",
        $types,
        $params
    );

    // Sensitive columns are encrypted, so decrypt first and search in PHP.
    $accounts = [];
    foreach ($rows as $r) {
        $acct = [
            'user_id'    => (int)$r['User_ID'],
            'name'       => dec($so, $r['Full_Name']),
            'email'      => dec($so, $r['Email']),
            'student_id' => dec($so, $r['Student_ID']),
            'department' => dec($so, $r['Department']),
            'status'     => $r['Account_Status'],
            'created_at' => $r['Created_At'],
        ];
        if ($search !== '') {
            $hay = mb_strtolower($acct['name'] . ' ' . $acct['email'] . ' ' . $acct['student_id'] . ' ' . $acct['department']);
            if (mb_strpos($hay, $search) === false) {
                continue;
            }
        }
        $accounts[] = $acct;
    }

    sendResponse(true, 'Accounts loaded.', ['accounts' => $accounts, 'counts' => $counts, 'tab' => $tab]);
}

function handle_update_account_status(mysqli $con): void {
    $user_id = (int)gv($_POST, 'user_id');
    $status  = gv($_POST, 'status');

    if ($user_id <= 0 || !in_array($status, ACCOUNT_STATUSES, true)) {
        sendResponse(false, 'Invalid account or status.', [], 400);
    }

    $current = db_all($con, "SELECT Account_Status FROM user_table WHERE User_ID = ? AND Role = 'Student'", 'i', [$user_id]);
    if (!$current) {
        sendResponse(false, 'Student account not found.', [], 404);
    }
    if ($current[0]['Account_Status'] === $status) {
        sendResponse(true, "Account is already {$status}.");
    }

    $stmt = $con->prepare("UPDATE user_table SET Account_Status = ? WHERE User_ID = ? AND Role = 'Student'");
    $stmt->bind_param('si', $status, $user_id);
    $stmt->execute();
    $stmt->close();

    // A suspended student must not be able to resume through a "remember me" token.
    if ($status === 'Suspended') {
        $del = $con->prepare('DELETE FROM user_tokens WHERE user_id = ?');
        $del->bind_param('i', $user_id);
        $del->execute();
        $del->close();
    }

    sendResponse(true, "Account set to {$status}.", ['status' => $status]);
}

/* ----- Report handlers --------------------------------------------------- */

function handle_get_reports(mysqli $con, SystemOperators $so): void {
    $empty_stats = ['current' => 0, 'forwarded' => 0, 'broken_links' => 0, 'resolved_week' => 0];

    if (!table_exists($con, 'report_table')) {
        sendResponse(true, 'Report tickets are not set up yet.', [
            'table_missing' => true,
            'reports'       => [],
            'stats'         => $empty_stats,
            'pagination'    => ['page' => 1, 'per_page' => 8, 'total' => 0, 'pages' => 1],
        ]);
    }

    $search   = gv($_GET, 'search');
    $status   = gv($_GET, 'status');
    $sort     = gv($_GET, 'sort');
    $per_page = (int)gv($_GET, 'per_page');
    $page     = max(1, (int)gv($_GET, 'page'));
    if (!in_array($per_page, [8, 12, 20, 40], true)) {
        $per_page = 8;
    }

    $orderMap = [
        'name-asc'    => 'b.Title ASC, r.Report_ID DESC',
        'name-desc'   => 'b.Title DESC, r.Report_ID DESC',
        'date-newest' => 'r.Created_At DESC, r.Report_ID DESC',
        'date-oldest' => 'r.Created_At ASC, r.Report_ID ASC',
    ];
    $orderSql = $orderMap[$sort] ?? $orderMap['date-newest'];

    $conds  = [];
    $types  = '';
    $params = [];
    if (in_array($status, REPORT_STATUSES, true)) {
        $conds[]  = 'r.Status = ?';
        $types   .= 's';
        $params[] = $status;
    }
    if ($search !== '') {
        $like     = like_term($search);
        $conds[]  = '(b.Title LIKE ? OR b.Author LIKE ? OR r.Issue_Type LIKE ?)';
        $types   .= 'sss';
        array_push($params, $like, $like, $like);
    }
    $whereSql = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

    $from = 'FROM report_table r
             LEFT JOIN book_table b ON b.Book_ID = r.Book_ID
             LEFT JOIN user_table u ON u.User_ID = r.User_ID';

    $total = (int)(db_all($con, "SELECT COUNT(*) AS c {$from} {$whereSql}", $types, $params)[0]['c'] ?? 0);
    $pages = max(1, (int)ceil($total / $per_page));
    $page  = min($page, $pages);
    $offset = ($page - 1) * $per_page;

    $rows = db_all(
        $con,
        "SELECT r.Report_ID, r.Issue_Type, r.Description, r.Status, r.Created_At,
                b.Title AS Book_Title, b.Author AS Book_Author, u.Full_Name AS Reporter
           {$from} {$whereSql}
          ORDER BY {$orderSql}
          LIMIT ? OFFSET ?",
        $types . 'ii',
        array_merge($params, [$per_page, $offset])
    );

    $reports = [];
    foreach ($rows as $r) {
        $reports[] = [
            'report_id'   => (int)$r['Report_ID'],
            'book_title'  => $r['Book_Title'] ?? 'Deleted book',
            'book_author' => $r['Book_Author'] ?? '',
            'issue_type'  => $r['Issue_Type'],
            'description' => $r['Description'] ?? '',
            'status'      => $r['Status'],
            'reporter'    => dec($so, $r['Reporter']) ?: 'Unknown user',
            'created_at'  => $r['Created_At'],
        ];
    }

    $s = db_all(
        $con,
        "SELECT
            SUM(Status IN ('New','Reviewing'))                                        AS current_reports,
            SUM(Status = 'Forwarded')                                                 AS forwarded,
            SUM(Issue_Type = 'Broken Link' AND Status <> 'Resolved')                  AS broken_links,
            SUM(Status = 'Resolved' AND Resolved_At >= (NOW() - INTERVAL 7 DAY))      AS resolved_week
           FROM report_table"
    )[0] ?? [];

    sendResponse(true, 'Reports loaded.', [
        'table_missing' => false,
        'reports'       => $reports,
        'stats'         => [
            'current'       => (int)($s['current_reports'] ?? 0),
            'forwarded'     => (int)($s['forwarded'] ?? 0),
            'broken_links'  => (int)($s['broken_links'] ?? 0),
            'resolved_week' => (int)($s['resolved_week'] ?? 0),
        ],
        'pagination'    => ['page' => $page, 'per_page' => $per_page, 'total' => $total, 'pages' => $pages],
    ]);
}
