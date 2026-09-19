<?php
include("./connection/config.php");
include("./helpers/SystemOperators.php");

$con = connection();$so = new SystemOperators();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegister'])) {$student_id = $so->encrypt(filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS));$email = $so->encrypt(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));$raw_password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

    $role = 'Student';$select_query = "SELECT * FROM `user_table` WHERE `Student_ID` = ? OR `Email` = ?";
    $select_stmt =$con->prepare($select_query);$select_stmt->bind_param('ss', $student_id,$email);

    if ($select_stmt->execute()) {
        $result =$select_stmt->get_result();
        if ($result->num_rows > 0) {
            echo "<script> alert('User already exists');</script>";
        } else {
            $insert_query = 'INSERT INTO `user_table` (`Student_ID`, `Email`, `Password`, `Role`) VALUES (?, ?, ?, ?)';
            $insert_stmt =$con->prepare($insert_query);$insert_stmt->bind_param('ssss', $student_id,$email, $hashed_password,$role);
            
            try {
                $insert_stmt->execute();
                echo "<script> alert('Registration successful!');
                            window.location='login.php';
                        </script>";
            } catch (mysqli_sql_exception $e) {
                echo $e->getMessage();
            }
        }
    } else {
        echo "Failed to execute query.";
        $select_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Account Registration</title>
</head>
<body>
    <form action="" method="post">
        <div class="form-fields">
            <div class="form-items">
                <label for="student_id">Enter FEUR Student ID:</label>
                <input type="text" name="student_id" required placeholder="R20XXXXXXXX">
            </div>

            <div class="form-items">
                <label for="email">Enter School Email Address:</label>
                <input type="email" name="email" required placeholder="R20XXXXXXXX@feuroosevelt.edu.ph">
            </div>

            <div class="form-items">
                <label for="password">Enter Password:</label>
                <input type="password" name="password" required minlength="6" placeholder="At least 6 characters">
            </div>
            <button type="submit" name="btnRegister">Register</button>
        </div>
    </form>
</body>
</html>