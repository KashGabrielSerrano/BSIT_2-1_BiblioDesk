<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/SystemOperators.php";

$con = connection();
$so  = new SystemOperators();

// JSON Helper
function sendResponse(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit();
}

// Client IP Helper
function get_client_ip(): string {
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

function record_failed_attempt(mysqli $con, string $user_ip, ?string $encrypted_email = null): void {
    $log_stmt = $con->prepare("INSERT INTO login_attempts (ip_address, email_attempted) VALUES (?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("ss", $user_ip, $encrypted_email);
        $log_stmt->execute();
        $log_stmt->close();
    }
}

// Cleanup old login attempts periodically
if (rand(1, 100) === 1) {
    $con->query("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 1 DAY");
}

$action = $_POST['action'] ?? '';

// ==========================================
// 1. ACTION: USER LOGIN
// ==========================================
if ($action === 'login') {
    $raw_email = trim($_POST['email'] ?? '');
    $passInput = $_POST['password'] ?? '';
    $remember  = isset($_POST['remember']);

    if (empty($raw_email) || empty($passInput)) {
        sendResponse(false, 'Please fill in all security fields.');
    }

    $encrypted_email = $so->encrypt($raw_email);
    $lockout_minutes = 15;
    $max_attempts    = 5;
    $time_threshold  = date("Y-m-d H:i:s", strtotime("-$lockout_minutes minutes"));

    // Check Lockout Status
    $stmt = $con->prepare("SELECT COUNT(*), MAX(attempt_time) FROM login_attempts WHERE (ip_address = ? OR (email_attempted = ? AND email_attempted IS NOT NULL)) AND attempt_time > ?");
    $stmt->bind_param("sss", $user_ip, $encrypted_email, $time_threshold);
    $stmt->execute();
    $stmt->bind_result($failed_count, $latest_attempt_time);
    $stmt->fetch();
    $stmt->close();

    if ($failed_count >= $max_attempts) {
        $latest_time = !empty($latest_attempt_time) ? strtotime((string)$latest_attempt_time) : time();
        $seconds_remaining = max(1, ($lockout_minutes * 60) - (time() - $latest_time));
        $minutes_remaining = (int) ceil($seconds_remaining / 60);
        sendResponse(false, "Too many failed login attempts. Please try again in {$minutes_remaining} minute(s).");
    }

    // Query User Account
    $stmt = $con->prepare("SELECT User_ID, Student_ID, Email, Password, Role, Account_Status FROM user_table WHERE Email = ?");
    $stmt->bind_param("s", $encrypted_email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($passInput, $user['Password'])) {
        record_failed_attempt($con, $user_ip, $encrypted_email);
        sendResponse(false, 'Incorrect Email or Password. Please try again.');
    }

    $status = $user['Account_Status'] ?? 'Active';

    if ($status === 'Suspended') {
        sendResponse(false, 'Your account has been suspended. Please contact the Library Administrator.');
    }

    // Handle Unverified / Pending Registration
    if ($status === 'Pending') {
        $user_id = $user['User_ID'];
        $_SESSION['pending_user_id'] = $user_id;
        $_SESSION['pending_email']   = $raw_email;
        $_SESSION['last_otp_resend'] = time();

        // Invalidate previous active OTPs
        $inval_stmt = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE User_ID = ? AND Is_Used = 0");
        $inval_stmt->bind_param("i", $user_id);
        $inval_stmt->execute();
        $inval_stmt->close();

        // Generate New Activation OTP
        $otp_code   = $so->generateOTPCode();
        $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
        $otp_stmt   = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used, Attempts) VALUES (?, ?, ?, 0, 0)");
        $otp_stmt->bind_param("iss", $user_id, $otp_code, $expires_at);
        $otp_stmt->execute();
        $otp_stmt->close();

        // Get Expiry
        $time_stmt = $con->prepare("SELECT UNIX_TIMESTAMP(Expires_At) AS expires_at FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
        $time_stmt->bind_param("i", $user_id);
        $time_stmt->execute();
        $expires_at_unix = $time_stmt->get_result()->fetch_assoc()['expires_at'] ?? (time() + 300);
        $time_stmt->close();

        $so->sendEmail($raw_email, $otp_code, 'registration');

        sendResponse(true, 'Account unverified. An activation code has been sent to your email.', [
            'requires_otp'  => true,
            'email'         => $raw_email,
            'expires_at_ms' => $expires_at_unix * 1000
        ]);
    }

    // Successful Login
    session_regenerate_id(true);
    $_SESSION['user_id']    = $user['User_ID'];
    $_SESSION['email']      = $so->decrypt($user['Email']);
    $_SESSION['role']       = $user['Role'];
    $_SESSION['student_id'] = !empty($user['Student_ID']) ? $so->decrypt($user['Student_ID']) : null;

    // Handle Remember Me Token Generation
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $hashed_token = hash('sha256', $token);
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $expires_at = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

        $tok_stmt = $con->prepare("INSERT INTO user_tokens (user_id, token_hash, user_agent, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)");
        if ($tok_stmt) {
            $tok_stmt->bind_param("issss", $user['User_ID'], $hashed_token, $user_agent, $user_ip, $expires_at);
            $tok_stmt->execute();
            $tok_stmt->close();
        }

        $is_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        setcookie("remember_me", $token, [
            'expires'  => time() + (30 * 24 * 60 * 60),
            'path'     => '/',
            'domain'   => '',
            'secure'   => $is_secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // Clear Failed Login Attempts
    $clear_stmt = $con->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR email_attempted = ?");
    $clear_stmt->bind_param("ss", $user_ip, $encrypted_email);
    $clear_stmt->execute();
    $clear_stmt->close();

    $redirect_url = ($user['Role'] === 'Librarian' || $user['Role'] === 'Admin') ? 'admindb.php' : 'studentdb.php';
    sendResponse(true, 'Login successful!', ['redirect' => $redirect_url]);
}

// ==========================================
// 2. ACTION: FORGOT PASSWORD - SEND OTP
// ==========================================
if ($action === 'fp_send_otp') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please enter a valid email address.');
    }

    $generic_message = 'If an account associated with this email address exists, we have sent a verification code.';

    $encrypted_email = $so->encrypt($email);

    $stmt = $con->prepare("SELECT User_ID, Account_Status FROM user_table WHERE Email = ?");
    $stmt->bind_param("s", $encrypted_email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || $user['Account_Status'] !== 'Active') {
        sendResponse(true, $generic_message);
    }

    $user_id = $user['User_ID'];

    // Invalidate old OTPs
    $inval_stmt = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE User_ID = ? AND Is_Used = 0");
    $inval_stmt->bind_param("i", $user_id);
    $inval_stmt->execute();
    $inval_stmt->close();

    $otp_code   = $so->generateOTPCode();
    $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
    $otp_stmt   = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used, Attempts) VALUES (?, ?, ?, 0, 0)");
    $otp_stmt->bind_param("iss", $user_id, $otp_code, $expires_at);

    if ($otp_stmt->execute()) {
        $otp_stmt->close();

        $time_stmt = $con->prepare("SELECT UNIX_TIMESTAMP(Expires_At) AS expires_at FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
        $time_stmt->bind_param("i", $user_id);
        $time_stmt->execute();
        $expires_at_unix = $time_stmt->get_result()->fetch_assoc()['expires_at'] ?? (time() + 300);
        $time_stmt->close();

        $_SESSION['fp_user_id']  = $user_id;
        $_SESSION['fp_email']    = $email;
        $_SESSION['fp_verified'] = false;

        if ($so->sendEmail($email, $otp_code, 'forgot_password')) {
            sendResponse(true, $generic_message, [
                'expires_at' => $expires_at_unix * 1000
            ]);
        } else {
            sendResponse(false, 'Failed to send OTP email. Please try again.');
        }
    } else {
        $otp_stmt->close();
        sendResponse(false, 'Server error generating OTP code.');
    }
}

// ==========================================
// 3. ACTION: FORGOT PASSWORD - VERIFY OTP
// ==========================================
if ($action === 'fp_verify_otp') {
    $otp_code = trim($_POST['otp_code'] ?? '');
    $user_id  = $_SESSION['fp_user_id'] ?? null;

    if (!$user_id || empty($_SESSION['fp_email'])) {
        sendResponse(false, 'Session expired. Please restart the request.');
    }

    if (empty($otp_code) || strlen($otp_code) !== 6) {
        sendResponse(false, 'Please enter a valid 6-digit verification code.');
    }

    $stmt = $con->prepare("SELECT Verification_ID, OTP_Code, Expires_At, Is_Used, Attempts FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $otp_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$otp_record || $otp_record['Is_Used'] == 1) {
        sendResponse(false, 'No active verification code found. Please request a new one.');
    }

    if (strtotime($otp_record['Expires_At']) < time()) {
        sendResponse(false, 'Your verification code has expired. Please request a new one.');
    }

    if ($otp_record['Attempts'] >= 5) {
        $inval = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?");
        $inval->bind_param("i", $otp_record['Verification_ID']);
        $inval->execute();
        $inval->close();
        sendResponse(false, 'Too many failed attempts. Code invalidated. Request a new one.');
    }

    if ($otp_record['OTP_Code'] === $otp_code) {
        $upd = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?");
        $upd->bind_param("i", $otp_record['Verification_ID']);
        $upd->execute();
        $upd->close();

        $_SESSION['fp_verified'] = true;
        sendResponse(true, 'Code verified successfully.');
    } else {
        $inc = $con->prepare("UPDATE email_verifications SET Attempts = Attempts + 1 WHERE Verification_ID = ?");
        $inc->bind_param("i", $otp_record['Verification_ID']);
        $inc->execute();
        $inc->close();

        $remaining_attempts = 5 - ($otp_record['Attempts'] + 1);
        if ($remaining_attempts > 0) {
            sendResponse(false, "Invalid verification code. {$remaining_attempts} attempt(s) remaining.");
        } else {
            sendResponse(false, 'Too many failed attempts. Code invalidated. Request a new one.');
        }
    }
}

// ==========================================
// 4. ACTION: FORGOT PASSWORD - RESEND OTP
// ==========================================
if ($action === 'fp_resend_otp') {
    $user_id = $_SESSION['fp_user_id'] ?? null;
    $email   = $_SESSION['fp_email'] ?? '';

    if (!$user_id || empty($email)) {
        sendResponse(false, 'Session expired. Please restart the password reset process.');
    }

    $inval_stmt = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE User_ID = ? AND Is_Used = 0");
    $inval_stmt->bind_param("i", $user_id);
    $inval_stmt->execute();
    $inval_stmt->close();

    $otp_code   = $so->generateOTPCode();
    $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
    $otp_stmt   = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used, Attempts) VALUES (?, ?, ?, 0, 0)");
    $otp_stmt->bind_param("iss", $user_id, $otp_code, $expires_at);

    if ($otp_stmt->execute()) {
        $otp_stmt->close();

        $time_stmt = $con->prepare("SELECT UNIX_TIMESTAMP(Expires_At) AS expires_at FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
        $time_stmt->bind_param("i", $user_id);
        $time_stmt->execute();
        $expires_at_unix = $time_stmt->get_result()->fetch_assoc()['expires_at'] ?? (time() + 300);
        $time_stmt->close();

        if ($so->sendEmail($email, $otp_code, 'forgot_password')) {
            sendResponse(true, 'A new verification code has been sent to your email.', [
                'expires_at' => $expires_at_unix * 1000
            ]);
        } else {
            sendResponse(false, 'Failed to send OTP email. Please try again.');
        }
    } else {
        $otp_stmt->close();
        sendResponse(false, 'Failed to generate new OTP code.');
    }
}

// ==========================================
// 5. ACTION: FORGOT PASSWORD - RESET PASSWORD
// ==========================================
if ($action === 'fp_reset_password') {
    $user_id     = $_SESSION['fp_user_id'] ?? null;
    $is_verified = $_SESSION['fp_verified'] ?? false;
    $new_pass    = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (!$user_id || !$is_verified) {
        sendResponse(false, 'Unauthorized session. Please verify your email code first.');
    }

    if (strlen($new_pass) < 8) {
        sendResponse(false, 'Password must be at least 8 characters long.');
    }

    if (strlen($new_pass) > 64) {
        sendResponse(false, 'Password must not exceed 64 characters.');
    }

    if ($new_pass !== $confirm_pass) {
        sendResponse(false, 'Passwords do not match.');
    }

    $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = $con->prepare("UPDATE user_table SET Password = ? WHERE User_ID = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        $stmt->close();

        // Destroy persistent user tokens
        $del_tok = $con->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        $del_tok->bind_param("i", $user_id);
        $del_tok->execute();
        $del_tok->close();

        unset($_SESSION['fp_user_id'], $_SESSION['fp_email'], $_SESSION['fp_verified']);
        sendResponse(true, 'Password updated successfully! You can now log in.');
    } else {
        $stmt->close();
        sendResponse(false, 'Failed to update password. Please try again.');
    }
}

sendResponse(false, 'Invalid action request.');