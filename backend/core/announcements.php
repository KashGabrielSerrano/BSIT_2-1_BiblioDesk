<?php
const ANNOUNCEMENT_CATEGORIES = ['System Update', 'Library Policy', 'Events', 'Collections'];
const ANNOUNCEMENT_STATUSES   = ['Draft', 'Scheduled', 'Published'];
const ANNOUNCEMENT_AUDIENCES  = ['All', 'Students', 'Staff'];

/** Roles that may see an announcement for this audience. */
function announcement_roles(string $audience): array {
    return match ($audience) {
        'Students' => ['Student'],
        'Staff'    => ['Librarian', 'Admin'],
        default    => ['Student', 'Librarian', 'Admin'],
    };
}

/** Audiences a role may see. */
function audiences_for_role(string $role): array {
    return $role === 'Student' ? ['All', 'Students'] : ['All', 'Staff'];
}

/**
 * DB row -> the item shape frontend/js/admin_announcement.js works with:
 * { id, title, body, category, audience, status: 'published'|'scheduled'|'draft', date: 'Y-m-d' (local) }
 */
function announcement_item(array $r): array {
    $when = $r['Status'] === 'Draft' ? ($r['Updated_At'] ?? $r['Created_At']) : ($r['Publish_At'] ?? $r['Created_At']);
    return [
        'id'       => (string)$r['Announcement_ID'],
        'title'    => $r['Title'],
        'body'     => $r['Body'],
        'category' => $r['Category'],
        'audience' => $r['Audience'],
        'status'   => strtolower($r['Status']),
        'date'     => local_date($when, 'Y-m-d'),
    ];
}

function announcement_link(int $id): string {
    return '#announcement-' . $id;
}

/** Notify the announcement's audience (once; skipped if its notifications already exist). */
function notify_announcement(array $r): void {
    $id = (int)$r['Announcement_ID'];
    if (db_one("SELECT 1 FROM notification_table WHERE Link = ? LIMIT 1", 's', [announcement_link($id)])) {
        return;
    }
    $body = trim(preg_replace('/\s+/', ' ', $r['Body']));
    notify_roles(announcement_roles($r['Audience']), 'announcement:' . $r['Category'], $r['Title'],
                 $body, announcement_link($id), (int)($r['Created_By'] ?? 0));
}

/** Remove an announcement's notifications (when it is moved to Draft or deleted). */
function unnotify_announcement(int $id): void {
    db_exec("DELETE FROM notification_table WHERE Link = ?", 's', [announcement_link($id)]);
}

/**
 * Publish every Scheduled announcement whose time has come, and notify its audience.
 * Called lazily (no cron on InfinityFree) whenever announcements or notifications are read.
 */
function publish_due_announcements(): void {
    $due = db_all(db(), "SELECT * FROM announcement_table WHERE Status = 'Scheduled' AND Publish_At <= UTC_TIMESTAMP()");
    foreach ($due as $r) {
        db_exec("UPDATE announcement_table SET Status = 'Published' WHERE Announcement_ID = ? AND Status = 'Scheduled'",
                'i', [(int)$r['Announcement_ID']]);
        $r['Status'] = 'Published';
        notify_announcement($r);
    }
}

/** Latest published announcements a role may see (newest first), as announcement_item()s. */
function published_announcements_for(string $role, int $limit = 5, ?string $category = null, bool $exclude_category = false): array {
    publish_due_announcements();
    $aud   = audiences_for_role($role);
    $sql   = "SELECT * FROM announcement_table WHERE Status = 'Published' AND Audience IN (?, ?)";
    $types = 'ss';
    $args  = $aud;
    if ($category !== null) {
        $sql   .= $exclude_category ? " AND Category <> ?" : " AND Category = ?";
        $types .= 's';
        $args[] = $category;
    }
    $sql   .= " ORDER BY Publish_At DESC, Announcement_ID DESC LIMIT ?";
    $types .= 'i';
    $args[] = $limit;
    return array_map('announcement_item', db_all(db(), $sql, $types, $args));
}
