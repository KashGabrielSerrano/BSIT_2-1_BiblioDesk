<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/auth.php';   // OTP issue / verify, password rules, limits
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$con     = db();
$so      = so();
$user_ip = client_ip();
require_csrf();

if (random_int(1, 100) === 1) {
    $con->query("DELETE FROM login_attempts WHERE attempt_time < UTC_TIMESTAMP() - INTERVAL 1 DAY");
}

$action = gv($_POST, 'action');

switch ($action) {

    /* ============================================================================ */
    /* LOGIN                                                                        */
    /* ============================================================================ */
    case 'login':
        $raw_email = strtolower(trim($_POST['email'] ?? ''));
        $password  = $_POST['password'] ?? '';
        if ($raw_email === '' || $password === '') {
            sendResponse(false, 'Please fill in all security fields.');
        }
        $enc_email = $so->encrypt($raw_email);

        $since = gmdate('Y-m-d H:i:s', time() - LOGIN_LOCK_MINUTES * 60);
        $fails = db_one("SELECT COUNT(*) AS c, MAX(attempt_time) AS last FROM login_attempts
                          WHERE (ip_address = ? OR (email_attempted = ? AND email_attempted IS NOT NULL)) AND attempt_time > ?",
                        'sss', [$user_ip, $enc_email, $since]);
        if ((int)$fails['c'] >= LOGIN_MAX_FAILS) {
            $left = max(1, LOGIN_LOCK_MINUTES * 60 - (time() - strtotime($fails['last'] . ' UTC')));
            sendResponse(false, 'Too many failed login attempts. Please try again in ' . (int)ceil($left / 60) . ' minute(s).');
        }

        $user = db_one("SELECT User_ID, Student_ID, Email, Password, Role, Account_Status FROM user_table WHERE Email = ?", 's', [$enc_email]);
        if (!$user || !password_verify($password, $user['Password'])) {
            record_failed_attempt($user_ip, $enc_email);
            sendResponse(false, 'Incorrect Email or Password. Please try again.');
        }
        if ($user['Account_Status'] === 'Suspended') {
            sendResponse(false, 'Your account has been suspended. Please contact the Library Administrator.');
        }
        if ($user['Account_Status'] === 'Pending') {
            // not verified yet: send an activation code and let login.js show the OTP modal
            $uid = (int)$user['User_ID'];
            if (otp_rate_limited($uid, $user_ip)) {
                sendResponse(false, 'Too many verification codes were requested. Please wait 15 minutes and try again.');
            }
            $_SESSION['pending_user_id'] = $uid;
            $_SESSION['pending_email']   = $raw_email;
            $_SESSION['last_otp_resend'] = time();
            $otp = issue_otp($uid, $raw_email, 'registration', $user_ip);
            sendResponse(true, 'Account unverified. An activation code has been sent to your email.',
                         ['requires_otp' => true, 'email' => $raw_email, 'expires_at_ms' => $otp['expires_ms']]);
        }

        start_user_session($user);
        if (isset($_POST['remember'])) {
            issue_remember_token((int)$user['User_ID']);
        }
        db_exec("DELETE FROM login_attempts WHERE ip_address = ? OR email_attempted = ?", 'ss', [$user_ip, $enc_email]);
        sendResponse(true, 'Login successful!', ['redirect' => after_login_redirect($user['Role'])]);

    /* ============================================================================ */
    /* REGISTRATION                                                                 */
    /* ============================================================================ */
    case 'submit_registration':
        $first  = trim($_POST['first_name'] ?? '');
        $last   = trim($_POST['last_name'] ?? '');
        $full   = trim(preg_replace('/\s+/', ' ', $first . ' ' . trim($_POST['middle_name'] ?? '') . ' ' . $last . ' ' . trim($_POST['suffix'] ?? '')));
        $sid    = trim($_POST['student_id'] ?? '');
        $email  = strtolower(trim($_POST['email'] ?? ''));
        $dept   = trim($_POST['department'] ?? '');
        $pw     = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendResponse(false, 'Invalid email address format.');
        if ($first === '' || $last === '')               sendResponse(false, 'Please enter your first and last name.');
        if ($sid === '' || mb_strlen($sid) > 30)         sendResponse(false, 'Please enter your student ID.');
        if (!in_array($dept, DEPARTMENTS, true))         sendResponse(false, 'Invalid department selected.');
        if ($p = password_problem($pw, $_POST['confirm_password'] ?? '')) sendResponse(false, $p);

        $wait = otp_wait('last_otp_resend');
        if ($wait > 0 && !empty($_SESSION['pending_user_id'])) {
            sendResponse(false, "Please wait {$wait} second(s) before requesting a new code.");
        }

        $bday = '';
        $raw_bday = trim($_POST['birthday'] ?? '');
        if ($raw_bday !== '' && ($d = DateTime::createFromFormat('F j, Y', $raw_bday) ?: DateTime::createFromFormat('Y-m-d', $raw_bday))) {
            $bday = $d->format('m-d-Y');
        }

        $enc = fn($v) => $so->encrypt($v);
        $enc_sid = $enc($sid);
        $enc_email = $enc($email);

        // an existing Pending account (unfinished registration) may register again; anything else is a duplicate
        $pending_id = null;
        foreach (db_all($con, "SELECT User_ID, Account_Status FROM user_table WHERE Student_ID = ? OR Email = ?", 'ss', [$enc_sid, $enc_email]) as $row) {
            if ($row['Account_Status'] !== 'Pending') {
                sendResponse(false, 'An account with this Student ID or Email already exists.');
            }
            $pending_id = (int)$row['User_ID'];
        }

        session_regenerate_id(true);
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $bday_enc = $bday !== '' ? $enc($bday) : null;
        if ($pending_id !== null) {
            $uid = $pending_id;
            db_exec("UPDATE user_table SET Full_Name = ?, Password = ?, Department = ?, Birthday = ? WHERE User_ID = ? AND Account_Status = 'Pending'",
                    'ssssi', [$enc($full), $hash, $enc($dept), $bday_enc, $uid]);
        } else {
            db_exec("INSERT INTO user_table (Full_Name, Student_ID, Email, Password, Role, Department, Birthday, Account_Status)
                     VALUES (?, ?, ?, ?, 'Student', ?, ?, 'Pending')", 'ssssss',
                    [$enc($full), $enc_sid, $enc_email, $hash, $enc($dept), $bday_enc]);
            $uid = (int)$con->insert_id;
        }
        if (otp_rate_limited($uid, $user_ip)) {
            sendResponse(false, 'Too many verification codes were requested. Please wait 15 minutes and try again.');
        }

        $_SESSION['pending_user_id'] = $uid;
        $_SESSION['pending_email']   = $email;
        $_SESSION['last_otp_resend'] = time();
        $otp = issue_otp($uid, $email, 'registration', $user_ip);
        $otp['sent']
            ? sendResponse(true, 'OTP sent to your email address.', ['expires_at' => $otp['expires_ms']])
            : sendResponse(false, 'Registration saved, but email failed to send. Please try resending.');

    case 'verify_otp':
        $uid = (int)($_SESSION['pending_user_id'] ?? 0);
        $code = trim($_POST['otp_code'] ?? '');
        if (!$uid || $code === '') {
            sendResponse(false, 'Session expired or invalid request.');
        }
        $check = verify_otp($uid, $code);
        if (!$check['ok']) {
            if ($check['locked']) {
                unset($_SESSION['pending_user_id'], $_SESSION['pending_email']);
            }
            sendResponse(false, $check['message']);
        }
        db_exec("UPDATE user_table SET Account_Status = 'Active' WHERE User_ID = ? AND Account_Status = 'Pending'", 'i', [$uid]);
        unset($_SESSION['pending_user_id'], $_SESSION['pending_email']);
        sendResponse(true, 'Account successfully verified! Redirecting to login...');

    case 'resend_otp':
        $uid   = (int)($_SESSION['pending_user_id'] ?? 0);
        $email = $_SESSION['pending_email'] ?? strtolower(trim($_POST['email'] ?? ''));
        if (!$uid && $email !== '') {
            // session expired: find the Pending account by its email
            $row = db_one("SELECT User_ID FROM user_table WHERE Email = ? AND Account_Status = 'Pending' ORDER BY User_ID DESC LIMIT 1",
                          's', [$so->encrypt($email)]);
            if ($row) {
                $uid = (int)$row['User_ID'];
                $_SESSION['pending_user_id'] = $uid;
                $_SESSION['pending_email']   = $email;
            }
        }
        if (!$uid || $email === '') {
            sendResponse(false, 'Registration session expired. Please submit the form again.');
        }
        if (($wait = otp_wait('last_otp_resend')) > 0) {
            sendResponse(false, "Please wait {$wait} second(s) before requesting a new code.");
        }
        if (otp_rate_limited($uid, $user_ip)) {
            sendResponse(false, 'Too many verification codes were requested. Please wait 15 minutes and try again.');
        }
        $_SESSION['last_otp_resend'] = time();
        $otp = issue_otp($uid, $email, 'registration', $user_ip);
        $otp['sent']
            ? sendResponse(true, 'A new code has been sent to your email.', ['expires_at' => $otp['expires_ms']])
            : sendResponse(false, 'Failed to resend code. Please try again.');

    /* ============================================================================ */
    /* FORGOT PASSWORD                                                              */
    /* ============================================================================ */
    case 'fp_send_otp':
    case 'fp_resend_otp':
        $resend = $action === 'fp_resend_otp';
        if ($resend) {
            $uid   = (int)($_SESSION['fp_user_id'] ?? 0);
            $email = $_SESSION['fp_email'] ?? '';
            if (!$uid || $email === '') {
                sendResponse(false, 'Session expired. Please restart the password reset process.');
            }
        } else {
            $email = strtolower(trim($_POST['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendResponse(false, 'Please enter a valid email address.');
            }
        }
        // cooldown per session, also for unknown emails (so it cannot be used to spam or probe)
        if (($wait = otp_wait('last_fp_otp')) > 0) {
            sendResponse(false, "Please wait {$wait} second(s) before requesting a new code.");
        }
        $_SESSION['last_fp_otp'] = time();

        $generic = 'If an account associated with this email address exists, we have sent a verification code.';
        if (!$resend) {
            $user = db_one("SELECT User_ID, Account_Status FROM user_table WHERE Email = ?", 's', [$so->encrypt($email)]);
            if (!$user || $user['Account_Status'] !== 'Active') {
                sendResponse(true, $generic);  
            }
            $uid = (int)$user['User_ID'];
        }
        if (otp_rate_limited($uid, $user_ip)) {
            sendResponse(false, 'Too many verification codes were requested. Please wait 15 minutes and try again.');
        }
        $_SESSION['fp_user_id']  = $uid;
        $_SESSION['fp_email']    = $email;
        $_SESSION['fp_verified'] = false;
        $otp = issue_otp($uid, $email, 'forgot_password', $user_ip);
        $otp['sent']
            ? sendResponse(true, $resend ? 'A new verification code has been sent to your email.' : $generic, ['expires_at' => $otp['expires_ms']])
            : sendResponse(false, 'Failed to send OTP email. Please try again.');

    case 'fp_verify_otp':
        $uid = (int)($_SESSION['fp_user_id'] ?? 0);
        if (!$uid || empty($_SESSION['fp_email'])) {
            sendResponse(false, 'Session expired. Please restart the request.');
        }
        $code = trim($_POST['otp_code'] ?? '');
        if (!preg_match('/^\d{6}$/', $code)) {
            sendResponse(false, 'Please enter a valid 6-digit verification code.');
        }
        $check = verify_otp($uid, $code);
        if (!$check['ok']) {
            sendResponse(false, $check['message']);
        }
        $_SESSION['fp_verified'] = true;
        sendResponse(true, 'Code verified successfully.');

    case 'fp_reset_password':
        $uid = (int)($_SESSION['fp_user_id'] ?? 0);
        if (!$uid || empty($_SESSION['fp_verified'])) {
            sendResponse(false, 'Unauthorized session. Please verify your email code first.');
        }
        if ($p = password_problem($_POST['new_password'] ?? '', $_POST['confirm_password'] ?? '')) {
            sendResponse(false, $p);
        }
        db_exec("UPDATE user_table SET Password = ? WHERE User_ID = ?", 'si', [password_hash($_POST['new_password'], PASSWORD_DEFAULT), $uid]);
        db_exec("DELETE FROM user_tokens WHERE user_id = ?", 'i', [$uid]);   // sign out "remember me" everywhere
        unset($_SESSION['fp_user_id'], $_SESSION['fp_email'], $_SESSION['fp_verified']);
        sendResponse(true, 'Password updated successfully! You can now log in.');

    default:
        sendResponse(false, 'Invalid action request.', [], 400); 
}
