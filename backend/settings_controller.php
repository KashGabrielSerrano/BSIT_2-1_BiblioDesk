<?php
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$admin = require_role_api(['Admin'], 'Only admins can change system settings.');
require_csrf();

function settings_payload(): array {
    $values = settings_all(true);
    $out = [];
    foreach (SETTINGS_SPEC as $key => [$label, $type, $min, $max, $default, $help, $group]) {
        $out[] = compact('key', 'label', 'type', 'min', 'max', 'default', 'help', 'group') + ['value' => $values[$key]];
    }
    return $out;
}

$action = gv($_POST, 'action') ?: gv($_GET, 'action');

switch ($action) {
    case 'get':
        sendResponse(true, 'Settings loaded.', ['settings' => settings_payload()]);

    case 'update':
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            sendResponse(false, 'This action requires POST.', [], 405);
        }
        $current = settings_all(true);
        $changes = [];
        $errors  = [];
        foreach (SETTINGS_SPEC as $key => $spec) {
            if (!array_key_exists($key, $_POST)) {
                continue;   // only the settings sent are changed
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
            db_exec("INSERT INTO system_settings (Setting_Key, Setting_Value, Updated_By) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE Setting_Value = VALUES(Setting_Value), Updated_By = VALUES(Updated_By)",
                    'ssi', [$key, $stored, $admin['user_id']]);
            log_activity('setting_changed', 'setting', null, "{$key}: {$current[$key]} -> {$stored}");
        }
        db()->commit();

        $n = count($changes);
        sendResponse(true, $n === 1 ? '1 setting saved.' : "{$n} settings saved.", ['settings' => settings_payload()]);

    default:
        sendResponse(false, 'Invalid action.', [], 400);
}
