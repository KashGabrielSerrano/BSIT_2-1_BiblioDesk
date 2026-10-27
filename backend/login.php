<?php
session_start();
require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/SystemOperators.php";

$con = connection();
$so = new SystemOperators();

if (rand(1, 100) === 1) {
    $con->query("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 1 DAY");
}

$max_attempts    = 5;
$lockout_minutes = 15;

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $client_ip = trim($ip_list[0]);
        if (filter_var($client_ip, FILTER_VALIDATE_IP)) {
            return $client_ip;
        }
    }
    return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

$user_ip = get_client_ip();
function record_failed_attempt($con, $user_ip, $encrypted_email = null) {
    $log_stmt = $con->prepare("INSERT INTO login_attempts (ip_address, email_attempted) VALUES (?, ?)");
    $log_stmt->bind_param("ss", $user_ip, $encrypted_email);
    $log_stmt->execute();
    $log_stmt->close();
}


$raw_email = isset($_POST['email']) ? trim($_POST['email']) : '';
$encrypted_email = !empty($raw_email) ? $so->encrypt($raw_email) : null;

$time_threshold = date("Y-m-d H:i:s", strtotime("-$lockout_minutes minutes"));

$stmt = $con->prepare("SELECT COUNT(*), MAX(attempt_time) FROM login_attempts 
    WHERE (ip_address = ? OR (email_attempted = ? AND email_attempted IS NOT NULL)) 
    AND attempt_time > ?");
$stmt->bind_param("sss", $user_ip, $encrypted_email, $time_threshold);
$stmt->execute();
$stmt->bind_result($failed_count, $latest_attempt_time);
$stmt->fetch();
$stmt->close();

$is_locked_out = false;
$error = '';

if ($failed_count >= $max_attempts) {
    $is_locked_out = true;
    
    $latest_time = !empty($latest_attempt_time) ? strtotime((string)$latest_attempt_time) : time();
    $seconds_elapsed = time() - $latest_time;
    $seconds_remaining = max(1, ($lockout_minutes * 60) - $seconds_elapsed);
    $minutes_remaining = (int) ceil($seconds_remaining / 60);
    
    $error = "Too many failed login attempts for this IP or account. Please try again in {$minutes_remaining} minute(s).";
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: login.php");
    exit;
}

// Redirect if already logged in
$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'Librarian' || $_SESSION['role'] === 'Admin')) {
        header("Location: admindb.php");
    } else {
        header("Location: studentdb.php");
    }
    exit;
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnLogin']) && !$is_logged_in && !$is_locked_out) {
    $passInput = $_POST['password'] ?? '';

    if (empty($raw_email) || empty($passInput)) {
        $error = "Please fill in all security fields.";
        record_failed_attempt($con, $user_ip, $encrypted_email);
    } else {
        $stmt = $con->prepare("SELECT `User_ID`, `Student_ID`, `Email`, `Password`, `Role`, `Account_Status` FROM `user_table` WHERE `Email` = ?");
        $stmt->bind_param('s', $encrypted_email);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row && password_verify($passInput, $row['Password'])) {
                $status = $row['Account_Status'] ?? 'Active';

                // Status checks occur AFTER password verification (not logged as failed attempts)
                if ($status === 'Pending') {
                    $error = "Your account is pending Admin approval. Please wait for activation.";
                } elseif ($status === 'Suspended') {
                    $error = "Your account has been suspended. Please contact the Library Administrator.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']    = $row['User_ID'];
                    $_SESSION['email']      = $so->decrypt($row['Email']);
                    $_SESSION['role']       = $row['Role'];
                    $_SESSION['student_id'] = !empty($row['Student_ID']) ? $so->decrypt($row['Student_ID']) : null;

                    // Clear failed attempts on successful login
                    $clear_stmt = $con->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR email_attempted = ?");
                    $clear_stmt->bind_param("ss", $user_ip, $encrypted_email);
                    $clear_stmt->execute();
                    $clear_stmt->close();

                    if ($row['Role'] === 'Librarian' || $row['Role'] === 'Admin') {
                        header("Location: admindb.php");
                    } else {
                        header("Location: studentdb.php");
                    }
                    exit;
                }
            } else {
                // Invalid password or unknown email
                $error = "The system credentials provided do not match our database records.";
                record_failed_attempt($con, $user_ip, $encrypted_email);
            }
            $stmt->close();
        } else {
            $error = "Failed to execute authentication request.";
            record_failed_attempt($con, $user_ip, $encrypted_email);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk LMS - Portal Login</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/login.css">
</head>
<body>


<div class="login-container">
    <div class="logo-panel">
        <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
        <p class="tagline">Read, Archive, Advance,<br>Knowledge.</p>
    </div>

    
        <div class="form-wrapper">
                <form action="login.php" method="post">
                    <div class="form-fields">
                        <h1>Log In</h1>

                        <?php if (!empty($error)): ?>
                            <div class="alert bg-danger text-white d-flex align-items-center" role="alert">
                                <p class="mb-0"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                                <button type="button" class="btn-close ms-auto btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                             </div>
                        <?php endif; ?>

                    <div class="form-items">
                        <label for="email" class="form-label">Personal Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="bibliodesk@example.com" required>
                    </div>
                    
                    <div class="form-items">
                        <label for="password" class="form-label">Password</label>
                         <div class="password-container">
                          <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="••••••••" required>
                          <span id="togglePassword" class="password-toggle-icon">
                           <i class="bi bi-eye" id="toggleIcon"></i>
                          </span>
                         </div>
                    </div>

                    <div class="form-options">
                     <div class="form-field">
                        <input type="checkbox" id="checkLabel" name="remember" />
                        <label for="checkLabel">Remember me</label>
                     </div>
                        <a href="forgotpassword.php" class="forgot-password">Forgot Password?</a>
                    </div>

                        <button type="submit" name="btnLogin">Login</button>

                        <hr>
                        <p class="register">Don't have an account? <a href="registration.php">REGISTER NOW</a></p>
                        <p class="terms">By proceeding, you agree to our <a href="terms.php">Terms of Use</a> <br> and confirm that you have read our <a href="privacy.php">Privacy Policy</a>.</p>
                    </div>
                </form>
        </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="../frontend/js/Toggle.js"></script>
</body>
</html>