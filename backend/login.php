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
$show_otp_modal = false;
$otp_expires_at_ms = null;
$pending_email_display = '';

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
                
                if ($status === 'Pending') {
                    // Set pending session variables for OTP recovery
                    $_SESSION['pending_user_id'] = $row['User_ID'];
                    $_SESSION['pending_email']   = $raw_email;
                    $_SESSION['last_otp_resend'] = time();

                    // Invalidate old active OTPs
                    $inval_stmt = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE User_ID = ? AND Is_Used = 0");
                    $inval_stmt->bind_param("i", $row['User_ID']);
                    $inval_stmt->execute();
                    $inval_stmt->close();

                    // Generate new OTP
                    $otp_code = $so->generateOTPCode();
                    $otp_stmt = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used, Attempts) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0, 0)");
                    $otp_stmt->bind_param("is", $row['User_ID'], $otp_code);
                    $otp_stmt->execute();
                    $otp_stmt->close();

                    // Get expiration timestamp
                    $time_stmt = $con->prepare("SELECT UNIX_TIMESTAMP(Expires_At) AS expires_at FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
                    $time_stmt->bind_param("i", $row['User_ID']);
                    $time_stmt->execute();
                    $expires_at_unix = $time_stmt->get_result()->fetch_assoc()['expires_at'] ?? null;
                    $time_stmt->close();

                    $otp_expires_at_ms = $expires_at_unix ? ($expires_at_unix * 1000) : null;
                    $pending_email_display = $raw_email;
                    $so->sendEmail($raw_email, $otp_code);
                    $show_otp_modal = true;
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
                $error = "Incorrect Email or Password. Please try again.";
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
                    <input type="email" class="form-control" id="email" name="email" placeholder="bibliodesk@example.com" value="<?= htmlspecialchars($raw_email, ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                
                <div class="form-items">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Enter your password" required>
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

<!-- OTP Verification Modal -->
<div class="modal fade" id="otpModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="otpModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bibliodesk-modal-header">
                <h5 class="modal-title d-flex align-items-center" id="otpModalTitle">
                    <i class="bi bi-shield-lock-fill me-2 fs-4"></i>Email Verification
                </h5>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-1">Your account is unverified. A code was sent to:</p>
                <p class="fw-bold fs-6 text-dark mb-3" id="otpEmailDisplay"><?= htmlspecialchars($pending_email_display, ENT_QUOTES, 'UTF-8') ?></p>
                
                <div id="otpAlert" class="alert d-none py-2 px-3 small rounded-3" role="alert"></div>
                <div class="mb-3">
                    <input type="text" id="otpCodeInput" inputmode="numeric" pattern="[0-9]*" class="form-control text-center font-monospace fs-2 py-2 bibliodesk-otp-input" maxlength="6" placeholder="000000" autocomplete="off">
                </div>
                <div class="mb-3 text-muted small">
                    <i class="bi bi-clock-history me-1"></i>Code expires in <span id="otpExpiryTimer" class="fw-bold text-danger">05:00</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <small class="text-muted">Didn't receive the code?</small>
                    <button type="button" id="btnResendOtp" class="btn btn-link btn-sm p-0 text-decoration-none bibliodesk-resend-link" disabled>
                        Resend code in <span id="resendTimer">60</span>s
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" id="btnVerifyOtp" class="btn bibliodesk-btn-primary w-100 py-2 rounded-3 shadow-sm">
                    Verify & Complete Registration
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="../frontend/js/Toggle.js"></script>
<?php if ($show_otp_modal): ?>
<script>
    window.otpExpiresAtMs = <?= json_encode($otp_expires_at_ms) ?>;
</script>
<script src="../frontend/js/login_otp.js"></script>
<?php endif; ?>
</body>
</html>