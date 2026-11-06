<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | BiblioDesk</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/root.css">
    <link rel="stylesheet" href="../frontend/css/template.css">
    <link rel="stylesheet" href="../frontend/css/admin_announcement.css">
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
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                        <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admin_announcement.php" aria-current="page">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
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
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="admindb.php">
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

    <main class="container-fluid px-3 px-md-5 py-4">
        <!--Page title and create button-->
        <section class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-semibold mb-1">Announcements</h1>
            </div>
            <button class="btn btnMain" id="btnCreate" type="button">+ Create Announcement</button>
        </section>

        <!--Announcement quick stats (numbers are filled in by admin_announcement.js)-->
        <section aria-label="Announcement summary">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">
                <div class="col">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="neutralBox d-inline-block p-4 rounded-3"></span>
                                <p class="display-4 mb-0" id="statTotal">0</p>
                            </div>
                            <p class="text-end fs-5 mb-0 mt-3">Total Announcements</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="greenBox d-inline-block p-4 rounded-3"></span>
                                <p class="display-4 mb-0" id="statPublished">0</p>
                            </div>
                            <p class="text-end fs-5 mb-0 mt-3">Published</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="yellowBox d-inline-block p-4 rounded-3"></span>
                                <p class="display-4 mb-0" id="statScheduled">0</p>
                            </div>
                            <p class="text-end fs-5 mb-0 mt-3">Scheduled</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="tealBox d-inline-block p-4 rounded-3"></span>
                                <p class="display-4 mb-0" id="statDrafts">0</p>
                            </div>
                            <p class="text-end fs-5 mb-0 mt-3">Drafts</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!--Search, filters, sort-->
        <section class="d-flex flex-wrap align-items-center gap-2 mt-5 mb-3" aria-label="Search and filter">
            <input class="form-control searchInput" id="searchInput" type="search" placeholder="Search announcements..." aria-label="Search announcements">

            <select class="form-select w-auto" id="statusFilter" aria-label="Filter by status">
                <option value="">All status</option>
                <option value="published">Published</option>
                <option value="scheduled">Scheduled</option>
                <option value="draft">Draft</option>
            </select>

            <select class="form-select w-auto" id="categoryFilter" aria-label="Filter by category">
                <option value="">All categories</option>
            </select>

            <select class="form-select w-auto" id="sortSelect" aria-label="Sort announcements">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="title-asc">Title (A - Z)</option>
                <option value="title-desc">Title (Z - A)</option>
            </select>

            <button class="btn btn-outline-dark" id="btnReset" type="button">Reset</button>
        </section>

        <!--Announcements list (rows are rendered by admin_announcement.js)-->
        <section class="cardsColor card border-0 shadow rounded-4" aria-labelledby="recentTitle">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-baseline gap-2 px-4 py-3 bg-transparent border-light border-opacity-25">
                <h2 class="h3 mb-0" id="recentTitle">Recent Announcements</h2>
                <p class="small mb-0 pageInfo" id="pageInfo" aria-live="polite"></p>
            </div>

            <div class="card-body p-0">
                <div class="annList" id="annList"></div>
            </div>

            <div class="card-footer px-4 py-3 bg-transparent border-light border-opacity-25">
                <nav class="pager" id="pager" aria-label="Announcements pages"></nav>
            </div>
        </section>
    </main>

    <!--Create / edit announcement-->
    <dialog class="annDialog" id="annDialog">
        <form id="annForm" method="dialog" novalidate>
            <h2 class="h4 fw-semibold mb-4" id="dialogTitle">Create Announcement</h2>

            <div class="mb-3">
                <label class="form-label" for="fTitle">Title</label>
                <input class="form-control" type="text" id="fTitle" maxlength="80" required>
                <small class="err" id="errTitle"></small>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-sm-6">
                    <label class="form-label" for="fCategory">Category</label>
                    <select class="form-select" id="fCategory"></select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label" for="fStatus">Status</label>
                    <select class="form-select" id="fStatus">
                        <option value="published">Published</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <div class="mb-3" id="dateField" hidden>
                <label class="form-label" for="fDate">Publish date</label>
                <input class="form-control" type="date" id="fDate">
                <small class="err" id="errDate"></small>
            </div>

            <div class="mb-3">
                <label class="form-label" for="fBody">Message</label>
                <textarea class="form-control" id="fBody" rows="4" maxlength="240" required></textarea>
                <small class="err" id="errBody"></small>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button class="btn btn-outline-dark" type="button" id="btnCancel">Cancel</button>
                <button class="btn btnMain" type="submit" id="btnSave">Save</button>
            </div>
        </form>
    </dialog>

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
    <script src="../frontend/js/admin_announcement.js" defer></script>
</body>
</html>