<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';

$user     = require_login_page();
$menu     = menu_for_role($user['role']);
$is_staff = $menu === 'staff';

/* ========================================================================== */
/* FAQ CONTENT                                                                */
/* ========================================================================== */
require_once __DIR__ . '/core/auth.php';   // OTP / login limits quoted in the answers

try {
    $rules = settings_all();
} catch (Throwable $e) {
    $rules = array_map(fn($spec) => $spec[4], SETTINGS_SPEC);
}

$n_days = fn($n) => (int)$n . ' day' . ((int)$n === 1 ? '' : 's');
$peso   = fn($n) => '₱' . number_format((float)$n, 2);

$lim = [
    'otp_min'  => OTP_MINUTES,
    'otp_try'  => OTP_MAX_ATTEMPTS,
    'otp_wait' => OTP_COOLDOWN,
    'fails'    => LOGIN_MAX_FAILS,
    'lock_min' => LOGIN_LOCK_MINUTES,
];

$loan_days  = $n_days($rules['loan_days']);
$hold_days  = $n_days($rules['reservation_hold_days']);
$max_borrow = (int)$rules['max_borrows'];
$borrow_cap = $max_borrow . ' book' . ($max_borrow === 1 ? '' : 's');
$max_res    = (int)$rules['max_reservations'];
$res_cap    = $max_res > 0
    ? 'You can hold up to ' . $max_res . ' active reservation' . ($max_res === 1 ? '' : 's') . ' at a time.'
    : 'Reservations are currently switched off by the library.';
$lost_fee   = $peso($rules['lost_book_fee']);
$overdue_fine = (float)$rules['fine_per_day'] > 0
    ? 'Overdue books: ' . $peso($rules['fine_per_day']) . ' for each day late'
        . ((int)$rules['grace_days'] > 0 ? ' (after a ' . $n_days($rules['grace_days']) . ' grace period)' : '')
        . ', up to ' . $peso($rules['max_fine_per_loan']) . ' per book.'
    : 'Overdue books: the library is not charging a daily fine right now.';
$fine_block = (int)$rules['block_if_unpaid_fines'] === 1
    ? '<p class="mb-0">While you have an unpaid fine you cannot borrow or reserve other books, so settle it first.</p>'
    : '';

// badge label and icon for each FAQ category (keys match the data-filter of the pills below)
$faq_cats = [
    'account' => ['Account',  'bi-person-circle'],
    'library' => ['Library',  'bi-building'],
    'mybooks' => ['My Books', 'bi-journal-bookmark'],
];

$faqs = [

    /* ------------------------------- ACCOUNT ------------------------------- */
    [
        'id'       => 'create-account',
        'cat'      => 'account',
        'icon'     => 'bi-person-plus-fill',
        'q'        => 'How do I create a BiblioDesk account?',
        'short'    => 'Register with your student details, then verify your email.',
        'keywords' => 'register sign up registration new account verify otp code student id',
        'a'        => <<<HTML
            <p>Open the login page and choose <strong>REGISTER NOW</strong>. Registration takes three quick steps:</p>
            <ol class="ps-3 mb-3">
                <li>Enter your <strong>name</strong> (middle name and suffix are optional).</li>
                <li>Add your <strong>birthday, Student ID, school email</strong> and <strong>department</strong>.</li>
                <li>Create a <strong>password</strong> (at least 8 characters), confirm it, and agree to the Terms of Use and Privacy Policy.</li>
            </ol>
            <p class="mb-0">We then email a 6-digit code to the address you entered. Type it in to verify your email and your account is ready to use. The code expires after {$lim['otp_min']} minutes, and you can ask for a new one after {$lim['otp_wait']} seconds.</p>
            HTML,
        'tip'      => "Closed the page before verifying? Just log in with the email and password you registered with and we will send you a fresh code.",
    ],
    [
        'id'       => 'login-trouble',
        'cat'      => 'account',
        'icon'     => 'bi-box-arrow-in-right',
        'q'        => "Why can't I log in?",
        'short'    => 'Check your email, your password and your account status.',
        'keywords' => 'login log in sign in locked suspended incorrect password too many attempts unverified',
        'a'        => <<<HTML
            <p>These are the most common reasons:</p>
            <ul class="ps-3 mb-0">
                <li><strong>Incorrect email or password.</strong> Check for typos and that Caps Lock is off. After {$lim['fails']} failed attempts, login is paused for {$lim['lock_min']} minutes before you can try again.</li>
                <li><strong>Unverified account.</strong> If you never entered your email code, logging in sends you a new one so you can finish registering.</li>
                <li><strong>Suspended account.</strong> You will see a message saying the account has been suspended. Please contact the Library Administrator at bibliodesk.help@gmail.com.</li>
            </ul>
            HTML,
        'tip'      => "Forgot your password? See &ldquo;How do I reset a forgotten password?&rdquo; below.",
    ],
    [
        'id'       => 'reset-password',
        'cat'      => 'account',
        'icon'     => 'bi-key-fill',
        'q'        => 'How do I reset a forgotten password?',
        'short'    => 'Use &ldquo;Forgot Password?&rdquo; on the login page.',
        'keywords' => 'forgot reset password email code otp verification recover',
        'a'        => <<<HTML
            <ol class="ps-3 mb-3">
                <li>On the login page, click <strong>Forgot Password?</strong></li>
                <li>Enter the email linked to your account and choose <strong>Send Verification Code</strong>.</li>
                <li>Type the 6-digit code from your inbox. It expires after {$lim['otp_min']} minutes and allows {$lim['otp_try']} attempts; you can ask for a new one after {$lim['otp_wait']} seconds.</li>
                <li>Create and confirm a new password (at least 8 characters), then log in.</li>
            </ol>
            <p class="mb-0">Resetting your password also signs you out of any device where you chose <strong>Remember me</strong>.</p>
            HTML,
        'tip'      => "No email? Check your spam folder and make sure you typed the email on your account. For privacy, BiblioDesk shows the same message whether or not the address is registered.",
    ],
    [
        'id'       => 'change-password',
        'cat'      => 'account',
        'icon'     => 'bi-shield-lock-fill',
        'q'        => 'How do I change my password while logged in?',
        'short'    => 'Settings &rarr; Password.',
        'keywords' => 'change update password security settings',
        'a'        => <<<HTML
            <ol class="ps-3 mb-3">
                <li>Click the <strong>settings</strong> icon in the top bar and open the <strong>Password</strong> tab.</li>
                <li>Enter your <strong>current password</strong>.</li>
                <li>Choose a new password with at least 8 characters, upper and lower case letters, and a number. The checklist turns on as you type.</li>
                <li>Confirm it and click <strong>Update password</strong>.</li>
            </ol>
            <p class="mb-0">Changing your password signs out any device where you chose <strong>Remember me</strong>.</p>
            HTML,
        'tip'      => "Use a password you do not use on any other site.",
    ],
    [
        'id'       => 'update-profile',
        'cat'      => 'account',
        'icon'     => 'bi-person-lines-fill',
        'q'        => 'How do I update my profile details or photo?',
        'short'    => 'Settings &rarr; Profile.',
        'keywords' => 'profile photo picture avatar email phone contact number name student no program year level',
        'a'        => <<<HTML
            <p>Click your profile picture (or the settings icon) in the top bar and open <strong>Profile</strong>. You can change:</p>
            <ul class="ps-3 mb-3">
                <li>your <strong>profile photo</strong> (JPG or PNG, up to 100 KB; use <strong>Remove</strong> to go back to the default),</li>
                <li>your <strong>email address</strong>,</li>
                <li>your <strong>contact number</strong> (optional PH mobile number, like 09123456789).</li>
            </ul>
            <p class="mb-0">When you edit something, a bar appears at the bottom of the page. Click <strong>Save changes</strong> to keep it, or <strong>Discard</strong> to undo.</p>
            HTML,
        'tip'      => "Your full name, Student No., program and year level are locked and can only be changed by the library. If one is wrong, send the correct details through Contact Support.",
    ],
    [
        'id'       => 'notifications',
        'cat'      => 'account',
        'icon'     => 'bi-bell-fill',
        'q'        => 'How do I manage my notifications?',
        'short'    => 'The bell shows your updates; choose what you get in Settings.',
        'keywords' => 'notification bell reminders announcements email alerts due date reservation',
        'a'        => <<<HTML
            <p>The <strong>bell</strong> in the top bar collects library announcements, borrow approvals, due-date reminders and reservation updates.</p>
            <p>To choose what you hear about, open <strong>Settings &rarr; Notifications</strong>:</p>
            <ul class="ps-3 mb-0">
                <li><strong>Due-date reminders</strong> (1, 2 or 3 days before).</li>
                <li><strong>Reservation updates</strong>, <strong>library announcements &amp; events</strong> and <strong>account activity</strong>.</li>
                <li><strong>Email</strong>, to also receive these in your inbox. The in-app bell is always on.</li>
            </ul>
            HTML,
        'tip'      => "Click Save changes at the bottom of the page after you switch anything.",
    ],
    [
        'id'       => 'appearance',
        'cat'      => 'account',
        'icon'     => 'bi-palette-fill',
        'q'        => 'Can I use dark mode or make the text bigger?',
        'short'    => 'Yes, in Settings &rarr; Reading &amp; appearance.',
        'keywords' => 'dark mode theme light text size font accessibility motion grid list layout genres reading time',
        'a'        => <<<HTML
            <p>Open <strong>Settings &rarr; Reading &amp; appearance</strong>. A live preview shows your choices before you save them:</p>
            <ul class="ps-3 mb-0">
                <li><strong>Theme:</strong> Light, Dark, or Match my device.</li>
                <li><strong>Text size:</strong> Small, Default, Large or Extra large.</li>
                <li><strong>Reduce motion</strong> turns off sliding and hover animations.</li>
                <li><strong>Default Library view:</strong> Grid or List.</li>
                <li><strong>Favorite genres</strong> shape your Recommendations in the Library.</li>
                <li><strong>Track my reading time</strong> powers the Active Reading Time on your overview.</li>
            </ul>
            HTML,
    ],
    [
        'id'       => 'my-data',
        'cat'      => 'account',
        'icon'     => 'bi-download',
        'q'        => 'How do I download my data or close my account?',
        'short'    => 'Settings &rarr; My data &amp; account.',
        'keywords' => 'download export csv json history delete close account remove data privacy',
        'a'        => <<<HTML
            <p>Open <strong>Settings &rarr; My data &amp; account</strong>. There you can download:</p>
            <ul class="ps-3 mb-3">
                <li>your <strong>borrowing history</strong> as a CSV file, and</li>
                <li>your <strong>profile &amp; settings</strong> as a JSON file.</li>
            </ul>
            <p class="mb-0">To close your account, choose <strong>Close my account&hellip;</strong>, type <strong>CLOSE</strong> to confirm, and send the request. A librarian reviews it. Your borrowing history is kept as required by library records, and you will not be able to sign in once the request is approved.</p>
            HTML,
    ],

    /* ------------------------------- LIBRARY ------------------------------- */
    [
        'id'       => 'browse-library',
        'cat'      => 'library',
        'icon'     => 'bi-compass',
        'q'        => 'How do I browse and find books in the Digital Library?',
        'short'    => 'Shelves, genre filters and grid or list views.',
        'keywords' => 'library browse find search shelf new arrivals recommendations educational research genre filter grid list',
        'a'        => <<<HTML
            <p>Open <strong>Library</strong> from the sidebar. Books are sorted into shelves: <strong>New Arrivals</strong>, <strong>Recommendations</strong>, <strong>Educational</strong>, <strong>Non-Educational</strong> and <strong>Research Articles</strong>. The buttons at the top jump straight to each shelf.</p>
            <ul class="ps-3 mb-3">
                <li><strong>Filter by genre</strong> (Fiction, Non-Fiction, Horror, Romance, Science, Technology, History, Self-help, Education) filters every shelf at once. Use <strong>Clear filter</strong> to see everything again.</li>
                <li>Switch between <strong>Grid</strong> and <strong>List</strong> layouts. BiblioDesk remembers your choice.</li>
                <li>Click any cover to open its <strong>Book Details</strong>.</li>
            </ul>
            HTML,
        'tip'      => "Pick your favorite genres in Settings &rarr; Reading &amp; appearance so Recommendations match your taste.",
    ],
    [
        'id'       => 'digital-vs-physical',
        'cat'      => 'library',
        'icon'     => 'bi-book-half',
        'q'        => 'What is the difference between digital and physical books?',
        'short'    => 'Digital books are read online; physical books are borrowed.',
        'keywords' => 'digital physical ebook pdf copies borrow read now counter',
        'a'        => <<<HTML
            <p><strong>Digital books</strong> show a <strong>Read Now</strong> button. They open instantly in the BiblioDesk reader, so there is nothing to borrow and no due date.</p>
            <p class="mb-0"><strong>Physical books</strong> are real copies on the library shelf. Their Book Details show how many copies are available and offer <strong>Borrow</strong> or <strong>Reserve</strong>. You pick them up and return them at the library counter.</p>
            HTML,
    ],
    [
        'id'       => 'read-digital',
        'cat'      => 'library',
        'icon'     => 'bi-book',
        'q'        => 'How do I read a digital book?',
        'short'    => 'Open the book and click Read Now.',
        'keywords' => 'read reader pdf page continue reading progress download print watermark',
        'a'        => <<<HTML
            <ol class="ps-3 mb-3">
                <li>Click the book cover to open <strong>Book Details</strong>.</li>
                <li>Click <strong>Read Now</strong>. The reader opens in a new tab.</li>
                <li>Use <strong>Previous</strong> and <strong>Next</strong>, or type a page number to jump to it.</li>
            </ol>
            <p class="mb-0">Your page is saved automatically. Next time the button says <strong>Continue Reading</strong> and takes you back to where you stopped. Digital books are view-only: they cannot be downloaded or printed, and your email appears faintly on the pages.</p>
            HTML,
        'tip'      => "If a book will not load or pages are missing, use Report issue in the reader so the librarians can fix it.",
    ],
    [
        'id'       => 'share-book',
        'cat'      => 'library',
        'icon'     => 'bi-share-fill',
        'q'        => 'How do I share a book with a classmate?',
        'short'    => 'Use the Share button to get a link and a QR code.',
        'keywords' => 'share link qr code classmate friend send copy',
        'a'        => <<<HTML
            <p>In a digital book&rsquo;s <strong>Book Details</strong> (or in the reader&rsquo;s top bar), click <strong>Share</strong>. A link and a QR code appear. Copy the link, or let your classmate scan the code.</p>
            <p class="mb-0">The link opens that book in the reader. Your classmate needs to be logged in to BiblioDesk to open it.</p>
            HTML,
    ],
    [
        'id'       => 'borrow-physical',
        'cat'      => 'library',
        'icon'     => 'bi-bookmark-check',
        'q'        => 'How do I borrow a physical book?',
        'short'    => 'Send a borrow request, then pick it up at the counter.',
        'keywords' => 'borrow request loan pick up counter due date limit approve',
        'a'        => <<<HTML
            <ol class="ps-3 mb-3">
                <li>Open the physical book&rsquo;s <strong>Book Details</strong>. It shows how many copies are available.</li>
                <li>Click <strong>Borrow</strong> to send your request.</li>
                <li>A librarian approves it and you get a notification.</li>
                <li>Pick the book up at the library counter.</li>
            </ol>
            <p class="mb-0">Your due date is {$loan_days} after the day the request is approved, and it is shown in <strong>My Books</strong>. You can have up to {$borrow_cap} borrowed or requested at a time.</p>
            HTML,
        'tip'      => "Changed your mind? Click Cancel request in Book Details or My Books before it is approved.",
    ],
    [
        'id'       => 'reserve-book',
        'cat'      => 'library',
        'icon'     => 'bi-calendar-plus',
        'q'        => 'What if every copy is already borrowed?',
        'short'    => 'Reserve the book and join the waiting list.',
        'keywords' => 'reserve reservation waiting list queue hold ready pickup expired unavailable',
        'a'        => <<<HTML
            <p>When no copy is available, the <strong>Borrow</strong> button becomes <strong>Reserve</strong>. Reserving puts you in line and shows your place, like <strong>#2</strong>.</p>
            <ul class="ps-3 mb-0">
                <li>When a copy comes back, the first person in line is notified and the reservation turns <strong>Ready for pickup</strong>.</li>
                <li>Collect it at the counter within {$hold_days}. After that the hold expires and the copy goes to the next person.</li>
                <li>{$res_cap} You can cancel a reservation at any time.</li>
            </ul>
            HTML,
    ],
    [
        'id'       => 'fines',
        'cat'      => 'library',
        'icon'     => 'bi-cash-coin',
        'q'        => 'Are there fines for late, lost or damaged books?',
        'short'    => 'Fines are paid at the library counter.',
        'keywords' => 'fine overdue late lost damaged pay payment peso penalty unpaid',
        'a'        => <<<HTML
            <ul class="ps-3 mb-3">
                <li>{$overdue_fine}</li>
                <li>Lost books: a flat fee of {$lost_fee}.</li>
                <li>Damaged books: the librarian sets the amount.</li>
            </ul>
            <p class="mb-3">A running overdue fine shows in <strong>My Books</strong> while a book is late and is charged when you return it. Fines are paid at the library counter.</p>
            {$fine_block}
            HTML,
        'tip'      => "You get a reminder before a book is due and another when it is overdue, so keep notifications on.",
    ],
    [
        'id'       => 'report-book',
        'cat'      => 'library',
        'icon'     => 'bi-flag-fill',
        'q'        => 'How do I report a problem with a book?',
        'short'    => 'Use the flag icon in Book Details or Report issue in the reader.',
        'keywords' => 'report issue problem broken link missing pages wrong file license concern flag',
        'a'        => <<<HTML
            <ol class="ps-3 mb-3">
                <li>Click the <strong>flag</strong> icon in Book Details, or <strong>Report issue</strong> in the reader&rsquo;s top bar.</li>
                <li>Choose what is wrong: <strong>Broken Link</strong>, <strong>Missing Page/s</strong>, <strong>Wrong File</strong>, <strong>License Concern</strong> or <strong>Other</strong>.</li>
                <li>Add details if you can, like which page, then click <strong>Send report</strong>.</li>
            </ol>
            <p class="mb-0">The librarians receive your report and review it.</p>
            HTML,
    ],
    [
        'id'       => 'contact-library',
        'cat'      => 'library',
        'icon'     => 'bi-chat-dots-fill',
        'q'        => 'How do I contact the library for help or suggestions?',
        'short'    => 'Use Contact Support. Replies appear under My messages.',
        'keywords' => 'contact support message ticket help suggestion email staff reply request book',
        'a'        => <<<HTML
            <ol class="ps-3 mb-3">
                <li>Open <strong>Contact Support</strong> from the sidebar.</li>
                <li>Choose a subject: Account / Login, Borrowing &amp; Reservations, Digital Books / Reading, Fines &amp; Payments, Suggestion or Others.</li>
                <li>Write your message (10 to 2,000 characters) and click <strong>Send Message</strong>.</li>
            </ol>
            <p class="mb-0">Staff replies show up under <strong>My messages</strong> on the same page, where you can also send follow-ups on open tickets. You can also email bibliodesk.help@gmail.com.</p>
            HTML,
        'tip'      => "Your name and email come from your account, so staff always know who is writing.",
    ],

    /* ------------------------------- MY BOOKS ------------------------------ */
    [
        'id'       => 'mybooks-overview',
        'cat'      => 'mybooks',
        'icon'     => 'bi-journal-bookmark',
        'q'        => 'What can I find in My Books?',
        'short'    => 'Loans, reading progress, collections, bookmarks, favorites and history.',
        'keywords' => 'my books overview loans reservations continue reading collections bookmarks favorites history',
        'a'        => <<<HTML
            <p>My Books is your personal shelf. The buttons at the top jump to each section:</p>
            <ul class="ps-3 mb-0">
                <li><strong>Loans</strong>: your borrowed books, reservations and fines.</li>
                <li><strong>Continue Reading</strong>: digital books you started.</li>
                <li><strong>Collections</strong>: shelves you create yourself.</li>
                <li><strong>Bookmarks</strong> and <strong>Favorites</strong>: books you saved.</li>
                <li><strong>History</strong>: books you opened recently.</li>
            </ul>
            HTML,
    ],
    [
        'id'       => 'check-loans',
        'cat'      => 'mybooks',
        'icon'     => 'bi-card-checklist',
        'q'        => 'How do I check my due dates, reservations and fines?',
        'short'    => 'My Books &rarr; My Loans &amp; Reservations.',
        'keywords' => 'loans due date overdue reservations fines status requested borrowed ready waiting history cancel',
        'a'        => <<<HTML
            <p>Open <strong>My Books</strong> and look at <strong>My Loans &amp; Reservations</strong>. Badges at the top show how many books you have borrowed or requested and how many reservations you have, out of your limits. Unpaid fines appear there too.</p>
            <ul class="ps-3 mb-3">
                <li><strong>Requested</strong>: waiting for the library to approve it. You can cancel it.</li>
                <li><strong>Borrowed</strong>: shows your due date. <strong>Overdue</strong> shows how many days late and the fine so far.</li>
                <li><strong>Waiting #</strong>: your place in a reservation line. <strong>Ready for pickup</strong> shows the date you must collect it by.</li>
            </ul>
            <p class="mb-0">Past loans are kept under <strong>History</strong> at the bottom of the section.</p>
            HTML,
    ],
    [
        'id'       => 'return-book',
        'cat'      => 'mybooks',
        'icon'     => 'bi-arrow-return-left',
        'q'        => 'How do I return a book or get more time?',
        'short'    => 'Return physical books at the library counter.',
        'keywords' => 'return renew extend extension due date counter lost late',
        'a'        => <<<HTML
            <p>Bring the book to the library counter. A librarian records the return, you get a notification, and the loan moves to your <strong>History</strong>. Return it on or before the due date to avoid a fine.</p>
            <p class="mb-0">BiblioDesk has no online renewal. If you need more time, or a book was lost or damaged, tell the librarian at the counter or send a message through Contact Support.</p>
            HTML,
    ],
    [
        'id'       => 'collections',
        'cat'      => 'mybooks',
        'icon'     => 'bi-folder-plus',
        'q'        => 'How do I create and manage collections?',
        'short'    => 'Group books into your own named shelves.',
        'keywords' => 'collection collections shelf folder group organize rename delete add create',
        'a'        => <<<HTML
            <p>There are two ways to start a collection:</p>
            <ul class="ps-3 mb-3">
                <li>In <strong>My Books &rarr; Collections</strong>, click <strong>+ Add Collections</strong> and give it a name (up to 30 characters).</li>
                <li>In any <strong>Book Details</strong>, click <strong>Add To Collections</strong>, then tick a collection or create a new one right there.</li>
            </ul>
            <p class="mb-0">Click a collection to see its books, <strong>Rename</strong> it or <strong>Delete</strong> it. Deleting a collection never removes the books from the library.</p>
            HTML,
    ],
    [
        'id'       => 'bookmarks-favorites',
        'cat'      => 'mybooks',
        'icon'     => 'bi-bookmark-heart',
        'q'        => 'What is the difference between Bookmarks and Favorites?',
        'short'    => 'Bookmarks are for later; favorites are the ones you love.',
        'keywords' => 'bookmark favorite favourite heart save later like',
        'a'        => <<<HTML
            <p>Both are buttons in a book&rsquo;s <strong>Book Details</strong>:</p>
            <ul class="ps-3 mb-3">
                <li>The <strong>bookmark</strong> icon saves a book to read later.</li>
                <li>The <strong>heart</strong> icon marks a book you love.</li>
            </ul>
            <p class="mb-0">They collect under <strong>Bookmarks</strong> and <strong>Favorites</strong> in My Books. Click the icon again to remove a book from either one.</p>
            HTML,
    ],
    [
        'id'       => 'continue-reading',
        'cat'      => 'mybooks',
        'icon'     => 'bi-play-circle',
        'q'        => 'How do I pick up where I left off?',
        'short'    => 'BiblioDesk saves your page automatically.',
        'keywords' => 'continue reading progress resume page last read history reading time',
        'a'        => <<<HTML
            <p>While you read a digital book, BiblioDesk saves your page. Find it under <strong>Continue Reading</strong> in My Books. When you reopen it, the button says <strong>Continue Reading</strong> and opens the page you stopped at.</p>
            <p class="mb-0"><strong>History</strong> lists every book you opened recently, even ones you finished.</p>
            HTML,
        'tip'      => "Reading time is only counted while Track my reading time is on in Settings &rarr; Reading &amp; appearance.",
    ],
];

render_head($is_staff ? 'BiblioDesk - Admin Helpdesk' : 'BiblioDesk - Helpdesk', [$is_staff ? 'admin_helpdesk.css' : 'helpdesk.css']);
?>
    <!-- ========================================================================== -->
    <!-- SIDEBAR NAVIGATION                             -->
    <!-- ========================================================================== -->
    <?php render_sidebar($menu, 'helpdesk'); ?>

    <!-- ========================================================================== -->
    <!-- TOP NAVBAR                                                              -->
    <!-- ========================================================================== -->
    <?php render_topbar($menu); ?>

    <main>
        <div class="libraryHeader gap-3 mt-4 mb-5">
            <div>
                <h1 class="fw-bold mb-1">Help Desk</h1>
            </div>
        </div>

    <?php if ($is_staff): ?>
            <!-- Support tickets inbox (staff only) - frontend/js/helpdesk_tickets.js -->
            <section id="tickets" class="mx-auto mb-5 px-3 px-md-0" style="max-width: 1000px;" aria-labelledby="ticketsHeading">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="hd-section-icon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                            <i class="bi bi-headset fs-4"></i>
                        </span>
                        <div>
                            <h2 id="ticketsHeading" class="h3 fw-bold mb-0">Support Tickets</h2>
                            <p class="text-muted small mb-0">Review user concerns, view conversation threads, and send replies.</p>
                        </div>
                    </div>
                    <div class="hd-filter-pills d-flex flex-wrap gap-2" role="group" aria-label="Filter tickets by status" id="ticketFilters">
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn active" data-status="Open">
                            Open <span class="badge rounded-pill bg-warning text-dark ms-1" data-count="Open">0</span>
                        </button>
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn" data-status="Answered">
                            Answered <span class="badge rounded-pill bg-success ms-1" data-count="Answered">0</span>
                        </button>
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn" data-status="Closed">
                            Closed <span class="badge rounded-pill bg-secondary ms-1" data-count="Closed">0</span>
                        </button>
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn" data-status="">
                            All
                        </button>
                    </div>
                </div>
                <div id="ticketList" class="d-flex flex-column gap-4">
                    <p class="text-muted mb-0">Loading tickets&hellip;</p>
                </div>
            </section>

            <!-- Reply modal -->
            <div class="modal fade" id="ticketReplyModal" tabindex="-1" aria-labelledby="ticketReplyLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header text-white px-4 py-3 border-0" style="background-color: var(--main-color);">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-reply-fill fs-5 text-warning" aria-hidden="true"></i>
                                <h5 class="modal-title fw-bold mb-0" id="ticketReplyLabel">Reply to ticket</h5>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="ticketReplyForm" novalidate>
                            <div class="modal-body p-4" style="background-color: var(--main-bg-color);">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
                                    <p class="small text-muted mb-0" id="ticketReplyFrom"></p>
                                    <span id="ticketReplyBadge"></span>
                                </div>
                                <div id="ticketReplyThread" class="hd-thread-box d-flex flex-column gap-3 mb-4"></div>
                                <div>
                                    <label for="ticketReplyText" class="form-label fw-semibold small">
                                        <i class="bi bi-pencil-fill me-1 text-secondary" aria-hidden="true"></i>Your reply
                                    </label>
                                    <textarea class="form-control rounded-3 p-3" id="ticketReplyText" rows="4" maxlength="2000"
                                        placeholder="Write a helpful response to the user..." required></textarea>
                                    <div class="invalid-feedback" id="ticketReplyError"></div>
                                    <div class="form-text small">Up to 2,000 characters.</div>
                                </div>
                            </div>
                            <div class="modal-footer border-top px-4 py-3" style="background-color: var(--main-bg-color);">
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn rounded-pill px-4 text-white fw-semibold shadow-sm d-inline-flex align-items-center gap-2"
                                    style="background-color: var(--btn);" id="ticketReplySend">
                                    <i class="bi bi-send-fill" aria-hidden="true"></i> Send reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    <?php endif; ?>

        <!-- Tutorial -->
        <section class="tutorial-section">
            <div class="tutorial-wrapper mx-auto" style="max-width: 800px;">

                <div class="d-flex align-items-center gap-3 mt-5 mb-5">
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                    <h2 class="<?= $is_staff ? '' : 'h1 ' ?>fw-semibold mb-0">Tutorial</h2>
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                </div>


                <?php if ($is_staff): ?>
<div class="text-center my-3 position-relative">
                    <div class="d-flex justify-content-end mx-auto mb-2">
                        <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                            data-bs-toggle="modal" data-bs-target="#tutorialModal">
                            <i class="bi bi-pencil-square me-1"></i> Edit content
                        </button>
                    </div>
                </div>
<?php endif; ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-white"
                    style="background-color: var(--main-color);">
                    <div class="ratio ratio-16x9 bg-black">
                        <video controls class="w-100 h-100">
                            <source src="../frontend/assets/temporary/3_sec.mp4" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>

                    <div class="card-body p-4 p-md-5 text-center">
                        <h3 class="h4 fw-bold mb-3">Getting Started Guide</h3>
                        <p class="mx-auto mb-0" style="max-width: 600px; line-height: 1.7;">
                            Open <strong>Library</strong> to browse the catalog. Digital books open in the reader with
                            <strong>Read Now</strong>; physical books can be <strong>borrowed</strong> or
                            <strong>reserved</strong> and are picked up at the library counter. Track loans, due dates
                            and fines under <strong>My Books</strong>, and watch the bell for approvals and reminders.
                        </p>
                    </div>
                </div>
        </section>

        <!--FAQ-->
        <section class="faq-section">
            <div class="faq-wrapper mx-auto">
                <div class="d-flex align-items-center gap-3 mt-5 mb-5">
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                    <h2 class="<?= $is_staff ? '' : 'h1 ' ?>fw-semibold mb-0">Frequently Asked Questions</h2>
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                </div>

                <!-- FAQ Categories -->
                <div class="faq-wrapper mx-auto mb-4">
                    <ul class="nav nav-pills gap-2 flex-nowrap overflow-x-auto pb-2 faq-pills-wrapper"
                        id="faq-pills-tab" role="tablist">
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link active rounded-pill px-4 py-2" id="pill-all-tab" data-filter="all"
                                type="button" role="tab">All</button>
                        </li>
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link rounded-pill px-4 py-2" id="pill-account-tab" data-filter="account"
                                type="button" role="tab">Account</button>
                        </li>
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link rounded-pill px-4 py-2" id="pill-library-tab" data-filter="library"
                                type="button" role="tab">Library</button>
                        </li>
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link rounded-pill px-4 py-2" id="pill-mybooks-tab" data-filter="mybooks"
                                type="button" role="tab">My Books</button>
                        </li>
                    </ul>
                </div>

                <div class="faq-container">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 w-100">
                        <div class="searchForm flex-grow-1">
                            <div class="FAQsearchBox w-100">
                                <i class="bi bi-search"></i>
                                <input type="search" id="Search" class="form-control rounded-3 py-2"
                                    placeholder="Search Questions" aria-label="Search FAQ" autocomplete="off">
                            </div>
                        </div>

                        <?php if ($is_staff): ?>
<div class="flex-shrink-0">
                            <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                                data-bs-toggle="modal" data-bs-target="#faqModal">
                                <i class="bi bi-pencil-square me-1"></i> Edit content
                            </button>
                        </div>
<?php endif; ?>

                    </div>


                    <!-- FAQ Cards (generated from $faqs at the top of this file) -->
<?php foreach ($faqs as $faq):
    [$cat_label, $cat_icon] = $faq_cats[$faq['cat']];
    $modal_id = 'faqModal-' . $faq['id']; ?>
                    <a href="#<?= e($modal_id) ?>" class="card card-body shadow-sm border-0 rounded-4 mb-3 px-3 py-3 faq-card text-white text-decoration-none"
                        data-category="<?= e($faq['cat']) ?>" style="background-color: var(--main-color);"
                        data-bs-toggle="modal" data-bs-target="#<?= e($modal_id) ?>">
                        <div class="d-flex align-items-center gap-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                                style="width: 46px; height: 46px; background-color: rgba(255, 255, 255, .12); color: var(--accent-soft);" aria-hidden="true">
                                <i class="bi <?= e($faq['icon']) ?> fs-5"></i>
                            </span>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <p class="text-uppercase fw-semibold mb-1" style="color: var(--accent-soft); font-size: .7rem; letter-spacing: .06em;"><?= e($cat_label) ?></p>
                                <h5 class="fw-semibold mb-1"><?= e($faq['q']) ?></h5>
                                <p class="small text-white-50 mb-0"><?= $faq['short'] ?></p>
                            </div>
                            <i class="bi bi-chevron-right flex-shrink-0" aria-hidden="true"></i>
                        </div>
                        <span class="visually-hidden"><?= e($faq['keywords']) ?></span>
                    </a>
<?php endforeach; ?>
                </div>
            </div>
        </section>


        <!-- LOGOUT CONFIRMATION MODAL -->
        <?php render_logout_modal(); ?>

        <!-- FAQ Modals (one per question, generated from $faqs at the top of this file) -->
<?php foreach ($faqs as $faq):
    [$cat_label, $cat_icon] = $faq_cats[$faq['cat']];
    $modal_id = 'faqModal-' . $faq['id']; ?>
        <div class="modal fade" id="<?= e($modal_id) ?>" tabindex="-1" aria-labelledby="<?= e($modal_id) ?>Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-0 px-4 py-3 text-white" style="background-color: var(--main-color);">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi <?= e($cat_icon) ?> fs-5" style="color: var(--accent-soft);" aria-hidden="true"></i>
                            <span class="small fw-semibold text-uppercase" style="letter-spacing: .06em;"><?= e($cat_label) ?></span>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 py-4" style="background-color: var(--main-bg-color);">
                        <h5 class="modal-title fw-bold mb-2" id="<?= e($modal_id) ?>Label"><?= e($faq['q']) ?></h5>
                        <p class="text-muted fw-semibold mb-3 faq-details"><?= $faq['short'] ?></p>
                        <div class="faq-desc">
                            <?= $faq['a'] ?>
                        </div>
<?php if (!empty($faq['tip'])): ?>
                        <div class="d-flex align-items-start gap-2 mt-4 p-3 rounded-3 small"
                            style="background-color: rgba(var(--btn-rgb), .1); border-left: 4px solid var(--btn);">
                            <i class="bi bi-lightbulb-fill flex-shrink-0" style="color: var(--btn);" aria-hidden="true"></i>
                            <span><?= $faq['tip'] ?></span>
                        </div>
<?php endif; ?>
                    </div>
                    <div class="modal-footer border-0 d-flex justify-content-between align-items-center"
                        style="background-color: var(--main-bg-color);">
                        <?php if ($is_staff): ?>
<div class="flex-shrink-0">
                            <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                                data-bs-toggle="modal" data-bs-target="#editfaqModal">
                                <i class="bi bi-pencil-square me-1"></i> Edit content
                            </button>
                        </div>
<?php endif; ?>
                        <button type="button" class="btn rounded-pill px-4 text-white fw-semibold ms-auto"
                            style="background-color: var(--btn);" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
<?php endforeach; ?>

        <!-- Tutorial edit Modal -->
        <?php if ($is_staff): ?>
        <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: var(--main-color);">
                        <h5 class="modal-title fw-bold" id="tutorialModalLabel">Edit Tutorial Content</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: var(--main-bg-color);">
                        <div class="mb-3">
                            <label for="tutorialInputVideo" class="form-label fw-semibold">Select Video File
                                (.mp4)</label>
                            <input type="file" class="form-control" id="tutorialInputVideo" accept="video/mp4">
                        </div>
                        <div class="mb-3">
                            <label for="tutorialInputTitle" class="form-label fw-semibold">Tutorial Title</label>
                            <input type="text" class="form-control" id="tutorialInputTitle" value="Tutorial">
                        </div>
                        <div class="mb-3">
                            <label for="tutorialInputDesc" class="form-label fw-semibold">Tutorial Description</label>
                            <textarea class="form-control" id="tutorialInputDesc" rows="4">...</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="background-color: var(--main-bg-color);">
                        <button type="button" class="btn rounded-pill px-4 border border-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn rounded-pill px-4 text-white" id="saveTutorialBtn"
                            style="background-color: var(--btn);" data-bs-dismiss="modal">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- FAQ Add Modal -->
        <?php if ($is_staff): ?>
        <div class="modal fade" id="faqModal" tabindex="-1" aria-labelledby="faqModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: var(--main-color);">
                        <h5 class="modal-title fw-bold" id="faqModalLabel">Add FAQ</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: var(--main-bg-color);">
                        <div class="mb-3">
                            <label for="addInputTitle" class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" id="addInputTitle" value="FAQ">
                        </div>
                        <div class="mb-3">
                            <label for="addInputDetails" class="form-label fw-semibold">Additional Details</label>
                            <input type="text" class="form-control" id="addInputDetails" value="FAQ">
                        </div>
                        <div class="mb-3">
                            <label for="addInputDesc" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="addInputDesc" rows="4">...</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="background-color: var(--main-bg-color);">
                        <button type="button" class="btn rounded-pill px-4 border border-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-dark rounded-pill px-4" id="saveAddFaqBtn"
                            style="background-color: var(--btn);" data-bs-dismiss="modal">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- FAQ Edit Modal -->
        <?php if ($is_staff): ?>
        <div class="modal fade" id="editfaqModal" tabindex="-1" aria-labelledby="editfaqModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: var(--main-color);">
                        <h5 class="modal-title fw-bold" id="editfaqModalLabel">Edit FAQ</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: var(--main-bg-color);">
                        <div class="mb-3">
                            <label for="editInputTitle" class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" id="editInputTitle" value="editFAQ">
                        </div>
                        <div class="mb-3">
                            <label for="editInputDetails" class="form-label fw-semibold">Additional Details</label>
                            <input type="text" class="form-control" id="editInputDetails" value="editFAQ">
                        </div>
                        <div class="mb-3">
                            <label for="editInputDesc" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="editInputDesc" rows="4">...</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="background-color: var(--main-bg-color);">
                        <button type="button" class="btn rounded-pill px-4 border border-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-dark rounded-pill px-4" id="saveEditFaqBtn"
                            style="background-color: var(--btn);" data-bs-dismiss="modal">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>


    <!-- ========================================================================== -->
    <!-- FOOTER                                                                  -->
    <!-- ========================================================================== -->
    <?php render_footer($menu, 'class="text-light py-5" style="margin-top: 100px;"'); ?>

<?php render_scripts($is_staff ? ['helpdesk.js', 'helpdesk_tickets.js'] : ['helpdesk.js']); ?>
