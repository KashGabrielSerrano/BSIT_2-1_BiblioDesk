<?php
const BOOK_CATEGORY_LIST = ['Educational', 'Non-Educational', 'Research Articles'];

/* ----------------------------- student overview ----------------------------- */

function student_dashboard(int $user_id): array {
    $c = fn(string $sql) => (int)(db_one($sql, 'i', [$user_id])['c'] ?? 0);
    $stats = [
        'total_borrowed' => $c("SELECT COUNT(*) AS c FROM loan_table WHERE User_ID = ? AND Status IN ('Borrowed','Returned')"),
        'current'        => $c("SELECT COUNT(*) AS c FROM loan_table WHERE User_ID = ? AND Status = 'Borrowed'"),
        // due within the next 7 days, overdue ones included (they need returning too)
        'due_week'       => $c("SELECT COUNT(*) AS c FROM loan_table WHERE User_ID = ? AND Status = 'Borrowed' AND Due_At <= UTC_TIMESTAMP() + INTERVAL 7 DAY"),
        'finished'       => $c("SELECT COUNT(*) AS c FROM user_reading_progress WHERE User_ID = ? AND Is_Finished = 1"),
    ];
    // Recent activity = the user's latest notifications (loans, replies, reports, announcements...)
    $activity = array_map(fn($r) => ['title' => $r['Title'], 'when' => local_date($r['Created_At'], 'M d')],
        db_all(db(), "SELECT Title, Created_At FROM notification_table WHERE User_ID = ? ORDER BY Created_At DESC, Notification_ID DESC LIMIT 4",
               'i', [$user_id]));
    $bookmarks = db_all(db(), "SELECT b.Book_ID, b.Title, b.Cover_Image FROM user_bookmarks ub JOIN book_table b ON b.Book_ID = ub.Book_ID
                                WHERE ub.User_ID = ? ORDER BY ub.Created_At DESC LIMIT 6", 'i', [$user_id]);
    return compact('stats', 'activity', 'bookmarks');
}

/* ------------------------------ staff overview ------------------------------ */

function staff_dashboard(string $role): array {
    $one = fn(string $sql) => (int)(db_one($sql)['c'] ?? 0);

    $physical_total = $one("SELECT COALESCE(SUM(Total_Copies), 0) AS c FROM book_table WHERE Is_digital = 'No'");
    $digital_titles = $one("SELECT COUNT(*) AS c FROM book_table WHERE Is_digital = 'Yes'");
    $borrowed       = $one("SELECT COUNT(*) AS c FROM loan_table WHERE Status = 'Borrowed'");
    $held           = $one("SELECT COUNT(*) AS c FROM reservation_table WHERE Status = 'Ready'");

    $stats = [
        'users'        => $one("SELECT COUNT(*) AS c FROM user_table WHERE Role = 'Student' AND Account_Status = 'Active'"),
        // inventory = physical copies + digital titles (each digital title is always available)
        'inventory'    => $physical_total + $digital_titles,
        'borrows'      => $borrowed,
        'available'    => max(0, $physical_total - $borrowed - $held) + $digital_titles,
        'librarians'   => $one("SELECT COUNT(*) AS c FROM user_table WHERE Role = 'Librarian' AND Account_Status = 'Active'"),
        'registration' => $one("SELECT COUNT(*) AS c FROM user_table WHERE Role = 'Student' AND Account_Status = 'Pending'"),
        'reservations' => $one("SELECT COUNT(*) AS c FROM reservation_table WHERE Status IN ('Pending','Ready')"),
        'total_borrows'=> $one("SELECT COUNT(*) AS c FROM loan_table WHERE Status IN ('Borrowed','Returned')"),
    ];

    return [
        'stats'   => $stats,
        'popular' => popular_categories_this_month(),
        'users'   => dashboard_users($role),
        'logs'    => dashboard_logs(),
    ];
}

/**
 * Category popularity for the current month (school time): books borrowed per category.
 * With no borrowing yet this month it falls back to books opened in the reader, so the chart is
 * never empty on a digital-only library. All 3 real categories are always listed.
 */
function popular_categories_this_month(): array {
    $start = (new DateTime('first day of this month 00:00', new DateTimeZone(APP_TIMEZONE)))
        ->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $count = function (string $sql) use ($start): array {
        $out = array_fill_keys(BOOK_CATEGORY_LIST, 0);
        foreach (db_all(db(), $sql, 's', [$start]) as $r) {
            if (isset($out[$r['Category']])) $out[$r['Category']] = (int)$r['c'];
        }
        return $out;
    };
    $metric  = 'Books borrowed';
    $counts  = $count("SELECT b.Category, COUNT(*) AS c FROM loan_table l JOIN book_table b ON b.Book_ID = l.Book_ID
                        WHERE l.Borrowed_At >= ? GROUP BY b.Category");
    if (array_sum($counts) === 0) {
        $metric = 'Books opened in the reader';
        $counts = $count("SELECT b.Category, COUNT(*) AS c FROM user_reading_history h JOIN book_table b ON b.Book_ID = h.Book_ID
                           WHERE h.Last_Opened >= ? GROUP BY b.Category");
    }
    $data = [];
    foreach ($counts as $name => $n) {
        $data[] = ['name' => $name, 'count' => $n];
    }
    return ['metric' => $metric, 'month' => local_date(gmdate('Y-m-d H:i:s'), 'F Y'), 'data' => $data];
}

/** Accounts for the "View Users" quick action (Admins also see librarians), newest first. */
function dashboard_users(string $role): array {
    $roles = $role === 'Admin' ? "('Student','Librarian','Admin')" : "('Student')";
    $so = so();
    return array_map(fn($r) => [
        'name'   => dec($so, $r['Full_Name']) ?: 'Unnamed',
        'email'  => dec($so, $r['Email']),
        'role'   => $r['Role'],
        'status' => $r['Account_Status'],
    ], db_all(db(), "SELECT Full_Name, Email, Role, Account_Status FROM user_table WHERE Role IN {$roles} ORDER BY Created_At DESC LIMIT 300"));
}

/** Latest book reports for the "View Logs" quick action. */
function dashboard_logs(): array {
    $so = so();
    return array_map(fn($r) => [
        'book'   => $r['Title'] ?? 'Deleted book',
        'issue'  => $r['Issue_Type'],
        'by'     => dec($so, $r['Full_Name'] ?? '') ?: 'Unknown user',
        'date'   => local_date($r['Created_At'], 'M d, Y'),
        'status' => $r['Status'],
    ], db_all(db(), "SELECT r.Issue_Type, r.Status, r.Created_At, b.Title, u.Full_Name FROM report_table r
                      LEFT JOIN book_table b ON b.Book_ID = r.Book_ID LEFT JOIN user_table u ON u.User_ID = r.User_ID
                      ORDER BY r.Created_At DESC LIMIT 50"));
}
