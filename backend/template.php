<?php
/**
 * backend/template.php — copy this file to start a new dashboard page.
 *
 * Everything shared (session, DB, guards, sidebar, navbar + notification bell, logout modal,
 * footer, Bootstrap) comes from core/bootstrap.php and partials/layout.php, so a page only
 * contains its own <main> content.
 *
 * 1. Pick the guard:   require_login_page()  — any logged-in, Active account
 *                      require_role_page(['Librarian', 'Admin'])  — staff only
 * 2. Pick the menu:    'staff', 'student', or menu_for_role($user['role']) for pages every role uses
 * 3. Pick the active sidebar item (keys are listed at the top of partials/layout.php)
 * 4. List the page's stylesheet(s) (frontend/css/) and script(s) (frontend/js/)
 */
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';

$user = require_role_page(['Librarian', 'Admin']);   // 1
$menu = menu_for_role($user['role']);                // 2

render_head('Template | BiblioDesk', ['root.css', 'template.css']);   // 4
render_sidebar($menu, 'overview');                                     // 3
render_logout_modal();
render_topbar($menu);
?>

    <main class="container-fluid py-4">
        <h1 class="h3 fw-semibold">Page title</h1>
        <p>Page content goes here.</p>
    </main>

<?php
render_footer($menu);
render_scripts([]);   // 4 — e.g. ['mypage.js']
