<?php
/* ========================================================================== */
/* 1. BOOTSTRAP, HEADERS & DEPENDENCIES                                       */
/* ========================================================================== */
ob_start();
require_once __DIR__ . '/core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

set_exception_handler(function (Throwable $e): void {
    error_log('settings_controller error: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    sendResponse(false, 'A server error occurred. Please try again.', local_debug($e), 500);
});

const AVATAR_MAX_BYTES = 100 * 1024; // 100 KB max
const AVATAR_MIMES     = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
const DEFAULT_AVATAR   = '../frontend/assets/icons/pfp-icon.png';

/* ========================================================================== */
/* 2. SHARED HELPER FUNCTIONS                                                 */
/* ========================================================================== */
function supabase_ready(): bool {
    return defined('SUPABASE_URL') && defined('SUPABASE_SERVICE_KEY')
        && SUPABASE_URL !== '' && SUPABASE_SERVICE_KEY !== '';
}

/** @return array{0: int, 1: string} */
function supabase_storage_request(string $method, string $path, ?string $body = null, ?string $contentType = null): array {
    $headers = [
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'apikey: ' . SUPABASE_SERVICE_KEY,
    ];
    if ($contentType !== null) {
        $headers[] = 'Content-Type: ' . $contentType;
    }

    $opts = ['http' => [
        'method'        => $method,
        'header'        => implode("\r\n", $headers),
        'timeout'       => 30,
        'ignore_errors' => true,
    ]];
    if ($body !== null) {
        $opts['http']['content'] = $body;
    }

    $resp = @file_get_contents(
        rtrim(SUPABASE_URL, '/') . '/storage/v1/' . ltrim($path, '/'),
        false,
        stream_context_create($opts)
    );

    $resp_headers = function_exists('http_get_last_response_headers')
        ? (http_get_last_response_headers() ?? [])
        : ($http_response_header ?? []);

    $status = 0;
    foreach ($resp_headers as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $status = (int)$m[1];
        }
    }
    return [$status, $resp === false ? '' : $resp];
}

function delete_old_avatar(?string $url): void {
    if (!$url || !supabase_ready() || !is_profile_picture_url($url)) {
        return;
    }
    $bucket = profile_picture_bucket();
    $prefix = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . $bucket . '/';
    $object = substr($url, strlen($prefix));
    if ($object === '' || !preg_match('/^[A-Za-z0-9_\-]+\.(jpg|png)$/', $object)) {
        return;
    }
    [$status, $resp] = supabase_storage_request('DELETE', 'object/' . $bucket . '/' . $object);
    if ($status !== 200 && $status !== 204 && $status !== 404) {
        error_log("Supabase avatar delete failed (HTTP {$status}) for {$bucket}/{$object}: " . substr($resp, 0, 300));
    }
}

function settings_payload(): array {
    $values = settings_all(true);
    $out = [];
    foreach (SETTINGS_SPEC as $key => [$label, $type, $min, $max, $default, $help, $group]) {
        $out[] = compact('key', 'label', 'type', 'min', 'max', 'default', 'help', 'group') + ['value' => $values[$key]];
    }
    return $out;
}

/* ========================================================================== */
/* 3. SECURITY GATEWAY & ROUTER                                               */
/* ========================================================================== */
$user = require_login_api('Please log in to manage your settings.');
require_csrf();

$read_actions  = ['get', 'get_preferences'];
$write_actions = ['update', 'upload_avatar', 'remove_avatar', 'update_profile', 'save_preferences', 'track_reading_time', 'change_password'];

$action = gv($_POST, 'action') ?: gv($_GET, 'action');
if (!in_array($action, array_merge($read_actions, $write_actions), true)) {
    sendResponse(false, 'Invalid action.', [], 400);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (in_array($action, $write_actions, true) && $method !== 'POST') {
    sendResponse(false, 'This action requires POST.', [], 405);
}
if (in_array($action, $read_actions, true) && $method !== 'GET') {
    sendResponse(false, 'This action requires GET.', [], 405);
}

// System-wide settings remain strictly Admin-only
if (in_array($action, ['get', 'update'], true)) {
    require_role_api(['Admin'], 'Only admins can change system settings.');
}

switch ($action) {
    case 'upload_avatar':
        handle_upload_avatar((int)$user['user_id']);
        break;
    case 'remove_avatar':
        handle_remove_avatar((int)$user['user_id']);
        break;
    case 'update_profile':
    case 'save_preferences':
        handle_save_preferences((int)$user['user_id']);
        break;
    case 'get_preferences':
        sendResponse(true, 'Preferences loaded.', ['preferences' => get_user_preferences((int)$user['user_id'])]);
        break;
    case 'track_reading_time':
        handle_track_reading_time((int)$user['user_id']);
        break;
    case 'change_password':
        handle_change_password((int)$user['user_id']);
        break;
    case 'get':
        sendResponse(true, 'Settings loaded.', ['settings' => settings_payload()]);
        break;
    case 'update':
        handle_update_system_settings((int)$user['user_id']);
        break;
}

/* ========================================================================== */
/* 4. ACTION HANDLERS                                                         */
/* ========================================================================== */
function handle_upload_avatar(int $userId): void {
    $f = $_FILES['avatar'] ?? null;
    if (!is_array($f) || !is_int($f['error'] ?? null) || !is_string($f['tmp_name'] ?? null)) {
        sendResponse(false, 'Please choose a JPG or PNG image file.', [], 422);
    }
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        sendResponse(false, 'Profile photo must be 100 KB or smaller.', [], 422);
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        sendResponse(false, 'Photo upload failed (error code ' . $f['error'] . ').', [], 422);
    }
    if (!supabase_ready()) {
        sendResponse(false, 'File storage is not configured on the server.', [], 500);
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        sendResponse(false, 'Invalid uploaded file.', [], 422);
    }

    $size = filesize($f['tmp_name']);
    if ($size === false || $size <= 0) {
        sendResponse(false, 'The selected image is empty.', [], 422);
    }
    if ($size > AVATAR_MAX_BYTES) {
        sendResponse(false, 'Profile photo must be 100 KB or smaller.', [], 422);
    }

    // Validate real file signature / MIME (only jpeg/jpg and png)
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!is_string($mime) || !isset(AVATAR_MIMES[$mime])) {
        sendResponse(false, 'Only JPG, JPEG, and PNG images are allowed.', [], 422);
    }
    if (@getimagesize($f['tmp_name']) === false) {
        sendResponse(false, 'That file is not a valid image.', [], 422);
    }

    $data = file_get_contents($f['tmp_name']);
    if ($data === false) {
        sendResponse(false, 'Could not read the uploaded image.', [], 500);
    }

    $bucket   = profile_picture_bucket();
    $filename = bin2hex(random_bytes(16)) . '.' . AVATAR_MIMES[$mime];

    [$status, $resp] = supabase_storage_request('POST', 'object/' . $bucket . '/' . $filename, $data, $mime);
    unset($data);

    if ($status !== 200 && $status !== 201) {
        error_log("Supabase avatar upload failed (HTTP {$status}) for {$bucket}/{$filename}: " . substr($resp, 0, 300));
        sendResponse(false, 'Could not upload profile photo to storage. Please try again.', [], 502);
    }

    $publicUrl = rtrim(SUPABASE_URL, '/') . '/storage/v1/object/public/' . $bucket . '/' . $filename;

    $oldRow = db_one('SELECT Profile_Picture FROM user_table WHERE User_ID = ?', 'i', [$userId]);
    $oldUrl = $oldRow['Profile_Picture'] ?? null;

    try {
        db_exec('UPDATE user_table SET Profile_Picture = ? WHERE User_ID = ?', 'si', [$publicUrl, $userId]);
    } catch (Throwable $e) {
        delete_old_avatar($publicUrl);
        throw $e;
    }

    if ($oldUrl && $oldUrl !== $publicUrl) {
        delete_old_avatar($oldUrl);
    }

    log_activity('avatar_updated', 'user', $userId, 'Updated profile photo');
    sendResponse(true, 'Profile photo updated.', ['url' => $publicUrl]);
}

function handle_remove_avatar(int $userId): void {
    $oldRow = db_one('SELECT Profile_Picture FROM user_table WHERE User_ID = ?', 'i', [$userId]);
    $oldUrl = $oldRow['Profile_Picture'] ?? null;

    db_exec('UPDATE user_table SET Profile_Picture = NULL WHERE User_ID = ?', 'i', [$userId]);
    delete_old_avatar($oldUrl);

    log_activity('avatar_removed', 'user', $userId, 'Removed profile photo');
    sendResponse(true, 'Profile photo removed.', ['url' => DEFAULT_AVATAR]);
}

function handle_save_preferences(int $userId): void {
    $existing = get_user_preferences($userId);
    $email = strtolower(gv($_POST, 'email'));
    $hasPhone = array_key_exists('phone', $_POST);
    $phone = $hasPhone ? trim((string)$_POST['phone']) : $existing['contact_number'];
    $errors = [];

    // Optional email update if provided and changed
    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors['email'] = 'Please enter a valid email address.';
        } else {
            $so = so();
            $encEmail = enc($so, $email);
            $dup = db_one('SELECT User_ID FROM user_table WHERE Email = ? AND User_ID <> ? LIMIT 1', 'si', [$encEmail, $userId]);
            if ($dup) {
                $errors['email'] = 'That email address is already in use.';
            } else {
                db_exec('UPDATE user_table SET Email = ? WHERE User_ID = ?', 'si', [$encEmail, $userId]);
                $_SESSION['email'] = $email;
            }
        }
    }

    // Contact number validation (Philippine mobile numbers)
    $cleanPhone = preg_replace('/[\s\-()]/', '', $phone);
    if ($cleanPhone !== '' && !preg_match('/^(09|\+639|639)\d{9}$/', $cleanPhone)) {
        $errors['phone'] = 'Use a PH mobile number like 09123456789 or +639123456789.';
    }

    if ($errors) {
        sendResponse(false, reset($errors), ['errors' => $errors], 422);
    }

    // Parse notification preferences and reading/appearance preferences
    $notify = gv($_POST, 'notify');
    if (is_string($notify)) {
        $notify = json_decode($notify, true) ?: [];
    } elseif (!is_array($notify)) {
        $notify = [];
    }

    $prefs = gv($_POST, 'prefs');
    if (is_string($prefs)) {
        $prefs = json_decode($prefs, true) ?: [];
    } elseif (!is_array($prefs)) {
        $prefs = [];
    }

    $favGenres = $prefs['genres'] ?? $prefs['favorite_genres'] ?? $existing['favorite_genres'];
    if (!is_array($favGenres) && is_string($favGenres)) {
        $favGenres = json_decode($favGenres, true) ?: array_filter(array_map('trim', explode(',', $favGenres)));
    }

    $toSave = [
        'contact_number'       => $phone,
        'notify_due'           => array_key_exists('due', $notify) ? (!empty($notify['due']) ? 1 : 0) : (int)$existing['notify_due'],
        'notify_due_days'      => (string)($notify['dueDays'] ?? $notify['due_days'] ?? $existing['notify_due_days']),
        'notify_reservations'  => array_key_exists('reservations', $notify) ? (!empty($notify['reservations']) ? 1 : 0) : (int)$existing['notify_reservations'],
        'notify_announcements' => array_key_exists('announcements', $notify) ? (!empty($notify['announcements']) ? 1 : 0) : (int)$existing['notify_announcements'],
        'notify_activity'      => array_key_exists('activity', $notify) ? (!empty($notify['activity']) ? 1 : 0) : (int)$existing['notify_activity'],
        'notify_email'         => array_key_exists('email', $notify) ? (!empty($notify['email']) ? 1 : 0) : (int)$existing['notify_email'],
        'theme'                => (string)($prefs['theme'] ?? $existing['theme']),
        'text_size'            => (string)($prefs['textSize'] ?? $prefs['text_size'] ?? $existing['text_size']),
        'reduce_motion'        => array_key_exists('reduceMotion', $prefs)
            ? !empty($prefs['reduceMotion'])
            : (array_key_exists('reduce_motion', $prefs) ? !empty($prefs['reduce_motion']) : $existing['reduce_motion']),
        'library_view'         => (string)($prefs['libraryView'] ?? $prefs['library_view'] ?? $existing['library_view']),
        'favorite_genres'      => is_array($favGenres) ? $favGenres : $existing['favorite_genres'],
        'track_reading'        => array_key_exists('trackReading', $prefs)
            ? (!empty($prefs['trackReading']) ? 1 : 0)
            : (array_key_exists('track_reading', $prefs) ? (!empty($prefs['track_reading']) ? 1 : 0) : (int)$existing['track_reading']),
    ];

    save_user_preferences($userId, $toSave);
    log_activity('settings_updated', 'user', $userId, 'Saved profile settings and preferences');

    sendResponse(true, 'Settings saved.', [
        'email'       => $email !== '' ? $email : null,
        'preferences' => get_user_preferences($userId, true)
    ]);
}

function handle_track_reading_time(int $userId): void {
    $seconds = (int)gv($_POST, 'seconds');
    if ($seconds <= 0) {
        sendResponse(true, 'No reading time to record.', ['total_seconds' => get_user_preferences($userId)['reading_seconds']]);
    }
    $seconds = min($seconds, 3600);
    $total = add_user_reading_seconds($userId, $seconds);
    sendResponse(true, 'Reading time recorded.', ['total_seconds' => $total]);
}

function handle_change_password(int $userId): void {
    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($current === '') {
        sendResponse(false, 'Please enter your current password.', [], 422);
    }
    if (mb_strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        sendResponse(false, 'New password must be at least 8 characters with uppercase, lowercase, and a number.', [], 422);
    }
    if (!hash_equals($new, $confirm)) {
        sendResponse(false, 'New password and confirmation do not match.', [], 422);
    }

    $row = db_one('SELECT Password FROM user_table WHERE User_ID = ?', 'i', [$userId]);
    if (!$row || !password_verify($current, $row['Password'])) {
        sendResponse(false, 'Current password is incorrect.', [], 403);
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    db_exec('UPDATE user_table SET Password = ? WHERE User_ID = ?', 'si', [$hash, $userId]);
    db_exec('DELETE FROM user_tokens WHERE user_id = ?', 'i', [$userId]);

    log_activity('password_changed', 'user', $userId, 'Changed account password');
    sendResponse(true, 'Password updated successfully.');
}

function handle_update_system_settings(int $adminId): void {
    $current = settings_all(true);
    $changes = [];
    $errors  = [];

    foreach (SETTINGS_SPEC as $key => $spec) {
        if (!array_key_exists($key, $_POST)) {
            continue;
        }
        $value = parse_setting($key, $_POST[$key]);
        if ($value === null) {
            $errors[$key] = $spec[1] === 'bool'
                ? 'Choose on or off.'
                : "Enter a number from {$spec[2]} to {$spec[3]}" . ($spec[1] === 'money' ? ' (up to 2 decimals).' : '.');
            continue;
        }
        if ((string)$value !== (string)$current[$key]) {
            $changes[$key] = $value;
        }
    }

    if ($errors) {
        sendResponse(false, 'Some settings are not valid. Nothing was saved.', ['errors' => $errors], 422);
    }
    if (!$changes) {
        sendResponse(true, 'No changes to save.', ['settings' => settings_payload()]);
    }

    db()->begin_transaction();
    foreach ($changes as $key => $value) {
        $stored = SETTINGS_SPEC[$key][1] === 'money' ? number_format((float)$value, 2, '.', '') : (string)$value;
        db_exec(
            "INSERT INTO system_settings (Setting_Key, Setting_Value, Updated_By) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE Setting_Value = VALUES(Setting_Value), Updated_By = VALUES(Updated_By)",
            'ssi',
            [$key, $stored, $adminId]
        );
        log_activity('setting_changed', 'setting', null, "{$key}: {$current[$key]} -> {$stored}");
    }
    db()->commit();

    $n = count($changes);
    sendResponse(true, $n === 1 ? '1 setting saved.' : "{$n} settings saved.", ['settings' => settings_payload()]);
}