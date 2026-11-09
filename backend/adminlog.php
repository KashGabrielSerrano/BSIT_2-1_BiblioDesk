<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
$user = require_role_page(['Librarian', 'Admin']);
render_head('Report Logs | BiblioDesk', ['root.css', 'adminlog.css']);
?>
<!-- SIDEBAR -->
<?php render_sidebar('staff', 'logs'); ?>

<?php render_logout_modal(); ?>

<!-- NAVBAR -->
<?php render_topbar('staff'); ?>

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
<?php render_footer('staff'); ?>

<?php render_scripts(['librarianlog.js']); ?>
