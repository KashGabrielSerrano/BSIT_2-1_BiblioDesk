<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Logs | BiblioDesk</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/root.css">
    <link rel="stylesheet" href="../frontend/css/adminlog.css">
</head>

<body>
<!-- SIDEBAR -->
<aside class="offcanvas-md offcanvas-start d-flex flex-column text-light"
       tabindex="-1"
       id="sidebar"
       aria-labelledby="sidebarLabel"
       data-bs-scroll="true">

    <div class="offcanvas-header py-3 px-3 d-md-none">
        <h5 class="offcanvas-title d-flex align-items-center m-0" id="sidebarLabel">
            <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk Logo" height="24">
        </h5>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar"
                aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">

        <div class="logoContain justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
            <img src="../frontend/assets/BiblioDesk_IconW.png"
                 alt="Bibliodesk Logo"
                 width="49">
        </div>

        <nav aria-label="Main">
            <ul class="nav flex-column gap-3 pt-4">
                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                       href="admindb_overview.php">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                        </span>
                        <span class="sideText">Overview</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                       href="admin_announcement.php">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/announcement.png" alt="create announcements page" width="28">
                        </span>
                        <span class="sideText">Announcements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                       href="librarymanagement.php">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/lib-default.png" alt="library management page" width="28">
                        </span>
                        <span class="sideText">Library Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                       href="AdminAccManagedb.php">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/business-mail.png" alt="accounts management page" width="28">
                        </span>
                        <span class="sideText">User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                       href="adminlog.php" aria-current="page">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                            <img src="../frontend/assets/icons/due.png" alt="report logs page" width="28">
                        </span>
                        <span class="sideText">Report Logs</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                       href="admin_helpdesk.php">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/help-default.png" alt="helpdesk page" width="28">
                        </span>
                        <span class="sideText">Helpdesk</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="mt-auto pb-4">
            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
               href="#"
               data-bs-toggle="modal"
               data-bs-target="#logoutModal">
                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                    <img src="../frontend/assets/icons/logout-default.png" alt="logout" width="28">
                </span>
                <span class="sideText">Logout</span>
            </a>
        </div>

    </div>
</aside>

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

            <button class="navbar-toggler d-md-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar"
                    aria-controls="sidebar"
                    aria-label="Open menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand m-0" href="adminlog.php">
                <img src="../frontend/assets/BiblioDesk_White_name.png"
                     alt="BiblioDesk"
                     height="26">
            </a>

        </div>

        <div class="d-flex align-items-center gap-3 gap-md-4">

            <div class="dropdown">

                <button type="button"
                        class="btn p-0 border-0 position-relative"
                        id="notifToggle"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Open announcements">
                    <img src="../frontend/assets/icons/notifbell.png" alt="Notifications" width="34">
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

            <a href="userdb.php?tab=settings" aria-label="Settings">
                <img src="../frontend/assets/icons/settings.png"
                     alt="Settings"
                     width="34">
            </a>

            <button type="button" class="btn p-0 border-0" aria-label="Account Information">
                <img src="../frontend/assets/icons/pfp-icon.png"
                     alt="Profile"
                     class="rounded-circle"
                     width="44"
                     height="44">
            </button>

        </div>
    </div>
</header>

<main class="container-fluid px-3 px-md-5 py-4">

    <h1 class="pageTitle">Report Logs</h1>

    <section aria-label="Report summary">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">
            <div class="col">
                <div class="cardsColor card border-0 shadow rounded-4 h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="redBox d-inline-block p-4 rounded-3"></span>
                            <p class="display-4 mb-0">0</p>
                        </div>
                        <p class="text-end fs-5 mb-0 mt-3">Current Reports</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="cardsColor card border-0 shadow rounded-4 h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="yellowBox d-inline-block p-4 rounded-3"></span>
                            <p class="display-4 mb-0">0</p>
                        </div>
                        <p class="text-end fs-5 mb-0 mt-3">Forwarded to Admin</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="cardsColor card border-0 shadow rounded-4 h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="redBox d-inline-block p-4 rounded-3"></span>
                            <p class="display-4 mb-0">0</p>
                        </div>
                        <p class="text-end fs-5 mb-0 mt-3">Broken Links</p>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="cardsColor card border-0 shadow rounded-4 h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="greenBox d-inline-block p-4 rounded-3"></span>
                            <p class="display-4 mb-0">0</p>
                        </div>
                        <p class="text-end fs-5 mb-0 mt-3">Resolved This Week</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="toolbar">

        <form class="searchWrap" method="get" action="adminlog.php" role="search">
            <input class="form-control searchInput"
                   type="search"
                   name="q"
                   value=""
                   placeholder="Search report..."
                   aria-label="Search reports"
                   autocomplete="off">
        </form>

        <a class="btnExport" href="adminlog.php">
            Export CSV
        </a>

        <div class="dropdown filterDropdown">

            <button class="btnFilter"
                    type="button"
                    id="filterDropdown"
                    data-bs-toggle="dropdown"
                    data-bs-display="static"
                    data-bs-auto-close="outside"
                    aria-expanded="false"
                    aria-label="Open filters">

                <img src="../frontend/assets/icons/filter.png"
                     alt="Filter"
                     width="22">
            </button>

            <div class="dropdown-menu dropdown-menu-end filterPanel"
                 aria-labelledby="filterDropdown">

                <form id="filterForm" method="get" action="adminlog.php">

                    <h5 class="filterTitle">Filter Reports</h5>

                    <div class="mb-3">
                        <label class="form-label" for="sortBy">Sort By</label>
                        <select class="form-select" id="sortBy" name="sort">
                            <option value="default">Default</option>
                            <option value="name-asc">Name (A - Z)</option>
                            <option value="name-desc">Name (Z - A)</option>
                            <option value="date-newest">Date: Newest first</option>
                            <option value="date-oldest">Date: Oldest first</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="status">Status</label>

                        <select class="form-select" id="status" name="status">
                            <option value="">All statuses</option>
                            <option value="new">New</option>
                            <option value="reviewing">Reviewing</option>
                            <option value="forwarded">Forwarded</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btnApply" type="submit">
                            Apply Filters
                        </button>

                        <a class="btnReset" href="adminlog.php">
                            Reset
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </section>

    <section>

        <div class="tableScroll">

            <div class="logTable">

                <div class="tableHead">
                    <span>Name</span>
                    <span>Issue</span>
                    <span>Reported by</span>
                    <span>Date</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-11-01" data-status="new">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-pink">Broken Link</span></div>
                    <div class="cell">Victor Magtanggol</div>
                    <div class="cell">Today</div>
                    <div class="cell"><span class="status-badge-green">New</span></div>
                    <div class="cell">
                        <form method="post" action="adminlog.php">
                            <input type="hidden" name="action" value="forward">
                            <input type="hidden" name="report_id" value="1">
                            <button class="action-btn" type="submit">Forward</button>
                        </form>
                    </div>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-10-31" data-status="reviewing">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-amber">Missing Page/s</span></div>
                    <div class="cell">Sofia the 1st</div>
                    <div class="cell">Yesterday</div>
                    <div class="cell"><span class="status-badge-red">Reviewing</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-10-23" data-status="forwarded">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-red">Wrong File</span></div>
                    <div class="cell">Dora the Explorer</div>
                    <div class="cell">Oct 23</div>
                    <div class="cell"><span class="status-badge-amber">Forwarded</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-10-03" data-status="resolved">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-blue">License Concern</span></div>
                    <div class="cell">Cardo Dalisay</div>
                    <div class="cell">Oct 03</div>
                    <div class="cell"><span class="status-badge-darkred">Resolved</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-10-03" data-status="resolved">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-blue">License Concern</span></div>
                    <div class="cell">Cardo Dalisay</div>
                    <div class="cell">Oct 03</div>
                    <div class="cell"><span class="status-badge-darkred">Resolved</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-10-23" data-status="forwarded">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-red">Wrong File</span></div>
                    <div class="cell">Dora the Explorer</div>
                    <div class="cell">Oct 23</div>
                    <div class="cell"><span class="status-badge-amber">Forwarded</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-11-01" data-status="new">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-pink">Broken Link</span></div>
                    <div class="cell">Victor Magtanggol</div>
                    <div class="cell">Today</div>
                    <div class="cell"><span class="status-badge-green">New</span></div>
                    <div class="cell">
                        <form method="post" action="adminlog.php">
                            <input type="hidden" name="action" value="forward">
                            <input type="hidden" name="report_id" value="7">
                            <button class="action-btn" type="submit">Forward</button>
                        </form>
                    </div>
                </div>

                <div class="reportRow" data-name="Book Title" data-date="2026-11-01" data-status="new">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>
                    <div class="cell"><span class="issue-badge-pink">Broken Link</span></div>
                    <div class="cell">Victor Magtanggol</div>
                    <div class="cell">Today</div>
                    <div class="cell"><span class="status-badge-green">New</span></div>
                    <div class="cell">
                        <form method="post" action="adminlog.php">
                            <input type="hidden" name="action" value="forward">
                            <input type="hidden" name="report_id" value="8">
                            <button class="action-btn" type="submit">Forward</button>
                        </form>
                    </div>
                </div>

                <div class="reportRow" data-name="Noli Me Tangere" data-date="2026-09-28" data-status="resolved">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Noli Me Tangere</p>
                        <p class="bookAuthor mb-0">Jose Rizal</p>
                    </div>
                    <div class="cell"><span class="issue-badge-amber">Missing Page/s</span></div>
                    <div class="cell">Maria Clara</div>
                    <div class="cell">Sep 28</div>
                    <div class="cell"><span class="status-badge-darkred">Resolved</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

                <div class="reportRow" data-name="El Filibusterismo" data-date="2026-11-05" data-status="reviewing">
                    <div class="cellName">
                        <p class="bookTitle mb-0">El Filibusterismo</p>
                        <p class="bookAuthor mb-0">Jose Rizal</p>
                    </div>
                    <div class="cell"><span class="issue-badge-red">Wrong File</span></div>
                    <div class="cell">Juan dela Cruz</div>
                    <div class="cell">Nov 05</div>
                    <div class="cell"><span class="status-badge-red">Reviewing</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

                <div class="reportRow" data-name="Florante at Laura" data-date="2026-11-06" data-status="new">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Florante at Laura</p>
                        <p class="bookAuthor mb-0">Francisco Balagtas</p>
                    </div>
                    <div class="cell"><span class="issue-badge-pink">Broken Link</span></div>
                    <div class="cell">Pedro Penduko</div>
                    <div class="cell">Nov 06</div>
                    <div class="cell"><span class="status-badge-green">New</span></div>
                    <div class="cell">
                        <form method="post" action="adminlog.php">
                            <input type="hidden" name="action" value="forward">
                            <input type="hidden" name="report_id" value="11">
                            <button class="action-btn" type="submit">Forward</button>
                        </form>
                    </div>
                </div>

                <div class="reportRow" data-name="Ibong Adarna" data-date="2026-10-15" data-status="forwarded">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Ibong Adarna</p>
                        <p class="bookAuthor mb-0">Unknown</p>
                    </div>
                    <div class="cell"><span class="issue-badge-blue">License Concern</span></div>
                    <div class="cell">Darna</div>
                    <div class="cell">Oct 15</div>
                    <div class="cell"><span class="status-badge-amber">Forwarded</span></div>
                    <div class="cell"><span class="action-badge-green">Forwarded</span></div>
                </div>

            </div>
        </div>

        <p class="pageInfo">Showing 1-8 of 12</p>

        <div class="logPager">

            <ul class="pagination"></ul>

            <form class="perPage" method="get" action="adminlog.php">
                <label for="perPage">Rows Per Page:</label>

                <select class="form-select" id="perPage" name="per_page">
                    <option value="8" selected>8</option>
                    <option value="12">12</option>
                    <option value="20">20</option>
                    <option value="40">40</option>
                </select>
            </form>

        </div>

    </section>

</main>

<!-- FOOTER -->
<footer class="text-light mt-5 py-5">

    <div class="container">

        <div class="row justify-content-around gy-4">

            <div class="col-md-5">

                <img src="../frontend/assets/BiblioDesk_White_name.png"
                     alt="BiblioDesk Logo"
                     height="56"
                     class="footerLogo">

                <ul class="list-unstyled lh-lg mt-3 ps-4">
                    <li><a class="footerLink" href="terms_privacy.php">Terms of Use</a></li>
                    <li><a class="footerLink" href="terms_privacy.php">Privacy Policy</a></li>
                    <li><a class="footerLink" href="#">More About BiblioDesk</a></li>
                    <li><a class="footerLink" href="#">BiblioDesk Developers</a></li>
                </ul>

            </div>

            <div class="col-md-5">
                <h2 class="h4 text-uppercase">Contact Us</h2>
                <p class="lh-lg">
                    bibliodesk@gmail.com<br>
                    bibliodesk.help@gmail.com
                </p>
            </div>

        </div>

    </div>

</footer>

<script src="../frontend/js/notif.js" defer></script>
<script src="../frontend/js/librarianlog.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>