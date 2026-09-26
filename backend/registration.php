<?php
require_once("connection/config.php");
require_once("helpers/SystemOperators.php");

$con = connection();$so = new SystemOperators();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegister'])) {
    $student_id = $so->encrypt(filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = $so->encrypt(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $department = $so->encrypt(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS));
    $raw_password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

    $role = 'Student';$select_query = "SELECT * FROM `user_table` WHERE `Student_ID` = ? OR `Email` = ?";
    $select_stmt =$con->prepare($select_query);$select_stmt->bind_param('ss', $student_id,$email);

    if ($select_stmt->execute()) {
        $result =$select_stmt->get_result();
        if ($result->num_rows > 0) {
            echo "<script> alert('User already exists');</script>";
        } else {
            $insert_query = 'INSERT INTO `user_table` (`Student_ID`, `Email`, `Password`, `Role`, `Department`) VALUES (?, ?, ?, ?, ?)';
            $insert_stmt =$con->prepare($insert_query);$insert_stmt->bind_param('sssss', $student_id,$email, $hashed_password,$role, $department);
            
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

    <link rel="stylesheet" href="../frontend/css/Registration.css">
</head>

<body>

    <div class="registration-container">
    <form action = "" method = "POST" id="registrationForm">

        <!-- PAGE 1 -->
        <div class="page active">

            <h1>REGISTRATION</h1>

            <div class="form-container">

                <div>

                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" placeholder="Fernando" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" placeholder="Bautista" required>
                    </div>

                    <div class="form-group">
                        <label>Middle Name (Optional)</label>
                        <input type="text" name="middle_name" placeholder="Reyes">
                    </div>

                    <div class="form-group">
                        <label>Suffix (Optional)</label>
                        <input type="text" name="suffix" placeholder="Jr. / Sr. / III">
                    </div>

                </div>


                <div>

                    <div class="form-group">
                        <label>Birthday (mm/dd/yy)</label>
                        <input type="text" name="birthday" placeholder="01/01/2026" required>
                    </div>

                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" name="student_id" placeholder="R2XXXXXXXXX" required>
                    </div>

                    <div class="form-group">
                        <label>School Email</label>
                        <input type="email" name="email" placeholder="test.school.email@gmail.com" required>
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" placeholder="BSIT" required>
                    </div>

                </div>

            </div>


            <div class="button-container">

                <button type="button" onclick="nextPage()">NEXT</button>

            </div>

        </div>


        <!-- PAGE 2 -->
        <div class="page">

            <h1>REGISTRATION</h1>

            <div class="form-container">

                <div>

                    <div class="form-group">
                        <label>Personal Email</label>
                        <input type="email" name="personal_email" placeholder="test.email@gmail.com" required>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone_number" placeholder="09XXXXXXXXX" required>
                    </div>

                    <div class="form-group">
                        <label>House / Unit Number</label>
                        <input type="text" name="house_number" placeholder="01" required>
                    </div>

                    <div class="form-group">
                        <label>Street</label>
                        <input type="text" name="street" placeholder="Street" required>
                    </div>

                </div>


                <div>

                    <div class="form-group">
                        <label>Barangay</label>
                        <input type="text" name="barangay" placeholder="Brgy" required>
                    </div>

                    <div class="form-group">
                        <label>City / Town</label>
                        <input type="text" name="city" placeholder="Marikina City" required>
                    </div>

                </div>

            </div>


            <div class="button-container">

                <button
                    type="button" class="back-button"
                    onclick="previousPage()"> PREVIOUS
                </button>

                <button
                    type="button" onclick="nextPage()"> NEXT
                </button>

            </div>

        </div>


        <!-- PAGE 3 -->
        <div class="page">

            <h1>REGISTRATION</h1>

            <div class="security-form">

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="XXXXXXXX" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input
                        type="password" name="confirm_password" id="confirmPassword" placeholder="XXXXXXXX" required>
                </div>

                <div class="form-group">
                    <label>OTP</label>
                    <input
                        type="text" name="otp" id="otp" placeholder="XXXXXX" required>
                </div>

            </div>


            <div class="button-container">

                <button type="button" 
                    class="back-button" onclick="previousPage()">BACK 
                </button>
                    
                <button type="submit" name="btnRegister"
                    onclick="register(event)">REGISTER
                </button>

            </div>

        </div>

    </form>

    </div>


    <script src="../frontend/js/Registration.js"></script>

</body>

</html>
