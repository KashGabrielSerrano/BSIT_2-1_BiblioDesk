<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Overview | BiblioDesk</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/root.css">
    <link rel="stylesheet" href="../frontend/css/admindb.css">
</head>

<body>
    <!-- SIDEBAR For Admin -->
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
            <div class="logoContain justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
                <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="49">
            </div>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admindb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                        <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admin_announcement.php" aria-current="page">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/announcement.png"
                                    alt="create announcements page" width="28">
                            </span>
                            <span class="sideText">Announcements</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="librarymanagement.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library management page"
                                    width="28">
                            </span>
                            <span class="sideText">Library Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="AdminAccManagedb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="accounts management page"
                                    width="28">
                            </span>
                            <span class="sideText">Account Manage</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="adminlog.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/due.png" alt="report logs page" width="28">
                            </span>
                            <span class="sideText">Report Logs</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="mt-auto pb-4">
                <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#"
                    data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                        <img src="../frontend/assets/icons/logout-default.png" alt="logout" width="28">
                    </span>
                    <span class="sideText">Logout</span>
                </a>
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

    <!-- NAVBAR -->
    <header class="navbar navbar-dark shadow topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="admindb.html">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>

            <div class="d-flex align-items-center gap-4 topActions">
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

                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Settings">
                    <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                </button>
                <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile" class="rounded-circle" width="44"
                        height="44">
                </button>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <!-- Profile + To-Do -->
        <section class="row g-4 mb-5" aria-label="Admin summary">
            <div class="col-12 col-lg-8">
                <div class="darkCard card border-0 shadow rounded-4 h-100">
                    <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center">
                        <h1 class="display-6 fw-semibold mb-3">Sofia Balthazar-Winslow I</h1>
                        <h2 class="h4 fw-semibold mb-3">System Administrator</h2>
                        <p class="fs-5 mb-0 d-flex align-items-center gap-2">
                            Account Status:
                            <span class="statusDot" aria-hidden="true"></span>
                            <strong>Active</strong>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="darkCard card border-0 shadow rounded-4 h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 text-center pb-2 todoHead">TO-DO</h2>
                        <ul class="list-unstyled mb-0 mt-3">
                            <li class="todoItem d-flex align-items-center gap-3">
                                <span class="dateBox rounded-2 text-center">
                                    <span class="dateDay">2</span>
                                    <span class="dateMonth">Nov</span>
                                </span>
                                <span>
                                    <span class="d-block fw-semibold small">Policy Updates</span>
                                    <span class="d-block todoDesc">Update the new policy</span>
                                </span>
                            </li>
                            <li class="todoItem d-flex align-items-center gap-3">
                                <span class="dateBox rounded-2 text-center">
                                    <span class="dateDay">10</span>
                                    <span class="dateMonth">Nov</span>
                                </span>
                                <span>
                                    <span class="d-block fw-semibold small">Librarian Accounts</span>
                                    <span class="d-block todoDesc">Check the new applications for the Librarians.</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Borrowing activity + Quick actions -->
        <section class="row g-4 mb-5" aria-label="Activity and actions">
            <div class="col-12 col-lg-9">
                <div class="lightCard card h-100 rounded-4 shadow">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                            <h2 class="h6 fw-bold mb-0">Most Popular Category</h2>
                            <div class="chartLegend d-flex gap-3 small">
                                <span class="d-flex align-items-center gap-1"><span class="legendDot legendBorrowed"></span>Borrowed</span>
                                <span class="d-flex align-items-center gap-1"><span class="legendDot legendReturned"></span>Returned</span>
                            </div>
                        </div>

                        <!-- Chart is built by admindb.js -->
                        <div class="catChartScroll">
                            <div class="catChart" id="catChart" role="img"
                                aria-label="Borrowing activity per book category per month"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-3">
                <div class="lightCard card h-100 rounded-4 shadow">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h6 fw-bold text-center pb-2 actionsHead">Quick Actions</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="actionItem">
                                <a class="d-flex align-items-center gap-3 text-decoration-none actionLink"
                                    href="librarymanagement.php">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>Add New Book</span>
                                </a>
                            </li>
                            <li class="actionItem">
                                <a class="d-flex align-items-center gap-3 text-decoration-none actionLink"
                                    href="AdminAccManagedb.php">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>Add New Librarian</span>
                                </a>
                            </li>
                            <li class="actionItem">
                                <a class="d-flex align-items-center gap-3 text-decoration-none actionLink"
                                    href="AdminAccManagedb.php">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>View Users</span>
                                </a>
                            </li>
                            <li class="actionItem">
                                <a class="d-flex align-items-center gap-3 text-decoration-none actionLink"
                                    href="adminlog.php">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>View Logs</span>
                                </a>
                            </li>
                            <li class="actionItem">
                                <a class="d-flex align-items-center gap-3 text-decoration-none actionLink"
                                    href="#">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>Generate Reports</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick stats -->
        <section aria-labelledby="statsTitle" class="mb-4">
            <h2 class="h4 fw-bold mb-3" id="statsTitle">Quick Stats</h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="greenBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statUsers">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Total Active Users</p>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="neutralBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statInventory">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Library Inventory</p>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="greenBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statBorrows">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Active Borrows</p>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="greenBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statAvailable">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Available Inventory</p>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="greenBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statLibrarians">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Active Librarians Count</p>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="yellowBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statRegistration">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Pending Registrations</p>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="yellowBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statReservations">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Pending Reservations</p>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="darkCard card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="neutralBox d-inline-block p-3 rounded-3"></span>
                                <p class="display-6 mb-0" id="statTotalBorrows">0</p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Total Borrows To Date</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="text-light mt-5 py-5">
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
    <script src="../frontend/js/notif.js" defer></script>
    <script src="../frontend/js/admindb.js"></script>
</body>

</html>