<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
require_role_page(['Librarian', 'Admin']);
render_head('BiblioDesk - Library Management', ['librarian.css']);
?>
    <!-- ========================================================================== -->
    <!-- 3. SIDEBAR NAVIGATION (Librarian offcanvas)                                -->
    <!-- ========================================================================== -->
    <?php render_sidebar('staff', 'library'); ?>

    <!-- ========================================================================== -->
    <!-- 4. TOP NAVBAR                                                              -->
    <!-- ========================================================================== -->
    <?php render_topbar('staff'); ?>

    <!-- ========================================================================== -->
    <!-- 5. MAIN CONTENT AREA (catalog | accounts | reports)                        -->
    <!-- ========================================================================== -->
    <main>
        <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-3">
            <h1 class="fw-bold mb-0 p-1">Library Management</h1>
            <!-- L6: open the student library to check how books look to students -->
            <a href="user_librarydb.php?view=library" class="btn btn-outline-dark rounded-pill px-3">
                <i class="bi bi-eye me-1" aria-hidden="true"></i> View as student
            </a>
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
            <li class="nav-item" role="presentation">
                <button class="nav-link" type="button" role="tab" data-main-tab="circulation" aria-selected="false">
                    Circulation
                </button>
            </li>
        </ul>

        <!-- ================================================================ -->
        <!-- PANEL 1: DIGITAL CATALOG                                         -->
        <!-- ================================================================ -->
        <div id="panel-catalog" data-main-panel="catalog">

            <!-- Add Book -->
            <section class="my-4">
                <div class="card shadow border-0 rounded-4 overflow-hidden mb-5" style="background-color: var(--main-color);">
                    <!-- Header with Icon & Subtitle -->
                    <div class="card-header bg-transparent border-bottom border-light border-opacity-25 py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-3 bg-white bg-opacity-10 text-white" style="width: 44px; height: 44px;">
                                <i class="bi bi-journal-plus fs-4 text-warning"></i>
                            </div>
                            <div>
                                <h2 class="h4 text-white fw-bold mb-0">Add New Digital Book</h2>
                                <p class="text-white-50 small mb-0">Register a new physical book or upload digital e-book materials to the catalog</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        <form id="addBookForm" data-book-form novalidate>
                            <div class="row g-3">
                                <!-- Section 1: Book Information -->
                                <div class="col-12 mb-1 d-flex align-items-center gap-2 text-warning fw-semibold">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span class="text-uppercase small tracking-wide">Book Information</span>
                                    <hr class="flex-grow-1 border-light border-opacity-25 my-0 ms-2">
                                </div>

                                <div class="form-group col-md-6">
                                    <label class="form-label text-white small fw-semibold" for="addTitle">
                                        <i class="bi bi-book-half me-1 text-warning"></i> Book Title <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="addTitle" name="title" class="form-control rounded-3 py-2 px-3 bg-light border-0 shadow-sm" required
                                        maxlength="255" placeholder="e.g. Introduction to Algorithms">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label text-white small fw-semibold" for="addAuthor">
                                        <i class="bi bi-person-fill me-1 text-warning"></i> Author <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="addAuthor" name="author" class="form-control rounded-3 py-2 px-3 bg-light border-0 shadow-sm" required
                                        maxlength="255" placeholder="e.g. Thomas H. Cormen">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label text-white small fw-semibold" for="addIsbn">
                                        <i class="bi bi-upc-scan me-1 text-warning"></i> ISBN
                                    </label>
                                    <input type="text" id="addIsbn" name="isbn" class="form-control rounded-3 py-2 px-3 bg-light border-0 shadow-sm" maxlength="20"
                                        placeholder="e.g. 978-0-262-03384-8">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label text-white small fw-semibold" for="addPublisher">
                                        <i class="bi bi-building me-1 text-warning"></i> Publisher
                                    </label>
                                    <input type="text" id="addPublisher" name="publisher" class="form-control rounded-3 py-2 px-3 bg-light border-0 shadow-sm"
                                        maxlength="150" placeholder="e.g. MIT Press (optional)">
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label text-white small fw-semibold" for="addCategory">
                                        <i class="bi bi-tags-fill me-1 text-warning"></i> Category <span class="text-danger">*</span>
                                    </label>
                                    <select id="addCategory" name="category" class="form-select rounded-3 py-2 px-3 bg-light border-0 shadow-sm" required>
                                        <option value="" selected disabled>Select a category</option>
                                        <option value="Educational">Educational</option>
                                        <option value="Non-Educational">Non-Educational</option>
                                        <option value="Research Articles">Research Articles</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="form-label text-white small fw-semibold" for="addIsDigital">
                                        <i class="bi bi-hdd-network-fill me-1 text-warning"></i> Digital Access
                                    </label>
                                    <select id="addIsDigital" name="is_digital" class="form-select rounded-3 py-2 px-3 bg-light border-0 shadow-sm">
                                        <option value="Yes" selected>Yes - PDF available online</option>
                                        <option value="No">No - physical copy only</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6" data-copies-field hidden>
                                    <label class="form-label text-white small fw-semibold" for="addCopies">
                                        <i class="bi bi-stack me-1 text-warning"></i> Physical Copies <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" id="addCopies" name="total_copies" class="form-control rounded-3 py-2 px-3 bg-light border-0 shadow-sm" min="0" max="1000" step="1" value="1">
                                    <div class="form-text text-white-50"><i class="bi bi-info-circle me-1"></i> How many copies students can borrow from the desk.</div>
                                </div>

                                <!-- Section 2: Description -->
                                <div class="col-12 mt-4 mb-1 d-flex align-items-center gap-2 text-warning fw-semibold">
                                    <i class="bi bi-card-text"></i>
                                    <span class="text-uppercase small tracking-wide">Summary &amp; Description</span>
                                    <hr class="flex-grow-1 border-light border-opacity-25 my-0 ms-2">
                                </div>
                                <div class="form-group col-12">
                                    <label class="form-label text-white small fw-semibold" for="addDescription">
                                        <i class="bi bi-text-paragraph me-1 text-warning"></i> Description <span class="text-danger">*</span>
                                    </label>
                                    <textarea id="addDescription" name="description" class="form-control rounded-3 p-3 bg-light border-0 shadow-sm" rows="3"
                                        required maxlength="5000" placeholder="Enter an informative book overview, chapter highlights, or subject topics..."></textarea>
                                </div>

                                <!-- Section 3: Media & Digital Files -->
                                <div class="col-12 mt-4 mb-1 d-flex align-items-center gap-2 text-warning fw-semibold">
                                    <i class="bi bi-paperclip"></i>
                                    <span class="text-uppercase small tracking-wide">Media &amp; Attachments</span>
                                    <hr class="flex-grow-1 border-light border-opacity-25 my-0 ms-2">
                                </div>

                                <!-- Cover Image Card -->
                                <div class="form-group col-md-6">
                                    <div class="p-3 rounded-4 bg-black bg-opacity-25 border border-light border-opacity-10 h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-2 text-white fw-semibold">
                                                <i class="bi bi-image-fill text-warning fs-5"></i>
                                                <span>Cover Image</span>
                                            </div>
                                            <label class="form-label text-white-50 small mb-1" for="addCoverFile">Choose image file (JPG, PNG or WEBP, up to 5 MB):</label>
                                            <input type="file" id="addCoverFile" name="cover_file" class="form-control rounded-3 bg-light border-0 mb-2"
                                                accept="image/jpeg,image/png,image/webp">
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 my-2 text-white-50 small">
                                                <hr class="flex-grow-1 border-light border-opacity-25 my-0">
                                                <span class="text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Or paste image URL</span>
                                                <hr class="flex-grow-1 border-light border-opacity-25 my-0">
                                            </div>
                                            <label class="visually-hidden" for="addCover">Cover Image URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white bg-opacity-10 text-white border-0"><i class="bi bi-link-45deg"></i></span>
                                                <input type="text" id="addCover" name="cover_image" class="form-control rounded-end-3 bg-light border-0 py-2"
                                                    maxlength="255" placeholder="https://.../cover.jpg">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PDF Document Card -->
                                <div class="form-group col-md-6" data-pdf-field>
                                    <div class="p-3 rounded-4 bg-black bg-opacity-25 border border-light border-opacity-10 h-100 d-flex flex-column justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-2 text-white fw-semibold">
                                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-5"></i>
                                                <span>PDF Document</span>
                                            </div>
                                            <label class="form-label text-white-50 small mb-1" for="addPdfFile">Choose PDF document (up to 20 MB):</label>
                                            <input type="file" id="addPdfFile" name="pdf_file" class="form-control rounded-3 bg-light border-0 mb-2"
                                                accept="application/pdf,.pdf">
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 my-2 text-white-50 small">
                                                <hr class="flex-grow-1 border-light border-opacity-25 my-0">
                                                <span class="text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Or paste storage link</span>
                                                <hr class="flex-grow-1 border-light border-opacity-25 my-0">
                                            </div>
                                            <label class="visually-hidden" for="addDrive">PDF Document URL (https)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white bg-opacity-10 text-white border-0"><i class="bi bi-cloud-arrow-up"></i></span>
                                                <input type="url" id="addDrive" name="drive_link" class="form-control rounded-end-3 bg-light border-0 py-2"
                                                    maxlength="255"
                                                    placeholder="https://<project>.supabase.co/storage/v1/object/public/...pdf">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Footer / Buttons -->
                                <div class="col-12 pt-4 border-top border-light border-opacity-25 mt-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <span class="text-white-50 small">
                                        <i class="bi bi-asterisk text-danger me-1"></i> Fields marked with an asterisk are required
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="reset" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                                        </button>
                                        <button type="submit"
                                            class="submitBookBtn btn text-white rounded-pill px-4 py-2 fw-semibold shadow d-inline-flex align-items-center gap-2"
                                            style="background-color: var(--btn);">
                                            <i class="bi bi-check-circle-fill"></i> Save &amp; Publish Book
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <!-- Catalog list -->
            <section class="catalog-container my-4 mt-5">
                <!-- Catalog Header -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <span class="catChip" aria-hidden="true"><i class="bi bi-collection-fill"></i></span>
                            <div>
                                <h2 class="h4 fw-bold mb-0">Catalog List</h2>
                                <p class="small mb-0 opacity-75">Browse, search, edit, and manage registered digital &amp; physical books</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="small d-none d-sm-inline opacity-75">Total:</span>
                            <span id="catalogCount" class="pill auto neutral" aria-live="polite">0 books</span>
                        </div>
                    </div>

                    <!-- Search & Sort Controls -->
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                            <form class="searchForm flex-grow-1" role="search" onsubmit="return false;"
                                style="min-width: 240px;">
                                <div class="input-group catSearch">
                                    <span class="input-group-text ps-3 pe-2 text-secondary">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="search" id="catalogSearch" class="form-control py-2 ps-2 pe-3"
                                        placeholder="Search title, author, ISBN, category..." aria-label="Search library"
                                        autocomplete="off">
                                </div>
                            </form>

                            <div class="dropdown">
                                <button
                                    class="btn filterBtn d-flex align-items-center gap-2 dropdown-toggle text-white rounded-pill py-2 px-4 shadow fw-semibold"
                                    type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                    style="background-color: var(--btn);">
                                    <i class="bi bi-funnel-fill"></i> <span id="catalogSortLabel">Newest To Oldest</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4 mt-2 py-2">
                                    <li><a class="dropdown-item js-catalog-sort py-2 px-3 fw-medium d-flex align-items-center gap-2" href="#" data-sort="newest"><i class="bi bi-sort-down text-success"></i> Newest To Oldest</a></li>
                                    <li><a class="dropdown-item js-catalog-sort py-2 px-3 fw-medium d-flex align-items-center gap-2" href="#" data-sort="oldest"><i class="bi bi-sort-up text-secondary"></i> Oldest To Newest</a></li>
                                </ul>
                            </div>
                        </div>

                    <!-- Catalog Table (same table system as Accounts and Circulation) -->
                    <p class="scrollHint">Swipe sideways to see all columns &rarr;</p>
                    <div class="amTableWrap">
                        <table class="amTable rowTable catTable fiveCol" id="catalogTable">
                            <thead>
                                <tr>
                                    <th scope="col">Book</th>
                                    <th scope="col">Author</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Digital Access</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="catalogBody"></tbody>
                        </table>
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
        <!-- ================================================================ -->
        <!-- PANEL 4: CIRCULATION (borrowing, reservations, fines - Stage 8)  -->
        <!-- frontend/js/circulation_staff.js -> circulation_controller.php    -->
        <!-- ================================================================ -->
        <div id="panel-circulation" data-main-panel="circulation" class="d-none">
            <ul class="nav subTabs gap-2 mb-4 flex-wrap" role="tablist" aria-label="Circulation sections" id="circTabs">
                <li class="nav-item" role="presentation"><button class="nav-link active" type="button" role="tab" data-circ-section="requests" aria-selected="true">Requests <span class="tabBadge amber" data-circ-count="requests">0</span></button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" type="button" role="tab" data-circ-section="borrowed" aria-selected="false">Borrowed <span class="tabBadge green" data-circ-count="borrowed">0</span></button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" type="button" role="tab" data-circ-section="overdue" aria-selected="false">Overdue <span class="tabBadge red" data-circ-count="overdue">0</span></button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" type="button" role="tab" data-circ-section="reservations" aria-selected="false">Reservations <span class="tabBadge amber" data-circ-count="reservations">0</span></button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" type="button" role="tab" data-circ-section="fines" aria-selected="false">Fines <span class="tabBadge red" data-circ-count="fines">0</span></button></li>
            </ul>
            <p class="small mb-2" id="circInfo" aria-live="polite"></p>
            <p class="scrollHint">Swipe sideways to see all columns &rarr;</p>
            <div class="amTableWrap">
                <table class="amTable circTable rowTable fiveCol" id="circTable">
                    <thead><tr id="circHead"></tr></thead>
                    <tbody id="circRows"><tr class="emptyRow"><td colspan="5">Loading…</td></tr></tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- ========================================================================== -->
    <!-- 6. MODALS & TOAST CONTAINERS                                               -->
    <!-- ========================================================================== -->

    <!-- EDIT BOOK MODAL -->
    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background-color: var(--main-bg-color);">
                <div class="modal-header text-white px-4 py-3 border-0" style="background-color: var(--main-color);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square fs-5 text-warning"></i>
                        <h5 class="modal-title fw-bold" id="editBookModalLabel">Edit Book</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="editBookForm" data-book-form novalidate>
                    <div class="modal-body p-4">
                        <input type="hidden" name="book_id" value="">
                        <div class="row g-3">
                            <div class="col-12 mb-1 d-flex align-items-center gap-2 fw-semibold" style="color: var(--brown);">
                                <i class="bi bi-info-circle-fill"></i>
                                <span class="text-uppercase small tracking-wide">Book Information</span>
                                <hr class="flex-grow-1 border-secondary my-0 ms-2">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="editTitle"><i class="bi bi-book-half me-1 text-secondary"></i> Book Title <span class="text-danger">*</span></label>
                                <input type="text" id="editTitle" name="title" class="form-control rounded-3" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="editAuthor"><i class="bi bi-person-fill me-1 text-secondary"></i> Author <span class="text-danger">*</span></label>
                                <input type="text" id="editAuthor" name="author" class="form-control rounded-3" required maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="editIsbn"><i class="bi bi-upc-scan me-1 text-secondary"></i> ISBN</label>
                                <input type="text" id="editIsbn" name="isbn" class="form-control rounded-3" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="editPublisher"><i class="bi bi-building me-1 text-secondary"></i> Publisher</label>
                                <input type="text" id="editPublisher" name="publisher" class="form-control rounded-3" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="editCategory"><i class="bi bi-tags-fill me-1 text-secondary"></i> Category <span class="text-danger">*</span></label>
                                <select id="editCategory" name="category" class="form-select rounded-3" required>
                                    <option value="" disabled>Select a category</option>
                                    <option value="Educational">Educational</option>
                                    <option value="Non-Educational">Non-Educational</option>
                                    <option value="Research Articles">Research Articles</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small" for="editIsDigital"><i class="bi bi-hdd-network-fill me-1 text-secondary"></i> Digital Access</label>
                                <select id="editIsDigital" name="is_digital" class="form-select rounded-3">
                                    <option value="Yes">Yes - PDF available online</option>
                                    <option value="No">No - physical copy only</option>
                                </select>
                            </div>
                            <div class="col-md-6" data-copies-field hidden>
                                <label class="form-label fw-semibold small" for="editCopies"><i class="bi bi-stack me-1 text-secondary"></i> Physical copies</label>
                                <input type="number" id="editCopies" name="total_copies" class="form-control rounded-3" min="0" max="1000" step="1">
                                <div class="form-text small">Cannot go below the copies currently borrowed or held for pickup.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small" for="editDescription"><i class="bi bi-text-paragraph me-1 text-secondary"></i> Description <span class="text-danger">*</span></label>
                                <textarea id="editDescription" name="description" class="form-control rounded-3" rows="3" required
                                    maxlength="5000"></textarea>
                            </div>
                            <div class="col-12 mt-4 mb-1 d-flex align-items-center gap-2 fw-semibold" style="color: var(--brown);">
                                <i class="bi bi-paperclip"></i>
                                <span class="text-uppercase small tracking-wide">Media &amp; Attachments</span>
                                <hr class="flex-grow-1 border-secondary my-0 ms-2">
                            </div>
                            <div class="col-md-6">
                                <div class="uploadPanel">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <img id="editCoverPreview" src="../frontend/assets/icons/library-default.png" alt="Current cover" class="catalogThumb catThumbLg flex-shrink-0">
                                        <label class="form-label fw-semibold small mb-0" for="editCoverFile"><i class="bi bi-image me-1 text-warning"></i> Cover Image</label>
                                    </div>
                                    <input type="file" id="editCoverFile" name="cover_file" class="form-control rounded-3 mb-2"
                                        accept="image/jpeg,image/png,image/webp">
                                    <div class="form-text small mb-2">Replace cover (JPG, PNG or WEBP, up to 5 MB), or edit URL below:</div>
                                    <label class="visually-hidden" for="editCover">Cover Image URL</label>
                                    <input type="text" id="editCover" name="cover_image" class="form-control rounded-3" maxlength="255" placeholder="https://.../cover.jpg">
                                </div>
                            </div>
                            <div class="col-md-6" data-pdf-field>
                                <div class="uploadPanel">
                                    <label class="form-label fw-semibold small mb-3 d-block" for="editPdfFile"><i class="bi bi-file-earmark-pdf me-1 text-danger"></i> PDF Document</label>
                                    <input type="file" id="editPdfFile" name="pdf_file" class="form-control rounded-3 mb-2"
                                        accept="application/pdf,.pdf">
                                    <div class="form-text small mb-2">Replace PDF (up to 20 MB), or edit storage link below:</div>
                                    <label class="visually-hidden" for="editDrive">PDF Document URL (https)</label>
                                    <input type="url" id="editDrive" name="drive_link" class="form-control rounded-3" maxlength="255" placeholder="https://<project>.supabase.co/...pdf">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light bg-opacity-50 px-4 py-3">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="editBookSaveBtn" class="btn text-white rounded-pill px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-1"
                            style="background-color: var(--btn);"><i class="bi bi-check-lg"></i> Save Changes</button>
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
                    <div id="confirmIconWrap" class="mb-3 text-danger fs-1"><i id="confirmIcon" class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></div>
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

    <!-- INPUT MODAL (decline reason, damage fine): window.lmPrompt() in librarymanagement.js.-->
    <div class="modal fade" id="promptModal" tabindex="-1" aria-labelledby="promptTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-secondary shadow-lg rounded-4"
                style="background-color: var(--main-bg-color);">
                <form id="promptForm" novalidate>
                    <div class="modal-body p-4 p-md-5">
                        <div id="promptIconWrap" class="text-center mb-3 fs-1"><i id="promptIcon" class="bi bi-pencil-square" aria-hidden="true"></i></div>
                        <h3 class="fw-bold mb-2 text-center" id="promptTitle">Title</h3>
                        <p class="text-center mb-4 d-none" id="promptMessage"></p>
                        <div id="promptFields" class="d-flex flex-column gap-3 text-start"></div>
                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <button type="button" class="btn px-4 py-2 fw-semibold rounded-3 text-white"
                                style="background-color: var(--muted);" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn px-4 py-2 fw-semibold rounded-3 text-white"
                                id="promptOkBtn" style="background-color: var(--btn);">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LOGOUT CONFIRMATION MODAL -->
    <?php render_logout_modal(); ?>

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
    <?php render_footer('staff'); ?>

    <script>
        window.csrfToken = <?php echo json_encode($_SESSION['csrf_token']); ?>;
    </script>
<?php render_scripts(['librarymanagement.js', 'circulation_staff.js']); ?>
