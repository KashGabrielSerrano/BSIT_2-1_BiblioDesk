<?php
require_once("connection/config.php");
require_once("helpers/SystemOperators.php");

$con = connection();$so = new SystemOperators();

$modal_message  = '';
$modal_type     = 'error';$modal_redirect = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegister'])) {$first_name = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $last_name      = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $middle_name    = trim(filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $suffix         = trim(filter_input(INPUT_POST, 'suffix', FILTER_SANITIZE_SPECIAL_CHARS));
    $full_name_raw  = trim(preg_replace('/\s+/', ' ', "$first_name $middle_name $last_name $suffix"));
    $full_name      = $so->encrypt($full_name_raw);
    
    $student_id     = $so->encrypt(filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS));
    $email          = $so->encrypt(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $department     = $so->encrypt(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS));
    $raw_password   = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $confirm_pwd    = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_SPECIAL_CHARS);

    $raw_birthday   = trim(filter_input(INPUT_POST, 'birthday', FILTER_SANITIZE_SPECIAL_CHARS));
    $birthday_formatted = '';
    if (!empty($raw_birthday)) {
        $date_obj = DateTime::createFromFormat('F j, Y',$raw_birthday);
        if (!$date_obj) {
            $date_obj = DateTime::createFromFormat('Y-m-d',$raw_birthday);
        }
        if ($date_obj) {
            $birthday_formatted =$date_obj->format('m-d-Y');
        }
    }
    $birthday_enc   = !empty($birthday_formatted) ? $so->encrypt($birthday_formatted) : null;

  if ($raw_password !== $confirm_pwd) {$modal_message = 'Passwords do not match.';
    } else {
        $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);
        $role            = 'Student';$account_status  = 'Pending';

        $select_query = "SELECT * FROM `user_table` WHERE `Student_ID` = ? OR `Email` = ?";
        $select_stmt  =$con->prepare($select_query);$select_stmt->bind_param('ss', $student_id,$email);

        if ($select_stmt->execute()) {
            $result =$select_stmt->get_result();
            if ($result->num_rows > 0) {$modal_message = 'User already exists.';
            } else {
                $insert_query = 'INSERT INTO `user_table` (`Full_Name`, `Student_ID`, `Email`, `Password`, `Role`, `Department`, `Birthday`, `Account_Status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                $insert_stmt  = $con->prepare($insert_query);
                $insert_stmt->bind_param('ssssssss', $full_name, $student_id, $email, $hashed_password, $role, $department, $birthday_enc, $account_status);

                try {
                    $insert_stmt->execute();
                    $modal_message  = 'Registration successful! Please wait for admin approval.';
                    $modal_type     = 'success';
                    $modal_redirect = 'login.php';
                } catch (mysqli_sql_exception $e) {
                    error_log($e->getMessage());
                    $modal_message = 'Registration failed. Please try again.';
                }
                $insert_stmt->close();
            }
        } else {
            $modal_message = 'Failed to execute query.';
        }
        $select_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk LMS - Registration</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../frontend/css/Registration.css?v=8">
</head>
<body>
<div class="registration-container">
    <div class="logo-panel">
        <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
        <p class="tagline">Read, Archive, Advance,<br>Knowledge.</p>
    </div>
 
    <div class="form-wrapper">
        <form id="registrationForm" action="registration.php" method="post" novalidate>
            <div class="form-fields">
                <h1>Registration</h1>
 
                <!-- Page 1: Name -->
                <div class="form-page active" id="page-1">
                    <div class="form-items">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" placeholder="Fernando" required>
                    </div>
                    <div class="form-items">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" placeholder="Bautista" required>
                    </div>
                    <div class="form-items">
                        <label for="middle_name">Middle Name (Optional)</label>
                        <input type="text" id="middle_name" name="middle_name" placeholder="Reyes">
                    </div>
                    <div class="form-items">
                        <label for="suffix">Suffix (Optional)</label>
                        <input type="text" id="suffix" name="suffix" placeholder="Jr. / Sr. / III">
                    </div>
                    <div class="step-actions">
                        <button type="button" data-action="prev">Previous</button>
                        <button type="button" data-action="next">Next</button>
                    </div>
                </div>
 
                <!-- Page 2: School details -->
                <div class="form-page" id="page-2">
                    <div class="form-items">
                        <label for="birthday">Birthday</label>
                        <input type="text" id="birthday" name="birthday" placeholder="Choose birthday" required>
                    </div>
                    <div class="form-items">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" name="student_id" placeholder="R2XXXXXXXXX" required>
                    </div>
                    <div class="form-items">
                        <label for="email">School Email</label>
                        <input type="email" id="email" name="email" placeholder="test.school.email@gmail.com" required>
                    </div>
                    <div class="form-items">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="" selected disabled>Select department</option>
                            <option value="BSIT">BSIT - Information Technology</option>
                            <option value="BSCS">BSCS - Computer Science</option>
                            <option value="BSIS">BSIS - Information Systems</option>
                            <option value="BSBA">BSBA - Business Administration</option>
                            <option value="BSA">BSA - Accountancy</option>
                        </select>
                    </div>
                    <div class="step-actions">
                        <button type="button" data-action="prev">Previous</button>
                        <button type="button" data-action="next">Next</button>
                    </div>
                </div>
 
                <!-- Page 3: Password & submit -->
                <div class="form-page" id="page-3">
                    <div class="form-items">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" minlength="8" placeholder="At least 8 characters" required>
                            <span class="password-toggle-icon" data-target="password"><i class="bi bi-eye"></i></span>
                        </div>
                    </div>
                    <div class="form-items">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" placeholder="Re-enter password" required>
                            <span class="password-toggle-icon" data-target="confirm_password"><i class="bi bi-eye"></i></span>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" data-action="prev">Previous</button>
                        <button type="submit" name="btnRegister">Submit Registration</button>
                    </div>
                </div>
 
                <hr>
                <p class="register">Already have an account? <a href="login.php">LOG IN</a></p>
                <p class="terms">By proceeding, you agree to our <a href="terms.php">Terms of Use</a> <br> and confirm that you have read our <a href="privacy.php">Privacy Policy</a>.</p>
            </div>
        </form>
    </div>
</div>
 
<!-- Error modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalTitle" aria-hidden="true"
     data-message="<?= htmlspecialchars($modal_message, ENT_QUOTES, 'UTF-8') ?>"
     data-type="<?= htmlspecialchars($modal_type, ENT_QUOTES, 'UTF-8') ?>"
     data-redirect="<?= htmlspecialchars($modal_redirect, ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="errorModalTitle">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>Notice
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="errorModalMessage"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="../frontend/js/Registration.js?v=8"></script>
</body>
</html>