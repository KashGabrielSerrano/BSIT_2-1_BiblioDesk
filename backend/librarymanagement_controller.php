<?php
/* ========================================================================== */
/* 1. BOOTSTRAP, HEADERS & DEPENDENCIES                                       */
/* ========================================================================== */

ob_start();

require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/circulation.php';   

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// DRIVE_LINK_ALLOWED_HOSTS (only Supabase project) is defined in core/bootstrap.php.

const REPORT_STATUSES  = ['New', 'Reviewing', 'Forwarded', 'Resolved'];
const ACCOUNT_STATUSES = ['Active', 'Suspended', 'Pending'];

// Must match the book_table.Category ENUM and the dropdowns in librarymanagement.php.
const BOOK_CATEGORIES  = ['Educational', 'Non-Educational', 'Research Articles'];

set_exception_handler(function (Throwable $e): void {
    error_log('librarymanagement_controller bootstrap error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    sendResponse(false, 'A server error occurred. Please try again.', local_debug($e), 500);
});

$con     = db();
$so      = so();
$user_ip = client_ip();

                        
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

function has_upload(string $field): bool {
    return isset($_FILES[$field]) && is_array($_FILES[$field])
        && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function supabase_public_url(string $bucket, string $name): string {
    return rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . $bucket . '/' . $name;
}

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
        'total_copies'=> gv($_POST, 'total_copies'),
    ];

    // A PDF uploaded with this request replaces any typed link and makes the book digital.
    if ($has_pdf_upload) {
        $d['drive_link'] = '';
        $d['is_digital'] = 'Yes';
    }

    if ($d['title'] === '')       return [$d, 'Title is required.'];
    if ($d['author'] === '')      return [$d, 'Author is required.'];
    if ($d['description'] === '') return [$d, 'Description is required.'];
    if (!in_array($d['category'], BOOK_CATEGORIES, true)) {
        return [$d, 'Please choose a category: ' . implode(', ', BOOK_CATEGORIES) . '.'];
    }

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
    // Physical books are borrowed (D1): they need a copy count. Digital books always have 0 copies.
    if ($d['is_digital'] === 'No') {
        if (!preg_match('/^\d{1,4}$/', $d['total_copies']) || (int)$d['total_copies'] > 1000) {
            return [$d, 'Enter how many physical copies the library has (0 to 1000).'];
        }
        $d['total_copies'] = (int)$d['total_copies'];
    } else {
        $d['total_copies'] = 0;
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

    foreach (['isbn', 'publisher', 'cover_image', 'drive_link'] as $nullable) {
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
        'total_copies'=> (int)($r['Total_Copies'] ?? 0),
        'available'   => isset($r['Available']) ? (int)$r['Available'] : null,
    ];
}

/* ========================================================================== */
/* 3. SECURITY GATEWAY & ROUTER (executes BEFORE the handlers below)          */
/* ========================================================================== */

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && empty($_POST) && empty($_FILES)
        && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        sendResponse(false, 'The upload is larger than the server allows. Try a smaller file.', [], 413);
    }

    /* ---- Action whitelists ---- */
    $read_actions  = ['get_catalog', 'get_book', 'get_accounts', 'get_reports'];
    $write_actions = ['add_book', 'update_book', 'delete_book', 'update_account_status', 'update_report_status', 'update_user_role'];

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
     * current_user() re-reads Role and Account_Status from the DB on every request, so a suspended
     * or demoted account loses access immediately. */
    if (!current_user()) {
        sendResponse(false, 'Librarian access required. Please log in.', [], 403);
    }
    require_role_api(['Librarian', 'Admin'], 'Access denied. Active Librarian or Admin status required.');

    /* ---- CSRF validation for every state-changing (POST) request ---- */
    require_csrf();

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
        case 'update_report_status':  handle_update_report_status(); break;
        case 'update_user_role':      handle_update_user_role(); break;
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
        "SELECT Book_ID, Title, Author, ISBN, Publisher, Category, Cover_Image, Drive_Link, Is_digital, Total_Copies
           FROM book_table {$where}
          ORDER BY Book_ID {$order}
          LIMIT 500",
        $types,
        $params
    );

    $avail = availability_map(array_column($rows, 'Book_ID'));
    foreach ($rows as &$r) {
        $r['Available'] = $avail[(int)$r['Book_ID']][1] ?? 0;
    }
    unset($r);
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
            'INSERT INTO book_table (Title, Description, Cover_Image, Drive_Link, Author, ISBN, Publisher, Category, Is_digital, Total_Copies)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssssssssi',
            $d['title'], $d['description'], $d['cover_image'], $d['drive_link'],
            $d['author'], $d['isbn'], $d['publisher'], $d['category'], $d['is_digital'], $d['total_copies']
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

    log_activity('book_added', 'book', (int)$new_id, $d['title'] . ($d['is_digital'] === 'No' ? " ({$d['total_copies']} copies)" : ' (digital)'));
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

    // Copies in use (borrowed + held for pickup) cannot be removed from the count.
    $in_use = (int)(db_one("SELECT (SELECT COUNT(*) FROM loan_table WHERE Book_ID = ? AND Status = 'Borrowed')
                                 + (SELECT COUNT(*) FROM reservation_table WHERE Book_ID = ? AND Status = 'Ready') AS n",
                           'ii', [$book_id, $book_id])['n'] ?? 0);
    if ($d['total_copies'] < $in_use) {
        sendResponse(false, "{$in_use} copies are borrowed or held for pickup right now, so the count cannot go below {$in_use}.", [], 422);
    }

    $uploaded = process_book_uploads($con, $d);

    try {
        $stmt = $con->prepare(
            'UPDATE book_table
                SET Title = ?, Description = ?, Cover_Image = ?, Drive_Link = ?, Author = ?,
                    ISBN = ?, Publisher = ?, Category = ?, Is_digital = ?, Total_Copies = ?
              WHERE Book_ID = ?'
        );
        $stmt->bind_param(
            'sssssssssii',
            $d['title'], $d['description'], $d['cover_image'], $d['drive_link'],
            $d['author'], $d['isbn'], $d['publisher'], $d['category'], $d['is_digital'], $d['total_copies'], $book_id
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

    $stale = [];
    if (($old[0]['Cover_Image'] ?? null) !== $d['cover_image']) {
        $stale[] = $old[0]['Cover_Image'] ?? null;
    }
    if (($old[0]['Drive_Link'] ?? null) !== $d['drive_link']) {
        $stale[] = $old[0]['Drive_Link'] ?? null;
    }
    release_stored_files($con, $stale, $book_id);

    promote_reservations($book_id);   // added copies go to the next people in line
    log_activity('book_updated', 'book', $book_id, $d['title'] . ($d['is_digital'] === 'No' ? " ({$d['total_copies']} copies)" : ' (digital)'));
    sendResponse(true, 'Book updated.');
}

function handle_delete_book(mysqli $con): void {
    $book_id = (int)gv($_POST, 'book_id');
    if ($book_id <= 0) {
        sendResponse(false, 'Invalid book ID.', [], 400);
    }

    // A book that is out on loan, requested or reserved cannot be deleted
    $busy = db_one("SELECT (SELECT COUNT(*) FROM loan_table WHERE Book_ID = ? AND Status IN ('Requested','Borrowed'))
                         + (SELECT COUNT(*) FROM reservation_table WHERE Book_ID = ? AND Status IN ('Pending','Ready')) AS n",
                   'ii', [$book_id, $book_id]);
    if ((int)($busy['n'] ?? 0) > 0) {
        sendResponse(false, 'This book has active loans, requests or reservations. Finish those first.', [], 409);
    }

    $old = db_all($con, 'SELECT Title, Cover_Image, Drive_Link FROM book_table WHERE Book_ID = ?', 'i', [$book_id]);
    $stmt = $con->prepare('DELETE FROM book_table WHERE Book_ID = ?');
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    if ($deleted === 0) {
        sendResponse(false, 'Book not found.', [], 404);
    }
    release_stored_files($con, [$old[0]['Cover_Image'] ?? null, $old[0]['Drive_Link'] ?? null]);

    log_activity('book_deleted', 'book', $book_id, (string)($old[0]['Title'] ?? ''));
    sendResponse(true, 'Book deleted.');
}

/* ----- Account handlers (Librarian scope = Student accounts only) -------- */

function handle_get_accounts(mysqli $con, SystemOperators $so): void {
    $tab    = gv($_GET, 'tab') === 'pending' ? 'pending' : 'users';
    $status = gv($_GET, 'status');
    $search = mb_strtolower(gv($_GET, 'search'));

    // Admins also see Librarian accounts (they can switch Student <-> Librarian). Librarians see students.
    $is_admin = current_user()['role'] === 'Admin';
    $roleSql  = $is_admin ? "Role IN ('Student','Librarian')" : "Role = 'Student'";

    // Tab badge counts (independent of search/filter)
    $counts = ['users' => 0, 'pending' => 0];
    foreach (db_all($con, "SELECT Account_Status, COUNT(*) AS c FROM user_table WHERE {$roleSql} GROUP BY Account_Status") as $r) {
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
        "SELECT User_ID, Full_Name, Student_ID, Email, Department, Role, Account_Status, Created_At
           FROM user_table
          WHERE {$roleSql} AND {$statusSql}
          ORDER BY Created_At DESC, User_ID DESC
          LIMIT 1000",
        $types,
        $params
    );

    // Sensitive columns are encrypted, so decrypt first and search
    $accounts = [];
    foreach ($rows as $r) {
        $acct = [
            'user_id'    => (int)$r['User_ID'],
            'name'       => dec($so, $r['Full_Name']),
            'email'      => dec($so, $r['Email']),
            'student_id' => dec($so, $r['Student_ID']),
            'department' => dec($so, $r['Department']),
            'status'     => $r['Account_Status'],
            'role'       => $r['Role'],
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

    sendResponse(true, 'Accounts loaded.', ['accounts' => $accounts, 'counts' => $counts, 'tab' => $tab,
                 'can_change_roles' => $is_admin]);
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

    $current_status = $current[0]['Account_Status'];

    // Prevent activating pending accounts directly without email OTP verification
    if ($current_status === 'Pending' && $status === 'Active') {
        sendResponse(false, 'Pending accounts must complete email verification to become active.', [], 422);
    }

    if ($current_status === $status) {
        sendResponse(true, "Account is already {$status}.");
    }

    $stmt = $con->prepare("UPDATE user_table SET Account_Status = ? WHERE User_ID = ? AND Role = 'Student'");
    $stmt->bind_param('si', $status, $user_id);
    $stmt->execute();
    $stmt->close();

    // Invalidate active session tokens when suspended
    if ($status === 'Suspended') {
        $del = $con->prepare('DELETE FROM user_tokens WHERE user_id = ?');
        $del->bind_param('i', $user_id);
        $del->execute();
        $del->close();
    }

    log_activity('account_status_changed', 'user', $user_id, "{$current_status} -> {$status}");
    sendResponse(true, "Account set to {$status}.", ['status' => $status]);
}

/* ----- Report handlers --------------------------------------------------- */

function handle_get_reports(mysqli $con, SystemOperators $so): void {
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

function handle_update_report_status(): void {
    $report_id = (int)gv($_POST, 'report_id');
    $status    = gv($_POST, 'status');
    if ($report_id <= 0 || !in_array($status, REPORT_STATUSES, true)) {
        sendResponse(false, 'Invalid report or status.', [], 400);
    }

    $current = db_one("SELECT r.Status, r.User_ID, r.Issue_Type, b.Title
                         FROM report_table r LEFT JOIN book_table b ON b.Book_ID = r.Book_ID
                        WHERE r.Report_ID = ?", 'i', [$report_id]);
    if (!$current) {
        sendResponse(false, 'Report not found.', [], 404);
    }
    if ($current['Status'] === $status) {
        sendResponse(true, "Report is already {$status}.", ['status' => $status]);
    }

    db_exec(
        "UPDATE report_table SET Status = ?, Resolved_At = IF(? = 'Resolved', NOW(), NULL) WHERE Report_ID = ?",
        'ssi',
        [$status, $status, $report_id]
    );

    // Tell the student who reported it (the bell), and keep a record for the admin log.
    $book = $current['Title'] ?? 'a book';
    $what = [
        'Reviewing' => 'is being reviewed by the library.',
        'Forwarded' => 'was forwarded for fixing.',
        'Resolved'  => 'has been resolved. Thank you for reporting it!',
        'New'       => 'was reopened.',
    ][$status];
    notify((int)$current['User_ID'], 'report_status', "Report update: {$book}",
           "Your \"{$current['Issue_Type']}\" report {$what}");
    log_activity('report_status_changed', 'report', $report_id, "{$current['Status']} -> {$status} ({$book})");

    sendResponse(true, "Report marked as {$status}.", ['status' => $status]);
}


function handle_update_user_role(): void {
    $admin = current_user();
    if ($admin['role'] !== 'Admin') {
        sendResponse(false, 'Only admins can change roles.', [], 403);
    }
    $user_id = (int)gv($_POST, 'user_id');
    $role    = gv($_POST, 'role');
    if ($user_id <= 0 && ($email = strtolower(gv($_POST, 'email'))) !== '') {
        $found = db_one("SELECT User_ID FROM user_table WHERE Email = ?", 's', [enc(so(), $email)]);
        if (!$found) {
            sendResponse(false, 'No account uses that email. The person must register first.', [], 404);
        }
        $user_id = (int)$found['User_ID'];
    }
    if ($user_id <= 0 || !in_array($role, ['Student', 'Librarian'], true)) {
        sendResponse(false, 'Accounts can only be switched between Student and Librarian.', [], 400);
    }
    if ($user_id === $admin['user_id']) {
        sendResponse(false, 'You cannot change your own role.', [], 403);
    }
    $target = db_one("SELECT Role, Account_Status FROM user_table WHERE User_ID = ?", 'i', [$user_id]);
    if (!$target) {
        sendResponse(false, 'Account not found.', [], 404);
    }
    if ($target['Role'] === 'Admin') {
        sendResponse(false, 'Admin accounts cannot be changed here.', [], 403);
    }
    if ($target['Account_Status'] !== 'Active') {
        sendResponse(false, 'Only active accounts can change role.', [], 422);
    }
    if ($target['Role'] === $role) {
        sendResponse(true, "Account is already a {$role}.", ['role' => $role]);
    }

    db_exec("UPDATE user_table SET Role = ? WHERE User_ID = ? AND Role IN ('Student','Librarian')", 'si', [$role, $user_id]);
    db_exec("DELETE FROM user_tokens WHERE user_id = ?", 'i', [$user_id]);
    notify($user_id, 'role_changed', "Your account is now a {$role} account",
           $role === 'Librarian' ? 'You can now open Library Management from the sidebar.' : 'You now have a student account.');
    log_activity('role_changed', 'user', $user_id, "{$target['Role']} -> {$role}");
    sendResponse(true, "Account changed to {$role}.", ['role' => $role]);
}
