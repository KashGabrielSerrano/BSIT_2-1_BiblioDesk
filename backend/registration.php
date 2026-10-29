<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'Librarian' || $_SESSION['role'] === 'Admin')) {
        header("Location: admindb.php");
    } else {
        header("Location: studentdb.php");
    }
    exit();
}
$modal_message  = '';
$modal_type     = 'error';
$modal_redirect = '';
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
    <link rel="stylesheet" href="../frontend/css/Root.css">
    <link rel="stylesheet" href="../frontend/css/Registration.css?v=11">
</head>
<body>
<div class="registration-container">
    <div class="logo-panel">
        <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
        <p class="tagline">Read, Archive, Advance,<br>Knowledge.</p>
    </div>
 
    <div class="form-wrapper">
        <form id="registrationForm" novalidate>
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
                            <span class="password-toggle-icon" data-target="password"><i class="bi bi-eye-slash"></i></span>
                        </div>
                    </div>
                    <div class="form-items">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" placeholder="Re-enter password" required>
                            <span class="password-toggle-icon" data-target="confirm_password"><i class="bi bi-eye-slash"></i></span>
                        </div>
                    </div>
                    <div class="step-actions">
                        <button type="button" data-action="prev">Previous</button>
                        <button type="submit" name="btnRegister" id="btnSubmitRegister">Submit Registration</button>
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

<!-- OTP Verification Modal -->
<div class="modal fade" id="otpModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="otpModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bibliodesk-modal-header">
                <h5 class="modal-title d-flex align-items-center" id="otpModalTitle">
                    <i class="bi bi-shield-lock-fill me-2 fs-4"></i>Email Verification
                </h5>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-1">A 6-digit verification code was sent to:</p>
                <p class="fw-bold fs-6 text-dark mb-3" id="otpEmailDisplay">user@example.com</p>
                
                <div id="otpAlert" class="alert d-none py-2 px-3 small rounded-3" role="alert"></div>

                <div class="mb-3">
                    <input type="text" id="otpCodeInput" inputmode="numeric" pattern="[0-9]*" class="form-control text-center font-monospace fs-2 py-2 bibliodesk-otp-input" maxlength="6" placeholder="000000" autocomplete="off">
                </div>

                <div class="mb-3 text-muted small">
                    <i class="bi bi-clock-history me-1"></i>Code expires in <span id="otpExpiryTimer" class="fw-bold text-danger">05:00</span>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <small class="text-muted">Didn't receive the code?</small>
                    <button type="button" id="btnResendOtp" class="btn btn-link btn-sm p-0 text-decoration-none bibliodesk-resend-link" disabled>
                        Resend code in <span id="resendTimer">60</span>s
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" id="btnVerifyOtp" class="btn bibliodesk-btn-primary w-100 py-2 rounded-3 shadow-sm">
                    Verify & Complete Registration
                </button>
            </div>
        </div>
    </div>
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="../frontend/js/Registration.js?"></script>
</body>
</html>