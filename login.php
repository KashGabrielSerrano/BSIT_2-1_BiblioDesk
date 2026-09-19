<?php
session_start();

include("./connection/config.php");
include("./helpers/SystemOperators.php");

$con = connection();$so = new SystemOperators();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnLogin']) && !$is_logged_in) {
    $raw_email = trim($_POST['email'] ?? '');
    $passInput =$_POST['password'] ?? '';

    if (empty($raw_email) || empty($passInput)) {$error = "Please fill in all security fields.";
    } else {
        $encrypted_email = $so->encrypt($raw_email);

        $stmt =$con->prepare("SELECT `User_ID`, `Student_ID`, `Email`, `Password`, `Role` FROM `user_table` WHERE `Email` = ?");
        $stmt->bind_param('s',$encrypted_email);

        if ($stmt->execute()) {
            $result =$stmt->get_result();
            if ($row =$result->fetch_assoc()) {
                if (password_verify($passInput,$row['Password'])) {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] =$row['User_ID'];
                    $_SESSION['student_id'] =$so->decrypt($row['Student_ID']);$_SESSION['email'] = $so->decrypt($row['Email']);
                    $_SESSION['role'] =$row['Role'];

                    header("Location: login.php");
                    exit;
                } else {
                    $error = "The system credentials provided do not match our database records.";
                }
            } else {
                $error = "The system credentials provided do not match our database records.";
            }
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
    <title>Learnify LMS - Student Portal Login</title>
</head>
<body>

    <?php if ($is_logged_in): ?>
        <div class="dashboard">
            <h2>Welcome Back!</h2>
            <p>Student ID: <strong><?= htmlspecialchars($_SESSION['student_id'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p>Email: <strong><?= htmlspecialchars($_SESSION['email'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p>Role: <strong><?= htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <a href="login.php?action=logout">Sign Out of Terminal</a>
        </div>
    <?php else: ?>
        <?php if (!empty($error)): ?>
            <div class="alert" style="color: red; margin-bottom: 15px;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-fields">
                <div class="form-items">
                    <label for="email">Enter School Email:</label>
                    <input type="email" id="email" name="email" required placeholder="R20XXXXXXXX@feuroosevelt.edu.ph">
                </div>

                <div class="form-items">
                    <label for="password">Enter Password:</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="••••••••">
                </div>
                <button type="submit" name="btnLogin">Login</button>
            </div>
        </form>
    <?php endif; ?>

</body>
</html>