<?php
session_start();
require_once "connection/config.php";

$con = connection();

function getDriveDirectImageUrl(string $url)  {
    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://lh3.googleusercontent.com/d/" . $matches[1];
    }
    return $url;
}

$user_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_book') {
    if (!$user_id) {
        echo "<script>alert('Please log in to request a book.'); window.location='login.php';</script>";
        exit();
    }

    $book_id    = intval($_POST['book_id']);
    $today_date = date("Y-m-d");
    $today_time = date("H:i:s");

    $check_stmt = $con->prepare("SELECT Reservation_ID FROM reservation_table WHERE User_ID = ? AND Book_ID = ? AND Request_approval IN ('Pending', 'Approved')");
    $check_stmt->bind_param("ii", $user_id, $book_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    if ($check_res->num_rows > 0) {
        echo "<script>alert('You already have an active or pending request for this book.'); window.location='librarydb.php';</script>";
    } else {
        $insert_stmt = $con->prepare("INSERT INTO reservation_table (User_ID, Book_ID, Reservation_Date, Reservation_Time, Request_approval) VALUES (?, ?, ?, ?, 'Pending')");
        $insert_stmt->bind_param("iiss", $user_id, $book_id, $today_date, $today_time);

        if ($insert_stmt->execute()) {
            echo "<script>alert('Book request submitted! Awaiting librarian approval.'); window.location='librarydb.php';</script>";
        } else {
            echo "<script>alert('Failed to submit request.');</script>";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

$search_query = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library</title>
    <link rel="stylesheet" href="../frontend/css/librarydb.css">
    <script src="../frontend/js/sidebar_Btn.js" defer></script>
    <script src="../frontend/js/carousel.js" defer></script>
</head>
<body>  
    <aside>
        <div class="logo-contain">
            <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
        </div>
        <nav>
            <div class="side-act-container">
                <a class="side-act" href="../backend/studentdb.php">
                    <img src="../frontend/assets/icons/overview-default.png" alt="Overview" class="sidebar-icon">
                    <span class="side-text">Overview</span>
                </a>
                <a class="side-act active" href="../backend/librarydb.php">
                    <img src="../frontend/assets/icons/library-default.png" alt="Library" class="sidebar-icon">
                    <span class="side-text">Library</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/borrowed-default.png" alt="Borrowed" class="sidebar-icon">
                    <span class="side-text">Borrowed</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/reservations-default.png" alt="Reservations" class="sidebar-icon">
                    <span class="side-text">Reservations</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/penalties-default.png" alt="Penalties" class="sidebar-icon">
                    <span class="side-text">Penalties</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/fees-default.png" alt="Fees & Account" class="sidebar-icon">
                    <span class="side-text">Fees & Account</span>
                </a>
            </div>
            
            <div class="log-out-container">
                <a class="side-act" href="login.php?action=logout">
                    <img src="../frontend/assets/icons/logout.png" alt="log out" class="sidebar-icon">
                    <span class="side-text">Log Out</span>
                </a>
            </div>
        </nav>
    </aside>

    <header class="topbar">
        <button type="button" class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="logo-name-container">
            <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" class="logo-name">
        </div>
            
        <div class="top-actions">
            <button type="button" class="notification-bell" aria-label="Open Notification Area">
                <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" class="button-icon">
                <span class="NotifNum">0</span>
            </button>

            <button type="button" class="settings" aria-label="Open Settings">
                <img src="../frontend/assets/icons/settings.png" alt="settings icon" class="button-icon">
            </button>

            <button type="button" class="accountpfp" aria-label="Open Account Information">
                <img src="../frontend/assets/icons/pfp-icon.png" alt="account profile picture" class="button-icon">
            </button>
        </div>
    </header>

    <main>
        <div class="library-header-row">
            <div class="title-group">
                <img src="../frontend/assets/icons/library-default.png" alt="Library Icon" class="library-icon">
                <h1 class="library-title">Library</h1>
            </div>

            <div class="search-filter-group">
                <form class="search-form" action="librarydb.php" method="get">
                    <div class="search-input-wrapper">
                        <svg class="search-icon-svg" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="search" placeholder="Search" class="search-input" value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </form>

                <button type="button" class="filter-btn">
                    <svg class="filter-icon-svg" viewBox="0 0 24 24">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    Filter
                </button>
            </div>
        </div>

        <?php
        if (!empty($search_query)) {
            $categories_to_display = ['Search Results'];
        } else {
            $categories_to_display = ['Recommendations', 'BSIT', 'Research'];
        }

        foreach ($categories_to_display as $cat_title):
            if ($cat_title === 'Search Results') {
                $sql = "SELECT * FROM book_table WHERE Title LIKE ? OR Author LIKE ? OR Category LIKE ? ORDER BY Book_ID DESC";
                $stmt = $con->prepare($sql);
                $searchTerm = "%" . $search_query . "%";
                $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            } elseif ($cat_title === 'Recommendations') {
                $sql = "SELECT * FROM book_table ORDER BY Book_ID DESC LIMIT 10";
                $stmt = $con->prepare($sql);
            } else {
                $sql = "SELECT * FROM book_table WHERE Category = ? ORDER BY Book_ID DESC";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("s", $cat_title);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0 && $cat_title !== 'Search Results') {
                $stmt->close();
                continue;
            }
        ?>
            <section class="book-panel">
                <div class="books-header">
                    <h2 class="books-section"><?php echo htmlspecialchars($cat_title); ?></h2>
                    <a class="Expand" href="More.php?category=<?php echo urlencode($cat_title); ?>">More &gt;</a>
                </div>
                
                <div class="carousel-wrapper">
                    <button onclick="toggleScroll(this)" class="floating-arrow-btn" aria-label="Scroll Books">&gt;</button>
                    <div class="books">
                        <?php 
                        if ($result->num_rows > 0):
                            $card_index = 0;
                            while ($book = $result->fetch_assoc()):
                                $card_class = ($card_index % 2 === 0) ? 'sage-sec' : 'tan-sec';
                                $card_index++;
                                $cover_url = !empty($book['Cover_Image']) ? getDriveDirectImageUrl($book['Cover_Image']) : '../frontend/assets/icons/library-default.png';
                        ?>
                            <div class="<?php echo $card_class; ?>">
                                <div class="card-content">
                                    <img src="<?php echo htmlspecialchars($cover_url); ?>" alt="Book Cover" class="book-cover">
                                    <div class="book-info">
                                        <p class="title"><?php echo htmlspecialchars($book['Title']); ?></p>
                                        <p class="author"><?php echo htmlspecialchars($book['Author']); ?></p>
                                        <div class="card-actions">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="request_book">
                                                <input type="hidden" name="book_id" value="<?php echo $book['Book_ID']; ?>">
                                                <button type="submit" class="reservation-pill" style="border:none; cursor:pointer;">Reservation</button>
                                            </form>

                                            <?php if (!empty($book['Drive_Link'])): ?>
                                                <a class="readonline-pill" href="read_book.php?id=<?php echo $book['Book_ID']; ?>" target="_blank">Read Online</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <p style="padding: 15px; color: #666;">No books found matching your query.</p>
                        <?php 
                        endif; 
                        $stmt->close();
                        ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</body>
</html>