<?php
/**
 * backend/core/auth.php — shared login / OTP rules (Stage 10). Needs core/bootstrap.php first.
 * Used by auth_controller.php; kept separate so the rules can be unit-tested (dev/test_auth.php).
 */

const OTP_MINUTES       = 5;
const OTP_MAX_ATTEMPTS  = 5;
const OTP_COOLDOWN      = 60;   // seconds between two codes from the same browser session
const OTP_WINDOW_MAX    = 5;    // codes per account or IP address per 15 minutes
const LOGIN_MAX_FAILS   = 5;
const LOGIN_LOCK_MINUTES = 15;
const DEPARTMENTS       = ['BSIT', 'BSCS', 'BSIS', 'BSBA', 'BSA'];

/**
 * Email an OTP. LOCAL TESTING ONLY: when the flag file dev/mail_to_log.flag exists, the email is
 * written to dev/mail.local.log instead of being sent through Gmail (dev/ is never uploaded to
 * the live server, so live behaviour cannot change).
 */
function send_otp_email(string $email, string $code, string $type): bool {
    $dev = __DIR__ . '/../../dev';
    if (is_file($dev . '/mail_to_log.flag')) {
        return (bool)file_put_contents($dev . '/mail.local.log', gmdate('c') . "\t{$type}\t{$email}\t{$code}\n", FILE_APPEND);
    }
    return (bool)so()->sendEmail($email, $code, $type);
}


/** Seconds until another code may be sent ($key = session timestamp key), 0 when allowed. */
function otp_wait(string $key): int {
    $left = OTP_COOLDOWN - (time() - (int)($_SESSION[$key] ?? 0));
    return max(0, $left);
}

/** Too many codes for this account or IP in the last 15 minutes? */
function otp_rate_limited(int $user_id, string $ip): bool {
    $n = (int)(db_one("SELECT COUNT(*) AS c FROM email_verifications
                        WHERE (User_ID = ? OR ip_address = ?) AND Created_At > UTC_TIMESTAMP() - INTERVAL 15 MINUTE",
                      'is', [$user_id, $ip])['c'] ?? 0);
    return $n >= OTP_WINDOW_MAX;
}

/**
 * Invalidate the user's older codes, create a new one (with the requester's IP) and email it.
 * Returns ['sent' => bool, 'expires_ms' => int].
 */
function issue_otp(int $user_id, string $email, string $type, string $ip): array {
    db_exec("UPDATE email_verifications SET Is_Used = 1 WHERE User_ID = ? AND Is_Used = 0", 'i', [$user_id]);
    $code = generate_otp();
    db_exec("INSERT INTO email_verifications (User_ID, OTP_Code, Expires_At, Is_Used, Attempts, ip_address)
             VALUES (?, ?, UTC_TIMESTAMP() + INTERVAL " . OTP_MINUTES . " MINUTE, 0, 0, ?)", 'iss', [$user_id, $code, $ip]);
    $expires = db_one("SELECT UNIX_TIMESTAMP(Expires_At) AS t FROM email_verifications WHERE User_ID = ? ORDER BY Verification_ID DESC LIMIT 1",
                      'i', [$user_id]);
    $sent = send_otp_email($email, $code, $type);
    return ['sent' => (bool)$sent, 'expires_ms' => (int)($expires['t'] ?? (time() + OTP_MINUTES * 60)) * 1000];
}

/**
 * Check a code against the user's newest unused, unexpired code.
 * Returns ['ok' => bool, 'message' => string, 'locked' => bool (code invalidated after too many tries)].
 * The attempt limit is checked BEFORE comparing, so the 5th try with the right code still works (bug #2).
 */
function verify_otp(int $user_id, string $entered): array {
    $row = db_one("SELECT Verification_ID, OTP_Code, Attempts FROM email_verifications
                    WHERE User_ID = ? AND Is_Used = 0 AND Expires_At > UTC_TIMESTAMP()
                    ORDER BY Verification_ID DESC LIMIT 1", 'i', [$user_id]);
    if (!$row) {
        return ['ok' => false, 'locked' => false, 'message' => 'Your verification code has expired or is invalid. Please request a new one.'];
    }
    $id = (int)$row['Verification_ID'];
    if ((int)$row['Attempts'] >= OTP_MAX_ATTEMPTS) {
        db_exec("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?", 'i', [$id]);
        return ['ok' => false, 'locked' => true, 'message' => 'Too many failed attempts. The code was invalidated. Please request a new one.'];
    }
    if (preg_match('/^\d{6}$/', $entered) && hash_equals((string)$row['OTP_Code'], $entered)) {
        db_exec("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?", 'i', [$id]);
        return ['ok' => true, 'locked' => false, 'message' => 'Code verified.'];
    }
    db_exec("UPDATE email_verifications SET Attempts = Attempts + 1 WHERE Verification_ID = ?", 'i', [$id]);
    $left = OTP_MAX_ATTEMPTS - ((int)$row['Attempts'] + 1);
    if ($left <= 0) {
        db_exec("UPDATE email_verifications SET Is_Used = 1 WHERE Verification_ID = ?", 'i', [$id]);
        return ['ok' => false, 'locked' => true, 'message' => 'Too many failed attempts. The code was invalidated. Please request a new one.'];
    }
    return ['ok' => false, 'locked' => false, 'message' => "Invalid verification code. {$left} attempt(s) remaining."];
}

/** Password rules shared by registration and reset. Returns an error message or null. */
function password_problem(string $pw, string $confirm): ?string {
    if (strlen($pw) < 8)  return 'Password must be at least 8 characters long.';
    if (strlen($pw) > 64) return 'Password must not exceed 64 characters.';
    if ($pw !== $confirm) return 'Passwords do not match.';
    return null;
}

function record_failed_attempt(string $ip, ?string $encrypted_email): void {
    db_exec("INSERT INTO login_attempts (ip_address, email_attempted) VALUES (?, ?)", 'ss', [$ip, $encrypted_email]);
}
