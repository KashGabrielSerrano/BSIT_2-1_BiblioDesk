<?php
// Session, config, DB, guards and sendResponse() come from the shared core.
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/booklink.php';
header('Content-Type: application/json; charset=utf-8');

$con = db();

// Logged-in and still Active (re-checked against the DB on every request).
$user_id = require_login_api()['user_id'];

// Enforce CSRF Token Validation across POST requests
require_csrf();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function verifyBookExists(mysqli $con, int $book_id, bool $readable_only = false): bool {
    $sql = "SELECT Book_ID FROM book_table WHERE Book_ID = ?";
    if ($readable_only) {
        $sql .= " AND Is_digital = 'Yes' AND Drive_Link IS NOT NULL AND Drive_Link != ''";
    }
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

if ($action === 'save_progress') {
    $book_id = book_id_param($_POST);
    $current_page = max(1, intval($_POST['current_page'] ?? 1));
    $total_pages = max(1, intval($_POST['total_pages'] ?? 1));

    if (!verifyBookExists($con, $book_id, true)) {
        http_response_code(404);
        sendResponse(false, 'Book not found or file missing.');
    }

    $is_finished = ($current_page >= $total_pages) ? 1 : 0;

    $stmt = $con->prepare("INSERT INTO user_reading_progress (User_ID, Book_ID, Current_Page, Total_Pages, Is_Finished, Updated_At) 
                           VALUES (?, ?, ?, ?, ?, NOW()) 
                           ON DUPLICATE KEY UPDATE Current_Page = VALUES(Current_Page), Total_Pages = VALUES(Total_Pages), Is_Finished = VALUES(Is_Finished), Updated_At = NOW()");
    $stmt->bind_param("iiiii", $user_id, $book_id, $current_page, $total_pages, $is_finished);
    $stmt->execute();
    $stmt->close();

    sendResponse(true, 'Progress saved successfully.', ['is_finished' => (bool)$is_finished]);

} elseif ($action === 'toggle_bookmark') {
    $book_id = book_id_param($_POST);

    if (!verifyBookExists($con, $book_id)) {
        http_response_code(404);
        sendResponse(false, 'Book not found.');
    }

    $check = $con->prepare("SELECT Book_ID FROM user_bookmarks WHERE User_ID = ? AND Book_ID = ?");
    $check->bind_param("ii", $user_id, $book_id);
    $check->execute();
    $has = $check->get_result()->num_rows > 0;
    $check->close();

    if ($has) {
        $del = $con->prepare("DELETE FROM user_bookmarks WHERE User_ID = ? AND Book_ID = ?");
        $del->bind_param("ii", $user_id, $book_id);
        $del->execute();
        $del->close();
        $bookmarked = false;
    } else {
        $ins = $con->prepare("INSERT INTO user_bookmarks (User_ID, Book_ID) VALUES (?, ?)");
        $ins->bind_param("ii", $user_id, $book_id);
        $ins->execute();
        $ins->close();
        $bookmarked = true;
    }

    sendResponse(true, 'Bookmark status updated.', ['bookmarked' => $bookmarked]);

} elseif ($action === 'toggle_favorite') {
    $book_id = book_id_param($_POST);

    if (!verifyBookExists($con, $book_id)) {
        http_response_code(404);
        sendResponse(false, 'Book not found.');
    }

    $check = $con->prepare("SELECT Book_ID FROM user_favorites WHERE User_ID = ? AND Book_ID = ?");
    $check->bind_param("ii", $user_id, $book_id);
    $check->execute();
    $has = $check->get_result()->num_rows > 0;
    $check->close();

    if ($has) {
        $del = $con->prepare("DELETE FROM user_favorites WHERE User_ID = ? AND Book_ID = ?");
        $del->bind_param("ii", $user_id, $book_id);
        $del->execute();
        $del->close();
        $favorited = false;
    } else {
        $ins = $con->prepare("INSERT INTO user_favorites (User_ID, Book_ID) VALUES (?, ?)");
        $ins->bind_param("ii", $user_id, $book_id);
        $ins->execute();
        $ins->close();
        $favorited = true;
    }

    sendResponse(true, 'Favorite status updated.', ['favorited' => $favorited]);

} elseif ($action === 'get_book_status') {
    $book_id = book_id_param($_GET);

    if ($book_id <= 0) {
        http_response_code(400);
        sendResponse(false, 'Invalid Book ID.');
    }

    // Optimized Single SQL Query for Bookmark, Favorite, and Progress
    $query = "SELECT 
                (SELECT COUNT(1) FROM user_bookmarks WHERE User_ID = ? AND Book_ID = ?) AS is_bookmarked,
                (SELECT COUNT(1) FROM user_favorites WHERE User_ID = ? AND Book_ID = ?) AS is_favorited,
                p.Current_Page, p.Is_Finished
              FROM (SELECT 1) AS dummy
              LEFT JOIN user_reading_progress p ON p.User_ID = ? AND p.Book_ID = ?";
              
    $stmt = $con->prepare($query);
    $stmt->bind_param("iiiiii", $user_id, $book_id, $user_id, $book_id, $user_id, $book_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $bookmarked   = !empty($row['is_bookmarked']);
    $favorited    = !empty($row['is_favorited']);
    $has_progress = (isset($row['Current_Page']) && $row['Current_Page'] !== null && (int)$row['Is_Finished'] === 0);

    sendResponse(true, 'Status retrieved.', [
        'bookmarked'   => $bookmarked,
        'favorited'    => $favorited,
        'has_progress' => $has_progress,
        'resume_page'  => $has_progress ? (int)$row['Current_Page'] : 1
    ]);

} elseif ($action === 'report_issue') {
    $issue_types = ['Broken Link', 'Missing Page/s', 'Wrong File', 'License Concern', 'Other'];
    $book_id     = book_id_param($_POST);
    $issue       = gv($_POST, 'issue_type');
    $details     = gv($_POST, 'description');

    $book = db_one("SELECT Title FROM book_table WHERE Book_ID = ?", 'i', [$book_id]);
    if (!$book) {
        http_response_code(404);
        sendResponse(false, 'Book not found.');
    }
    if (!in_array($issue, $issue_types, true)) {
        http_response_code(422);
        sendResponse(false, 'Please choose what is wrong with the book.');
    }
    if (mb_strlen($details) > 1000) {
        http_response_code(422);
        sendResponse(false, 'Please keep the details under 1,000 characters.');
    }
    if (db_one("SELECT 1 FROM report_table WHERE User_ID = ? AND Book_ID = ? AND Issue_Type = ? AND Status <> 'Resolved' LIMIT 1",
               'iis', [$user_id, $book_id, $issue])) {
        sendResponse(false, 'You already reported this issue for this book. The library is looking into it.');
    }
    $today = (int)(db_one("SELECT COUNT(*) AS c FROM report_table WHERE User_ID = ? AND Created_At > UTC_TIMESTAMP() - INTERVAL 1 DAY",
                          'i', [$user_id])['c'] ?? 0);
    if ($today >= 10) {
        http_response_code(429);
        sendResponse(false, 'You have sent many reports today. Please try again tomorrow.');
    }

    db_exec("INSERT INTO report_table (Book_ID, User_ID, Issue_Type, Description) VALUES (?, ?, ?, ?)",
            'iiss', [$book_id, $user_id, $issue, $details !== '' ? $details : null]);
    notify_roles(['Librarian', 'Admin'], 'new_report', "Book issue reported: {$book['Title']}",
                 $issue . ($details !== '' ? ' - ' . mb_substr($details, 0, 150) : ''), 'librarymanagement.php#reports', $user_id);
    sendResponse(true, 'Thank you! Your report was sent to the library. You will get a notification when it is updated.');
}


/* ---------------------------- My Collections ----------------------------
 * VERY IMPORTANT!
 * A user's own named shelves of books (user_collections / user_collection_books).
 * Any book can go in a collection, physical or digital. Every query is scoped to the logged-in user.
 */
const COLLECTION_NAME_MAX = 30;
const COLLECTIONS_PER_USER = 30;
const BOOKS_PER_COLLECTION = 200;

/** The collection when it belongs to the current user, otherwise a 404 response. */
function own_collection(int $user_id, int $collection_id): array {
    $c = db_one("SELECT Collection_ID, Name FROM user_collections WHERE Collection_ID = ? AND User_ID = ?",
                'ii', [$collection_id, $user_id]);
    if (!$c) {
        sendResponse(false, 'Collection not found.', [], 404);
    }
    return $c;
}

/** Validated collection name, or a 422 response. $except = the collection being renamed. */
function collection_name(int $user_id, int $except = 0): string {
    $name = preg_replace('/\s+/u', ' ', gv($_POST, 'name'));
    if ($name === '') {
        sendResponse(false, 'Enter a collection name.', [], 422);
    }
    if (mb_strlen($name) > COLLECTION_NAME_MAX) {
        sendResponse(false, 'Collection names can be at most ' . COLLECTION_NAME_MAX . ' characters.', [], 422);
    }
    // case-insensitive (utf8mb4_general_ci), so "School" and "school" count as the same name
    if (db_one("SELECT 1 FROM user_collections WHERE User_ID = ? AND Name = ? AND Collection_ID <> ?",
               'isi', [$user_id, $name, $except])) {
        sendResponse(false, 'You already have a collection with that name.', [], 422);
    }
    return $name;
}

/** Put a book in a collection (no-op when it is already there). */
function collection_add_book(int $collection_id, int $book_id): void {
    $count = (int)(db_one("SELECT COUNT(*) AS c FROM user_collection_books WHERE Collection_ID = ?", 'i', [$collection_id])['c'] ?? 0);
    if ($count >= BOOKS_PER_COLLECTION) {
        sendResponse(false, 'A collection can hold at most ' . BOOKS_PER_COLLECTION . ' books.', [], 422);
    }
    db_exec("INSERT IGNORE INTO user_collection_books (Collection_ID, Book_ID) VALUES (?, ?)", 'ii', [$collection_id, $book_id]);
}

if ($action === 'list_collections') {
    $book_id = book_id_param($_GET);
    $rows = db_all($con,
        "SELECT c.Collection_ID, c.Name, COUNT(cb.Book_ID) AS Books, COALESCE(MAX(cb.Book_ID = ?), 0) AS Has_Book
           FROM user_collections c
           LEFT JOIN user_collection_books cb ON cb.Collection_ID = c.Collection_ID
          WHERE c.User_ID = ?
          GROUP BY c.Collection_ID, c.Name
          ORDER BY c.Created_At, c.Collection_ID", 'ii', [$book_id, $user_id]);
    sendResponse(true, 'Collections loaded.', ['collections' => array_map(fn($r) => [
        'id' => (int)$r['Collection_ID'], 'name' => $r['Name'], 'books' => (int)$r['Books'], 'has_book' => (bool)$r['Has_Book'],
    ], $rows)]);

} elseif ($action === 'create_collection') {
    $count = (int)(db_one("SELECT COUNT(*) AS c FROM user_collections WHERE User_ID = ?", 'i', [$user_id])['c'] ?? 0);
    if ($count >= COLLECTIONS_PER_USER) {
        sendResponse(false, 'You can have at most ' . COLLECTIONS_PER_USER . ' collections.', [], 422);
    }
    $name = collection_name($user_id);
    $book_id = book_id_param($_POST);
    if ($book_id > 0 && !verifyBookExists($con, $book_id)) {
        sendResponse(false, 'Book not found.', [], 404);
    }
    db_exec("INSERT INTO user_collections (User_ID, Name) VALUES (?, ?)", 'is', [$user_id, $name]);
    $id = (int)$con->insert_id;
    if ($book_id > 0) {
        collection_add_book($id, $book_id);
    }
    sendResponse(true, "Collection \"{$name}\" created.",
                 ['collection' => ['id' => $id, 'name' => $name, 'books' => $book_id > 0 ? 1 : 0, 'has_book' => $book_id > 0]]);

} elseif ($action === 'rename_collection') {
    $c = own_collection($user_id, max(0, intval($_POST['collection_id'] ?? 0)));
    $name = collection_name($user_id, (int)$c['Collection_ID']);
    db_exec("UPDATE user_collections SET Name = ? WHERE Collection_ID = ? AND User_ID = ?", 'sii', [$name, $c['Collection_ID'], $user_id]);
    sendResponse(true, 'Collection renamed.', ['name' => $name]);

} elseif ($action === 'delete_collection') {
    $c = own_collection($user_id, max(0, intval($_POST['collection_id'] ?? 0)));
    // the books stay in the library; only this shelf (and its rows, by cascade) goes
    db_exec("DELETE FROM user_collections WHERE Collection_ID = ? AND User_ID = ?", 'ii', [$c['Collection_ID'], $user_id]);
    sendResponse(true, "Collection \"{$c['Name']}\" deleted.");

} elseif ($action === 'set_collection_book') {
    // in=1 adds the book, in=0 removes it
    $c = own_collection($user_id, max(0, intval($_POST['collection_id'] ?? 0)));
    $book_id = book_id_param($_POST);
    if (!verifyBookExists($con, $book_id)) {
        sendResponse(false, 'Book not found.', [], 404);
    }
    if (($_POST['in'] ?? '') === '1') {
        collection_add_book((int)$c['Collection_ID'], $book_id);
        sendResponse(true, "Added to \"{$c['Name']}\".", ['in' => true]);
    }
    db_exec("DELETE FROM user_collection_books WHERE Collection_ID = ? AND Book_ID = ?", 'ii', [$c['Collection_ID'], $book_id]);
    sendResponse(true, "Removed from \"{$c['Name']}\".", ['in' => false]);

} elseif ($action === 'collection_books') {
    $c = own_collection($user_id, max(0, intval($_GET['collection_id'] ?? 0)));
    $rows = db_all($con,
        "SELECT b.Book_ID, b.Title, b.Author, b.Description, b.ISBN, b.Category, b.Genre, b.Publisher, b.Cover_Image,
                (b.Drive_Link IS NOT NULL AND b.Drive_Link <> '') AS Has_File
           FROM user_collection_books cb
           JOIN book_table b ON b.Book_ID = cb.Book_ID
          WHERE cb.Collection_ID = ?
          ORDER BY cb.Added_At DESC, b.Book_ID DESC", 'i', [$c['Collection_ID']]);
    sendResponse(true, 'Books loaded.', ['name' => $c['Name'], 'books' => array_map(fn($b) => [
        'token' => book_token((int)$b['Book_ID']), 'title' => $b['Title'], 'author' => $b['Author'] ?? '', 'desc' => $b['Description'] ?? '',
        'isbn' => $b['ISBN'] ?? '', 'genre' => $b['Category'] ?? '', 'book_genre' => $b['Genre'] ?? '', 'publisher' => $b['Publisher'] ?? '',
        'cover' => trim((string)($b['Cover_Image'] ?? '')), 'has_file' => (bool)$b['Has_File'],
    ], $rows)]);
}

http_response_code(400);
sendResponse(false, 'Invalid action.');