<?php
session_start();
header('Content-Type: application/json');

require_once "connection/config.php";
require_once "helpers/SystemOperators.php";

$con = connection();
$so  = new SystemOperators();
$action = $_POST['action'] ?? '';

// INITIAL REGISTRATION & SEND OTP
if ($action === 'submit_registration') {
    $first_name   = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $last_name    = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $middle_name  = trim(filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $suffix       = trim(filter_input(INPUT_POST, 'suffix', FILTER_SANITIZE_SPECIAL_CHARS));
    
    $full_name_raw = trim(preg_replace('/\s+/', ' ', "$first_name $middle_name $last_name $suffix"));
    $full_name     = $so->encrypt($full_name_raw);
    
    $student_id_raw = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS);
    $email_raw      = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $student_id   = $so->encrypt($student_id_raw);
    $email        = $so->encrypt($email_raw);
    $department   = $so->encrypt(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS));
    $raw_password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $confirm_pwd  = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $raw_birthday = trim(filter_input(INPUT_POST, 'birthday', FILTER_SANITIZE_SPECIAL_CHARS));
    $birthday_formatted = '';
    if (!empty($raw_birthday)) {
        $date_obj = DateTime::createFromFormat('F j, Y', $raw_birthday) ?: DateTime::createFromFormat('Y-m-d', $raw_birthday);
        if ($date_obj) $birthday_formatted = $date_obj->format('m-d-Y');
    }
    $birthday_enc = !empty($birthday_formatted) ? $so->encrypt($birthday_formatted) : null;

    if ($raw_password !== $confirm_pwd) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    }

    // Check duplicate student ID or Email
    $check_stmt = $con->prepare("SELECT User_ID FROM user_table WHERE Student_ID = ? OR Email = ?");
    $check_stmt->bind_param("ss", $student_id, $email);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student ID or Email is already registered.']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();

    // Insert user with Account_Status = 'Pending'
    $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);
    $insert_stmt = $con->prepare("INSERT INTO user_table (Full_Name, Student_ID, Email, Password, Role, Department, Birthday, Account_Status) VALUES (?, ?, ?, ?, 'Student', ?, ?, 'Pending')");
    $insert_stmt->bind_param("ssssss", $full_name, $student_id, $email, $hashed_password, $department, $birthday_enc);

    if ($insert_stmt->execute()) {
        $user_id = $con->insert_id;
        $insert_stmt->close();

        // Save session state
        $_SESSION['pending_user_id'] = $user_id;
        $_SESSION['pending_email']   = $email_raw;

        // Generate OTP and insert expiration directly via MySQL DATE_ADD
        $otp_code = $so->generateOTPCode();

        $otp_stmt = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
        $otp_stmt->bind_param("is", $user_id, $otp_code);
        $otp_stmt->execute();
        $otp_stmt->close();

        // Query the exact expiration timestamp that MySQL set
        $time_stmt = $con->prepare("SELECT UNIX_TIMESTAMP(Expires_At) AS expires_at FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
        $time_stmt->bind_param("i", $user_id);
        $time_stmt->execute();
        $expires_at_unix = $time_stmt->get_result()->fetch_assoc()['expires_at'] ?? null;
        $time_stmt->close();

        $expires_at_ms = $expires_at_unix ? ($expires_at_unix * 1000) : null;

        // Send Email via SystemOperators / PHPMailer
        if ($so->sendEmail($email_raw, $otp_code)) {
            echo json_encode([
                'success'    => true, 
                'message'    => 'OTP sent to your email address.',
                'expires_at' => $expires_at_ms
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration saved, but failing to send verification email. Please try resending.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account. Please try again.']);
    }
    exit();
}

// VERIFY OTP CODE
if ($action === 'verify_otp') {
    $user_id     = $_SESSION['pending_user_id'] ?? null;
    $entered_otp = trim($_POST['otp_code'] ?? '');

    if (!$user_id || empty($entered_otp)) {
        echo json_encode(['success' => false, 'message' => 'Session expired or invalid submission.']);
        exit();
    }

    $stmt = $con->prepare("SELECT Verification_ID FROM email_verifications WHERE User_ID = ? AND OTP_Code = ? AND Is_Used = 0 AND Expires_At > NOW() ORDER BY Verification_ID DESC LIMIT 1");
    $stmt->bind_param("is", $user_id, $entered_otp);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $row    = $res->fetch_assoc();
        $ver_id = $row['Verification_ID'];
        $stmt->close();

        // Mark OTP used
        $u1 = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?");
        $u1->bind_param("i", $ver_id);
        $u1->execute();
        $u1->close();

        // Activate User Account
        $u2 = $con->prepare("UPDATE user_table SET Account_Status = 'Active' WHERE User_ID = ?");
        $u2->bind_param("i", $user_id);
        $u2->execute();
        $u2->close();

        unset($_SESSION['pending_user_id'], $_SESSION['pending_email']);
        echo json_encode(['success' => true, 'message' => 'Account successfully verified! Redirecting to login...']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP code.']);
    }
    exit();
}

// RESEND OTP
if ($action === 'resend_otp') {
    $user_id   = $_SESSION['pending_user_id'] ?? null;
    $email_raw = $_SESSION['pending_email'] ?? null;

    if (!$user_id || !$email_raw) {
        echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh the page and try registering again.']);
        exit();
    }

    $otp_code = $so->generateOTPCode();

    $otp_stmt = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
    $otp_stmt->bind_param("is", $user_id, $otp_code);
    $otp_stmt->execute();
    $otp_stmt->close();

    // Query the exact expiration timestamp that MySQL set
    $time_stmt = $con->prepare("SELECT UNIX_TIMESTAMP(Expires_At) AS expires_at FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
    $time_stmt->bind_param("i", $user_id);
    $time_stmt->execute();
    $expires_at_unix = $time_stmt->get_result()->fetch_assoc()['expires_at'] ?? null;
    $time_stmt->close();

    $expires_at_ms = $expires_at_unix ? ($expires_at_unix * 1000) : null;

    if ($so->sendEmail($email_raw, $otp_code)) {
        echo json_encode([
            'success'    => true, 
            'message'    => 'A new code has been sent to your email.',
            'expires_at' => $expires_at_ms
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to resend code. Please try again.']);
    }
    exit();
}