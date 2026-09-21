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
    <title>Registration</title>
    <link rel="stylesheet" href="../frontend/css/registration.css">
</head>

<body>

    <div class="registration-container">

        
        <div class="page active">

            <h1>REGISTRATION</h1>

            <div class="form-container">

                <div>
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" placeholder="Fernando">
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" placeholder="Bautista">
                    </div>

                    <div class="form-group">
                        <label>Middle Name (Optional)</label>
                        <input type="text" placeholder="Reyes">
                    </div>

                    <div class="form-group">
                        <label>Suffix (Optional)</label>
                        <input type="text" placeholder="Jr. / Sr. / III">
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>Birthday (mm/dd/yy)</label>
                        <input type="text" placeholder="01/01/01">
                    </div>

                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" placeholder="R2XXXXXXXXX">
                    </div>

                    <div class="form-group">
                        <label>School Email</label>
                        <input type="email" placeholder="test.school.email@gmail.com">
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" placeholder="BSIT">
                    </div>
                </div>

            </div>

            <div class="button-container">
                <button onclick="nextPage()">NEXT</button>
            </div>

        </div>


        
        <div class="page">

            <h1>REGISTRATION</h1>

            <div class="form-container">

                <div>
                    <div class="form-group">
                        <label>Personal email</label>
                        <input type="email" placeholder="test.email@gmail.com">
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" placeholder="09XXXXXXXXX">
                    </div>

                    <div class="form-group">
                        <label>House / Unit Number</label>
                        <input type="text" placeholder="01">
                    </div>

                    <div class="form-group">
                        <label>Street</label>
                        <input type="text" placeholder="Street">
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>Barangay</label>
                        <input type="text" placeholder="Brgy">
                    </div>

                    <div class="form-group">
                        <label>City / Town</label>
                        <input type="text" placeholder="Marikina City">
                    </div>
                </div>

            </div>

            <div class="button-container">
                <button class="back-button" onclick="previousPage()">Previous</button>
                <button onclick="nextPage()">NEXT</button>
            </div>

        </div>


      
        <div class="page">

            <h1>REGISTRATION</h1>

            <div class="security-form">

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" placeholder="XXXXXXXX">
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" placeholder="XXXXXXXX">
                </div>

                <div class="form-group">
                    <label>OTP</label>
                    <input type="text" placeholder="XXXXXX">
                </div>

            </div>

            <div class="button-container">
                <button class="back-button" onclick="previousPage()">BACK</button>
                <button onclick="register()">REGISTER</button>
            </div>

        </div>

    </div>

    <script src="../frontend/js/Registration.js"></script>

</body>
</html>