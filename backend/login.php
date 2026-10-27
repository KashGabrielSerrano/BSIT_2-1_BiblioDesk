<?php
session_start();

require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/SystemOperators.php";


$con = $conn;
$so = new SystemOperators();


$max_attempts = 5;       
$lockout_minutes = 15;   
$user_ip = $_SERVER['REMOTE_ADDR'];


$time_threshold = date("Y-m-d H:i:s", strtotime("-$lockout_minutes minutes"));
$stmt = $con->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
$stmt->bind_param("ss", $user_ip, $time_threshold);
$stmt->execute();
$stmt->bind_result($failed_count);
$stmt->fetch();
$stmt->close();


if (isset($_GET['action']) && $_GET['action'] === 'logout') {$_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: login.php");
    exit;
}

$is_logged_in = isset($_SESSION['user_id']);$error = '';

if ($is_logged_in) {
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'Librarian' || $_SESSION['role'] === 'Admin')) {
        header("Location: admindb.php");
    } else {
        header("Location: studentdb.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnLogin']) && !$is_logged_in) {
    $raw_email = trim($_POST['email'] ?? '');
    $passInput =$_POST['password'] ?? '';

    if (empty($raw_email) || empty($passInput)) {$error = "Please fill in all security fields.";
    } else {
        $encrypted_email = $so->encrypt($raw_email);

        $stmt =$con->prepare("SELECT `User_ID`, `Student_ID`, `Email`, `Password`, `Role`, `Account_Status` FROM `user_table` WHERE `Email` = ?");
        $stmt->bind_param('s',$encrypted_email);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            if ($row && password_verify($passInput, $row['Password'])) {
                $status = $row['Account_Status'] ?? 'Active';

                if ($status === 'Pending') {
                    $error = "Your account is pending Admin approval. Please wait for activation.";
                } elseif ($status === 'Suspended') {
                    $error = "Your account has been suspended. Please contact the Library Administrator.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['User_ID'];
                    $_SESSION['email'] = $so->decrypt($row['Email']);
                    $_SESSION['role'] = $row['Role'];

                    if (!empty($row['Student_ID'])) {
                        $_SESSION['student_id'] = $so->decrypt($row['Student_ID']);
                    } else {
                        $_SESSION['student_id'] = null;
                    }

                    if ($row['Role'] === 'Librarian' || $row['Role'] === 'Admin') {
                        header("Location: admindb.php");
                    } else {
                        header("Location: studentdb.php");
                    }
                    exit;
                }
            } else {
                $error = "The system credentials provided do not match our database records.";
            }
            $stmt->close();
        } else {
            $error = "Failed to execute authentication request.";
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