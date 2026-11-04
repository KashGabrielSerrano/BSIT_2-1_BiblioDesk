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

// Only files hosted on our own Supabase project may be linked as a PDF.
const DRIVE_LINK_ALLOWED_HOSTS = ['fqwxstfflwbkmajqdjog.supabase.co'];

const REPORT_STATUSES  = ['New', 'Reviewing', 'Forwarded', 'Resolved'];
const ACCOUNT_STATUSES = ['Active', 'Suspended', 'Pending'];

// sendResponse() is declared in section 2; top-level functions are hoisted, so it is callable here.
set_exception_handler(function (Throwable $e): void {
    error_log('librarymanagement_controller bootstrap error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    sendResponse(false, 'A server error occurred. Please try again.', local_debug($e), 500);
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

/**
 * NOTE: on a loopback request (localhost) the real exception is added to the JSON as `debug`.
 * Remote requests never get it. Safe to delete together with the two call sites below.
 */
function local_debug(Throwable $e): array {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($ip !== '127.0.0.1' && $ip !== '::1') {
        return [];
    }
    return ['debug' => get_class($e) . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine()];
}

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

/* ----- Supabase Storage (plain file_get_contents, no cURL) ---------------- *
 * SUPABASE_URL / SUPABASE_SERVICE_KEY / bucket names live in connection/config.php
 * (gitignored). The service key never leaves the server.                      */

function supabase_ready(): bool {
    return defined('SUPABASE_URL') && defined('SUPABASE_SERVICE_KEY')
        && SUPABASE_URL !== '' && SUPABASE_SERVICE_KEY !== '';
}

/** @return array{0: int, 1: string} [http status (0 = no response), response body] */
function supabase_request(string $method, string $path, ?string $body = null, ?string $contentType = null): array {
    $headers = [
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'apikey: ' . SUPABASE_SERVICE_KEY,
    ];
    if ($contentType !== null) {
        $headers[] = 'Content-Type: ' . $contentType;
    }

    $opts = ['http' => [
        'method'        => $method,
        'header'        => implode("\r\n", $headers),
        'timeout'       => 60,
        'ignore_errors' => true,   // otherwise 4xx/5xx returns false with no body
    ]];
    if ($body !== null) {
        $opts['http']['content'] = $body;
    }

    $resp = @file_get_contents(
        rtrim(SUPABASE_URL, '/') . '/storage/v1/' . ltrim($path, '/'),
        false,
        stream_context_create($opts)
    );

    $resp_headers = function_exists('http_get_last_response_headers')
        ? (http_get_last_response_headers() ?? [])
        : ($http_response_header ?? []);

    $status = 0;
    foreach ($resp_headers as $h) {              // last status line wins (redirects)
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $status = (int)$m[1];
        }
    }
    return [$status, $resp === false ? '' : $resp];
}

function upload_rules(string $kind): array {
    if ($kind === 'cover') {
        return [
            'bucket' => defined('SUPABASE_BUCKET_COVERS') ? SUPABASE_BUCKET_COVERS : 'book-covers',
            'max'    => 5 * 1024 * 1024,
            'mimes'  => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
            'label'  => 'Cover image',
            'hint'   => 'JPG, PNG or WEBP up to 5 MB',
        ];
    }
    return [
        'bucket' => defined('SUPABASE_BUCKET_PDFS') ? SUPABASE_BUCKET_PDFS : 'pdf-books',
        'max'    => 20 * 1024 * 1024,
        'mimes'  => ['application/pdf' => 'pdf'],
        'label'  => 'PDF',
        'hint'   => 'PDF up to 20 MB',
    ];
}

/** True when the form actually sent something in this file field (including failed uploads). */
function has_upload(string $field): bool {
    return isset($_FILES[$field]) && is_array($_FILES[$field])
        && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function supabase_public_url(string $bucket, string $name): string {
    return rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . $bucket . '/' . $name;
}

/** Map one of OUR public URLs back to [bucket, object]; anything else returns null (never deleted). */
function supabase_object_from_url($url): ?array {
    if (!supabase_ready() || !is_string($url) || $url === '') {
        return null;
    }
    $prefix = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/';
    if (strncmp($url, $prefix, strlen($prefix)) !== 0) {
        return null;
    }
    if (!preg_match('#^([A-Za-z0-9_\-]+)/([A-Za-z0-9_.\-]+)$#', substr($url, strlen($prefix)), $m)) {
        return null;
    }
    if (!in_array($m[1], [upload_rules('cover')['bucket'], upload_rules('pdf')['bucket']], true)) {
        return null;
    }
    return [$m[1], $m[2]];
}

/**
 * Validate one uploaded file for real (size, sniffed MIME, magic bytes), then store it in Supabase
 * under a random server-side name.
 * @return array{0: ?string, 1: ?string} [public_url, error_message]
 */
function supabase_upload(string $field, string $kind): array {
    $rules = upload_rules($kind);
    $label = $rules['label'];
    $f     = $_FILES[$field] ?? null;

    if (!is_array($f) || !is_int($f['error'] ?? null) || !is_string($f['tmp_name'] ?? null)) {
        return [null, "{$label} upload was invalid."];
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        return [null, "{$label} is larger than the server allows ({$rules['hint']})."];
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return [null, "{$label} could not be uploaded (error code {$f['error']})."];
    }
    if (!supabase_ready()) {
        return [null, 'File storage is not configured on the server.'];
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        return [null, "{$label} upload was invalid."];
    }

    $size = filesize($f['tmp_name']);
    if ($size === false || $size <= 0) {
        return [null, "{$label} is empty."];
    }
    if ($size > $rules['max']) {
        return [null, "{$label} is too large ({$rules['hint']})."];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!is_string($mime) || !isset($rules['mimes'][$mime])) {
        return [null, "{$label} must be: {$rules['hint']}."];
    }

    if ($kind === 'pdf') {
        $fh   = fopen($f['tmp_name'], 'rb');
        $head = $fh ? fread($fh, 5) : '';
        if ($fh) {
            fclose($fh);
        }
        if ($head !== '%PDF-') {
            return [null, 'That file is not a valid PDF.'];
        }
    } elseif (@getimagesize($f['tmp_name']) === false) {
        return [null, 'That file is not a valid image.'];
    }

    $data = file_get_contents($f['tmp_name']);
    if ($data === false) {
        return [null, "{$label} could not be read."];
    }

    $name = bin2hex(random_bytes(16)) . '.' . $rules['mimes'][$mime];
    [$status, $resp] = supabase_request('POST', 'object/' . $rules['bucket'] . '/' . $name, $data, $mime);
    unset($data);

    if ($status !== 200 && $status !== 201) {
        error_log("Supabase upload failed (HTTP {$status}) for {$rules['bucket']}/{$name}: " . substr($resp, 0, 300));
        return [null, 'The file could not be stored. Please try again.'];
    }
    return [supabase_public_url($rules['bucket'], $name), null];
}

/**
 * Best-effort delete of stored files, skipping anything that is not ours or that another
 * book row still points to. Failures are logged, never shown to the user.
 */
function release_stored_files(mysqli $con, array $urls, int $except_book_id = 0): void {
    foreach (array_unique(array_filter($urls)) as $url) {
        $obj = supabase_object_from_url($url);
        if ($obj === null) {
            continue;
        }
        $in_use = db_all(
            $con,
            'SELECT 1 FROM book_table WHERE (Cover_Image = ? OR Drive_Link = ?) AND Book_ID <> ? LIMIT 1',
            'ssi',
            [$url, $url, $except_book_id]
        );
        if ($in_use) {
            continue;
        }
        [$status, $resp] = supabase_request('DELETE', 'object/' . $obj[0] . '/' . $obj[1]);
        if ($status !== 200 && $status !== 204 && $status !== 404) {
            error_log("Supabase delete failed (HTTP {$status}) for {$obj[0]}/{$obj[1]}: " . substr($resp, 0, 300));
        }
    }
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
function read_book_input(bool $has_pdf_upload = false): array {
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

    // A PDF uploaded with this request replaces any typed link and makes the book digital.
    if ($has_pdf_upload) {
        $d['drive_link'] = '';
        $d['is_digital'] = 'Yes';
    }

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
    } elseif ($d['is_digital'] === 'Yes' && !$has_pdf_upload) {
        return [$d, 'Upload a PDF or provide a PDF link for digital books.'];
    }

    // Optional columns: empty string -> NULL (ISBN has a UNIQUE index, so '' would collide).
    foreach (['isbn', 'publisher', 'category', 'cover_image', 'drive_link'] as $nullable) {
        if ($d[$nullable] === '') {
            $d[$nullable] = null;
        }
    }
    return [$d, null];
}

/**
 * Store any cover / PDF uploaded with the request and write the resulting public URLs into $d.
 * On a failed upload, files stored earlier in this same request are removed and the request ends.
 * @return string[] URLs created by this request (so the caller can roll back if the DB write fails)
 */
function process_book_uploads(mysqli $con, array &$d): array {
    $uploaded = [];
    foreach ([['cover_file', 'cover', 'cover_image'], ['pdf_file', 'pdf', 'drive_link']] as [$field, $kind, $col]) {
        if (!has_upload($field)) {
            continue;
        }
        [$url, $upload_err] = supabase_upload($field, $kind);
        if ($upload_err !== null) {
            release_stored_files($con, $uploaded);
            sendResponse(false, $upload_err, [], 422);
        }
        $d[$col]    = $url;
        $uploaded[] = $url;
    }
    if (has_upload('pdf_file')) {
        $d['is_digital'] = 'Yes';
    }
    return $uploaded;
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
    /* ---- Oversized upload guard ----
     * A body larger than post_max_size arrives with empty $_POST and $_FILES, which would otherwise
     * be reported as an invalid action / CSRF failure. */
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && empty($_FILES)
        && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        sendResponse(false, 'The upload is larger than the server allows. Try a smaller file.', [], 413);
    }

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
    sendResponse(false, 'A server error occurred. Please try again.', local_debug($e), 500);
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
    [$d, $err] = read_book_input(has_upload('pdf_file'));
    if ($err !== null) {
        sendResponse(false, $err, [], 422);
    }

    $uploaded = process_book_uploads($con, $d);

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
        release_stored_files($con, $uploaded); // DB write failed: do not leave orphan files behind
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
    $old = db_all($con, 'SELECT Cover_Image, Drive_Link FROM book_table WHERE Book_ID = ?', 'i', [$book_id]);
    if (!$old) {
        sendResponse(false, 'Book not found.', [], 404);
    }

    [$d, $err] = read_book_input(has_upload('pdf_file'));
    if ($err !== null) {
        sendResponse(false, $err, [], 422);
    }

    $uploaded = process_book_uploads($con, $d);

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
        release_stored_files($con, $uploaded); // DB write failed: do not leave orphan files behind
        if ((int)$e->getCode() === 1062) {
            sendResponse(false, 'Another book already uses this ISBN.', [], 409);
        }
        throw $e;
    }

    // The row now points at new files: remove the ones it no longer uses from Supabase.
    $stale = [];
    if (($old[0]['Cover_Image'] ?? null) !== $d['cover_image']) {
        $stale[] = $old[0]['Cover_Image'] ?? null;
    }
    if (($old[0]['Drive_Link'] ?? null) !== $d['drive_link']) {
        $stale[] = $old[0]['Drive_Link'] ?? null;
    }
    release_stored_files($con, $stale, $book_id);

    sendResponse(true, 'Book updated.');
}

function handle_delete_book(mysqli $con): void {
    $book_id = (int)gv($_POST, 'book_id');
    if ($book_id <= 0) {
        sendResponse(false, 'Invalid book ID.', [], 400);
    }

    $old = db_all($con, 'SELECT Cover_Image, Drive_Link FROM book_table WHERE Book_ID = ?', 'i', [$book_id]);

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
    // Row is gone: remove its cover and PDF from Supabase (only our own files, only if no other book uses them).
    release_stored_files($con, [$old[0]['Cover_Image'] ?? null, $old[0]['Drive_Link'] ?? null]);

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
