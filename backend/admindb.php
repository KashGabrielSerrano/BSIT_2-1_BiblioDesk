<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'Student';
if ($user_role !== 'Admin' && $user_role !== 'Librarian') {
    header("Location: studentdb.php");
    exit();
}

require_once "connection/config.php";
require_once "helpers/SystemOperators.php";

$con = connection();
$so = new SystemOperators();

function getDriveDirectImageUrl(string $url): string {
    $url = trim($url);
    if (empty($url)) return '../frontend/assets/icons/library-default.png';

    $file_id = '';
    if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $file_id = $matches[1];
    } elseif (preg_match('/id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $file_id = $matches[1];
    }
    if (!empty($file_id)) {
        return "https://drive.google.com/thumbnail?id=" . $file_id . "&sz=w500";
    }

    return $url;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $staff_id = $_SESSION['user_id']; 

    if ($action === 'approve_reservation' && isset($_POST['reservation_id'])) {
        $res_id = intval($_POST['reservation_id']);
        
        $res_stmt = $con->prepare("SELECT User_ID, Book_ID FROM reservation_table WHERE Reservation_ID = ?");
        $res_stmt->bind_param("i", $res_id);
        $res_stmt->execute();
        $res_data = $res_stmt->get_result()->fetch_assoc();
        $res_stmt->close();

        if ($res_data) {
            $user_id = $res_data['User_ID'];
            $book_id = $res_data['Book_ID'];
            $today = date("Y-m-d");

            $loan_days = 7;
            $stmt_set = $con->prepare("SELECT Setting_Value FROM system_settings WHERE Setting_Key = 'loan_duration_days'");
            if ($stmt_set) {
                $stmt_set->execute();
                $res_set = $stmt_set->get_result()->fetch_assoc();
                if (!empty($res_set['Setting_Value'])) {
                    $loan_days = intval($res_set['Setting_Value']);
                }
                $stmt_set->close();
            }

            $due_date = date("Y-m-d", strtotime("+$loan_days days"));

            $stmt = $con->prepare("UPDATE reservation_table SET Request_approval = 'Approved' WHERE Reservation_ID = ?");
            $stmt->bind_param("i", $res_id);
            $stmt->execute();
            $stmt->close();

            $trans_stmt = $con->prepare("INSERT INTO transaction_table (User_ID, Book_ID, Issue_Date, Due_Date, Issued_By) VALUES (?, ?, ?, ?, ?)");
            $trans_stmt->bind_param("iissi", $user_id, $book_id, $today, $due_date, $staff_id);
            $trans_stmt->execute();
            $trans_stmt->close();
        }
    } elseif ($action === 'deny_reservation' && isset($_POST['reservation_id'])) {
        $res_id = intval($_POST['reservation_id']);
        $stmt = $con->prepare("UPDATE reservation_table SET Request_approval = 'Denied' WHERE Reservation_ID = ?");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'return_book' && isset($_POST['transaction_id'])) {
        $trans_id = intval($_POST['transaction_id']);
        $today = date("Y-m-d");
        $stmt = $con->prepare("UPDATE transaction_table SET Return_Date = ?, Received_By = ? WHERE Transaction_ID = ?");
        $stmt->bind_param("sii", $today, $staff_id, $trans_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'collect_fine' && isset($_POST['fine_id'])) {
        $fine_id = intval($_POST['fine_id']);
        $stmt = $con->prepare("UPDATE fine_table SET Payment_status = 'Paid', Date_Paid = NOW(), Collected_By = ? WHERE Fine_ID = ?");
        $stmt->bind_param("ii", $staff_id, $fine_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'approve_user' && isset($_POST['user_id'])) {
        $uid = intval($_POST['user_id']);
        $stmt = $con->prepare("UPDATE user_table SET Account_Status = 'Active' WHERE User_ID = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'suspend_user' && isset($_POST['user_id'])) {
        $uid = intval($_POST['user_id']);
        $stmt = $con->prepare("UPDATE user_table SET Account_Status = 'Suspended' WHERE User_ID = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'remove_user' && isset($_POST['user_id'])) {
        if ($user_role === 'Admin') {
            $user_id = intval($_POST['user_id']);
            $stmt = $con->prepare("DELETE FROM user_table WHERE User_ID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($action === 'add_book') {
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS));
        $author = trim(filter_input(INPUT_POST, 'author', FILTER_SANITIZE_SPECIAL_CHARS));
        $isbn = trim(filter_input(INPUT_POST, 'isbn', FILTER_SANITIZE_SPECIAL_CHARS));
        $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS));
        $cover_drive_raw = trim(filter_input(INPUT_POST, 'cover_drive_link', FILTER_SANITIZE_URL));
        $pdf_drive_raw = trim(filter_input(INPUT_POST, 'pdf_drive_link', FILTER_SANITIZE_URL));

        $cover_image_url = getDriveDirectImageUrl($cover_drive_raw);
        
        $pdf_clean_link = $pdf_drive_raw;
        if (preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $pdf_drive_raw, $matches)) {
            $pdf_clean_link = "https://drive.google.com/file/d/" . $matches[1] . "/view";
        }

        $is_digital = 'Yes';

        $stmt = $con->prepare("INSERT INTO book_table (Title, Author, ISBN, Category, Description, Cover_Image, Drive_Link, Is_digital) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $title, $author, $isbn, $category, $description, $cover_image_url, $pdf_clean_link, $is_digital);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'remove_book' && isset($_POST['book_id'])) {
        $book_id = intval($_POST['book_id']);
        $stmt = $con->prepare("DELETE FROM book_table WHERE Book_ID = ?");
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'update_settings') {
        if ($user_role === 'Admin') {
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    $setting_key = filter_var($key, FILTER_SANITIZE_SPECIAL_CHARS);
                    $setting_val = filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);

                    $stmt = $con->prepare("UPDATE system_settings SET Setting_Value = ?, Updated_By = ? WHERE Setting_Key = ?");
                    $stmt->bind_param("sis", $setting_val, $staff_id, $setting_key);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    header("Location: admindb.php?tab=" . ($_GET['tab'] ?? 'overview'));
    exit();
}

$active_tab = $_GET['tab'] ?? 'overview';

$admin_only_tabs = ['settings']; 
if (in_array($active_tab, $admin_only_tabs) && $user_role !== 'Admin') {
    header("Location: admindb.php?tab=overview");
    exit();
}

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
    <title>Librarian | Admin Dashboard</title>
    <link rel="stylesheet" href="../frontend/css/admindb.css">
</head>
<body>  
    <aside>
        <div class="logo-contain">
            <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
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
                <?php if ($user_role === 'Admin'): ?>
                    <a class="side-act <?php echo $active_tab === 'settings' ? 'active-nav' : ''; ?>" href="admindb.php?tab=settings">
                        <img src="../frontend/assets/icons/settings.png" alt="System Settings" class="sidebar-icon">
                        <span class="side-text">System Settings</span>
                    </a>
                <?php endif; ?>
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

            <?php if ($user_role === 'Admin'): ?>
                <a href="admindb.php?tab=settings" class="settings" aria-label="Open Settings">
                    <img src="../frontend/assets/icons/settings.png" alt="settings icon" class="button-icon">
                </a>
            <?php endif; ?>

            <button type="button" class="accountpfp" aria-label="Open Account Information">
                <img src="../frontend/assets/icons/pfp-icon.png" alt="account profile picture" class="button-icon">
            </button>
        </div>
    </header>

    <main>
        <?php if ($active_tab === 'overview'): ?>
            <?php
            $current_user_name = "User";
            $user_stmt = $con->prepare("SELECT Full_Name FROM user_table WHERE User_ID = ?");
            $user_stmt->bind_param("i", $_SESSION['user_id']);
            $user_stmt->execute();
            $user_row = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();

            if (!empty($user_row['Full_Name'])) {
                $current_user_name = $so->decrypt($user_row['Full_Name']);
            }
            ?>
            <section class="user_preview">
                <div class="user_info">
                    <p class="greetings"><?php echo htmlspecialchars($user_role); ?> Control Panel</p>
                    <h1><?php echo htmlspecialchars($current_user_name); ?></h1>
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
                                <th>Full Name</th>
                                <th>Student ID</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT User_ID, Full_Name, Student_ID, Email, Department, Account_Status 
                                    FROM user_table 
                                    WHERE Role = 'Student'
                                    ORDER BY User_ID ASC";
                            $result = $con->query($sql);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $full_name  = !empty($row['Full_Name']) ? $so->decrypt($row['Full_Name']) : 'N/A';
                                    $student_id = !empty($row['Student_ID']) ? $so->decrypt($row['Student_ID']) : 'N/A';
                                    $email      = !empty($row['Email']) ? $so->decrypt($row['Email']) : 'N/A';
                                    $dept       = !empty($row['Department']) ? $so->decrypt($row['Department']) : 'N/A';
                                    $status     = $row['Account_Status'] ?? 'Pending';
                            ?>
                                <tr>
                                    <td>#<?php echo $row['User_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($full_name); ?></td>
                                    <td><?php echo htmlspecialchars($student_id); ?></td>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                    <td><?php echo htmlspecialchars($dept); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($status); ?>">
                                            <?php echo $status; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($status === 'Pending' || $status === 'Suspended'): ?>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="action" value="approve_user">
                                                <input type="hidden" name="user_id" value="<?php echo $row['User_ID']; ?>">
                                                <button type="submit" class="action-btn approve-btn">Approve / Activate</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($status === 'Active'): ?>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Suspend this student account?');">
                                                <input type="hidden" name="action" value="suspend_user">
                                                <input type="hidden" name="user_id" value="<?php echo $row['User_ID']; ?>">
                                                <button type="submit" class="action-btn deny-btn">Suspend</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($user_role === 'Admin'): ?>
                                            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to permanently remove this account?');">
                                                <input type="hidden" name="action" value="remove_user">
                                                <input type="hidden" name="user_id" value="<?php echo $row['User_ID']; ?>">
                                                <button type="submit" class="action-btn deny-btn" style="background-color: #555;">Remove</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr><td colspan="7" class="no-records">No student accounts registered.</td></tr>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT f.Fine_ID, f.Fine_amount, f.Payment_status, u.Email, b.Title 
                                    FROM fine_table f
                                    LEFT JOIN user_table u ON f.User_ID = u.User_ID
                                    LEFT JOIN book_table b ON f.Book_ID = b.Book_ID
                                    ORDER BY f.Fine_ID ASC";
                            $result = $con->query($sql);

                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                                    $decrypted_email = !empty($row['Email']) ? $so->decrypt($row['Email']) : 'N/A';
                                    $pstatus = $row['Payment_status'] ?? 'Unpaid';
                            ?>
                                <tr>
                                    <td>#<?php echo $row['Fine_ID']; ?></td>
                                    <td><?php echo htmlspecialchars($decrypted_email); ?></td>
                                    <td><?php echo htmlspecialchars($row['Title']); ?></td>
                                    <td>₱<?php echo number_format($row['Fine_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($pstatus); ?>">
                                            <?php echo htmlspecialchars($pstatus); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($pstatus === 'Unpaid'): ?>
                                            <form method="POST" style="display:inline-block;">
                                                <input type="hidden" name="action" value="collect_fine">
                                                <input type="hidden" name="fine_id" value="<?php echo $row['Fine_ID']; ?>">
                                                <button type="submit" class="action-btn approve-btn">Mark Paid</button>
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
                                <tr><td colspan="6" class="no-records">No fines recorded in the system.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        <?php elseif ($active_tab === 'settings' && $user_role === 'Admin'): ?>
            <section class="table-section">
                <h2>Global System Configuration</h2>
                <div class="form-container">
                    <form method="POST" class="add-book-form">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <?php
                        $settings_query = "SELECT Setting_Key, Setting_Value, Description FROM system_settings";
                        $settings_res = $con->query($settings_query);
                        if ($settings_res && $settings_res->num_rows > 0):
                            while ($setting = $settings_res->fetch_assoc()):
                        ?>
                            <div class="form-group">
                                <label><?php echo htmlspecialchars($setting['Description'] ?? $setting['Setting_Key']); ?></label>
                                <input type="text" name="<?php echo htmlspecialchars($setting['Setting_Key']); ?>" value="<?php echo htmlspecialchars($setting['Setting_Value']); ?>" required>
                            </div>
                        <?php 
                            endwhile;
                        endif; 
                        ?>

                        <div class="form-group full-width" style="margin-top: 15px;">
                            <button type="submit" class="action-btn approve-btn submit-book-btn">Update System Settings</button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        const aside = document.querySelector('aside');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            aside.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        hamburgerBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>