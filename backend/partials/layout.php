<?php
const STAFF_MENU = [
    'overview'      => ['staff_overview.php',     'ow-default.png',    'overview page',           'Overview'],
    'announcements' => ['admin_announcement.php', 'announcement.png',  'announcements page',      'Announcements'],
    'library'       => ['librarymanagement.php',  'lib-default.png',   'library management page', 'Library Management'],
    // User Management = the account list (Library Management -> Account Management tab)
    'users'         => ['librarymanagement.php#accounts', 'business-mail.png', 'accounts management page', 'User Management'],
    // Report Logs = the book report list (Library Management -> Report Logs tab)
    'logs'          => ['librarymanagement.php#reports', 'due.png',     'report logs page',        'Report Logs'],
    'helpdesk'      => ['helpdesk.php',           'help-default.png',  'helpdesk page',           'Helpdesk'],
    'activity'      => ['adminlog.php',           'pending-reserve.png', 'activity log page',     'Activity Log'],
];
const ADMIN_ONLY_MENU = ['activity'];

const STUDENT_MENU = [
    'overview' => ['userdb_overview.php',             'ow-default.png',      'overview page',        'Overview'],
    'mybooks'  => ['user_librarydb.php?view=mybooks', 'mybooks-default.png', 'my books page',        'My Books'],
    'library'  => ['user_librarydb.php?view=library', 'lib-default.png',     'library page',         'Library'],
    'contact'  => ['contactsupport.php',              'contact-default.png', 'contact support page', 'Contact Support'],
    'helpdesk' => ['helpdesk.php',                    'help-default.png',    'helpdesk page',        'Helpdesk'],
];

function menu_for_role(?string $role): string {
    return in_array($role, ['Librarian', 'Admin'], true) ? 'staff' : 'student';
}

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function render_head(string $title, array $css = [], string $extra_head = ''): void { ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <meta name="csrf-token" content="<?= e($_SESSION['csrf_token'] ?? '') ?>">
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/notif.css">
<?php foreach ($css as $file): ?>
    <link rel="stylesheet" href="../frontend/css/<?= e($file) ?>">
<?php endforeach; ?>
<?= $extra_head ?>
</head>

<body>
<?php }

/** Sidebar (offcanvas on mobile). $active is a key of the menu (see top of file). */
function render_sidebar(string $menu, string $active = ''): void {
    $items = $menu === 'staff' ? STAFF_MENU : STUDENT_MENU;
    if (($menu === 'staff') && (current_user()['role'] ?? '') !== 'Admin') {
        $items = array_diff_key($items, array_flip(ADMIN_ONLY_MENU));
    } ?>
    <!-- SIDEBAR -->
    <aside class="offcanvas-md offcanvas-start d-flex flex-column text-light" tabindex="-1" id="sidebar"
        aria-labelledby="sidebarLabel" data-bs-scroll="true">
<?php if ($menu === 'staff'): ?>
        <div class="offcanvas-header py-3 px-3 d-md-none">
            <h5 class="offcanvas-title d-flex align-items-center m-0" id="sidebarLabel">
                <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk Logo" height="24">
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
            <div class="logoContain justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
                <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="49">
            </div>
<?php else: ?>
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
            <div class="logoContain d-flex align-items-center px-3">
                <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="48">
            </div>
<?php endif; ?>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">
<?php foreach ($items as $key => [$href, $icon, $alt, $label]): $on = $key === $active; ?>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="<?= e($href) ?>"<?= $on ? ' aria-current="page"' : '' ?>>
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center<?= $on ? ' bg-light' : '' ?>">
                                <img src="../frontend/assets/icons/<?= e($icon) ?>" alt="<?= e($alt) ?>" width="28">
                            </span>
                            <span class="sideText"><?= e($label) ?></span>
                        </a>
                    </li>
<?php endforeach; ?>
                </ul>
            </nav>

            <div class="mt-auto pb-4">
                <ul class="nav flex-column m-0 p-0">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#"
                            data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/logout-default.png" alt="logout" width="28">
                            </span>
                            <span class="sideText">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>
<?php }

/** "Are you sure you want to log out?" modal, opened by the sidebar's Logout item. */
function render_logout_modal(): void { ?>
    <!-- LOGOUT CONFIRMATION MODAL -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: var(--main-bg-color);">
                <div class="modal-header text-white border-secondary" style="background-color: var(--main-color);">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body justify-content-center text-center m-5 fs-4">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post" action="login.php" class="d-inline m-0">
                        <input type="hidden" name="action" value="logout">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn btn-danger">Log Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php }

/** Top navbar: menu toggle, logo, notification bell (notif.js), settings and account links. */
function render_topbar(string $menu): void {
    $staff = $menu === 'staff';
    $home  = $staff ? 'staff_overview.php' : 'userdb_overview.php'; ?>
    <!-- TOP NAVBAR -->
    <header class="navbar navbar-dark shadow topbar">
<?php if ($staff): ?>
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="<?= $home ?>">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>

            <div class="d-flex align-items-center gap-4 topActions">
<?php else: /* student: the logo stays visible, so on phones it shrinks instead of the icons wrapping */ ?>
        <div class="container-fluid flex-nowrap">
            <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                <button class="navbar-toggler d-md-none flex-shrink-0" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0" href="<?= $home ?>" style="min-width: 0;">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26"
                        style="max-width: 100%; max-height: 26px; height: auto;">
                </a>
            </div>

            <div class="d-flex align-items-center gap-3 gap-md-4 flex-shrink-0">
<?php endif; ?>
                <!-- ANNOUNCEMENTS (notification dropdown, filled by notif.js) -->
                <div class="dropdown">
                    <button type="button" class="btn p-0 border-0 position-relative" id="notifToggle"
                        data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open Notification Area">
                        <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill notifBadge"
                            id="notifBadge" hidden>0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end notifMenu" id="notifMenu">
                        <li class="notifHead">
                            <span>Announcements</span>
                            <span class="notifHeadCount">0 new</span>
                        </li>
                        <li><span class="notifEmpty">No announcements yet.</span></li>
                    </ul>
                </div>

                <a href="settings.php?tab=settings" class="btn p-0 border-0 position-relative" aria-label="Open Settings">
                    <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                </a>

                <a href="account.php" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile" class="rounded-circle" width="44" height="44">
                </a>
            </div>
        </div>
    </header>
<?php }

/** Footer. $footer_attrs replaces the default spacing (some pages use a larger top margin). */
function render_footer(string $menu, string $footer_attrs = 'class="text-light mt-5 py-5"'): void {
    $staff = $menu === 'staff'; ?>
    <!-- FOOTER -->
    <footer <?= $footer_attrs ?>>
        <div class="container">
            <div class="row justify-content-around gy-4">
                <div class="<?= $staff ? 'col-12 col-md-5 ps-0' : 'col-md-5' ?>">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56"
                        class="footerLogo">
                    <ul class="list-unstyled lh-lg mt-3 ps-4<?= $staff ? ' mb-0' : '' ?>">
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=terms">Terms of Use</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=privacy">Privacy Policy</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="index.php#about">More About BiblioDesk</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="index.php#developers">BiblioDesk Developers</a>
                        </li>
                    </ul>
                </div>
                <div class="<?= $staff ? 'col-12 col-md-5 ps-4 ps-md-0' : 'col-md-5' ?>">
                    <h2 class="h4 text-uppercase">Contact Us</h2>
                    <p class="lh-lg<?= $staff ? ' mb-0' : '' ?>">bibliodesk@gmail.com<br>bibliodesk.help@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>
<?php }

/**
 * Bootstrap + the notification bell script, then the page scripts (file names inside frontend/js/,
 * or full https URLs). Closes </body></html> unless $close is false (for pages that add inline scripts).
 */
function render_scripts(array $js = [], bool $close = true): void { ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../frontend/js/notif.js" defer></script>
<?php foreach ($js as $file): ?>
    <script src="<?= str_starts_with($file, 'https://') ? e($file) : '../frontend/js/' . e($file) ?>"></script>
<?php endforeach;
    if ($close): ?>
</body>

</html>
<?php endif;
}

/**
 * "Preview mode" strip for staff viewing a student page. Only shown to Librarian/Admin users, not students. 
 * The strip is a warning that the page is being viewed as a staff member, and a link to return 
 * to the staff library management page.
 */
function render_preview_banner(): void {
    $user = current_user();
    if (!$user || !in_array($user['role'], ['Librarian', 'Admin'], true)) {
        return;
    } ?>
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 rounded-3 mb-4" role="status">
        <span><i class="bi bi-eye me-2" aria-hidden="true"></i><strong>Preview mode:</strong>
            you are viewing the student library as <?= e($user['role']) ?>.</span>
        <a href="librarymanagement.php" class="btn btn-sm btn-dark rounded-pill px-3">Back to Library Management</a>
    </div>
<?php }
