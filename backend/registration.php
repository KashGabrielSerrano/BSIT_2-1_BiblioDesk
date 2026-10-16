<?php
require_once("connection/config.php");
require_once("helpers/SystemOperators.php");

$con = connection();$so = new SystemOperators();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegister'])) {
    $first_name = trim(filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS));$last_name = trim(filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $middle_name = trim(filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_SPECIAL_CHARS));$suffix = trim(filter_input(INPUT_POST, 'suffix', FILTER_SANITIZE_SPECIAL_CHARS));

    $full_name_raw = trim(preg_replace('/\s+/', ' ', "$first_name $middle_name $last_name $suffix"));
    $full_name = $so->encrypt($full_name_raw);

    $student_id = $so->encrypt(filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_SPECIAL_CHARS));$email = $so->encrypt(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));$department = $so->encrypt(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_SPECIAL_CHARS));$raw_password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

    $role = 'Student';
    $account_status = 'Pending';
    $select_query = "SELECT * FROM `user_table` WHERE `Student_ID` = ? OR `Email` = ?";
    $select_stmt =$con->prepare($select_query);$select_stmt->bind_param('ss', $student_id,$email);

    if ($select_stmt->execute()) {
        $result =$select_stmt->get_result();
        if ($result->num_rows > 0) {
            echo "<script> alert('User already exists');</script>";
        } else {
            $insert_query = 'INSERT INTO `user_table` (`Full_Name`, `Student_ID`, `Email`, `Password`, `Role`, `Department`, `Account_Status`) VALUES (?, ?, ?, ?, ?, ?, ?)';
            $insert_stmt = $con->prepare($insert_query);
            $insert_stmt->bind_param('sssssss',$full_name, $student_id,$email, $hashed_password,$role, $department,$account_status);
            
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
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BiblioDesk LMS | Registration</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <!-- Must come after Bootstrap so these rules override it -->
  <link rel="stylesheet" href="/Bibliodesk/frontend/css/Registration.css">
</head>
<body>
  <main class="app-container">

    <!-- Left: Branding sidebar -->
    <aside class="brand-side">
      <img class="brand-icon" src="/Bibliodesk/frontend/assets/BiblioDesk_White.png" alt="BiblioDesk">
      <p class="brand-tagline">Read, Archive, Advance,<br>Knowledge.</p>
    </aside>

    <!-- Right: cream panel + white card -->
    <section class="form-side">
      <div class="form-card">

        <form id="registrationForm" action="" method="POST" novalidate>

          <!-- PAGE 1: Name -->
          <div class="form-page active" id="page-1">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Fernando" autocomplete="given-name" required>
              </div>
              <div class="col-12">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Bautista" autocomplete="family-name" required>
              </div>
              <div class="col-12">
                <label for="middle_name" class="form-label">Middle Name (Optional)</label>
                <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Reyes" autocomplete="additional-name">
              </div>
              <div class="col-12">
                <label for="suffix" class="form-label">Suffix (Optional)</label>
                <input type="text" class="form-control" id="suffix" name="suffix" placeholder="Jr. / Sr. / III" autocomplete="honorific-suffix">
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="prev">Previous</button>
              <button type="button" class="btn btn-biblio" data-action="next">Next</button>
            </div>
          </div>

          <!-- PAGE 2: School details -->
          <div class="form-page" id="page-2">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="birthday" class="form-label">Birthday (mm/dd/yyyy)</label>
                <input type="date" class="form-control" id="birthday" name="birthday" autocomplete="bday" required>
              </div>
              <div class="col-12">
                <label for="student_id" class="form-label">Student ID</label>
                <input type="text" class="form-control" id="student_id" name="student_id" placeholder="R2XXXXXXXXX" autocomplete="off" required>
              </div>
              <div class="col-12">
                <label for="email" class="form-label">School Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="test.school.email@gmail.com" autocomplete="email" required>
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
                  <option value="BSED">BSED - Secondary Education</option>
                  <option value="BEED">BEED - Elementary Education</option>
                  <option value="BSHM">BSHM - Hospitality Management</option>
                  <option value="BSN">BSN - Nursing</option>
                  <option value="BSCE">BSCE - Civil Engineering</option>
                </select>
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="prev">Previous</button>
              <button type="button" class="btn btn-biblio" data-action="next">Next</button>
            </div>
          </div>

          <!-- PAGE 3: Contact & street -->
          <div class="form-page" id="page-3">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="personal_email" class="form-label">Personal Email</label>
                <input type="email" class="form-control" id="personal_email" name="personal_email" placeholder="test.email@gmail.com" autocomplete="email" required>
              </div>
              <div class="col-12">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" maxlength="11" title="11 digits, starting with 09" autocomplete="tel" required>
              </div>
              <div class="col-12">
                <label for="house_number" class="form-label">House / Unit Number</label>
                <input type="text" class="form-control" id="house_number" name="house_number" placeholder="01" autocomplete="address-line1" required>
              </div>
              <div class="col-12">
                <label for="street" class="form-label">Street</label>
                <input type="text" class="form-control" id="street" name="street" placeholder="Street" autocomplete="address-line2" required>
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="prev">Previous</button>
              <button type="button" class="btn btn-biblio" data-action="next">Next</button>
            </div>
          </div>

          <!-- PAGE 4: Barangay & city -->
          <div class="form-page" id="page-4">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="barangay" class="form-label">Barangay</label>
                <input type="text" class="form-control" id="barangay" name="barangay" placeholder="Brgy" autocomplete="address-level3" required>
              </div>
              <div class="col-12">
                <label for="city" class="form-label">City / Town</label>
                <input type="text" class="form-control" id="city" name="city" value="Marikina City" autocomplete="address-level2" required>
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="prev">Previous</button>
              <button type="button" class="btn btn-biblio" data-action="next">Next</button>
            </div>
          </div>

          <!-- PAGE 5: Password -->
          <div class="form-page" id="page-5">
            <h2 class="form-title">Registration</h2>
            <div class="row g-3">
              <div class="col-12">
                <label for="password" class="form-label">Password</label>
                <div class="password-field">
                  <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" minlength="8" autocomplete="new-password" required>
                  <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><line class="slash" x1="3" y1="21" x2="21" y2="3"/></svg>
                  </button>
                </div>
              </div>
              <div class="col-12">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="password-field">
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" autocomplete="new-password" required>
                  <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/><line class="slash" x1="3" y1="21" x2="21" y2="3"/></svg>
                  </button>
                </div>
              </div>
            </div>
            <div class="step-actions">
              <button type="button" class="btn btn-biblio" data-action="prev">Previous</button>
              <button type="button" class="btn btn-biblio" data-action="next">Submit</button>
            </div>
          </div>

          <!-- PAGE 6: OTP -->
          <div class="form-page" id="page-6">
            <h2 class="form-title otp-title">OTP</h2>
            <p class="otp-sub">Enter the OTP that we sent to your email address</p>

            <div class="otp-boxes">
              <!-- real field that PHP receives; filled by the six boxes below -->
              <input type="text" class="otp-hidden" id="otp" name="otp" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" title="Enter the 6-digit code sent to your email" tabindex="-1" aria-hidden="true" required>
              <input type="text" class="form-control otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" autocomplete="one-time-code" aria-label="OTP digit 1">
              <input type="text" class="form-control otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="OTP digit 2">
              <input type="text" class="form-control otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="OTP digit 3">
              <input type="text" class="form-control otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="OTP digit 4">
              <input type="text" class="form-control otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="OTP digit 5">
              <input type="text" class="form-control otp-box" inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="OTP digit 6">
            </div>

            <div class="step-actions step-actions--single">
              <button type="submit" class="btn btn-biblio" name="btnRegister">Submit</button>
            </div>
            <p class="otp-resend">Didn't receive any? <span class="resend-link">Resend in 0s</span></p>
          </div>

        </form>

        <div class="step-indicator" id="stepIndicator" aria-live="polite">Step 1 of 6</div>

      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/Bibliodesk/frontend/js/Registration.js"></script>
</body>
</html>