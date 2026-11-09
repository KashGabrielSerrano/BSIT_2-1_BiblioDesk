<?php
// Session, config, DB, guards and sendResponse() come from the shared core.
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$con = db();

// Logged-in and still Active (re-checked against the DB on every request).
$user_id = require_login_api()['user_id'];

// Enforce CSRF Token Validation across POST requests
require_csrf();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function to verify book exists and is accessible
function verifyBookExists(mysqli $con, int $book_id): bool {
    $stmt = $con->prepare("SELECT Book_ID FROM book_table WHERE Book_ID = ? AND Is_digital = 'Yes' AND Drive_Link IS NOT NULL AND Drive_Link != ''");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res->num_rows > 0;
    $stmt->close();
    return $exists;
}

if ($action === 'save_progress') {
    $book_id = max(0, intval($_POST['book_id'] ?? 0));
    $current_page = max(1, intval($_POST['current_page'] ?? 1));
    $total_pages = max(1, intval($_POST['total_pages'] ?? 1));

    if (!verifyBookExists($con, $book_id)) {
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
    $book_id = max(0, intval($_POST['book_id'] ?? 0));

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
    $book_id = max(0, intval($_POST['book_id'] ?? 0));

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
    $book_id = max(0, intval($_GET['book_id'] ?? 0));

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
    $book_id     = max(0, intval($_POST['book_id'] ?? 0));
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

http_response_code(400);
sendResponse(false, 'Invalid action.');