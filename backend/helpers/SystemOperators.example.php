<?php
/**
 * backend/helpers/SystemOperators.example.php
 * Copy to SystemOperators.php (git-ignored) and fill in the secrets. NEVER commit the real file.
 *
 * !! Use the SAME $secretKey / $cipher / $iv as the live server. Emails, names and student IDs in
 * !! the database are encrypted with them; different values make every account unreadable.
 * The IV is fixed on purpose: encryption must be deterministic so "WHERE Email = ?" lookups work.
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('UTC');

require_once __DIR__ . '/email-otp/PHPMailer/src/Exception.php';
require_once __DIR__ . '/email-otp/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/email-otp/PHPMailer/src/SMTP.php';

class SystemOperators {
    private $secretKey = 'ASK_THE_TEAM_FOR_THE_REAL_KEY';
    private $cipher    = 'AES-256-CBC';
    private $iv        = 'ASK_THE_TEAM_16B';   // must be exactly 16 bytes for AES-*-CBC

    private $smtpUser  = 'YOUR_GMAIL_ADDRESS';
    private $smtpPass  = 'YOUR_GMAIL_APP_PASSWORD';

    public function encrypt($value) {
        return base64_encode(openssl_encrypt($value, $this->cipher, $this->secretKey, 0, $this->iv));
    }

    public function decrypt($encrypted) {
        return openssl_decrypt(base64_decode($encrypted), $this->cipher, $this->secretKey, 0, $this->iv);
    }

    public function randomStringGenerator($length = 8) {
        return bin2hex(random_bytes(intdiv($length, 2)));
    }

    public function generateOTPCode() {
        return sprintf("%06d", random_int(0, 999999));
    }

    /** $type: 'registration' (account activation) or 'forgot_password'. Returns true when sent. */
    public function sendEmail($recipientEmail, $otpCode, $type = 'registration') {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom($this->smtpUser, 'Bibliodesk Portal');
            $mail->addAddress($recipientEmail);

            $title = $type === 'forgot_password' ? 'Bibliodesk Password Reset OTP' : 'Bibliodesk Account Activation OTP';
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $title;
            $mail->Body    = "<p>Your verification code is <strong>{$otpCode}</strong>. It expires in 5 minutes.</p>";
            $mail->AltBody = "Your verification code is {$otpCode}. It expires in 5 minutes.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    /** Log the visitor in from a valid "remember_me" cookie (rotates the token). */
    public function checkRememberMe($con) {
        if (isset($_SESSION['user_id']) || empty($_COOKIE['remember_me'])) {
            return;
        }
        if (rand(1, 100) === 1) {
            $con->query("DELETE FROM user_tokens WHERE expires_at < NOW()");
        }

        $hashed = hash('sha256', $_COOKIE['remember_me']);
        $agent  = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $con->prepare("SELECT user_id, user_agent FROM user_tokens WHERE token_hash = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $hashed);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $del = $con->prepare("DELETE FROM user_tokens WHERE token_hash = ?");
        $del->bind_param("s", $hashed);
        $del->execute();
        $del->close();

        if (!$row || !hash_equals($row['user_agent'], $agent)) {
            clear_remember_cookie();               // core/bootstrap.php
            return;
        }

        $u = $con->prepare("SELECT User_ID, Email, Role, Student_ID FROM user_table WHERE User_ID = ? AND Account_Status = 'Active'");
        $u->bind_param("i", $row['user_id']);
        $u->execute();
        $user = $u->get_result()->fetch_assoc();
        $u->close();

        if ($user) {
            start_user_session($user);              // core/bootstrap.php
            issue_remember_token((int)$user['User_ID']);
        } else {
            clear_remember_cookie();
        }
    }

    public function getClientIP(): string {
        return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    /** True when the user or IP requested $max_requests or more OTPs in the last $minutes. */
    public function isOtpRateLimited(mysqli $con, int $user_id, string $ip_address, int $max_requests = 3, int $minutes = 15): bool {
        $since = date("Y-m-d H:i:s", strtotime("-{$minutes} minutes"));
        $stmt  = $con->prepare("SELECT COUNT(*) FROM email_verifications WHERE (User_ID = ? OR ip_address = ?) AND Created_At > ?");
        $stmt->bind_param("iss", $user_id, $ip_address, $since);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count >= $max_requests;
    }
}
