<?php
require_once __DIR__ . '/core/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const TICKET_SUBJECTS  = ['Account / Login', 'Borrowing & Reservations', 'Digital Books / Reading', 'Fines & Payments', 'Suggestion', 'Others'];
const TICKET_STATUSES  = ['Open', 'Answered', 'Closed'];
const TICKET_COOLDOWN  = 60;   // seconds between two tickets from the same browser session
const GUEST_HOURLY_MAX = 3;    // tickets per guest email address per hour

require_csrf();
$action  = gv($_POST, 'action') ?: gv($_GET, 'action');
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if (in_array($action, ['submit', 'reply', 'set_status'], true) && !$is_post) {
    sendResponse(false, 'This action requires POST.', [], 405);
}

function ticket_item(array $r, bool $for_staff): array {
    $so = so();
    $item = [
        'id'         => (int)$r['Ticket_ID'],
        'subject'    => $r['Subject'],
        'message'    => $r['Message'],
        'status'     => $r['Status'],
        'reply'      => $r['Staff_Reply'] ?? '',
        'created_at' => local_date($r['Created_At'], 'M d, Y g:i A'),
        'replied_at' => local_date($r['Replied_At'] ?? null, 'M d, Y g:i A'),
    ];
    if ($for_staff) {
        $is_guest = empty($r['User_ID']);
        $item['from_name']  = $is_guest ? dec($so, $r['Contact_Name']) : dec($so, $r['Full_Name'] ?? '');
        $item['from_email'] = $is_guest ? dec($so, $r['Contact_Email']) : dec($so, $r['Email'] ?? '');
        $item['from_role']  = $is_guest ? 'Guest' : ($r['Role'] ?? '');
        $item['guest']      = $is_guest;
        $item['replied_by'] = $r['Replier'] ? dec($so, $r['Replier']) : '';
    }
    return $item;
}

switch ($action) {
    case 'submit':
        $user    = current_user();
        $subject = gv($_POST, 'subject');
        $message = gv($_POST, 'message');
        $errors  = [];

        if (gv($_POST, 'website') !== '') {    
            sendResponse(true, 'Thank you! Your message has been sent.');
        }
        if (!in_array($subject, TICKET_SUBJECTS, true)) $errors['subject'] = 'Please choose a subject.';
        if (mb_strlen($message) < 10 || mb_strlen($message) > 2000) $errors['message'] = 'Please write your message (10 to 2,000 characters).';

        $name = $email = null;
        if (!$user) {
            $name  = gv($_POST, 'name');
            $email = strtolower(gv($_POST, 'email'));
            if ($name === '' || mb_strlen($name) > 100) $errors['name'] = 'Please enter your name.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) $errors['email'] = 'Please enter a valid email address.';
        }
        if ($errors) {
            sendResponse(false, reset($errors), ['errors' => $errors], 422);
        }

        $last = (int)($_SESSION['last_ticket_at'] ?? 0);
        if (time() - $last < TICKET_COOLDOWN) {
            sendResponse(false, 'Please wait a minute before sending another message.', [], 429);
        }
        $enc_name  = $name !== null ? enc(so(), $name) : null;
        $enc_email = $email !== null ? enc(so(), $email) : null;
        if (!$user) {
            $recent = (int)(db_one("SELECT COUNT(*) AS c FROM helpdesk_ticket_table
                                     WHERE Contact_Email = ? AND Created_At > UTC_TIMESTAMP() - INTERVAL 1 HOUR",
                                   's', [$enc_email])['c'] ?? 0);
            if ($recent >= GUEST_HOURLY_MAX) {
                sendResponse(false, 'You have sent several messages already. Please wait before sending more.', [], 429);
            }
        }

        db_exec("INSERT INTO helpdesk_ticket_table (User_ID, Contact_Name, Contact_Email, Subject, Message) VALUES (?, ?, ?, ?, ?)",
                'issss', [$user['user_id'] ?? null, $enc_name, $enc_email, $subject, $message]);
        $id = (int)db()->insert_id;
        $_SESSION['last_ticket_at'] = time();

        $from = $user ? (current_profile()['full_name'] ?? 'A user') : $name;
        notify_roles(['Librarian', 'Admin'], 'ticket', "New helpdesk message: {$subject}",
                     "From {$from}: " . mb_substr(preg_replace('/\s+/', ' ', $message), 0, 180), 'helpdesk.php#tickets');
        sendResponse(true, $user ? 'Thank you! Your message has been sent. You will see our reply under "My messages".'
                                 : 'Thank you! Your message has been sent. We will reply to your email.', ['id' => $id]);

    case 'my_tickets':
        $user = require_login_api();
        $rows = db_all(db(), "SELECT * FROM helpdesk_ticket_table WHERE User_ID = ? ORDER BY Created_At DESC, Ticket_ID DESC LIMIT 20",
                       'i', [$user['user_id']]);
        sendResponse(true, 'Tickets loaded.', ['items' => array_map(fn($r) => ticket_item($r, false), $rows)]);

    case 'list':
        require_role_api(['Librarian', 'Admin'], 'Only librarians and admins can view the helpdesk inbox.');
        $status = gv($_GET, 'status');
        $where  = in_array($status, TICKET_STATUSES, true) ? 'WHERE t.Status = ?' : '';
        $rows = db_all(db(),
            "SELECT t.*, u.Full_Name, u.Email, u.Role, r.Full_Name AS Replier
               FROM helpdesk_ticket_table t
               LEFT JOIN user_table u ON u.User_ID = t.User_ID
               LEFT JOIN user_table r ON r.User_ID = t.Replied_By
               {$where}
              ORDER BY FIELD(t.Status, 'Open', 'Answered', 'Closed'), t.Created_At DESC LIMIT 100",
            $where ? 's' : '', $where ? [$status] : []);
        $counts = ['Open' => 0, 'Answered' => 0, 'Closed' => 0];
        foreach (db_all(db(), "SELECT Status, COUNT(*) AS c FROM helpdesk_ticket_table GROUP BY Status") as $c) {
            $counts[$c['Status']] = (int)$c['c'];
        }
        sendResponse(true, 'Tickets loaded.', ['items' => array_map(fn($r) => ticket_item($r, true), $rows), 'counts' => $counts]);

    case 'reply':
        $staff = require_role_api(['Librarian', 'Admin'], 'Only librarians and admins can reply.');
        $id    = (int)gv($_POST, 'id');
        $reply = gv($_POST, 'reply');
        $t     = db_one("SELECT * FROM helpdesk_ticket_table WHERE Ticket_ID = ?", 'i', [$id]);
        if (!$t) sendResponse(false, 'Ticket not found.', [], 404);
        if (mb_strlen($reply) < 2 || mb_strlen($reply) > 2000) {
            sendResponse(false, 'Please write a reply (up to 2,000 characters).', ['errors' => ['reply' => 'Please write a reply.']], 422);
        }
        if (!empty($t['Staff_Reply'])) {
            sendResponse(false, 'This ticket already has a reply (one reply per ticket).', [], 409);
        }
        db_exec("UPDATE helpdesk_ticket_table SET Staff_Reply = ?, Status = 'Answered', Replied_By = ?, Replied_At = UTC_TIMESTAMP()
                  WHERE Ticket_ID = ?", 'sii', [$reply, $staff['user_id'], $id]);
        if (!empty($t['User_ID'])) {
            notify((int)$t['User_ID'], 'ticket', "The library replied: {$t['Subject']}",
                   mb_substr(preg_replace('/\s+/', ' ', $reply), 0, 200), 'contactsupport.php#my-messages');
        }
        log_activity('ticket_replied', 'ticket', $id, $t['Subject']);
        sendResponse(true, !empty($t['User_ID']) ? 'Reply sent. The user was notified.'
                                                : 'Reply saved. This is a guest: use "Reply by email" to send it.');

    case 'set_status':
        require_role_api(['Librarian', 'Admin'], 'Only librarians and admins can change tickets.');
        $id     = (int)gv($_POST, 'id');
        $status = gv($_POST, 'status');
        $t      = db_one("SELECT Subject, Staff_Reply FROM helpdesk_ticket_table WHERE Ticket_ID = ?", 'i', [$id]);
        if (!$t) sendResponse(false, 'Ticket not found.', [], 404);
        if (!in_array($status, ['Closed', 'Open'], true)) sendResponse(false, 'Invalid status.', [], 400);
        if ($status === 'Open' && !empty($t['Staff_Reply'])) $status = 'Answered';   // reopening keeps the reply
        db_exec("UPDATE helpdesk_ticket_table SET Status = ? WHERE Ticket_ID = ?", 'si', [$status, $id]);
        log_activity($status === 'Closed' ? 'ticket_closed' : 'ticket_reopened', 'ticket', $id, $t['Subject']);
        sendResponse(true, $status === 'Closed' ? 'Ticket closed.' : 'Ticket reopened.', ['status' => $status]);

    default:
        sendResponse(false, 'Invalid action.', [], 400);
}
