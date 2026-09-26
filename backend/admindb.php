<?php
session_start();
require_once "connection/config.php";
require_once "helpers/SystemOperators.php";

$con = connection();
$so = new SystemOperators();

// Convert Google Drive share links to direct thumbnail URLs
function getDriveDirectImageUrl(string $url): string {
    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://lh3.googleusercontent.com/d/" . $matches[1];
    }
    return $url;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'approve_reservation' && isset($_POST['reservation_id'])) {
        $res_id = intval($_POST['reservation_id']);
        $stmt = $con->prepare("UPDATE reservation_table SET Request_approval = 'Approved' WHERE Reservation_ID = ?");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $stmt->close();
    } 
    elseif ($action === 'deny_reservation' && isset($_POST['reservation_id'])) {
        $res_id = intval($_POST['reservation_id']);
        $stmt = $con->prepare("UPDATE reservation_table SET Request_approval = 'Denied' WHERE Reservation_ID = ?");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $stmt->close();
    } 
    elseif ($action === 'return_book' && isset($_POST['transaction_id'])) {
        $trans_id = intval($_POST['transaction_id']);
        $today = date("Y-m-d");
        $stmt = $con->prepare("UPDATE transaction_table SET Return_Date = ? WHERE Transaction_ID = ?");
        $stmt->bind_param("si", $today, $trans_id);
        $stmt->execute();
        $stmt->close();
    } 
    elseif ($action === 'remove_user' && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $stmt = $con->prepare("DELETE FROM user_table WHERE User_ID = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    elseif ($action === 'add_book') {
        $title            = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
        $author           = trim(filter_input(INPUT_POST, 'author', FILTER_SANITIZE_SPECIAL_CHARS));
        $isbn             = trim(filter_input(INPUT_POST, 'isbn', FILTER_SANITIZE_SPECIAL_CHARS));
        $category         = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS));
        $description      = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
        $cover_drive_raw  = trim(filter_input(INPUT_POST, 'cover_drive_link', FILTER_SANITIZE_URL));
        $pdf_drive_raw    = trim(filter_input(INPUT_POST, 'pdf_drive_link', FILTER_SANITIZE_URL));

        $cover_image_url = getDriveDirectImageUrl($cover_drive_raw);
        $pdf_preview_url = preg_replace('/\/view.*/', '/preview', $pdf_drive_raw);

        $is_digital = 'Yes';

        $stmt = $con->prepare("INSERT INTO book_table (Title, Author, ISBN, Category, Description, Cover_Image, Drive_Link, Is_digital) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $title, $author, $isbn, $category, $description, $cover_image_url, $pdf_preview_url, $is_digital);
        $stmt->execute();
        $stmt->close();
    }
    elseif ($action === 'remove_book' && isset($_POST['book_id'])) {
        $book_id = intval($_POST['book_id']);
        $stmt = $con->prepare("DELETE FROM book_table WHERE Book_ID = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: admindb.php?tab=" . ($_GET['tab'] ?? 'overview'));
    exit();
}

$active_tab = $_GET['tab'] ?? 'overview';

$total_students_res = $con->query("SELECT COUNT(*) AS total FROM user_table WHERE Role = 'Student'");
$total_students = $total_students_res->fetch_assoc()['total'] ?? 0;

$pending_res_res = $con->query("SELECT COUNT(*) AS total FROM reservation_table WHERE Request_approval = 'Pending'");
$pending_reservations = $pending_res_res->fetch_assoc()['total'] ?? 0;

$active_loans_res = $con->query("SELECT COUNT(*) AS total FROM transaction_table WHERE Return_Date IS NULL");
$active_loans = $active_loans_res->fetch_assoc()['total'] ?? 0;

$unpaid_fines_res = $con->query("SELECT SUM(Fine_amount) AS total FROM fine_table WHERE Payment_status = 'Unpaid'");
$unpaid_fines = $unpaid_fines_res->fetch_assoc()['total'] ?? 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Admin Dashboard</title>
    <link rel="stylesheet" href="../frontend/css/admindb.css">
</head>
<body>  
    <aside>
        <div class="logo-contain">
            <img src="../frontend/assets/Bibliodesk_White.png" class="logo">
        </div>
        <nav>
            <div class="side-act-container">
                <a class="side-act <?php echo $active_tab === 'overview' ? 'active-nav' : ''; ?>" href="admindb.php?tab=overview">
                    <img src="../frontend/assets/icons/overview-default.png" alt="Overview" class="sidebar-icon">
                    <span class="side-text">Overview</span>
                </a>

                <a class="side-act <?php echo $active_tab === 'books' ? 'active-nav' : ''; ?>" href="admindb.php?tab=books">
                    <img src="../frontend/assets/icons/overview-default.png" alt="Manage Books" class="sidebar-icon">
                    <span class="side-text">Manage Books</span>
                </a>

                <a class="side-act <?php echo $active_tab === 'reservations' ? 'active-nav' : ''; ?>" href="admindb.php?tab=reservations">
                    <img src="../frontend/assets/icons/reservations-default.png" alt="Reservations" class="sidebar-icon">
                    <span class="side-text">Reservations</span>
                </a>
        
                <a class="side-act <?php echo $active_tab === 'borrowed' ? 'active-nav' : ''; ?>" href="admindb.php?tab=borrowed">
                    <img src="../frontend/assets/icons/borrowed-default.png" alt="Borrowed" class="sidebar-icon">
                    <span class="side-text">Active Loans</span>
                </a>

                <a class="side-act <?php echo $active_tab === 'students' ? 'active-nav' : ''; ?>" href="admindb.php?tab=students">
                    <img src="../frontend/assets/icons/fees-default.png" alt="Student Accounts" class="sidebar-icon">
                    <span class="side-text">Student Accounts</span>
                </a>

                <a class="side-act <?php echo $active_tab === 'fines' ? 'active-nav' : ''; ?>" href="admindb.php?tab=fines">
                    <img src="../frontend/assets/icons/penalties-default.png" alt="Fines & Penalties" class="sidebar-icon">
                    <span class="side-text">Fines & Penalties</span>
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
                <span class="NotifNum"><?php echo $pending_reservations; ?></span>
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
        <?php if ($active_tab === 'overview'): ?>
            <section class="user_preview">
                <div class="user_info">
                    <p class="greetings">Librarian Control Panel,</p>
                    <h1>Administrator Workspace</h1>
                    <dd>System Management & Approvals</dd>
                    <dd>BiblioDesk Admin Console</dd>
                </div>

                <div class="recent_activity">
                    <h2 class="recent-section">Pending Tasks</h2>
                    <ul class="recent-list">
                        <li><?php echo $pending_reservations; ?> reservation request(s) awaiting review.</li>
                        <li><?php echo $active_loans; ?> active book loan(s) currently out.</li>
                    </ul>
                </div>
            </section>

            <section class="preview">
                <a class="stats-container" href="admindb.php?tab=students">
                    <img src="../frontend/assets/icons/library-hover.png" alt="students icon" class="stats-icon-tbb">
                    <div class="stats-text">
                        <span class="stats-num"><?php echo $total_students; ?></span>
                        <span class="stats-label">Registered Students</span>
                    </div>
                </a>

                <a class="stats-container" href="admindb.php?tab=reservations">
                    <img src="../frontend/assets/icons/pending-reserve.png" alt="reservations icon" class="stats-icon-pr">
                    <div class="stats-text">
                        <span class="stats-num"><?php echo $pending_reservations; ?></span>
                        <span class="stats-label">Pending Requests</span>
                    </div>
                </a>

                <a class="stats-container" href="admindb.php?tab=borrowed">
                    <img src="../frontend/assets/icons/due.png" alt="loans icon" class="stats-icon-bd">
                    <div class="stats-text">
                        <span class="stats-num"><?php echo $active_loans; ?></span>
                        <span class="stats-label">Active Loans</span>
                    </div>
                </a>
            </section>

        <?php elseif ($active_tab === 'books'): ?>
            <section class="table-section">
                <h2>Add New Digital Book</h2>
                <div class="form-container">
                    <form method="POST" class="add-book-form">
                        <input type="hidden" name="action" value="add_book">
                        
                        <div class="form-group">
                            <label>Book Title *</label>
                            <input type="text" name="title" required placeholder="Enter Title">
                        </div>

                        <div class="form-group">
                            <label>Author *</label>
                            <input type="text" name="author" required placeholder="Enter Author">
                        </div>

                        <div class="form-group">
                            <label>ISBN</label>
                            <input type="text" name="isbn" placeholder="e.g. 978-3-16-148410-0">
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" placeholder="e.g. BSIT / Research">
                        </div>

                        <div class="form-group full-width">
                            <label>Description / Summary *</label>
                            <textarea name="description" rows="3" required placeholder="Enter book summary..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Cover Image Google Drive Link *</label>
                            <input type="url" name="cover_drive_link" required placeholder="https://drive.google.com/file/d/.../view">
                        </div>

                        <div class="form-group">
                            <label>PDF Document Google Drive Link *</label>
                            <input type="url" name="pdf_drive_link" required placeholder="https://drive.google.com/file/d/.../view">
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" class="action-btn approve-btn submit-book-btn">Save & Publish Book</button>
                        </div>
                    </form>
                </div>

                <h2 style="margin-top: 35px;">Catalog List</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Digital Access</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT Book_ID, Title, Author, Category, Drive_Link FROM book_table ORDER BY Book_ID ASC";
                            $result = $con->query($sql);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td>#<?php echo $row['Book_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Author']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Category'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo !empty($row['Drive_Link']) ? 'approved' : 'denied'; ?>">
                                            <?php echo !empty($row['Drive_Link']) ? 'PDF Linked' : 'No PDF'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Delete this book from the catalog?');">
                                            <input type="hidden" name="action" value="remove_book">
                                            <input type="hidden" name="book_id" value="<?php echo $row['Book_ID']; ?>">
                                            <button type="submit" class="action-btn deny-btn">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="6" class="no-records">No books added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($active_tab === 'reservations'): ?>
            <section class="table-section">
                <h2>Reservation Requests</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Res ID</th>
                                <th>Student Email</th>
                                <th>Book Title</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT r.Reservation_ID, r.Reservation_Date, r.Reservation_Time, r.Request_approval,
                                           u.Email, b.Title 
                                    FROM reservation_table r
                                    JOIN user_table u ON r.User_ID = u.User_ID
                                    JOIN book_table b ON r.Book_ID = b.Book_ID
                                    ORDER BY r.Reservation_ID ASC";
                            $result = $con->query($sql);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $decrypted_email = !empty($row['Email']) ? $so->decrypt($row['Email']) : 'N/A';
                            ?>
                                <tr>
                                    <td>#<?php echo $row['Reservation_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($decrypted_email); ?></td>
                                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                    <td><?php echo $row['Reservation_Date'] . ' ' . $row['Reservation_Time']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['Request_approval']); ?>">
                                            <?php echo $row['Request_approval']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['Request_approval'] === 'Pending'): ?>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="action" value="approve_reservation">
                                                <input type="hidden" name="reservation_id" value="<?php echo $row['Reservation_ID']; ?>">
                                                <button type="submit" class="action-btn approve-btn">Approve</button>
                                            </form>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="action" value="deny_reservation">
                                                <input type="hidden" name="reservation_id" value="<?php echo $row['Reservation_ID']; ?>">
                                                <button type="submit" class="action-btn deny-btn">Deny</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="action-done">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="6" class="no-records">No reservation records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($active_tab === 'borrowed'): ?>
            <section class="table-section">
                <h2>Currently Borrowed Books</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Trans ID</th>
                                <th>Student Email</th>
                                <th>Book Title</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT t.Transaction_ID, t.Issue_Date, t.Due_Date, u.Email, b.Title 
                                    FROM transaction_table t
                                    JOIN user_table u ON t.User_ID = u.User_ID
                                    JOIN book_table b ON t.Book_ID = b.Book_ID
                                    WHERE t.Return_Date IS NULL
                                    ORDER BY t.Transaction_ID ASC";
                            $result = $con->query($sql);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $decrypted_email = !empty($row['Email']) ? $so->decrypt($row['Email']) : 'N/A';
                            ?>
                                <tr>
                                    <td>#<?php echo $row['Transaction_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($decrypted_email); ?></td>
                                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                    <td><?php echo $row['Issue_Date']; ?></td>
                                    <td><?php echo $row['Due_Date']; ?></td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="return_book">
                                            <input type="hidden" name="transaction_id" value="<?php echo $row['Transaction_ID']; ?>">
                                            <button type="submit" class="action-btn approve-btn">Mark Returned</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="6" class="no-records">No active loans currently recorded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($active_tab === 'students'): ?>
            <section class="table-section">
                <h2>Student Accounts Management</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Student ID</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Max Limits</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT User_ID, Student_ID, Email, Department, Max_Borrow_Limit 
                                    FROM user_table 
                                    WHERE Role = 'Student'
                                    ORDER BY User_ID ASC";
                            $result = $con->query($sql);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $student_id = !empty($row['Student_ID']) ? $so->decrypt($row['Student_ID']) : 'N/A';
                                    $email      = !empty($row['Email']) ? $so->decrypt($row['Email']) : 'N/A';
                                    $dept       = !empty($row['Department']) ? $so->decrypt($row['Department']) : 'N/A';
                            ?>
                                <tr>
                                    <td>#<?php echo $row['User_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($student_id); ?></td>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                    <td><?php echo htmlspecialchars($dept); ?></td>
                                    <td><?php echo $row['Max_Borrow_Limit']; ?> Books</td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to remove this account?');">
                                            <input type="hidden" name="action" value="remove_user">
                                            <input type="hidden" name="user_id" value="<?php echo $row['User_ID']; ?>">
                                            <button type="submit" class="action-btn deny-btn">Remove Account</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="6" class="no-records">No student accounts registered.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($active_tab === 'fines'): ?>
            <section class="table-section">
                <h2>Fees, Fines & Penalties</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Fine ID</th>
                                <th>Student Email</th>
                                <th>Book Title</th>
                                <th>Amount</th>
                                <th>Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT f.Fine_ID, f.Fine_amount, f.Payment_status, u.Email, b.Title 
                                    FROM fine_table f
                                    JOIN user_table u ON f.User_ID = u.User_ID
                                    JOIN book_table b ON f.Book_ID = b.Book_ID
                                    ORDER BY f.Fine_ID ASC";
                            $result = $con->query($sql);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $decrypted_email = !empty($row['Email']) ? $so->decrypt($row['Email']) : 'N/A';
                            ?>
                                <tr>
                                    <td>#<?php echo $row['Fine_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($decrypted_email); ?></td>
                                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                    <td>₱<?php echo number_format($row['Fine_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($row['Payment_status']); ?>">
                                            <?php echo $row['Payment_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="5" class="no-records">No fines recorded in the system.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        const aside = document.querySelector('aside');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar(){
            aside.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        hamburgerBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>