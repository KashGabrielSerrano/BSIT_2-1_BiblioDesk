<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/announcements.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$user    = require_login_api();
$user_id = $user['user_id'];
require_csrf();

const NOTIF_LIMIT = 10;

$action = gv($_POST, 'action') ?: gv($_GET, 'action');

switch ($action) {
    case 'list':
        publish_due_announcements();
        $rows = db_all(db(),
            "SELECT Notification_ID, Type, Title, Message, Link, Is_Read, Created_At
               FROM notification_table WHERE User_ID = ? ORDER BY Created_At DESC, Notification_ID DESC LIMIT ?",
            'ii', [$user_id, NOTIF_LIMIT]);
        $unread = (int)(db_one("SELECT COUNT(*) AS c FROM notification_table WHERE User_ID = ? AND Is_Read = 0",
                               'i', [$user_id])['c'] ?? 0);
        $items = array_map(fn($r) => [
            'id'       => (int)$r['Notification_ID'],
            'type'     => $r['Type'],
            // announcements carry their category for the coloured dot: "announcement:Events"
            'category' => str_starts_with($r['Type'], 'announcement:') ? substr($r['Type'], 13) : null,
            'title'    => $r['Title'],
            'body'     => $r['Message'],
            'link'     => $r['Link'],
            'read'     => (bool)$r['Is_Read'],
            'date'     => gmdate('c', strtotime($r['Created_At'] . ' UTC')),
        ], $rows);
        sendResponse(true, 'Notifications loaded.', ['items' => $items, 'unread' => $unread]);

    case 'mark_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(false, 'This action requires POST.', [], 405);
        db_exec("UPDATE notification_table SET Is_Read = 1 WHERE Notification_ID = ? AND User_ID = ?",
                'ii', [(int)gv($_POST, 'id'), $user_id]);
        sendResponse(true, 'Marked as read.');

    case 'mark_all_read':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse(false, 'This action requires POST.', [], 405);
        db_exec("UPDATE notification_table SET Is_Read = 1 WHERE User_ID = ? AND Is_Read = 0", 'i', [$user_id]);
        sendResponse(true, 'All notifications marked as read.');

    default:
        sendResponse(false, 'Invalid action.', [], 400);
}
