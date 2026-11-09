<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/circulation.php';
header('Cache-Control: no-store');

$user = require_login_api();
require_csrf();
$action  = gv($_POST, 'action') ?: gv($_GET, 'action');
$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$uid     = $user['user_id'];

const STAFF_ACTIONS = ['staff_list', 'approve', 'reject', 'return', 'lost', 'damaged', 'fulfill', 'reject_reservation', 'settle_fine'];
const POST_ACTIONS  = ['request', 'cancel_request', 'reserve', 'cancel_reservation', 'approve', 'reject', 'return', 'lost',
                       'damaged', 'fulfill', 'reject_reservation', 'settle_fine'];

if (in_array($action, POST_ACTIONS, true) && !$is_post) {
    sendResponse(false, 'This action requires POST.', [], 405);
}
if (in_array($action, STAFF_ACTIONS, true)) {
    $staff = require_role_api(['Librarian', 'Admin'], 'Only librarians and admins can manage circulation.');
}
if (in_array($action, ['request', 'reserve'], true) && $user['role'] !== 'Student') {
    sendResponse(false, 'Borrowing and reserving are for student accounts.', [], 403);
}

if ($action !== 'export_csv') {
    header('Content-Type: application/json; charset=utf-8');
}
circulation_upkeep();

/** Load a loan (with its book title) or stop with error. */
function find_loan(int $id): array {
    $l = db_one("SELECT l.*, b.Title, b.Author FROM loan_table l JOIN book_table b ON b.Book_ID = l.Book_ID WHERE l.Loan_ID = ?", 'i', [$id]);
    if (!$l) sendResponse(false, 'Loan not found.', [], 404);
    return $l;
}

function find_reservation(int $id): array {
    $r = db_one("SELECT r.*, b.Title FROM reservation_table r JOIN book_table b ON b.Book_ID = r.Book_ID WHERE r.Reservation_ID = ?", 'i', [$id]);
    if (!$r) sendResponse(false, 'Reservation not found.', [], 404);
    return $r;
}

/** A physical book with copies, locked FOR UPDATE inside the caller's transaction. */
function lock_book(int $book_id): array {
    $b = db_one("SELECT Book_ID, Title, Is_digital, Total_Copies FROM book_table WHERE Book_ID = ? FOR UPDATE", 'i', [$book_id]);
    if (!$b) sendResponse(false, 'Book not found.', [], 404);
    return $b;
}

function add_fine(array $loan, string $reason, float $amount, string $note = ''): void {
    if ($amount <= 0) return;
    db_exec("INSERT INTO fine_table (Loan_ID, User_ID, Amount, Reason, Note) VALUES (?, ?, ?, ?, ?)",
            'iidss', [(int)$loan['Loan_ID'], (int)$loan['User_ID'], $amount, $reason, $note !== '' ? $note : null]);
    notify((int)$loan['User_ID'], 'fine_added', "Fine: {$loan['Title']}",
           "{$reason} fine of " . peso($amount) . '. Please pay at the library counter.', 'user_librarydb.php?view=mybooks#my-loans');
}

switch ($action) {

    /* ================================ STUDENT ================================ */

    case 'book_status':
        $status = book_circulation_status((int)gv($_GET, 'book_id'), $uid, $user['role']);
        if (!$status['found']) sendResponse(false, 'Book not found.', [], 404);
        sendResponse(true, 'Status loaded.', ['status' => $status]);

    case 'request':
        $book_id = (int)gv($_POST, 'book_id');
        db()->begin_transaction();
        $book = lock_book($book_id);
        $st = book_circulation_status($book_id, $uid, $user['role']);
        if (!$st['can_borrow']) {
            db()->rollback();
            $msg = $st['loan'] ? 'You already have this book ' . ($st['loan']['status'] === 'Requested' ? 'requested.' : 'borrowed.')
                 : ($st['reservation'] ? 'You have a reservation for this book.'
                 : ($st['can_reserve'] ? 'All copies are borrowed right now. You can reserve it instead.'
                 : ($st['reason'] ?: 'This book cannot be borrowed right now.')));
            sendResponse(false, $msg, [], 409);
        }
        db_exec("INSERT INTO loan_table (User_ID, Book_ID, Status) VALUES (?, ?, 'Requested')", 'ii', [$uid, $book_id]);
        $loan_id = (int)db()->insert_id;
        db()->commit();
        notify_roles(['Librarian', 'Admin'], 'new_request', "Borrow request: {$book['Title']}",
                     (current_profile()['full_name'] ?? 'A student') . ' wants to borrow this book.', 'librarymanagement.php#circulation', $uid);
        sendResponse(true, 'Request sent! Pick up the book at the library counter once it is approved.', ['loan_id' => $loan_id]);

    case 'cancel_request':
        $loan = find_loan((int)gv($_POST, 'loan_id'));
        if ((int)$loan['User_ID'] !== $uid || $loan['Status'] !== 'Requested') {
            sendResponse(false, 'Only your own pending requests can be cancelled.', [], 403);
        }
        db_exec("UPDATE loan_table SET Status = 'Cancelled' WHERE Loan_ID = ? AND Status = 'Requested'", 'i', [(int)$loan['Loan_ID']]);
        sendResponse(true, 'Request cancelled.');

    case 'reserve':
        $book_id = (int)gv($_POST, 'book_id');
        db()->begin_transaction();
        $book = lock_book($book_id);
        $st = book_circulation_status($book_id, $uid, $user['role']);
        if (!$st['can_reserve']) {
            db()->rollback();
            $msg = $st['can_borrow'] ? 'A copy is available: you can borrow it now instead.'
                 : ($st['reservation'] ? 'You already reserved this book.' : ($st['loan'] ? 'You already have this book.' : ($st['reason'] ?: 'This book cannot be reserved right now.')));
            sendResponse(false, $msg, [], 409);
        }
        db_exec("INSERT INTO reservation_table (User_ID, Book_ID) VALUES (?, ?)", 'ii', [$uid, $book_id]);
        $res = db_one("SELECT * FROM reservation_table WHERE Reservation_ID = ?", 'i', [(int)db()->insert_id]);
        db()->commit();
        notify_roles(['Librarian', 'Admin'], 'new_request', "Reservation: {$book['Title']}",
                     (current_profile()['full_name'] ?? 'A student') . ' joined the waiting list.', 'librarymanagement.php#circulation', $uid);
        sendResponse(true, 'Reserved! You are number ' . queue_position($res) . ' in line. We will notify you when it is ready.');

    case 'cancel_reservation':
        $res = find_reservation((int)gv($_POST, 'id'));
        if ((int)$res['User_ID'] !== $uid || !in_array($res['Status'], RES_ACTIVE, true)) {
            sendResponse(false, 'Only your own active reservations can be cancelled.', [], 403);
        }
        db_exec("UPDATE reservation_table SET Status = 'Cancelled' WHERE Reservation_ID = ?", 'i', [(int)$res['Reservation_ID']]);
        if ($res['Status'] === 'Ready') promote_reservations((int)$res['Book_ID']);   // the held copy goes to the next person
        sendResponse(true, 'Reservation cancelled.');

    case 'mine':
        $loans = db_all(db(), "SELECT l.*, b.Title, b.Author FROM loan_table l JOIN book_table b ON b.Book_ID = l.Book_ID
                                WHERE l.User_ID = ? ORDER BY FIELD(l.Status, 'Borrowed', 'Requested') DESC, l.Requested_At DESC LIMIT 100", 'i', [$uid]);
        $res = db_all(db(), "SELECT r.*, b.Title FROM reservation_table r JOIN book_table b ON b.Book_ID = r.Book_ID
                              WHERE r.User_ID = ? AND r.Status IN ('Pending','Ready') ORDER BY r.Created_At", 'i', [$uid]);
        $fines = db_all(db(), "SELECT f.*, b.Title FROM fine_table f JOIN loan_table l ON l.Loan_ID = f.Loan_ID JOIN book_table b ON b.Book_ID = l.Book_ID
                                WHERE f.User_ID = ? ORDER BY f.Status = 'Unpaid' DESC, f.Created_At DESC LIMIT 50", 'i', [$uid]);
        sendResponse(true, 'Loaded.', [
            'loans'        => array_map('loan_item', $loans),
            'reservations' => array_map(fn($r) => ['id' => (int)$r['Reservation_ID'], 'book_id' => (int)$r['Book_ID'], 'title' => $r['Title'],
                                                   'status' => $r['Status'], 'position' => queue_position($r),
                                                   'hold_until' => local_date($r['Hold_Until'], 'M d, Y'), 'created' => local_date($r['Created_At'], 'M d, Y')], $res),
            'fines'        => array_map(fn($f) => ['id' => (int)$f['Fine_ID'], 'title' => $f['Title'], 'reason' => $f['Reason'],
                                                   'amount' => (float)$f['Amount'], 'status' => $f['Status'], 'date' => local_date($f['Created_At'], 'M d, Y'),
                                                   'note' => $f['Note'] ?? ''], $fines),
            'summary'      => ['active_loans' => active_loan_count($uid), 'max_borrows' => (int)get_setting('max_borrows'),
                               'active_reservations' => active_reservation_count($uid), 'max_reservations' => (int)get_setting('max_reservations'),
                               'unpaid' => unpaid_fines_total($uid), 'loan_days' => (int)get_setting('loan_days')],
        ]);

    case 'export_csv':
        $rows = db_all(db(), "SELECT l.*, b.Title, b.Author FROM loan_table l JOIN book_table b ON b.Book_ID = l.Book_ID
                               WHERE l.User_ID = ? AND l.Status IN ('Borrowed','Returned') ORDER BY l.Borrowed_At DESC", 'i', [$uid]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bibliodesk-borrowing-history-' . gmdate('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM so Excel reads UTF-8
        fputcsv($out, ['Title', 'Author', 'Borrowed', 'Due', 'Returned', 'Status', 'Note']);
        foreach ($rows as $r) {
            $i = loan_item($r);
            // neutralise spreadsheet formulas in user-visible text
            $safe = fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v;
            fputcsv($out, array_map($safe, [$i['title'], $i['author'], $i['borrowed'], $i['due'], $i['returned'],
                                            $i['overdue'] ? 'Overdue' : $i['status'], $i['note']]));
        }
        fclose($out);
        exit();

    /* ================================= STAFF ================================= */

    case 'staff_list':
        $section = gv($_GET, 'section') ?: 'requests';
        $so = so();
        $who = fn($r) => dec($so, $r['Full_Name'] ?? '') ?: 'Unknown user';
        $counts = [
            'requests'     => (int)db_one("SELECT COUNT(*) AS c FROM loan_table WHERE Status = 'Requested'")['c'],
            'borrowed'     => (int)db_one("SELECT COUNT(*) AS c FROM loan_table WHERE Status = 'Borrowed'")['c'],
            'overdue'      => (int)db_one("SELECT COUNT(*) AS c FROM loan_table WHERE Status = 'Borrowed' AND Due_At < UTC_TIMESTAMP()")['c'],
            'reservations' => (int)db_one("SELECT COUNT(*) AS c FROM reservation_table WHERE Status IN ('Pending','Ready')")['c'],
            'fines'        => (int)db_one("SELECT COUNT(*) AS c FROM fine_table WHERE Status = 'Unpaid'")['c'],
        ];
        $items = [];
        if (in_array($section, ['requests', 'borrowed', 'overdue'], true)) {
            $where = ['requests' => "l.Status = 'Requested'", 'borrowed' => "l.Status = 'Borrowed'",
                      'overdue' => "l.Status = 'Borrowed' AND l.Due_At < UTC_TIMESTAMP()"][$section];
            foreach (db_all(db(), "SELECT l.*, b.Title, b.Author, u.Full_Name, u.Student_ID FROM loan_table l
                                    JOIN book_table b ON b.Book_ID = l.Book_ID JOIN user_table u ON u.User_ID = l.User_ID
                                   WHERE {$where} ORDER BY " . ($section === 'requests' ? 'l.Requested_At' : 'l.Due_At') . " LIMIT 200") as $r) {
                $items[] = loan_item($r) + ['student' => $who($r), 'student_id' => dec($so, $r['Student_ID'] ?? ''),
                                            'available' => $section === 'requests' ? available_copies((int)$r['Book_ID']) : null];
            }
        } elseif ($section === 'reservations') {
            foreach (db_all(db(), "SELECT r.*, b.Title, u.Full_Name, u.Student_ID FROM reservation_table r
                                    JOIN book_table b ON b.Book_ID = r.Book_ID JOIN user_table u ON u.User_ID = r.User_ID
                                   WHERE r.Status IN ('Pending','Ready') ORDER BY r.Status = 'Ready' DESC, r.Created_At LIMIT 200") as $r) {
                $items[] = ['id' => (int)$r['Reservation_ID'], 'title' => $r['Title'], 'status' => $r['Status'], 'position' => queue_position($r),
                            'hold_until' => local_date($r['Hold_Until'], 'M d, Y'), 'created' => local_date($r['Created_At'], 'M d, Y'),
                            'student' => $who($r), 'student_id' => dec($so, $r['Student_ID'] ?? '')];
            }
        } elseif ($section === 'fines') {
            foreach (db_all(db(), "SELECT f.*, b.Title, u.Full_Name, u.Student_ID FROM fine_table f
                                    JOIN loan_table l ON l.Loan_ID = f.Loan_ID JOIN book_table b ON b.Book_ID = l.Book_ID
                                    JOIN user_table u ON u.User_ID = f.User_ID
                                   ORDER BY f.Status = 'Unpaid' DESC, f.Created_At DESC LIMIT 200") as $f) {
                $items[] = ['id' => (int)$f['Fine_ID'], 'title' => $f['Title'], 'reason' => $f['Reason'], 'amount' => (float)$f['Amount'],
                            'status' => $f['Status'], 'date' => local_date($f['Created_At'], 'M d, Y'), 'note' => $f['Note'] ?? '',
                            'settled' => local_date($f['Settled_At'] ?? null, 'M d, Y'), 'student' => $who($f), 'student_id' => dec($so, $f['Student_ID'] ?? '')];
            }
        } else {
            sendResponse(false, 'Unknown section.', [], 400);
        }
        sendResponse(true, 'Loaded.', ['section' => $section, 'items' => $items, 'counts' => $counts,
                                       'settings' => ['loan_days' => (int)get_setting('loan_days'), 'lost_book_fee' => (float)get_setting('lost_book_fee')]]);

    case 'approve':
        $loan = find_loan((int)gv($_POST, 'loan_id'));
        if ($loan['Status'] !== 'Requested') sendResponse(false, 'Only pending requests can be approved.', [], 409);
        db()->begin_transaction();
        lock_book((int)$loan['Book_ID']);
        if (available_copies((int)$loan['Book_ID']) <= 0) {
            db()->rollback();
            sendResponse(false, 'No copy is available right now. Reject the request or wait for a return.', [], 409);
        }
        $due = end_of_local_day_plus((int)get_setting('loan_days'));
        db_exec("UPDATE loan_table SET Status = 'Borrowed', Approved_By = ?, Borrowed_At = UTC_TIMESTAMP(), Due_At = ? WHERE Loan_ID = ? AND Status = 'Requested'",
                'isi', [$staff['user_id'], $due, (int)$loan['Loan_ID']]);
        db()->commit();
        notify((int)$loan['User_ID'], 'loan_approved', "Borrowed: {$loan['Title']}",
               'Enjoy the book! Please return it by ' . local_date($due, 'M d, Y') . '.', 'user_librarydb.php?view=mybooks#my-loans');
        log_activity('loan_approved', 'loan', (int)$loan['Loan_ID'], $loan['Title']);
        sendResponse(true, 'Approved. Due ' . local_date($due, 'M d, Y') . '.');

    case 'reject':
        $loan = find_loan((int)gv($_POST, 'loan_id'));
        if ($loan['Status'] !== 'Requested') sendResponse(false, 'Only pending requests can be rejected.', [], 409);
        $note = mb_substr(gv($_POST, 'note'), 0, 255);
        db_exec("UPDATE loan_table SET Status = 'Rejected', Approved_By = ?, Note = ? WHERE Loan_ID = ?", 'isi',
                [$staff['user_id'], $note !== '' ? $note : null, (int)$loan['Loan_ID']]);
        notify((int)$loan['User_ID'], 'loan_rejected', "Request declined: {$loan['Title']}", $note !== '' ? $note : 'Please ask at the library counter.');
        log_activity('loan_rejected', 'loan', (int)$loan['Loan_ID'], $loan['Title']);
        sendResponse(true, 'Request rejected.');

    case 'return':
    case 'lost':
        $loan = find_loan((int)gv($_POST, 'loan_id'));
        if ($loan['Status'] !== 'Borrowed') sendResponse(false, 'Only borrowed books can be returned.', [], 409);
        db()->begin_transaction();
        lock_book((int)$loan['Book_ID']);
        $lost = $action === 'lost';
        db_exec("UPDATE loan_table SET Status = 'Returned', Returned_At = UTC_TIMESTAMP(), Returned_To = ?, Note = ? WHERE Loan_ID = ?",
                'isi', [$staff['user_id'], $lost ? 'Lost' : $loan['Note'], (int)$loan['Loan_ID']]);
        if ($lost) {
            db_exec("UPDATE book_table SET Total_Copies = GREATEST(Total_Copies - 1, 0) WHERE Book_ID = ?", 'i', [(int)$loan['Book_ID']]);
            $fine = (float)get_setting('lost_book_fee');
            add_fine($loan, 'Lost', $fine, 'Copy marked as lost');
        } else {
            $fine = overdue_fine($loan['Due_At']);
            add_fine($loan, 'Overdue', $fine, days_late($loan['Due_At']) . ' day(s) late');
        }
        db()->commit();
        if (!$lost) {
            notify((int)$loan['User_ID'], 'loan_returned', "Returned: {$loan['Title']}", 'Thank you for returning the book.');
            promote_reservations((int)$loan['Book_ID']);   // the copy goes to the next person in line
        }
        log_activity($lost ? 'loan_lost' : 'loan_returned', 'loan', (int)$loan['Loan_ID'], $loan['Title'] . ($fine > 0 ? ' (fine ' . peso($fine) . ')' : ''));
        sendResponse(true, ($lost ? 'Marked as lost.' : 'Returned.') . ($fine > 0 ? ' Fine added: ' . peso($fine) . '.' : ''), ['fine' => $fine]);

    case 'damaged':
        $loan   = find_loan((int)gv($_POST, 'loan_id'));
        $amount = gv($_POST, 'amount');
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount) || (float)$amount <= 0 || (float)$amount > 100000) {
            sendResponse(false, 'Enter the damage fine as an amount like 150 or 150.50.', [], 422);
        }
        if (db_one("SELECT 1 FROM fine_table WHERE Loan_ID = ? AND Reason = 'Damaged'", 'i', [(int)$loan['Loan_ID']])) {
            sendResponse(false, 'This loan already has a damage fine.', [], 409);
        }
        add_fine($loan, 'Damaged', round((float)$amount, 2), mb_substr(gv($_POST, 'note'), 0, 255));
        log_activity('fine_damaged', 'loan', (int)$loan['Loan_ID'], $loan['Title'] . ' ' . peso((float)$amount));
        sendResponse(true, 'Damage fine added: ' . peso((float)$amount) . '.');

    case 'fulfill':
        $res = find_reservation((int)gv($_POST, 'id'));
        if ($res['Status'] !== 'Ready') sendResponse(false, 'Only reservations that are ready for pickup can be handed out.', [], 409);
        db()->begin_transaction();
        lock_book((int)$res['Book_ID']);
        $due = end_of_local_day_plus((int)get_setting('loan_days'));
        db_exec("INSERT INTO loan_table (User_ID, Book_ID, Status, Approved_By, Borrowed_At, Due_At, Note)
                 VALUES (?, ?, 'Borrowed', ?, UTC_TIMESTAMP(), ?, 'From reservation')",
                'iiis', [(int)$res['User_ID'], (int)$res['Book_ID'], $staff['user_id'], $due]);
        $loan_id = (int)db()->insert_id;
        db_exec("UPDATE reservation_table SET Status = 'Fulfilled', Handled_By = ?, Loan_ID = ? WHERE Reservation_ID = ?",
                'iii', [$staff['user_id'], $loan_id, (int)$res['Reservation_ID']]);
        db()->commit();
        notify((int)$res['User_ID'], 'loan_approved', "Borrowed: {$res['Title']}",
               'You picked up your reserved book. Please return it by ' . local_date($due, 'M d, Y') . '.', 'user_librarydb.php?view=mybooks#my-loans');
        log_activity('reservation_fulfilled', 'reservation', (int)$res['Reservation_ID'], $res['Title']);
        sendResponse(true, 'Handed out. Due ' . local_date($due, 'M d, Y') . '.');

    case 'reject_reservation':
        $res = find_reservation((int)gv($_POST, 'id'));
        if (!in_array($res['Status'], RES_ACTIVE, true)) sendResponse(false, 'This reservation is no longer active.', [], 409);
        db_exec("UPDATE reservation_table SET Status = 'Rejected', Handled_By = ? WHERE Reservation_ID = ?", 'ii',
                [$staff['user_id'], (int)$res['Reservation_ID']]);
        notify((int)$res['User_ID'], 'reservation_rejected', "Reservation declined: {$res['Title']}", 'Please ask at the library counter.');
        if ($res['Status'] === 'Ready') promote_reservations((int)$res['Book_ID']);
        log_activity('reservation_rejected', 'reservation', (int)$res['Reservation_ID'], $res['Title']);
        sendResponse(true, 'Reservation rejected.');

    case 'settle_fine':
        $id     = (int)gv($_POST, 'id');
        $status = gv($_POST, 'status');
        if (!in_array($status, ['Paid', 'Waived'], true)) sendResponse(false, 'Choose Paid or Waived.', [], 400);
        $f = db_one("SELECT f.*, b.Title FROM fine_table f JOIN loan_table l ON l.Loan_ID = f.Loan_ID JOIN book_table b ON b.Book_ID = l.Book_ID
                      WHERE f.Fine_ID = ?", 'i', [$id]);
        if (!$f) sendResponse(false, 'Fine not found.', [], 404);
        if ($f['Status'] !== 'Unpaid') sendResponse(false, 'This fine is already settled.', [], 409);
        db_exec("UPDATE fine_table SET Status = ?, Settled_At = UTC_TIMESTAMP(), Settled_By = ? WHERE Fine_ID = ?", 'sii',
                [$status, $staff['user_id'], $id]);
        notify((int)$f['User_ID'], 'fine_settled', ($status === 'Paid' ? 'Fine paid: ' : 'Fine waived: ') . $f['Title'],
               peso((float)$f['Amount']) . ' ' . strtolower($f['Reason']) . ' fine marked as ' . strtolower($status) . '.');
        log_activity('fine_' . strtolower($status), 'fine', $id, $f['Title'] . ' ' . peso((float)$f['Amount']));
        sendResponse(true, 'Fine marked as ' . strtolower($status) . '.');

    default:
        sendResponse(false, 'Invalid action.', [], 400);
}
