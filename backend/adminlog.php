<!-- The pagination, search, and rows-per-page features
don't work properly because the PHP backend doesn't
process the data, while JavaScript only handles
the 8 rows currently displayed -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Logs | BiblioDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/adminlog.css">
</head>

<body>
<!-- SIDEBAR -->
<aside class="offcanvas-md offcanvas-start d-flex flex-column text-light"
       tabindex="-1"
       id="sidebar"
       data-bs-scroll="true">

    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar"
                aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">

        <div class="logoContain d-flex align-items-center px-3">
            <img src="../frontend/assets/BiblioDesk_IconW.png"
                 alt="BiblioDesk Logo"
                 width="48">
        </div>

        <nav aria-label="Main">
            <ul class="nav flex-column gap-3 pt-4">

                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                       href="userdb.php">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/ow-default.png" alt="Overview" width="28">
                        </span>
                        <span class="sideText">Overview</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/mybooks-default.png" alt="My Books" width="28">
                        </span>
                        <span class="sideText">My Books</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#">
                        <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/lib-default.png" alt="Library" width="28">
                        </span>
                        <span class="sideText">Library</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"href="adminlog.php" aria-current="page">
                        <span class="sideIcon sideIconActive bg-light rounded-circle d-flex align-items-center justify-content-center">
                            <img src="../frontend/assets/icons/due.png" alt="Report Logs" width="28">
                        </span>
                        <span class="sideText">Report Logs</span>
                    </a>
                </li>
                <li class="nav-item">
                 <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="admin_announcement.php" aria-current="page">
                  <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                     <img src="../frontend/assets/icons/announcement.png" alt="announcements page" width="28">
                        </span>
                         <span class="sideText">Announcements</span>
                      </a>
                 </li>

            </ul>
        </nav>

        <div class="mt-auto pb-4">
            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
               href="logout.php">
                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                    <img src="../frontend/assets/icons/logout.png" alt="Log Out" width="28">
                </span>
                <span class="sideText">Log Out</span>
            </a>
        </div>

    </div>
</aside>


<!-- TOP NAVBAR -->
<header class="navbar navbar-dark shadow topbar">

    <div class="container-fluid">

        <div class="d-flex align-items-center gap-2">

            <button class="navbar-toggler d-md-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar"
                    aria-controls="sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <a class="navbar-brand m-0" href="liblog.php">
                <img src="../frontend/assets/BiblioDesk_White_name.png"
                     alt="BiblioDesk"
                     height="26">
            </a>

        </div>

        <div class="d-flex align-items-center gap-4">

            <!-- ANNOUNCEMENTS -->
            <div class="dropdown">

                <button type="button"
                        class="btn p-0 border-0 position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open announcements">
                    <img src="../frontend/assets/icons/notifbell.png" alt="Notifications" width="34">
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill notifBadge"> 4
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end notifMenu">

                    <li class="notifHead">
                        <span>Announcements</span>
                        <span class="notifHeadCount">4 new</span>
                    </li>

                    <li>
                        <a class="dropdown-item notifItem" href="#">
                            <span class="notifDot dotGreen"></span>
                            <span class="notifText">
                                <span class="notifTitle">New arrivals in Fiction</span>
                                <span class="notifBody">12 new titles added to the Fiction shelf this week.</span>
                                <span class="notifDate">Today</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item notifItem" href="#">
                            <span class="notifDot dotGreen"></span>
                            <span class="notifText">
                                <span class="notifTitle">2 books approved for your reservation</span>
                                <span class="notifBody">Pick them up at the front desk before Friday.</span>
                                <span class="notifDate">Yesterday</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item notifItem" href="#">
                            <span class="notifDot dotTeal"></span>
                            <span class="notifText">
                                <span class="notifTitle">Digital Library ID QR available</span>
                                <span class="notifBody">Scan your QR on the Overview page for faster checkout.</span>
                                <span class="notifDate">3 days ago</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item notifItem" href="#">
                            <span class="notifDot dotBlue"></span>
                            <span class="notifText">
                                <span class="notifTitle">Annual Book Fest — November 10</span>
                                <span class="notifBody">Join us at the library hall for the annual book fest.</span>
                                <span class="notifDate">5 days ago</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item notifItem" href="#">
                            <span class="notifDot dotYellow"></span>
                            <span class="notifText">
                                <span class="notifTitle">Policy update: 3 books per week</span>
                                <span class="notifBody">Users can now borrow up to 3 books per week.</span>
                                <span class="notifDate">Oct 16</span>
                            </span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item notifItem" href="#">
                            <span class="notifDot dotRed"></span>
                            <span class="notifText">
                                <span class="notifTitle">System maintenance on Oct 31</span>
                                <span class="notifBody">5:00 pm to 8:00 pm (GMT+8). All services will be paused.</span>
                                <span class="notifDate">Oct 08</span>
                            </span>
                        </a>
                    </li>

                </ul>
            </div>

            <!-- SETTINGS -->
            <a href="userdb.php?tab=settings" aria-label="Settings">
                <img src="../frontend/assets/icons/settings.png"
                     alt="Settings"
                     width="34">
            </a>

            <!-- PROFILE -->
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


<!-- MAIN CONTENT -->
<main class="container-fluid px-3 px-md-5 py-4">

    <h1 class="pageTitle">Report Logs</h1>

    <!-- QUICK STATS -->
    <section class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">

        <div class="col">
            <div class="cardsColor card border-0 shadow rounded-4 h-100">
                <div class="card-body">
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
                <div class="card-body">
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
                <div class="card-body">
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
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="greenBox d-inline-block p-4 rounded-3"></span>
                        <p class="display-4 mb-0">0</p>
                    </div>
                    <p class="text-end fs-5 mb-0 mt-3">Resolved Reports This Week</p>
                </div>
            </div>
        </div>

    </section>


    <!-- TOOLBAR -->
    <section class="toolbar">

        <form class="searchWrap" method="get" action="liblog.php" role="search">
            <input class="form-control searchInput"
                   type="search"
                   name="q"
                   value=""
                   placeholder="Search report..."
                   aria-label="Search reports">
        </form>

        <a class="btnExport" href="liblog-export.php">
            Export CSV
        </a>

<!-- FLOATING FILTER -->
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

                <form id="filterForm" method="get" action="liblog.php">

                    <h5 class="filterTitle">Filter Reports</h5>

                    <div class="mb-3">
                        <label class="form-label" for="sortBy">Sort By</label>
                        <!-- FIX: added name="sort" so the value is submitted/readable -->
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

                        <a class="btnReset" href="liblog.php">
                            Reset
                        </a>
                    </div>

                </form>
            </div>
        </div>

    </section>

    <!-- REPORT TABLE -->
    <section>

        <div class="tableScroll">

            <!-- FIX: each .reportRow now carries data-name, data-date (YYYY-MM-DD), data-status -->
            <div class="logTable">

                <div class="tableHead">
                    <span>Name</span>
                    <span>Issue</span>
                    <span>Reported by</span>
                    <span>Date</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>

                <!-- ROW 1 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-11-01" data-status="new">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-pink">Broken Link</span>
                    </div>

                    <div class="cell">Victor Magtanggol</div>
                    <div class="cell">Today</div>

                    <div class="cell">
                        <span class="status-badge-green">New</span>
                    </div>

                    <div class="cell">
                        <form method="post" action="liblog.php">
                            <input type="hidden" name="action" value="forward">
                            <input type="hidden" name="report_id" value="1">
                            <button class="action-btn" type="submit">Forward</button>
                        </form>
                    </div>
                </div>

                <!-- ROW 2 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-10-31" data-status="reviewing">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-amber">Missing Page/s</span>
                    </div>

                    <div class="cell">Sofia the 1st</div>
                    <div class="cell">Yesterday</div>

                    <div class="cell">
                        <span class="status-badge-red">Reviewing</span>
                    </div>

                    <div class="cell">
                        <span class="action-badge-green">Forwarded</span>
                    </div>
                </div>

                <!-- ROW 3 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-10-23" data-status="forwarded">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-red">Wrong File</span>
                    </div>

                    <div class="cell">Dora the Explorer</div>
                    <div class="cell">Oct 23</div>

                    <div class="cell">
                        <span class="status-badge-amber">Forwarded</span>
                    </div>

                    <div class="cell">
                        <span class="action-badge-green">Forwarded</span>
                    </div>
                </div>

                <!-- ROW 4 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-10-03" data-status="resolved">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-blue">License Concern</span>
                    </div>

                    <div class="cell">Cardo Dalisay</div>
                    <div class="cell">Oct 03</div>

                    <div class="cell">
                        <span class="status-badge-darkred">Resolved</span>
                    </div>

                    <div class="cell">
                        <span class="action-badge-green">Forwarded</span>
                    </div>
                </div>

                <!-- ROW 5 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-10-03" data-status="resolved">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-blue">License Concern</span>
                    </div>

                    <div class="cell">Cardo Dalisay</div>
                    <div class="cell">Oct 03</div>

                    <div class="cell">
                        <span class="status-badge-darkred">Resolved</span>
                    </div>

                    <div class="cell">
                        <span class="action-badge-green">Forwarded</span>
                    </div>
                </div>

                <!-- ROW 6 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-10-23" data-status="forwarded">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-red">Wrong File</span>
                    </div>

                    <div class="cell">Dora the Explorer</div>
                    <div class="cell">Oct 23</div>

                    <div class="cell">
                        <span class="status-badge-amber">Forwarded</span>
                    </div>

                    <div class="cell">
                        <span class="action-badge-green">Forwarded</span>
                    </div>
                </div>

                <!-- ROW 7 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-11-01" data-status="new">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-pink">Broken Link</span>
                    </div>

                    <div class="cell">Victor Magtanggol</div>
                    <div class="cell">Today</div>

                    <div class="cell">
                        <span class="status-badge-green">New</span>
                    </div>

                    <div class="cell">
                        <form method="post" action="liblog.php">
                            <input type="hidden" name="action" value="forward">
                            <input type="hidden" name="report_id" value="7">
                            <button class="action-btn" type="submit">Forward</button>
                        </form>
                    </div>
                </div>

                <!-- ROW 8 -->
                <div class="reportRow" data-name="Book Title" data-date="2026-11-01" data-status="new">
                    <div class="cellName">
                        <p class="bookTitle mb-0">Book Title</p>
                        <p class="bookAuthor mb-0">Book Author</p>
                    </div>

                    <div class="cell">
                        <span class="issue-badge-pink">Broken Link</span>
                    </div>

                    <div class="cell">Victor Magtanggol</div>
                    <div class="cell">Today</div>

                    <div class="cell">
                        <span class="status-badge-green">New</span>
                    </div>

                    <div class="cell">
                        <form method="post" action="liblog.php">
                            <input type="hidden" name="action" value="forward">
                            <input type="hidden" name="report_id" value="8">
                            <button class="action-btn" type="submit">Forward</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <p class="pageInfo">Showing 1-8 of 80</p>

        <!-- PAGINATION -->
        <div class="logPager">

            <ul class="pagination">
                <li class="page-item disabled">
                    <a class="page-link" href="#">Prev</a>
                </li>

                <li class="page-item active">
                    <a class="page-link" href="liblog.php?page=1">1</a>
                </li>

                <li class="page-item">
                    <a class="page-link" href="liblog.php?page=2">2</a>
                </li>

                <li class="page-item">
                    <a class="page-link" href="liblog.php?page=3">3</a>
                </li>

                <li class="page-item">
                    <a class="page-link" href="liblog.php?page=2">Next</a>
                </li>
            </ul>

            <form class="perPage" method="get" action="liblog.php">
                <label for="perPage">Rows Per Page:</label>

                <select class="form-select"
                        id="perPage"
                        name="per_page"
                        onchange="this.form.submit()">

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
    <script src="../frontend/js/librarianlog.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>



</body>
</html>