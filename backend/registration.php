<?php
require_once("connection/config.php");
require_once("helpers/SystemOperators.php");

$con = connection();$so = new SystemOperators();

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

    if ($raw_password !==$confirm_pwd) {
        echo "<script>alert('Passwords do not match.');</script>";
    } else {
        $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);
        $role           = 'Student';$account_status = 'Pending';

        $select_query = "SELECT * FROM `user_table` WHERE `Student_ID` = ? OR `Email` = ?";
        $select_stmt  =$con->prepare($select_query);$select_stmt->bind_param('ss', $student_id,$email);

        if ($select_stmt->execute()) {
            $result =$select_stmt->get_result();
            if ($result->num_rows > 0) {
                echo "<script>alert('User already exists');</script>";
            } else {
                $insert_query = 'INSERT INTO `user_table` (`Full_Name`, `Student_ID`, `Email`, `Password`, `Role`, `Department`, `Account_Status`) VALUES (?, ?, ?, ?, ?, ?, ?)';
                $insert_stmt  = $con->prepare($insert_query);
                $insert_stmt->bind_param('sssssss',$full_name, $student_id,$email, $hashed_password,$role, $department,$account_status);

                try {
                    $insert_stmt->execute();
                    echo "<script>
                        alert('Registration successful! Please wait for admin approval.');
                        window.location='login.php';
                    </script>";
                } catch (mysqli_sql_exception $e) {
                    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
                }
                $insert_stmt->close();
            }
        } else {
            echo "Failed to execute query.";
        }
        $select_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BiblioDesk LMS | Registration</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../frontend/css/Registration.css">
</head>
<body>
  <main class="app-container">
    <aside class="brand-side">
      <img class="brand-icon" src="../frontend/assets/BiblioDesk_White.png" alt="BiblioDesk">
      <p class="brand-tagline">Read, Archive, Advance,<br>Knowledge.</p>
    </aside>
    <section class="form-side">
      <div class="form-card">
        <form id="registrationForm" action="registration.php" method="POST" novalidate>
          <!-- PAGE 1: Name -->
          <div class="form-page active" id="page-1">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Fernando" required>
              </div>
              <div class="col-12">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Bautista" required>
              </div>
              <div class="col-12">
                <label for="middle_name" class="form-label">Middle Name (Optional)</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Reyes">
              </div>
              <div class="col-12">
                <label for="suffix" class="form-label">Suffix (Optional)</label>
                <input type="text" class="form-control" id="suffix" name="suffix" placeholder="Jr. / Sr. / III">
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="next">Next</button>
            </div>
          </div>

          <!-- PAGE 2: School details -->
          <div class="form-page" id="page-2">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="birthday" class="form-label">Birthday</label>
                <input type="date" class="form-control" id="birthday" name="birthday" required>
              </div>
              <div class="col-12">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" placeholder="R2XXXXXXXXX" required>
              </div>
              <div class="col-12">
                <label for="email" class="form-label">School Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="test.school.email@gmail.com" required>
              </div>
              <div class="col-12">
                <label for="department" class="form-label">Department</label>
                <select class="form-select" id="department" name="department" required>
                  <option value="" selected disabled>Select department</option>
                  <option value="BSIT">BSIT - Information Technology</option>
                  <option value="BSCS">BSCS - Computer Science</option>
                  <option value="BSIS">BSIS - Information Systems</option>
                  <option value="BSBA">BSBA - Business Administration</option>
                  <option value="BSA">BSA - Accountancy</option>
                </select>
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="prev">Previous</button>
              <button type="button" class="btn btn-biblio" data-action="next">Next</button>
            </div>
          </div>

          <!-- PAGE 3: Password & Submit -->
          <div class="form-page" id="page-3">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" minlength="8" required>
              </div>
              <div class="col-12">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="prev">Previous</button>
              <button type="submit" class="btn btn-biblio" name="btnRegister">Submit Registration</button>
            </div>
          </div>
        </form>
      </div>
    </section>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../frontend/js/Registration.js"></script>
</body>
</html>