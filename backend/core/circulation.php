<?php
/**
 * backend/core/circulation.php — borrowing, reservations and fines for PHYSICAL books (D1, D2).
 * Needs core/bootstrap.php first. Used by circulation_controller.php, notification_controller.php,
 * librarymanagement_controller.php (catalog availability) and the student pages.
 *
 * Loans:        Requested -> Borrowed (staff approve at the counter) -> Returned
 *               Requested -> Rejected (staff) | Cancelled (student)
 *               "Overdue" is not stored: Status = 'Borrowed' AND Due_At has passed.
 * Reservations: only when no copy is free. Pending (queue) -> Ready (a copy is held until Hold_Until)
 *               -> Fulfilled (picked up: becomes a Borrowed loan) | Expired | Cancelled | Rejected
 * Copies:       available = Total_Copies - Borrowed loans - Ready reservations (always calculated).
 * Fines:        Overdue  = min(max_fine_per_loan, max(0, days late - grace_days) x fine_per_day), on return
 *               Lost     = lost_book_fee (the copy is removed from Total_Copies)
 *               Damaged  = amount typed by staff.        Paid / Waived at the counter.
 * Dates:        stored in UTC; due dates end at 23:59:59 school time (APP_TIMEZONE).
 * No cron (InfinityFree): circulation_upkeep() runs lazily when pages / the bell load.
 */

const LOAN_ACTIVE = ['Requested', 'Borrowed'];
const RES_ACTIVE  = ['Pending', 'Ready'];

/* ----- time ---------------------------------------------------------------- */

function utc_now(): string {
    return gmdate('Y-m-d H:i:s');
}

/** End of the local school day, $days days from today, as a UTC 'Y-m-d H:i:s'. */
function end_of_local_day_plus(int $days): string {
    $d = new DateTime('now', new DateTimeZone(APP_TIMEZONE));
    $d->modify("+{$days} days")->setTime(23, 59, 59);
    return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

/** Whole calendar days (school time) from $due_utc to $when_utc; 0 if not late. */
function days_late(string $due_utc, ?string $when_utc = null): int {
    $tz   = new DateTimeZone(APP_TIMEZONE);
    $due  = (new DateTime($due_utc, new DateTimeZone('UTC')))->setTimezone($tz)->setTime(0, 0);
    $when = (new DateTime($when_utc ?? utc_now(), new DateTimeZone('UTC')))->setTimezone($tz)->setTime(0, 0);
    return $when > $due ? (int)$due->diff($when)->days : 0;
}

/** Overdue fine for a loan returned (or checked) at $when_utc, using the current settings. */
function overdue_fine(string $due_utc, ?string $when_utc = null): float {
    $late = days_late($due_utc, $when_utc) - (int)get_setting('grace_days');
    if ($late <= 0) {
        return 0.0;
    }
    return round(min((float)get_setting('max_fine_per_loan'), $late * (float)get_setting('fine_per_day')), 2);
}

function peso(float $amount): string {
    return '₱' . number_format($amount, 2);
}

/* ----- copies -------------------------------------------------------------- */

function available_copies(int $book_id): int {
    $r = db_one(
        "SELECT b.Total_Copies
                - (SELECT COUNT(*) FROM loan_table l WHERE l.Book_ID = b.Book_ID AND l.Status = 'Borrowed')
                - (SELECT COUNT(*) FROM reservation_table r WHERE r.Book_ID = b.Book_ID AND r.Status = 'Ready') AS avail
           FROM book_table b WHERE b.Book_ID = ?", 'i', [$book_id]);
    return max(0, (int)($r['avail'] ?? 0));
}

/** Book_ID => [total, available] for many books at once (catalog lists). */
function availability_map(array $book_ids): array {
    $book_ids = array_values(array_unique(array_map('intval', $book_ids)));
    if (!$book_ids) {
        return [];
    }
    $marks = implode(',', array_fill(0, count($book_ids), '?'));
    $rows = db_all(db(),
        "SELECT b.Book_ID, b.Total_Copies,
                (SELECT COUNT(*) FROM loan_table l WHERE l.Book_ID = b.Book_ID AND l.Status = 'Borrowed') AS borrowed,
                (SELECT COUNT(*) FROM reservation_table r WHERE r.Book_ID = b.Book_ID AND r.Status = 'Ready') AS held
           FROM book_table b WHERE b.Book_ID IN ($marks)", str_repeat('i', count($book_ids)), $book_ids);
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['Book_ID']] = [(int)$r['Total_Copies'], max(0, (int)$r['Total_Copies'] - (int)$r['borrowed'] - (int)$r['held'])];
    }
    return $map;
}

/* ----- student eligibility -------------------------------------------------- */

function unpaid_fines_total(int $user_id): float {
    return (float)(db_one("SELECT COALESCE(SUM(Amount), 0) AS t FROM fine_table WHERE User_ID = ? AND Status = 'Unpaid'",
                          'i', [$user_id])['t'] ?? 0);
}

/** Why this user may not borrow / reserve right now (null = no general block). */
function circulation_block_reason(int $user_id): ?string {
    if ((int)get_setting('block_if_unpaid_fines') === 1) {
        $owed = unpaid_fines_total($user_id);
        if ($owed > 0) {
            return 'You have unpaid fines (' . peso($owed) . '). Please settle them at the library counter first.';
        }
    }
    return null;
}

function active_loan_count(int $user_id): int {
    return (int)(db_one("SELECT COUNT(*) AS c FROM loan_table WHERE User_ID = ? AND Status IN ('Requested','Borrowed')",
                        'i', [$user_id])['c'] ?? 0);
}

function active_reservation_count(int $user_id): int {
    return (int)(db_one("SELECT COUNT(*) AS c FROM reservation_table WHERE User_ID = ? AND Status IN ('Pending','Ready')",
                        'i', [$user_id])['c'] ?? 0);
}

function queue_position(array $res): int {
    if ($res['Status'] !== 'Pending') {
        return 0;
    }
    return 1 + (int)(db_one(
        "SELECT COUNT(*) AS c FROM reservation_table WHERE Book_ID = ? AND Status = 'Pending'
            AND (Created_At < ? OR (Created_At = ? AND Reservation_ID < ?))",
        'issi', [(int)$res['Book_ID'], $res['Created_At'], $res['Created_At'], (int)$res['Reservation_ID']])['c'] ?? 0);
}

/**
 * Everything the book details modal needs for one book and one user:
 * physical, total, available, the user's active loan / reservation, and whether they can borrow or
 * reserve (with the reason when not).
 */
function book_circulation_status(int $book_id, int $user_id, string $role): array {
    $book = db_one("SELECT Book_ID, Title, Is_digital, Total_Copies FROM book_table WHERE Book_ID = ?", 'i', [$book_id]);
    if (!$book) {
        return ['found' => false];
    }
    $physical = $book['Is_digital'] === 'No';
    $out = ['found' => true, 'physical' => $physical, 'total' => (int)$book['Total_Copies'],
            'available' => $physical ? available_copies($book_id) : 0,
            'loan' => null, 'reservation' => null, 'can_borrow' => false, 'can_reserve' => false, 'reason' => ''];
    if (!$physical) {
        $out['reason'] = 'Digital books are read online and are not borrowed.';
        return $out;
    }

    $loan = db_one("SELECT Loan_ID, Status, Due_At FROM loan_table WHERE User_ID = ? AND Book_ID = ? AND Status IN ('Requested','Borrowed')
                    ORDER BY Loan_ID DESC LIMIT 1", 'ii', [$user_id, $book_id]);
    if ($loan) {
        $out['loan'] = ['id' => (int)$loan['Loan_ID'], 'status' => $loan['Status'],
                        'due' => local_date($loan['Due_At'], 'M d, Y'), 'overdue' => $loan['Due_At'] && $loan['Due_At'] < utc_now()];
    }
    $res = db_one("SELECT * FROM reservation_table WHERE User_ID = ? AND Book_ID = ? AND Status IN ('Pending','Ready')
                   ORDER BY Reservation_ID DESC LIMIT 1", 'ii', [$user_id, $book_id]);
    if ($res) {
        $out['reservation'] = ['id' => (int)$res['Reservation_ID'], 'status' => $res['Status'],
                               'position' => queue_position($res), 'hold_until' => local_date($res['Hold_Until'], 'M d, Y')];
    }

    if ($role !== 'Student') {
        $out['reason'] = 'Borrowing is for student accounts (you are previewing).';
    } elseif ($loan || $res) {
        $out['reason'] = '';
    } elseif ((int)$book['Total_Copies'] <= 0) {
        $out['reason'] = 'The library has no physical copy of this book yet.';
    } elseif (($block = circulation_block_reason($user_id)) !== null) {
        $out['reason'] = $block;
    } elseif ($out['available'] > 0) {
        if (active_loan_count($user_id) >= (int)get_setting('max_borrows')) {
            $out['reason'] = 'You have reached the limit of ' . get_setting('max_borrows') . ' borrowed books.';
        } else {
            $out['can_borrow'] = true;
        }
    } else {
        if (active_reservation_count($user_id) >= (int)get_setting('max_reservations')) {
            $out['reason'] = 'All copies are out, and you have reached the limit of ' . get_setting('max_reservations') . ' reservations.';
        } else {
            $out['can_reserve'] = true;
        }
    }
    return $out;
}

/* ----- reservation queue --------------------------------------------------- */

/** While copies are free and people are waiting: oldest Pending -> Ready (held for pickup) + notify. */
function promote_reservations(int $book_id): void {
    $hold_days = (int)get_setting('reservation_hold_days');
    while (available_copies($book_id) > 0) {
        $next = db_one("SELECT r.Reservation_ID, r.User_ID, b.Title FROM reservation_table r JOIN book_table b ON b.Book_ID = r.Book_ID
                         WHERE r.Book_ID = ? AND r.Status = 'Pending' ORDER BY r.Created_At, r.Reservation_ID LIMIT 1", 'i', [$book_id]);
        if (!$next) {
            return;
        }
        $until = end_of_local_day_plus($hold_days);
        db_exec("UPDATE reservation_table SET Status = 'Ready', Ready_At = UTC_TIMESTAMP(), Hold_Until = ? WHERE Reservation_ID = ? AND Status = 'Pending'",
                'si', [$until, (int)$next['Reservation_ID']]);
        notify((int)$next['User_ID'], 'reservation_ready', "Ready for pickup: {$next['Title']}",
               'Your reserved book is waiting at the library counter until ' . local_date($until, 'M d, Y') . '.', 'user_librarydb.php?view=mybooks#my-loans');
    }
}

/**
 * Lazy upkeep (no cron): expire uncollected holds, hand freed copies to the next in line, and send
 * due-soon / overdue reminders once per loan. Runs at most once per request.
 */
function circulation_upkeep(): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // 1. holds that were not picked up in time
    foreach (db_all(db(), "SELECT r.Reservation_ID, r.User_ID, r.Book_ID, b.Title FROM reservation_table r JOIN book_table b ON b.Book_ID = r.Book_ID
                            WHERE r.Status = 'Ready' AND r.Hold_Until < UTC_TIMESTAMP()") as $r) {
        db_exec("UPDATE reservation_table SET Status = 'Expired' WHERE Reservation_ID = ? AND Status = 'Ready'", 'i', [(int)$r['Reservation_ID']]);
        notify((int)$r['User_ID'], 'reservation_expired', "Reservation expired: {$r['Title']}",
               'The book was not picked up in time, so it went to the next person in line.');
    }
    // 2. free copies -> next reservation in line (covers expiries, returns and added copies)
    foreach (db_all(db(), "SELECT DISTINCT Book_ID FROM reservation_table WHERE Status = 'Pending'") as $b) {
        promote_reservations((int)$b['Book_ID']);
    }
    // 3. reminders, once each (tracked by the notification link)
    foreach (db_all(db(), "SELECT l.Loan_ID, l.User_ID, l.Due_At, b.Title FROM loan_table l JOIN book_table b ON b.Book_ID = l.Book_ID
                            WHERE l.Status = 'Borrowed' AND l.Due_At < UTC_TIMESTAMP() + INTERVAL 1 DAY") as $l) {
        $overdue = $l['Due_At'] < utc_now();
        $link = '#loan-' . $l['Loan_ID'] . ($overdue ? '-overdue' : '-due');
        if (db_one("SELECT 1 FROM notification_table WHERE User_ID = ? AND Link = ? LIMIT 1", 'is', [(int)$l['User_ID'], $link])) {
            continue;
        }
        if ($overdue) {
            notify((int)$l['User_ID'], 'loan_overdue', "Overdue: {$l['Title']}",
                   'This book was due on ' . local_date($l['Due_At'], 'M d, Y') . '. Please return it; a fine of '
                   . peso((float)get_setting('fine_per_day')) . ' per day applies.', $link);
        } else {
            notify((int)$l['User_ID'], 'loan_due_soon', "Due soon: {$l['Title']}",
                   'Please return it by ' . local_date($l['Due_At'], 'M d, Y g:i A') . '.', $link);
        }
    }
}

/* ----- shapes for the JS ---------------------------------------------------- */

function loan_item(array $r): array {
    $overdue = $r['Status'] === 'Borrowed' && $r['Due_At'] && $r['Due_At'] < utc_now();
    return [
        'id'          => (int)$r['Loan_ID'],
        'book_id'     => (int)$r['Book_ID'],
        'title'       => $r['Title'],
        'author'      => $r['Author'] ?? '',
        'status'      => $r['Status'],
        'overdue'     => $overdue,
        'days_late'   => $overdue ? days_late($r['Due_At']) : 0,
        'fine_so_far' => $overdue ? overdue_fine($r['Due_At']) : 0,   // shown live; charged on return
        'requested'   => local_date($r['Requested_At'], 'M d, Y'),
        'borrowed'    => local_date($r['Borrowed_At'] ?? null, 'M d, Y'),
        'due'         => local_date($r['Due_At'] ?? null, 'M d, Y'),
        'returned'    => local_date($r['Returned_At'] ?? null, 'M d, Y'),
        'note'        => $r['Note'] ?? '',
    ];
}
