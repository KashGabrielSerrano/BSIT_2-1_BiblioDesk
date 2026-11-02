<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/SystemOperators.php";

$con = connection();
$so  = new SystemOperators();
$user_ip = $so->getClientIP();

// Check Login Session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized session. Please log in.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Enforce CSRF Token Validation across POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($submitted_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted_token)) {
        http_response_code(403);
        error_log("Security Warning: Potential CSRF attempt blocked from IP " . $user_ip);
        sendResponse(false, 'Your session has expired or the request could not be verified. Please reload the page.');
    }
}

function sendResponse(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
}

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
}

http_response_code(400);
sendResponse(false, 'Invalid action.');