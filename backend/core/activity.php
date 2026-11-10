<?php
const ACTIVITY_ACTIONS = [
    'role_changed'           => ['accounts', 'Role changed'],
    'account_status_changed' => ['accounts', 'Account status changed'],
    'book_added'             => ['catalog', 'Book added'],
    'book_updated'           => ['catalog', 'Book edited'],
    'book_deleted'           => ['catalog', 'Book deleted'],
    'loan_approved'          => ['circulation', 'Loan approved'],
    'loan_rejected'          => ['circulation', 'Loan request rejected'],
    'loan_returned'          => ['circulation', 'Book returned'],
    'loan_lost'              => ['circulation', 'Copy marked lost'],
    'fine_damaged'           => ['circulation', 'Damage fine added'],
    'fine_paid'              => ['circulation', 'Fine paid'],
    'fine_waived'            => ['circulation', 'Fine waived'],
    'reservation_fulfilled'  => ['circulation', 'Reservation picked up'],
    'reservation_rejected'   => ['circulation', 'Reservation rejected'],
    'report_status_changed'  => ['reports', 'Report status changed'],
    'ticket_replied'         => ['helpdesk', 'Ticket answered'],
    'ticket_closed'          => ['helpdesk', 'Ticket closed'],
    'ticket_reopened'        => ['helpdesk', 'Ticket reopened'],
    'announcement_created'   => ['announcements', 'Announcement created'],
    'announcement_updated'   => ['announcements', 'Announcement edited'],
    'announcement_published' => ['announcements', 'Announcement published'],
    'announcement_unpublished' => ['announcements', 'Announcement moved to draft'],
    'announcement_deleted'   => ['announcements', 'Announcement deleted'],
    'setting_changed'        => ['settings', 'System setting changed'],
];

/** Category => [label, badge class from adminlog.css]. */
const ACTIVITY_CATEGORIES = [
    'accounts'      => ['Accounts', 'status-badge-red'],
    'circulation'   => ['Circulation', 'status-badge-green'],
    'catalog'       => ['Catalog', 'issue-badge-blue'],
    'reports'       => ['Reports', 'issue-badge-pink'],
    'helpdesk'      => ['Help desk', 'status-badge-amber'],
    'announcements' => ['Announcements', 'status-badge-yellow'],
    'settings'      => ['Settings', 'status-badge-darkred'],
    'other'         => ['Other', 'issue-badge-blue'],
];

/** Latest activity entries, ready to show (decrypted actor name, labels, local time). */
function activity_entries(int $limit = 1000): array {
    $so = so();
    $rows = db_all(db(),
        "SELECT a.*, u.Full_Name, u.Role FROM activity_log a LEFT JOIN user_table u ON u.User_ID = a.Actor_ID
          ORDER BY a.Created_At DESC, a.Log_ID DESC LIMIT ?", 'i', [$limit]);
    return array_map(function ($r) use ($so) {
        [$cat, $label] = ACTIVITY_ACTIONS[$r['Action']] ?? ['other', ucfirst(str_replace('_', ' ', $r['Action']))];
        return [
            'id'       => (int)$r['Log_ID'],
            'label'    => $label,
            'category' => $cat,
            'details'  => (string)($r['Details'] ?? ''),
            'target'   => $r['Target_Type'] ? ucfirst($r['Target_Type']) . ($r['Target_ID'] ? ' #' . $r['Target_ID'] : '') : '',
            'actor'    => $r['Actor_ID'] ? (dec($so, $r['Full_Name'] ?? '') ?: 'Deleted user') : 'System',
            'role'     => $r['Role'] ?? '',
            'iso'      => gmdate('c', strtotime($r['Created_At'] . ' UTC')),
            'when'     => local_date($r['Created_At'], 'M d, Y g:i A'),
        ];
    }, $rows);
}

/** Summary numbers for the cards at the top of the page. */
function activity_summary(): array {
    $c = fn(string $where) => (int)(db_one("SELECT COUNT(*) AS c FROM activity_log WHERE {$where}")['c'] ?? 0);
    $today = (new DateTime('today', new DateTimeZone(APP_TIMEZONE)))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    return [
        'today'    => (int)(db_one("SELECT COUNT(*) AS c FROM activity_log WHERE Created_At >= ?", 's', [$today])['c'] ?? 0),
        'week'     => $c("Created_At >= UTC_TIMESTAMP() - INTERVAL 7 DAY"),
        'accounts' => $c("Action IN ('role_changed','account_status_changed') AND Created_At >= UTC_TIMESTAMP() - INTERVAL 30 DAY"),
        'settings' => $c("Action = 'setting_changed' AND Created_At >= UTC_TIMESTAMP() - INTERVAL 30 DAY"),
    ];
}
