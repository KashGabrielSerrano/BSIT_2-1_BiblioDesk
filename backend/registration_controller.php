<?php
require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/SystemOperators.php";

header('Content-Type: application/json');

$con = connection();
$so  = new SystemOperators();
$user_ip = $so->getClientIP();

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

$action = $_POST['action'] ?? '';


// 1. REGISTRATION
if ($action === 'submit_registration') {
    session_regenerate_id(true);

    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $suffix      = trim($_POST['suffix'] ?? '');
    
    $full_name_raw = trim(preg_replace('/\s+/', ' ', "$first_name $middle_name $last_name $suffix"));
    $full_name     = $so->encrypt($full_name_raw);
    
    $student_id_raw = trim($_POST['student_id'] ?? '');
    $email_raw      = strtolower(trim($_POST['email'] ?? ''));
    $department_raw = trim($_POST['department'] ?? '');

    if (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
        exit();
    }

    $allowed_departments = ['BSIT', 'BSCS', 'BSIS', 'BSBA', 'BSA'];
    if (!in_array($department_raw, $allowed_departments, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid department selected.']);
        exit();
    }

    $student_id = $so->encrypt($student_id_raw);
    $email      = $so->encrypt($email_raw);
    $department = $so->encrypt($department_raw);
    
    $raw_password = $_POST['password'] ?? '';
    $confirm_pwd  = $_POST['confirm_password'] ?? '';
    
    if (strlen($raw_password) > 64) {
        echo json_encode(['success' => false, 'message' => 'Password must not exceed 64 characters.']);
        exit();
    }

    if (strlen($raw_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        exit();
    }

    if ($raw_password !== $confirm_pwd) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    }

    $raw_birthday = trim($_POST['birthday'] ?? '');
    $birthday_formatted = '';
    if (!empty($raw_birthday)) {
        $date_obj = DateTime::createFromFormat('F j, Y', $raw_birthday) ?: DateTime::createFromFormat('Y-m-d', $raw_birthday);
        if ($date_obj) $birthday_formatted = $date_obj->format('m-d-Y');
    }
    $birthday_enc = !empty($birthday_formatted) ? $so->encrypt($birthday_formatted) : null;
    $check_stmt = $con->prepare("SELECT User_ID, Account_Status FROM user_table WHERE Student_ID = ? OR Email = ?");
    $check_stmt->bind_param("ss", $student_id, $email);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();

    $pending_user_id = null;

    while ($row = $check_res->fetch_assoc()) {
        if ($row['Account_Status'] === 'Pending') {
            $pending_user_id = $row['User_ID'];
        } else {
            echo json_encode(['success' => false, 'message' => 'An account with this Student ID or Email already exists.']);
            $check_stmt->close();
            exit();
        }
    }
    $check_stmt->close();

    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    // Reuse existing User_ID if re-registering as Pending, otherwise INSERT
    if ($pending_user_id !== null) {
        $user_id = $pending_user_id;
        $stmt = $con->prepare("UPDATE user_table SET Full_Name = ?, Password = ?, Department = ?, Birthday = ? WHERE User_ID = ? AND Account_Status = 'Pending'");
        $stmt->bind_param("ssssi", $full_name, $hashed_password, $department, $birthday_enc, $user_id);
    } else {
        $stmt = $con->prepare("INSERT INTO user_table (Full_Name, Student_ID, Email, Password, Role, Department, Birthday, Account_Status) VALUES (?, ?, ?, ?, 'Student', ?, ?, 'Pending')");
        $stmt->bind_param("ssssss", $full_name, $student_id, $email, $hashed_password, $department, $birthday_enc);
    }

    if ($stmt->execute()) {
        if ($pending_user_id === null) {
            $user_id = $con->insert_id;
        }
        $stmt->close();

        $_SESSION['pending_user_id'] = $user_id;
        $_SESSION['pending_email']   = $email_raw;
        $_SESSION['last_otp_resend'] = time();

        // Invalidate old active OTP codes for this user
        $inval_stmt = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE User_ID = ? AND Is_Used = 0");
        $inval_stmt->bind_param("i", $user_id);
        $inval_stmt->execute();
        $inval_stmt->close();

        // Create new OTP
        $otp_code   = $so->generateOTPCode();
        $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
        $otp_stmt   = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used, Attempts) VALUES (?, ?, ?, 0, 0)");
        $otp_stmt->bind_param("iss", $user_id, $otp_code, $expires_at);
        $otp_stmt->execute();
        $otp_stmt->close();

        $time_stmt = $con->prepare("SELECT UNIX_TIMESTAMP(Expires_At) AS expires_at FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1");
        $time_stmt->bind_param("i", $user_id);
        $time_stmt->execute();
        $expires_at_unix = $time_stmt->get_result()->fetch_assoc()['expires_at'] ?? null;
        $time_stmt->close();

        $expires_at_ms = $expires_at_unix ? ($expires_at_unix * 1000) : null;

        if ($so->sendEmail($email_raw, $otp_code)) {
            echo json_encode([
                'success'    => true, 
                'message'    => 'OTP sent to your email address.',
                'expires_at' => $expires_at_ms
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration saved, but email failed to send. Please try resending.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account.']);
    }
    exit();
}


// 2. VERIFY OTP CODE (RATE LIMITING THROUGH THE DATABASE)
if ($action === 'verify_otp') {
    $user_id     = $_SESSION['pending_user_id'] ?? null;
    $entered_otp = trim($_POST['otp_code'] ?? '');

    if (!$user_id || empty($entered_otp)) {
        echo json_encode(['success' => false, 'message' => 'Session expired or invalid request.']);
        exit();
    }

    // Fetch active OTP row
    $stmt = $con->prepare("SELECT Verification_ID, OTP_Code, Attempts FROM email_verifications WHERE User_ID = ? AND Is_Used = 0 AND Expires_At > NOW() ORDER BY Verification_ID DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $row        = $res->fetch_assoc();
        $ver_id     = $row['Verification_ID'];
        $db_otp     = $row['OTP_Code'];
        $attempts   = $row['Attempts'] + 1; // Increment attempt count
        $stmt->close();

        // Update attempts in the database
        $upd_att = $con->prepare("UPDATE email_verifications SET Attempts = ? WHERE Verification_ID = ?");
        $upd_att->bind_param("ii", $attempts, $ver_id);
        $upd_att->execute();
        $upd_att->close();

        // Check if attempts exceed limit
        if ($attempts >= 5) {
            // Invalidate the OTP in DB
            $inval = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?");
            $inval->bind_param("i", $ver_id);
            $inval->execute();
            $inval->close();

            unset($_SESSION['pending_user_id'], $_SESSION['pending_email']);
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. OTP has been invalidated. Please request a new code.']);
            exit();
        }

        // Verify if entered OTP matches the one in the database
        if ($entered_otp === $db_otp) {
            $u1 = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?");
            $u1->bind_param("i", $ver_id);
            $u1->execute();
            $u1->close();

            $u2 = $con->prepare("UPDATE user_table SET Account_Status = 'Active' WHERE User_ID = ?");
            $u2->bind_param("i", $user_id);
            $u2->execute();
            $u2->close();

            unset($_SESSION['pending_user_id'], $_SESSION['pending_email']);
            echo json_encode(['success' => true, 'message' => 'Account successfully verified! Redirecting to login...']);
        } else {
            $remaining = 5 - $attempts;
            echo json_encode(['success' => false, 'message' => "Invalid OTP code. {$remaining} attempt(s) remaining."]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'OTP has expired or is invalid. Please resend a new code.']);
    }
    exit();
}

// 3. RESEND OTP (Server Side Cooldown to prevent spam)
if ($action === 'resend_otp') {
    $user_id   = $_SESSION['pending_user_id'] ?? null;
    $email_raw = $_SESSION['pending_email'] ?? strtolower(trim($_POST['email'] ?? ''));

    // Fallback: If session expired, look up the Pending user record via email
    if (!$user_id && !empty($email_raw)) {
        $email_enc = $so->encrypt($email_raw);
        $find_stmt = $con->prepare("SELECT User_ID FROM user_table WHERE Email = ? AND Account_Status = 'Pending' ORDER BY User_ID DESC LIMIT 1");
        $find_stmt->bind_param("s", $email_enc);
        $find_stmt->execute();
        $find_res  = $find_stmt->get_result();

        if ($find_res && $find_res->num_rows === 1) {
            $user_id = $find_res->fetch_assoc()['User_ID'];
            // Restore session variables cleanly
            $_SESSION['pending_user_id'] = $user_id;
            $_SESSION['pending_email']   = $email_raw;
        }
        $find_stmt->close();
    }

    if (!$user_id || !$email_raw) {
        echo json_encode(['success' => false, 'message' => 'Registration session expired. Please submit the form again.']);
        exit();
    }

    // Cooldown Enforcement: 60 seconds server-side check
    $last_resend = $_SESSION['last_otp_resend'] ?? 0;
    if ((time() - $last_resend) < 60) {
        $time_left = 60 - (time() - $last_resend);
        echo json_encode(['success' => false, 'message' => "Please wait {$time_left} second(s) before requesting a new code."]);
        exit();
    }

    $_SESSION['last_otp_resend'] = time();

    // Invalidate old OTPs for this user
    $inval_stmt = $con->prepare("UPDATE email_verifications SET Is_Used = 1 WHERE User_ID = ? AND Is_Used = 0");
    $inval_stmt->bind_param("i", $user_id);
    $inval_stmt->execute();
    $inval_stmt->close();

    // Generate & store new OTP
    $otp_code   = $so->generateOTPCode();
    $expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));
    $otp_stmt   = $con->prepare("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used, Attempts) VALUES (?, ?, ?, 0, 0)");
    $otp_stmt->bind_param("iss", $user_id, $otp_code, $expires_at);
    $otp_stmt->execute();
    $otp_stmt->close();

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