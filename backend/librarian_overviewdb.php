<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Overview | BiblioDesk</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/root.css">
    <link rel="stylesheet" href="../frontend/css/admindb_overview.css?v=2">
</head>

<body>
    <!-- SIDEBAR -->
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
                            href="admindb_overview.php" aria-current="page">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admin_announcement.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/announcement.png" alt="announcements page"
                                    width="28">
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
                                <img src="../frontend/assets/icons/business-mail.png" alt="accounts management page"
                                    width="28">
                            </span>
                            <span class="sideText">User Management</span>
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

    <div class="modal fade qaModal" id="todoModal" tabindex="-1" aria-labelledby="todoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="todoModalLabel">Manage To-Do</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-unstyled mb-4" id="todoManageList"></ul>

                    <form id="todoForm" novalidate>
                        <h6 class="fw-bold mb-3" id="todoFormTitle">Add a to-do</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-5">
                                <label class="form-label" for="todoDate">Date</label>
                                <input class="form-control" type="date" id="todoDate">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label" for="todoTitle">Title</label>
                                <input class="form-control" type="text" id="todoTitle" maxlength="40">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label" for="todoDesc">Description</label>
                            <input class="form-control" type="text" id="todoDesc" maxlength="80">
                        </div>
                    </form>
                </div>

                <div class="modal-footer justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-dark d-none" id="todoCancelEdit">Cancel edit</button>
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Done</button>
                    <button type="submit" form="todoForm" class="btn" id="todoSave">Add to-do</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade qaModal" id="qaBookModal" tabindex="-1" aria-labelledby="qaBookLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaBookLabel">Add New Book</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="qaBookForm" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="qaBookTitle">Book title</label>
                            <input class="form-control" type="text" id="qaBookTitle" maxlength="120">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="qaBookAuthor">Author</label>
                            <input class="form-control" type="text" id="qaBookAuthor" maxlength="80">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-8">
                                <label class="form-label" for="qaBookCategory">Category</label>
                                <select class="form-select" id="qaBookCategory"></select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="qaBookCopies">Copies</label>
                                <input class="form-control" type="number" id="qaBookCopies" min="1" value="1">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a class="qaFullLink me-auto" href="librarymanagement.php">Open Library Management</a>
                        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn qaPrimary">Add Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade qaModal" id="qaUsersModal" tabindex="-1" aria-labelledby="qaUsersLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaUsersLabel">Users</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input class="form-control mb-3" type="search" id="qaUserSearch"
                        placeholder="Search name, email or role..." aria-label="Search users">
                    <div class="table-responsive">
                        <table class="table qaTable align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="qaUserRows"></tbody>
                        </table>
                    </div>
                    <p class="small mt-3 mb-0 qaCount" id="qaUserCount"></p>
                </div>
                <div class="modal-footer">
                    <a class="qaFullLink me-auto" href="AdminAccManagedb.php">Open Account Manage</a>
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade qaModal" id="qaLogsModal" tabindex="-1" aria-labelledby="qaLogsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaLogsLabel">Recent Report Logs</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table qaTable align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Issue</th>
                                    <th>Reported by</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="qaLogRows"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="qaFullLink me-auto" href="adminlog.php">Open Report Logs</a>
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade qaModal" id="qaReportModal" tabindex="-1" aria-labelledby="qaReportLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaReportLabel">Generate Report</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="qaReportForm" novalidate>
                    <div class="modal-body">
                        <label class="form-label" for="qaReportType">Report type</label>
                        <select class="form-select" id="qaReportType">
                            <option value="users">Users</option>
                            <option value="logs">Report Logs</option>
                        </select>
                        <p class="small mt-3 mb-0 qaCount">The report downloads as a CSV file you can open in Excel.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn qaPrimary">Download CSV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="qaToast" class="toast qaToast" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="qaToastBody"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
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
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="admindb_overview.php">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>

            <div class="d-flex align-items-center gap-4 topActions">
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
                <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile" class="rounded-circle" width="44"
                        height="44">
                </button>
            </div>
        </div>
    </header>

    <main class="container-fluid py-4">
        <section class="row g-4 mb-5" aria-label="Librarian summary">
            <div class="col-12 col-lg-8">
                <div class="darkCard card border-0 shadow rounded-4 h-100">
                    <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center">
                        <h1 class="display-6 fw-semibold mb-3">Sofia Balthazar-Winslow I</h1>
                        <h2 class="h4 fw-semibold mb-3">Librarian</h2>
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
                        <div class="todoHead d-flex align-items-center justify-content-center pb-2">
                            <h2 class="h5 mb-0">TO-DO</h2>
                            <button type="button" class="todoAdd" data-bs-toggle="modal" data-bs-target="#todoModal"
                                aria-label="Add or edit to-do items">+</button>
                        </div>

                        <ul class="list-unstyled mb-0 mt-3" id="todoList"></ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-4 mb-5" aria-label="Activity and actions">
            <div class="col-12 col-lg-9">
                <div class="lightCard card h-100 rounded-4 shadow">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
                            <h2 class="h6 fw-bold mb-0" id="popTitle">Popular Category this Month</h2>
                            <p class="small mb-0 popMonth" id="popMonth"></p>
                        </div>

                        <div class="popScroll">
                            <div class="popChart" id="popChart" role="img"
                                aria-label="Most borrowed book categories this month"></div>
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
                                <button type="button" class="actionLink actionBtn d-flex align-items-center gap-3"
                                    data-bs-toggle="modal" data-bs-target="#qaBookModal">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>Add New Book</span>
                                </button>
                            </li>
                            <li class="actionItem">
                                <button type="button" class="actionLink actionBtn d-flex align-items-center gap-3"
                                    data-bs-toggle="modal" data-bs-target="#qaUsersModal">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>View Users</span>
                                </button>
                            </li>
                            <li class="actionItem">
                                <button type="button" class="actionLink actionBtn d-flex align-items-center gap-3"
                                    data-bs-toggle="modal" data-bs-target="#qaLogsModal">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>View Logs</span>
                                </button>
                            </li>
                            <li class="actionItem">
                                <button type="button" class="actionLink actionBtn d-flex align-items-center gap-3"
                                    data-bs-toggle="modal" data-bs-target="#qaReportModal">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>Generate Reports</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

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

    <!-- FOOTER -->
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
    <script src="../frontend/js/admin_popular.js" defer></script>
    <script src="../frontend/js/admin_quickactions.js" defer></script>
    <script src="../frontend/js/admin_todo.js" defer></script>
</body>

</html>