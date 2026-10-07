<?php
session_start();

require_once("connection/config.php");
require_once("helpers/SystemOperators.php");

$con = connection();
$so = new SystemOperators();

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
    <link rel="stylesheet" href="../frontend/css/login.css">
</head>
<body>


<div class="login-container">
    <div class="logo-panel">
        <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
    </div>

    <div class="form-panel">
        <div class="form-wrapper">

        <?php if (!empty($error)): ?>
            <div class="alert" style="color: red; margin-bottom: 15px;"> 
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="form-fields">
                        <h1>Welcome to BiblioDesk LMS</h1>
                        <div class="form-items">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required placeholder="R20XXXXXXXX@feuroosevelt.edu.ph">
                        </div>

                        <div class="form-items">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required minlength="6" placeholder="••••••••">
                        </div>
                        <button type="submit" name="btnLogin">Login</button>

                        <hr>
                        <p class="register">Don't have an account? <a href="registration.php">REGISTER NOW</a></p>
                        <p class="terms">By proceeding, you agree to our Terms of Use and confirm that you have read our Privacy Policy.</p>
                    </div>
                </form>
        </div>
    </div>
</div>

</body>
</html>