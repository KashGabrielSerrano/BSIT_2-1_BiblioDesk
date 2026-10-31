<?php
require_once __DIR__ . "/connection/config.php";
require_once __DIR__ . "/helpers/SystemOperators.php";

$con = connection();
$so  = new SystemOperators();

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $is_secure = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';
    
    if (isset($_COOKIE['remember_me'])) {
        $hashed_token = hash('sha256', $_COOKIE['remember_me']);
        $del_stmt = $con->prepare("DELETE FROM user_tokens WHERE token_hash = ?");
        if ($del_stmt) {
            $del_stmt->bind_param("s", $hashed_token);
            $del_stmt->execute();
            $del_stmt->close();
        }
        
        setcookie("remember_me", "", [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $is_secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: login.php");
    exit();
}

// Auto-login via Persistent Cookie
$so->checkRememberMe($con);

// Redirect to dashboard if logged in
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'Librarian' || $_SESSION['role'] === 'Admin')) {
        header("Location: admindb.php");
    } else {
        header("Location: studentdb.php");
    }
    exit();
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
    <link rel="stylesheet" href="../frontend/css/root.css">
    <link rel="stylesheet" href="../frontend/css/login.css">
</head>
<body>
<div class="login-container">
    <div class="logo-panel">
        <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
        <p class="tagline">Read, Archive, Advance,<br>Knowledge.</p>
    </div>
    <div class="form-wrapper">
        <form id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="form-fields">
                <h1>Log In</h1>
                <div id="loginAlert" class="alert bg-danger text-white d-none align-items-center mb-3" role="alert">
                    <p class="mb-0" id="loginAlertText"></p>
                    <button type="button" class="btn-close ms-auto btn-close-white" onclick="document.getElementById('loginAlert').classList.add('d-none')"></button>
                </div>
                
                <div class="form-items">
                    <label for="email" class="form-label">Personal Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="bibliodesk@example.com" required>
                </div>
                
                <div class="form-items">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" minlength="6" placeholder="Enter your password" required>
                        <span id="togglePassword" class="password-toggle-icon" data-target="password">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                <div class="form-options">
                    <div class="form-field">
                        <input type="checkbox" id="checkLabel" name="remember" />
                        <label for="checkLabel">Remember me</label>
                    </div>
                    <a href="javascript:void(0);" class="forgot-password" data-bs-toggle="modal" data-bs-target="#forgotModal">Forgot Password?</a>
                </div>
                <button type="submit" id="btnLogin">Login</button>
                <hr>
                <p class="register">Don't have an account? <a href="registration.php">REGISTER NOW</a></p>
                <p class="terms">By proceeding, you agree to our <a href="terms_privacy.php?view=terms">Terms of Use</a> <br> 
                                 and confirm that you have read our <a href="terms_privacy.php?view=privacy">Privacy Policy</a>.</p>
            </div>
        </form>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="forgotModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bibliodesk-modal-header">
                <h5 class="modal-title d-flex align-items-center" id="forgotModalTitle">
                    <span id="fpTitleText">Forgot Password</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="fpAlert" class="alert d-none py-2 px-3 small rounded-3" role="alert"></div>
                <div data-step="1">
                    <p class="text-muted mb-3">Enter the email linked to your account for the verification code.</p>
                    <div class="text-start">
                        <label for="fpEmail" class="form-label fw-bold small">Personal Email</label>
                        <input type="email" id="fpEmail" class="form-control fp-input py-2" placeholder="bibliodesk@example.com" autocomplete="email">
                    </div>
                </div>
                <div data-step="2" class="d-none">
                    <p class="text-muted mb-1">A 6-digit verification code was sent to:</p>
                    <p class="fw-bold fs-6 text-dark mb-3" id="fpEmailDisplay">user@example.com</p>
                    <div class="mb-3">
                        <input type="text" id="fpOtpInput" inputmode="numeric" pattern="[0-9]*" class="form-control text-center font-monospace fs-2 py-2 bibliodesk-otp-input" maxlength="6" placeholder="000000" autocomplete="off">
                    </div>
                    <div class="mb-3 text-muted small">
                        <i class="bi bi-clock-history me-1"></i>Code expires in <span id="fpExpiryTimer" class="fw-bold text-danger">05:00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <button type="button" id="fpChangeEmail" class="btn btn-link btn-sm p-0 text-decoration-none fp-link">Change Email?</button>
                        <button type="button" id="fpResendBtn" class="btn btn-link btn-sm p-0 text-decoration-none bibliodesk-resend-link" disabled>
                            Resend code in <span id="fpResendTimer">60</span>s
                        </button>
                    </div>
                </div>
                <div data-step="3" class="d-none">
                    <p class="text-muted mb-3">Create a new password for your account.</p>
                    <div class="text-start mb-3">
                        <label for="fpNewPassword" class="form-label fw-bold small">New Password</label>
                        <div class="password-container">
                            <input type="password" id="fpNewPassword" class="form-control fp-input py-2" minlength="8" placeholder="At least 8 characters" autocomplete="new-password">
                            <span class="password-toggle-icon" data-target="fpNewPassword"><i class="bi bi-eye-slash"></i></span>
                        </div>
                    </div>
                    <div class="text-start mb-2">
                        <label for="fpConfirmPassword" class="form-label fw-bold small">Confirm New Password</label>
                        <div class="password-container">
                            <input type="password" id="fpConfirmPassword" class="form-control fp-input py-2" minlength="8" placeholder="Re-enter password" autocomplete="new-password">
                            <span class="password-toggle-icon" data-target="fpConfirmPassword"><i class="bi bi-eye-slash"></i></span>
                        </div>
                    </div>
                    <ul class="fp-rules text-start small text-muted mt-3 mb-0 list-unstyled">
                        <li id="fpRuleLength"><i class="bi bi-circle me-2"></i>At least 8 characters</li>
                        <li id="fpRuleMatch"><i class="bi bi-circle me-2"></i>Passwords match</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" id="fpSendBtn" class="btn bibliodesk-btn-primary w-100 py-2 rounded-3 shadow-sm">Send Verification Code</button>
                <button type="button" id="fpVerifyBtn" class="btn bibliodesk-btn-primary w-100 py-2 rounded-3 shadow-sm d-none">Verify Code</button>
                <button type="button" id="fpResetBtn" class="btn bibliodesk-btn-primary w-100 py-2 rounded-3 shadow-sm d-none">Reset Password</button>
            </div>
        </div>
    </div>
</div>

<!-- OTP Verification Modal -->
<div class="modal fade" id="otpModal" tabindex="-1" aria-labelledby="otpModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <div class="modal-header bibliodesk-modal-header">
                <h5 class="modal-title d-flex align-items-center" id="otpModalTitle">
                    <i class="bi bi-shield-lock-fill me-2 fs-4"></i>Email Verification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <p class="text-muted mb-1">Your account is unverified. A code was sent to:</p>
                <p class="fw-bold fs-6 text-dark mb-3" id="otpEmailDisplay"></p>
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
            <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary w-50 py-2 rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="btnVerifyOtp" class="btn bibliodesk-btn-primary w-50 py-2 rounded-3 shadow-sm">
                    Verify & Complete Registration
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$pending_email = $_SESSION['pending_email'] ?? '';
$is_pending    = !empty($_SESSION['pending_user_id']) && !empty($pending_email);
?>
<script>
    window.pendingEmail = <?php echo json_encode($pending_email); ?>;
    window.isPendingUser = <?php echo json_encode($is_pending); ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="../frontend/js/Toggle.js"></script>
<script src="../frontend/js/login.js"></script>
</body>
</html>