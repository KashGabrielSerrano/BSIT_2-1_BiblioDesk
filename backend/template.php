<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliodesk - Template</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/template.css">
</head>

<body>
    <!-- SIDEBAR For User-->
    <aside class="offcanvas-md offcanvas-start d-flex flex-column text-light" tabindex="-1" id="sidebar"
        aria-labelledby="sidebarLabel" data-bs-scroll="true">
        <div class="offcanvas-header py-3 px-3 d-md-none">
            <h5 class="offcanvas-title d-flex align-items-center m-0" id="sidebarLabel">
                <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk Logo" height="24">
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
            <div
                class="logoContain d-flex align-items-center justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
                <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="49">
            </div>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="userdb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="mybooks.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/mybooks-default.png" alt="my books page" width="28">
                            </span>
                            <span class="sideText">My Books</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="user_librarydb.php" aria-current="page">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library page" width="28">
                            </span>
                            <span class="sideText">Library</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="help.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="help center page" width="28">
                            </span>
                            <span class="sideText">Help Center</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="contact.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="contact us page"
                                    width="28">
                            </span>
                            <span class="sideText">Contact Us</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="mt-auto pb-4">
                <li class="nav-item flex-column">
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
                    <a href="login.php" class="btn btn-danger">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- SIDEBAR For Admin
<aside class="offcanvas-md offcanvas-start d-flex flex-column text-light" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel" data-bs-scroll="true">
    <div class="offcanvas-header py-3 px-3 d-md-none">
        <h5 class="offcanvas-title d-flex align-items-center m-0" id="sidebarLabel">
            <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk Logo" height="24">
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
        <div class="logoContain d-flex align-items-center justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
            <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="49">
        </div>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="admindb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="librarymanagement.php" aria-current="page">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library management page" width="28">
                            </span>
                            <span class="sideText">Library Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="Admin_librarian_help.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="help center page" width="28">
                            </span>
                            <span class="sideText">Help Center</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="AdminAccManagedb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="accounts management page" width="28">
                            </span>
                            <span class="sideText">Accounts Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="Admin_reportlogs.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="report logs page" width="28">
                            </span>
                            <span class="sideText">Report Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="create_announcements.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="create announcements page" width="28">
                            </span>
                            <span class="sideText">Create Announcements</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <div class="mt-auto pb-4">
            <li class="nav-item flex-column">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/logout-default.png" alt="logout" width="28">
                            </span>
                        <span class="sideText">Logout</span>
                        </a>
                    </li>
                </ul>
        </div>
    </div>
</aside> -->


    <!-- SIDEBAR For Librarian
<aside class="offcanvas-md offcanvas-start d-flex flex-column text-light" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel" data-bs-scroll="true">
    <div class="offcanvas-header py-3 px-3 d-md-none">
        <h5 class="offcanvas-title d-flex align-items-center m-0" id="sidebarLabel">
            <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk Logo" height="24">
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
        <div class="logoContain d-flex align-items-center justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
            <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="49">
        </div>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="librariandb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="librarymanagement.php" aria-current="page">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library management page" width="28">
                            </span>
                            <span class="sideText">Library Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="Admin_librarian_help.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="help center page" width="28">
                            </span>
                            <span class="sideText">Help Center</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="LibrarianAccManagedb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="accounts management page" width="28">
                            </span>
                            <span class="sideText">Accounts Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="Librarian_reportlogs.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="report logs page" width="28">
                            </span>
                            <span class="sideText">Report Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="create_announcements.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="create announcements page" width="28">
                            </span>
                            <span class="sideText">Create Announcements</span>
                        </a>
                    </li>
                </ul>
            </nav> 
            <div class="mt-auto pb-4">
            <li class="nav-item flex-column">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/logout-default.png" alt="logout" width="28">
                            </span>
                        <span class="sideText">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>-->

    <!-- NAVBAR -->
    <header class="navbar navbar-dark shadow topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="userdb.php">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>

            <div class="d-flex align-items-center gap-4">
                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                    <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                    <span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">4</span>
                </button>
                <a href="settings.php?tab=settings" class="btn p-0 border-0 position-relative" aria-label="Open Settings">
                    <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                </a>
                <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile" class="rounded-circle" width="44"
                        height="44">
                </button>
            </div>
        </div>
    </header>

    <main>
        <div class="container py-4">
            <h1>Page Content Goes Here</h1>
        </div>
</main>

    <!-- Footer -->
    <footer class="text-light py-5">
        <div class="container">
            <div class="row justify-content-around gy-4">
                <div class="col-12 col-md-5 ps-0">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56"
                        class="footerLogo">
                    <ul class="list-unstyled lh-lg mt-3 ps-4 mb-0">
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
                                href="more.php">More About BiblioDesk</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="aboutus.php">BiblioDesk Developers</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-5 ps-4 ps-md-0">
                    <h2 class="h4 text-uppercase">Contact Us</h2>
                    <p class="lh-lg mb-0">bibliodesk@gmail.com<br>bibliodesk.help@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>