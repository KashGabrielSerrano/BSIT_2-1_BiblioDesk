<?php
/**
 * backend/announcement_controller.php — announcements API for staff (Librarian + Admin, decision D3).
 * Used by frontend/js/admin_announcement.js and the overview quick action (admin_quickactions.js).
 *
 *   GET  ?action=list                                         every announcement (all statuses)
 *   POST action=save        [id] title body category status [date] [audience]   create / update
 *   POST action=set_status  id status=published|draft         "Publish now" / "Move to draft"
 *   POST action=delete      id
 *
 * status: published | scheduled | draft  (scheduled needs date = Y-m-d, today or later, local time).
 * Publishing notifies the audience (core/announcements.php); draft/delete removes those notifications.
 */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/announcements.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user = require_role_api(['Librarian', 'Admin'], 'Only librarians and admins can manage announcements.');
require_csrf();

$action = gv($_POST, 'action') ?: gv($_GET, 'action');
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if (in_array($action, ['save', 'set_status', 'delete'], true) && !$is_post) {
    sendResponse(false, 'This action requires POST.', [], 405);
}

function find_announcement(int $id): array {
    $row = $id > 0 ? db_one("SELECT * FROM announcement_table WHERE Announcement_ID = ?", 'i', [$id]) : null;
    if (!$row) {
        sendResponse(false, 'Announcement not found.', [], 404);
    }
    return $row;
}

/** After a status change: notify (published), or take notifications back (draft / scheduled). */
function sync_notifications(array $row): void {
    if ($row['Status'] === 'Published') {
        // an edit of an already-published announcement updates what people see in the bell
        db_exec("UPDATE notification_table SET Title = ?, Message = ?, Type = ? WHERE Link = ?", 'ssss',
                [mb_substr($row['Title'], 0, 120), mb_substr(trim(preg_replace('/\s+/', ' ', $row['Body'])), 0, 255),
                 'announcement:' . $row['Category'], announcement_link((int)$row['Announcement_ID'])]);
        notify_announcement($row);
    } else {
        unnotify_announcement((int)$row['Announcement_ID']);
    }
}

switch ($action) {
    case 'list':
        publish_due_announcements();
        $rows = db_all(db(), "SELECT * FROM announcement_table ORDER BY COALESCE(Publish_At, Updated_At) DESC, Announcement_ID DESC");
        sendResponse(true, 'Announcements loaded.', ['items' => array_map('announcement_item', $rows)]);

    case 'save':
        $id       = (int)gv($_POST, 'id');
        $title    = gv($_POST, 'title');
        $body     = gv($_POST, 'body');
        $category = gv($_POST, 'category');
        $status   = ucfirst(strtolower(gv($_POST, 'status')));
        $audience = gv($_POST, 'audience') ?: 'All';
        $date     = gv($_POST, 'date');

        $errors = [];
        if ($title === '' || mb_strlen($title) > 80)  $errors['title'] = 'Please enter a title (up to 80 characters).';
        if ($body === '' || mb_strlen($body) > 2000)  $errors['body']  = 'Please enter the message (up to 2,000 characters).';
        if (!in_array($category, ANNOUNCEMENT_CATEGORIES, true)) $errors['category'] = 'Please choose a category.';
        if (!in_array($status, ANNOUNCEMENT_STATUSES, true))     $errors['status']   = 'Invalid status.';
        if (!in_array($audience, ANNOUNCEMENT_AUDIENCES, true))  $errors['audience'] = 'Invalid audience.';

        $publish_at = null;
        if ($status === 'Scheduled') {
            $tz    = new DateTimeZone(APP_TIMEZONE);
            $local = DateTime::createFromFormat('!Y-m-d', $date, $tz);
            if (!$local || $local->format('Y-m-d') !== $date) {
                $errors['date'] = 'Please choose a publish date.';
            } elseif ($date < (new DateTime('now', $tz))->format('Y-m-d')) {
                $errors['date'] = 'The publish date cannot be in the past.';
            } else {
                // published at the start of that day, school time
                $publish_at = $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            }
        }
        if ($errors) {
            sendResponse(false, reset($errors), ['errors' => $errors], 422);
        }

        $existing = $id > 0 ? find_announcement($id) : null;
        if ($status === 'Published') {
            // keep the original publish time when an already-published announcement is edited
            $publish_at = ($existing && $existing['Status'] === 'Published' && $existing['Publish_At'])
                ? $existing['Publish_At'] : gmdate('Y-m-d H:i:s');
        }

        if ($existing) {
            db_exec("UPDATE announcement_table SET Title = ?, Body = ?, Category = ?, Audience = ?, Status = ?, Publish_At = ?
                      WHERE Announcement_ID = ?", 'ssssssi', [$title, $body, $category, $audience, $status, $publish_at, $id]);
            log_activity('announcement_updated', 'announcement', $id, "{$status}: {$title}");
        } else {
            db_exec("INSERT INTO announcement_table (Title, Body, Category, Audience, Status, Publish_At, Created_By)
                     VALUES (?, ?, ?, ?, ?, ?, ?)", 'ssssssi',
                    [$title, $body, $category, $audience, $status, $publish_at, $user['user_id']]);
            $id = (int)db()->insert_id;
            log_activity('announcement_created', 'announcement', $id, "{$status}: {$title}");
        }

        $row = find_announcement($id);
        sync_notifications($row);
        publish_due_announcements();   // a schedule for today publishes right away
        $row = find_announcement($id);
        $msg = ['Draft' => 'Draft saved.', 'Scheduled' => 'Announcement scheduled.', 'Published' => 'Announcement published.'][$row['Status']];
        sendResponse(true, $msg, ['item' => announcement_item($row)]);

    case 'set_status':
        $row    = find_announcement((int)gv($_POST, 'id'));
        $status = gv($_POST, 'status') === 'published' ? 'Published' : (gv($_POST, 'status') === 'draft' ? 'Draft' : '');
        if ($status === '') {
            sendResponse(false, 'Invalid status.', [], 400);
        }
        $publish_at = $status === 'Published' ? gmdate('Y-m-d H:i:s') : $row['Publish_At'];
        db_exec("UPDATE announcement_table SET Status = ?, Publish_At = ? WHERE Announcement_ID = ?", 'ssi',
                [$status, $publish_at, (int)$row['Announcement_ID']]);
        $row = find_announcement((int)$row['Announcement_ID']);
        sync_notifications($row);
        log_activity($status === 'Published' ? 'announcement_published' : 'announcement_unpublished',
                     'announcement', (int)$row['Announcement_ID'], $row['Title']);
        sendResponse(true, $status === 'Published' ? 'Announcement published.' : 'Moved to draft.', ['item' => announcement_item($row)]);

    case 'delete':
        $row = find_announcement((int)gv($_POST, 'id'));
        unnotify_announcement((int)$row['Announcement_ID']);
        db_exec("DELETE FROM announcement_table WHERE Announcement_ID = ?", 'i', [(int)$row['Announcement_ID']]);
        log_activity('announcement_deleted', 'announcement', (int)$row['Announcement_ID'], $row['Title']);
        sendResponse(true, 'Announcement deleted.');

    default:
        sendResponse(false, 'Invalid action.', [], 400);
}
