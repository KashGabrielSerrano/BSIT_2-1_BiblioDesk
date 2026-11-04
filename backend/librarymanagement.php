<?php
/**
 * WAG AALISIN YUNG COMMENT BLOCK NA TO PLS :)
 * backend/librarymanagement.php
 * Librarian SPA: Digital Catalog | Account Management | Report Logs
 * API: librarymanagement_controller.php   JS: ../frontend/js/librarymanagement.js
 *
 * Layout (top to bottom):
 *   1. PHP bootstrap & security guard   5. Main content area (SPA panels)
 *   2. HTML head & assets               6. Modals & toast containers
 *   3. Sidebar navigation               7. Footer
 *   4. Top navbar                       8. JavaScript
 */

/* ========================================================================== */
/* 1. PHP BOOTSTRAP & SECURITY GUARD                                          */
/* ========================================================================== */

session_start();

/* ---- Role authorization BYPASS (WAG GAGALAWIN TO KUKURITIN KO KAYO) ----
 * DEV BYPASS: role lock disabled so this page can be opened directly while testing.
 * Re-enable before deployment. Roles in the session are 'Student' | 'Librarian' | 'Admin'.
 *
 * if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['Librarian', 'Admin'], true)) {
 *     header('Location: login.php');
 *     exit();
 * }
 */

/* ---- CSRF token ----
 * The controller enforces CSRF on every POST. If login is bypassed, there may be no token yet. */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<!-- ========================================================================== -->
<!-- 2. HTML HEAD & ASSETS                                                      -->
<!-- ========================================================================== -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk - Library Management</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Librarian page styles: ONE file. It @imports root.css (colors/sizes) itself. Must load AFTER Bootstrap. -->
    <link rel="stylesheet" href="../frontend/css/librarian.css">
</head>

<body>
    <!-- ========================================================================== -->
    <!-- 3. SIDEBAR NAVIGATION (Librarian offcanvas)                                -->
    <!-- ========================================================================== -->
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
                            href="#catalog" data-main-tab="catalog" aria-current="page">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/lib-default.png" alt="catalog" width="28">
                            </span>
                            <span class="sideText">Catalog</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="#accounts" data-main-tab="accounts">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="accounts management" width="28">
                            </span>
                            <span class="sideText">Accounts</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="#reports" data-main-tab="reports">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="report logs" width="28">
                            </span>
                            <span class="sideText">Report Logs</span>
                        </a>
                    </li>
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

    <!-- ========================================================================== -->
    <!-- 4. TOP NAVBAR                                                              -->
    <!-- ========================================================================== -->
    <header class="navbar navbar-dark shadow topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="librarymanagement.php">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>
            <div class="d-flex align-items-center gap-4">
                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                    <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                </button>
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

    <!-- ========================================================================== -->
    <!-- 5. MAIN CONTENT AREA (catalog | accounts | reports)                        -->
    <!-- ========================================================================== -->
    <main>
        <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-3">
            <h1 class="fw-bold mb-0 p-1">Library Management</h1>
        </div>

        <!-- MAIN SPA TABS -->
        <ul class="nav amTabs mb-4" role="tablist" aria-label="Library management sections">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" type="button" role="tab" data-main-tab="catalog" aria-selected="true">
                    Digital Catalog
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" data-main-tab="accounts" aria-selected="false">
                    Account Management
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" data-main-tab="reports" aria-selected="false">
                    Report Logs
                </button>
            </li>
        </ul>

        <!-- ================================================================ -->
        <!-- PANEL 1: DIGITAL CATALOG                                         -->
        <!-- ================================================================ -->
        <div id="panel-catalog" data-main-panel="catalog">

            <!-- Add Book -->
            <section class="my-4">
                <div class="panelCard card shadow border-secondary rounded-4 overflow-hidden">
                    <div class="card-body p-4" style="background-color: var(--main-color);">
                        <h2 class="card-header text-white border-bottom border-secondary mb-4 bg-transparent px-0">
                            Add New Digital Book</h2>

                        <form id="addBookForm" data-book-form novalidate>
                            <div class="row g-3">
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addTitle">Book Title:</label>
                                    <input type="text" id="addTitle" name="title" class="form-control" required
                                        maxlength="255" placeholder="Title">
                                </div>
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addAuthor">Author:</label>
                                    <input type="text" id="addAuthor" name="author" class="form-control" required
                                        maxlength="255" placeholder="Author">
                                </div>
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addIsbn">ISBN:</label>
                                    <input type="text" id="addIsbn" name="isbn" class="form-control" maxlength="20"
                                        placeholder="978-3-16-148410-0">
                                </div>
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addPublisher">Publisher:</label>
                                    <input type="text" id="addPublisher" name="publisher" class="form-control"
                                        maxlength="150" placeholder="Publisher (optional)">
                                </div>
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addCategory">Category:</label>
                                    <select id="addCategory" name="category" class="form-select" required>
                                        <option value="" selected disabled>Select a category</option>
                                        <option value="Educational">Educational</option>
                                        <option value="Non-Educational">Non-Educational</option>
                                        <option value="Research Articles">Research Articles</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addIsDigital">Digital Access:</label>
                                    <select id="addIsDigital" name="is_digital" class="form-select">
                                        <option value="Yes" selected>Yes – PDF available online</option>
                                        <option value="No">No – physical copy only</option>
                                    </select>
                                </div>
                                <div class="form-group col-12 text-white fw-semibold">
                                    <label class="form-label" for="addDescription">Description:</label>
                                    <textarea id="addDescription" name="description" class="form-control" rows="3"
                                        required maxlength="5000" placeholder="Enter book summary..."></textarea>
                                </div>
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addCoverFile">Cover Image:</label>
                                    <input type="file" id="addCoverFile" name="cover_file" class="form-control mb-2"
                                        accept="image/jpeg,image/png,image/webp">
                                    <div class="form-text text-white-50 mb-2">JPG, PNG or WEBP, up to 5 MB. Or paste an image URL instead:</div>
                                    <label class="visually-hidden" for="addCover">Cover Image URL</label>
                                    <input type="text" id="addCover" name="cover_image" class="form-control"
                                        maxlength="255" placeholder="https://.../cover.jpg">
                                </div>
                                <div class="form-group col-md-6 text-white fw-semibold">
                                    <label class="form-label" for="addPdfFile">PDF Document:</label>
                                    <input type="file" id="addPdfFile" name="pdf_file" class="form-control mb-2"
                                        accept="application/pdf,.pdf">
                                    <div class="form-text text-white-50 mb-2">PDF, up to 20 MB. Or paste a Supabase https link instead:</div>
                                    <label class="visually-hidden" for="addDrive">PDF Document URL (https)</label>
                                    <input type="url" id="addDrive" name="drive_link" class="form-control"
                                        maxlength="255" required
                                        placeholder="https://<project>.supabase.co/storage/v1/object/public/...pdf">
                                </div>
                                <div class="form-group col-12 pt-3 d-flex justify-content-end">
                                    <button type="submit"
                                        class="submitBookBtn px-4 py-2 fw-semibold rounded-3 text-white border-0"
                                        style="background-color: var(--btn);">Save &amp; Publish Book</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Catalog list -->
            <section class="catalog-container my-4 mt-5">
                <div class="card shadow border-0 rounded-4 overflow-hidden mb-5"
                    style="background-color: var(--main-color);">
                    <div class="card-body p-4">
                        <div class="border-bottom border-secondary pb-3 mb-4 d-flex justify-content-between align-items-baseline">
                            <h2 class="text-white fw-bold mb-0">Catalog List</h2>
                            <span id="catalogCount" class="text-white-50 small" aria-live="polite"></span>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                            <form class="searchForm flex-grow-1" role="search" onsubmit="return false;"
                                style="min-width: 200px;">
                                <div class="catalogSearchBox w-100">
                                    <i class="bi bi-search"></i>
                                    <input type="search" id="catalogSearch" class="form-control rounded-3 py-2"
                                        placeholder="Search title, author, ISBN, category" aria-label="Search library"
                                        autocomplete="off">
                                </div>
                            </form>

                            <div class="dropdown">
                                <button
                                    class="btn filterBtn d-flex align-items-center gap-2 dropdown-toggle text-white rounded-3 py-2 px-3"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="background-color: var(--btn);">
                                    <i class="bi bi-funnel"></i> <span id="catalogSortLabel">Newest To Oldest</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item js-catalog-sort" href="#" data-sort="newest">Newest To Oldest</a></li>
                                    <li><a class="dropdown-item js-catalog-sort" href="#" data-sort="oldest">Oldest To Newest</a></li>
                                </ul>
                            </div>
                        </div>

                        <div class="table-responsive table-container rounded-3 overflow-hidden shadow-sm">
                            <table class="table table-hover align-middle mb-0 text-center catalogTable">
                                <thead>
                                    <tr>
                                        <th class="border-end border-muted">Cover</th>
                                        <th class="border-end border-muted text-start">Title</th>
                                        <th class="border-end border-muted">Author</th>
                                        <th class="border-end border-muted">Category</th>
                                        <th class="border-end border-muted">Digital Access</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="catalogBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- ================================================================ -->
        <!-- PANEL 2: ACCOUNT MANAGEMENT                                      -->
        <!-- ================================================================ -->
        <div id="panel-accounts" data-main-panel="accounts" class="d-none">
            <ul class="nav subTabs gap-2 mb-4" role="tablist" aria-label="Student account groups">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" type="button" role="tab" data-account-tab="users" aria-selected="true">
                        Student Accounts <span class="tabBadge green" id="countUsers">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" type="button" role="tab" data-account-tab="pending" aria-selected="false">
                        Pending Approvals <span class="tabBadge amber" id="countPending">0</span>
                    </button>
                </li>
            </ul>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                <input type="search" id="accountSearch" class="form-control accountSearchBox flex-grow-1"
                    placeholder="Search name, email, student no., department" aria-label="Search account"
                    autocomplete="off">
                <select id="accountStatusFilter" class="form-select w-auto" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="Active">Active</option>
                    <option value="Suspended">Suspended</option>
                </select>
            </div>

            <p id="accountResultCount" class="small mb-2" aria-live="polite"></p>

            <p class="scrollHint">Swipe sideways to see all columns &rarr;</p>
            <div class="amTableWrap">
                <table class="amTable fiveCol" id="amTable">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Student<br>No.</th>
                            <th scope="col">Department</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="accountRows"></tbody>
                </table>
            </div>
        </div>

        <!-- ================================================================ -->
        <!-- PANEL 3: REPORT LOGS                                             -->
        <!-- ================================================================ -->
        <div id="panel-reports" data-main-panel="reports" class="d-none">
            <section class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">
                <div class="col">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="redBox d-inline-block p-4 rounded-3"></span>
                                <p class="display-4 mb-0" id="statCurrent">0</p>
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
                                <p class="display-4 mb-0" id="statForwarded">0</p>
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
                                <p class="display-4 mb-0" id="statBroken">0</p>
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
                                <p class="display-4 mb-0" id="statResolved">0</p>
                            </div>
                            <p class="text-end fs-5 mb-0 mt-3">Resolved Reports This Week</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="toolbar">
                <form class="searchWrap" role="search" onsubmit="return false;">
                    <input class="form-control searchInput" type="search" id="reportSearch"
                        placeholder="Search book, author or issue..." aria-label="Search reports" autocomplete="off">
                </form>

                <div class="dropdown filterDropdown ms-auto">
                    <button class="btnFilter" type="button" id="reportFilterToggle" data-bs-toggle="dropdown"
                        data-bs-display="static" data-bs-auto-close="outside" aria-expanded="false"
                        aria-label="Open filters">
                        <img src="../frontend/assets/icons/filter.png" alt="Filter" width="22">
                    </button>
                    <div class="dropdown-menu dropdown-menu-end filterPanel" aria-labelledby="reportFilterToggle">
                        <form id="reportFilterForm">
                            <h5 class="filterTitle">Filter Reports</h5>
                            <div class="mb-3">
                                <label class="form-label" for="reportSort">Sort By</label>
                                <select class="form-select" id="reportSort">
                                    <option value="date-newest">Date: Newest first</option>
                                    <option value="date-oldest">Date: Oldest first</option>
                                    <option value="name-asc">Book (A - Z)</option>
                                    <option value="name-desc">Book (Z - A)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="reportStatus">Status</label>
                                <select class="form-select" id="reportStatus">
                                    <option value="">All statuses</option>
                                    <option value="New">New</option>
                                    <option value="Reviewing">Reviewing</option>
                                    <option value="Forwarded">Forwarded</option>
                                    <option value="Resolved">Resolved</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btnApply" type="submit">Apply Filters</button>
                                <button class="btnReset" type="button" id="reportFilterReset">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section>
                <div class="tableScroll">
                    <div class="logTable">
                        <div class="tableHead">
                            <span>Book</span>
                            <span>Issue</span>
                            <span>Reported by</span>
                            <span>Date</span>
                            <span>Status</span>
                            <span>Action</span>
                        </div>
                        <div id="reportRows"></div>
                    </div>
                </div>

                <p class="pageInfo" id="reportInfo" aria-live="polite"></p>

                <div class="logPager">
                    <ul class="pagination" id="reportPagination"></ul>
                    <div class="perPage">
                        <label for="reportPerPage">Rows Per Page:</label>
                        <select class="form-select" id="reportPerPage">
                            <option value="8" selected>8</option>
                            <option value="12">12</option>
                            <option value="20">20</option>
                            <option value="40">40</option>
                        </select>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- ========================================================================== -->
    <!-- 6. MODALS & TOAST CONTAINERS                                               -->
    <!-- ========================================================================== -->

    <!-- EDIT BOOK MODAL -->
    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content" style="background-color: var(--main-bg-color);">
                <div class="modal-header text-white border-secondary" style="background-color: var(--main-color);">
                    <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="editBookForm" data-book-form novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="book_id" value="">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editTitle">Book Title</label>
                                <input type="text" id="editTitle" name="title" class="form-control" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editAuthor">Author</label>
                                <input type="text" id="editAuthor" name="author" class="form-control" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editIsbn">ISBN</label>
                                <input type="text" id="editIsbn" name="isbn" class="form-control" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editPublisher">Publisher</label>
                                <input type="text" id="editPublisher" name="publisher" class="form-control" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editCategory">Category</label>
                                <select id="editCategory" name="category" class="form-select" required>
                                    <option value="" disabled>Select a category</option>
                                    <option value="Educational">Educational</option>
                                    <option value="Non-Educational">Non-Educational</option>
                                    <option value="Research Articles">Research Articles</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editIsDigital">Digital Access</label>
                                <select id="editIsDigital" name="is_digital" class="form-select">
                                    <option value="Yes">Yes - PDF available online</option>
                                    <option value="No">No - physical copy only</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold" for="editDescription">Description</label>
                                <textarea id="editDescription" name="description" class="form-control" rows="3" required
                                    maxlength="5000"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editCoverFile">Cover Image</label>
                                <input type="file" id="editCoverFile" name="cover_file" class="form-control mb-2"
                                    accept="image/jpeg,image/png,image/webp">
                                <div class="form-text mb-2">Choose a file to replace the current cover (JPG, PNG or WEBP, up to 5 MB), or edit the URL below.</div>
                                <label class="visually-hidden" for="editCover">Cover Image URL</label>
                                <input type="text" id="editCover" name="cover_image" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="editPdfFile">PDF Document</label>
                                <input type="file" id="editPdfFile" name="pdf_file" class="form-control mb-2"
                                    accept="application/pdf,.pdf">
                                <div class="form-text mb-2">Choose a file to replace the current PDF (up to 20 MB), or edit the https link below.</div>
                                <label class="visually-hidden" for="editDrive">PDF Document URL (https)</label>
                                <input type="url" id="editDrive" name="drive_link" class="form-control" maxlength="255" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editBookSaveBtn" class="btn text-white"
                            style="background-color: var(--btn);">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- GENERIC CONFIRM MODAL (delete book, suspend / deny account) -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-secondary shadow-lg rounded-4"
                style="background-color: var(--main-bg-color);">
                <div class="modal-body text-center p-4 p-md-5">
                    <div class="mb-3 text-danger fs-1"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h3 class="fw-bold mb-2" id="confirmTitle">Are you sure?</h3>
                    <p class="mb-4" id="confirmMessage"></p>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn px-4 py-2 fw-semibold rounded-3 text-white"
                            style="background-color: var(--muted);" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4 py-2 rounded-3 fw-semibold"
                            id="confirmOkBtn">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <!-- TOAST -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="appToast" class="toast amToast" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex align-items-center">
                <div class="toast-body d-flex align-items-center">
                    <i id="toastIcon" class="bi bi-check-circle-fill text-success me-2"></i>
                    <span id="toastText"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- ========================================================================== -->
    <!-- 7. FOOTER                                                                  -->
    <!-- ========================================================================== -->
    <footer class="text-light mt-5 py-5">
        <div class="container">
            <div class="row justify-content-around gy-4">
                <div class="col-12 col-md-5 ps-0">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56"
                        class="footerLogo">
                    <ul class="list-unstyled lh-lg mt-3 ps-4 mb-0">
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=terms">Terms of Use</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=privacy">Privacy Policy</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="more.php">More About BiblioDesk</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="aboutus.php">BiblioDesk Developers</a></li>
                    </ul>
                </div>
                <div class="col-12 col-md-5 ps-4 ps-md-0">
                    <h2 class="h4 text-uppercase">Contact Us</h2>
                    <p class="lh-lg mb-0">bibliodesk@gmail.com<br>bibliodesk.help@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- ========================================================================== -->
    <!-- 8. JAVASCRIPT                                                              -->
    <!-- ========================================================================== -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.csrfToken = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    </script>
    <script src="../frontend/js/librarymanagement.js"></script>
</body>

</html>
