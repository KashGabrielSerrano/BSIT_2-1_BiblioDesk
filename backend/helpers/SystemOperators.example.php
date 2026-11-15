<?php
/**
 * backend/helpers/SystemOperators.example.php
 * TEMPLATE ONLY. Copy to SystemOperators.php (git-ignored) and fill in the placeholders below.
 * NEVER commit the real file. 
 *
 * !! Use the SAME $secretKey / $cipher / $iv as the live server. Emails, names and student IDs in
 * !! the database are encrypted with them; different values make every account unreadable.
 * !! Pick them once, before the first account is created, and never change them afterwards.
 * The IV is fixed on purpose: encryption must be deterministic so "WHERE Email = ?" lookups work.
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('UTC');

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

class SystemOperators {
    // Replace all three with your own values. AES-256-CBC needs a key of exactly 32 bytes and an IV of exactly 16 bytes.
    private $secretKey = 'CHANGE_ME_TO_A_32_CHARACTER_KEY!';
    private $cipher = 'AES-256-CBC';
    private $iv = 'CHANGE_ME_16BYTE';

    public function encrypt($value) {
        return base64_encode(openssl_encrypt($value, $this->cipher, $this->secretKey, 0, $this->iv));
    }

    public function decrypt($encrypted) {
        return openssl_decrypt(base64_decode($encrypted), $this->cipher, $this->secretKey, 0, $this->iv);
    }

    public function randomStringGenerator($length = 8) {
        return bin2hex(random_bytes($length / 2));
    }

    public function generateOTPCode() {
        return sprintf("%06d", random_int(100000, 999999));
    }

    /** $type: 'registration' (account activation), 'forgot_password' or 'change_email'. Returns true when sent. */
    public function sendEmail($recipientEmail, $otpCode, $type = 'registration') {
        $mail = new PHPMailer(true);
        try {
            $this->configureSmtp($mail, $recipientEmail);

            if ($type === 'forgot_password') {
                $subject     = 'Bibliodesk Password Reset OTP';
                $headerTitle = 'Password Reset Request';
                $greeting    = 'We received a request to reset your Bibliodesk account password.';
                $instruction = 'Please enter the 6-digit OTP code below to verify your password reset. This code will expire in <strong>5 minutes</strong>.';
                $altBodyText = "Your Bibliodesk password reset OTP code is: {$otpCode}. It will expire in 5 minutes.";
            } elseif ($type === 'change_email') {
                $subject     = 'Bibliodesk Email Change OTP';
                $headerTitle = 'Confirm Your New Email';
                $greeting    = 'We received a request to change the email address on your Bibliodesk account to this address.';
                $instruction = 'Please enter the 6-digit OTP code below to confirm the change. This code will expire in <strong>5 minutes</strong>.';
                $altBodyText = "Your Bibliodesk email change OTP code is: {$otpCode}. It will expire in 5 minutes.";
            } else {
                $subject     = 'Bibliodesk Account Activation OTP';
                $headerTitle = 'Activate Your Account';
                $greeting    = 'Thank you for signing up with Bibliodesk!';
                $instruction = 'Please enter the 6-digit OTP code below to complete your registration. This code will expire in <strong>5 minutes</strong>.';
                $altBodyText = "Your Bibliodesk account activation OTP code is: {$otpCode}. It will expire in 5 minutes.";
            }

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $year = date('Y');

            $message_body = <<<HTML
            <!DOCTYPE html>
            <html>
            <body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:30px 0;">
                <tr>
                <td align="center">
                    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                    <tr>
                        <td align="center" style="background-color:#2563eb;padding:28px 20px;">
                        <h1 style="margin:0;color:#ffffff;font-size:24px;">{$headerTitle}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 40px;color:#333333;">
                        <h2 style="margin:0 0 16px;font-size:20px;color:#111827;">Email Verification Code</h2>
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">{$greeting}</p>
                        <p style="margin:0 0 20px;font-size:15px;line-height:1.6;">{$instruction}</p>
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;margin-bottom:24px;">
                            <tr>
                            <td style="padding:16px 20px;font-size:14px;color:#6b7280;">Verification Code:</td>
                            <td align="right" style="padding:16px 20px;font-size:24px;font-weight:bold;letter-spacing:4px;color:#2563eb;">{$otpCode}</td>
                            </tr>
                        </table>
                        <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">
                            If you did not request this code, please disregard this email.
                        </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background-color:#f9fafb;padding:20px;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">
                        &copy; {$year} Bibliodesk. All rights reserved.
                        </td>
                    </tr>
                    </table>
                </td>
                </tr>
            </table>
            </body>
            </html>
            HTML;

            $mail->Body    = $message_body;
            $mail->AltBody = $altBodyText;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    /** SMTP login + sender, shared by every email this class sends (the credentials live only here). */
    private function configureSmtp(PHPMailer $mail, string $recipientEmail, ?array $replyTo = null): void {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR_GMAIL_ADDRESS';
        $mail->Password   = 'YOUR_GMAIL_APP_PASSWORD';   // a Google "App password", not your normal Gmail password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->Timeout = 10;   // seconds; a blocked or slow SMTP host must not hang the page for minutes

        $mail->setFrom('YOUR_GMAIL_ADDRESS', 'Bibliodesk Portal');
        if ($replyTo && !empty($replyTo[0])) {
            $mail->addReplyTo($replyTo[0], $replyTo[1] ?? '');   // [email, name]
        } else {
            $mail->addReplyTo('noreply@bibliodesk.com', 'No Reply');
        }
        $mail->addAddress($recipientEmail);
    }

    /**
     * A notice email without a code: security alerts (password / email changed) and copies of notifications.
     * $bodyHtml must already be escaped HTML. $replyTo = [email, name] replaces the no-reply Reply-To
     * (used by the helpdesk so staff can answer a visitor straight from the inbox). Returns true when sent.
     */
    public function sendNotice($recipientEmail, $subject, $headline, $bodyHtml, $altText = '', ?array $replyTo = null) {
        $mail = new PHPMailer(true);
        try {
            $this->configureSmtp($mail, $recipientEmail, $replyTo);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $year  = date('Y');
            $title = htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
            $mail->Body = <<<HTML
            <!DOCTYPE html>
            <html>
            <body style="margin:0;padding:0;background-color:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:30px 0;">
                <tr><td align="center">
                    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.05);">
                    <tr><td align="center" style="background-color:#2563eb;padding:28px 20px;">
                        <h1 style="margin:0;color:#ffffff;font-size:22px;">{$title}</h1>
                    </td></tr>
                    <tr><td style="padding:32px 40px;color:#333333;font-size:15px;line-height:1.6;">
                        {$bodyHtml}
                    </td></tr>
                    <tr><td align="center" style="background-color:#f9fafb;padding:20px;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">
                        &copy; {$year} Bibliodesk. All rights reserved.
                    </td></tr>
                    </table>
                </td></tr>
            </table>
            </body>
            </html>
            HTML;
            $mail->AltBody = $altText !== '' ? $altText : strip_tags($bodyHtml);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public function checkRememberMe($con) {
        if (isset($_SESSION['user_id']) || empty($_COOKIE['remember_me'])) {
            return;
        }

        // Periodic garbage collection of dead tokens
        if (rand(1, 100) === 1) {
            $con->query("DELETE FROM user_tokens WHERE expires_at < NOW()");
        }

        $cookie_token = $_COOKIE['remember_me'];
        $hashed_token = hash('sha256', $cookie_token);
        $current_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $con->prepare("SELECT user_id, user_agent FROM user_tokens WHERE token_hash = ? AND expires_at > NOW()");
        if (!$stmt) {
            return;
        }

        $stmt->bind_param("s", $hashed_token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (hash_equals($row['user_agent'], $current_user_agent)) {
                $u_stmt = $con->prepare("SELECT User_ID, Email, Role, Student_ID FROM user_table WHERE User_ID = ? AND Account_Status = 'Active'");
                if ($u_stmt) {
                    $u_stmt->bind_param("i", $row['user_id']);
                    $u_stmt->execute();
                    $u_res = $u_stmt->get_result();

                    if ($user_row = $u_res->fetch_assoc()) {
                        // 1. Regenerate Session ID and refresh CSRF token
                        session_regenerate_id(true);
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        $_SESSION['user_id']    = $user_row['User_ID'];
                        $_SESSION['email']      = $this->decrypt($user_row['Email']);
                        $_SESSION['role']       = $user_row['Role'];
                        $_SESSION['student_id'] = !empty($user_row['Student_ID']) ? $this->decrypt($user_row['Student_ID']) : null;

                        // 2. TOKEN ROTATION: Delete spent token
                        $del_stmt = $con->prepare("DELETE FROM user_tokens WHERE token_hash = ?");
                        $del_stmt->bind_param("s", $hashed_token);
                        $del_stmt->execute();
                        $del_stmt->close();

                        // 3. Generate replacement token
                        $new_raw_token    = bin2hex(random_bytes(32));
                        $new_hashed_token = hash('sha256', $new_raw_token);
                        $user_ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                        $expires_at       = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));

                        $ins_stmt = $con->prepare("INSERT INTO user_tokens (user_id, token_hash, user_agent, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)");
                        if ($ins_stmt) {
                            $ins_stmt->bind_param("issss", $row['user_id'], $new_hashed_token, $current_user_agent, $user_ip, $expires_at);
                            $ins_stmt->execute();
                            $ins_stmt->close();
                        }

                        // 4. Dispatch new HTTP cookie matching original attributes
                        $is_secure = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';
                        setcookie("remember_me", $new_raw_token, [
                            'expires'  => time() + (30 * 24 * 60 * 60),
                            'path'     => '/',
                            'domain'   => '',
                            'secure'   => $is_secure,
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]);
                    }
                    $u_stmt->close();
                }
            } else {
                // Security Violation: User-Agent mismatch! Wipe token from DB and delete client cookie
                $del_stmt = $con->prepare("DELETE FROM user_tokens WHERE token_hash = ?");
                $del_stmt->bind_param("s", $hashed_token);
                $del_stmt->execute();
                $del_stmt->close();

                $is_secure = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';
                setcookie("remember_me", "", [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'domain'   => '',
                    'secure'   => $is_secure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
        } else {
            // Token expired or invalid: purge client cookie
            $is_secure = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on';
            setcookie("remember_me", "", [
                'expires'  => time() - 3600,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $is_secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        $stmt->close();
    }

    public function getClientIP(): string {
        return filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }

    /**
    * Check if an IP address or User ID has exceeded max allowed OTP requests within a time window.
    **/
    public function isOtpRateLimited(mysqli $con, int $user_id, string $ip_address, int $max_requests = 3, int $minutes = 15): bool {
        $time_window = date("Y-m-d H:i:s", strtotime("-{$minutes} minutes"));

        $stmt = $con->prepare("SELECT COUNT(*) FROM email_verifications WHERE (User_ID = ? OR ip_address = ?) AND Created_At > ?");
        $stmt->bind_param("iss", $user_id, $ip_address, $time_window);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return $count >= $max_requests;
    }

}