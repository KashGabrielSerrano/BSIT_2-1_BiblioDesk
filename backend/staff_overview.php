<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
require_once __DIR__ . '/core/circulation.php';
require_once __DIR__ . '/core/dashboard.php';
$user = require_role_page(['Librarian', 'Admin']);
$is_admin = $user['role'] === 'Admin';
circulation_upkeep();
$dash = staff_dashboard($user['role']);
render_head(($is_admin ? 'Admin' : 'Librarian') . ' Overview | BiblioDesk', ['root.css', 'admindb_overview.css?v=2']);
?>
    <!-- SIDEBAR -->
    <?php render_sidebar('staff', 'overview'); ?>

    <?php render_logout_modal(); ?>

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

    <?php if ($is_admin): ?>
    <div class="modal fade qaModal" id="qaLibrarianModal" tabindex="-1" aria-labelledby="qaLibrarianLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaLibrarianLabel">Add New Librarian</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="qaLibrarianForm" novalidate>
                    <div class="modal-body">
                        <p class="small mb-3">Librarians are made from <strong>registered, active</strong> accounts: enter the email the person signed up with and their account switches to Librarian.</p>
                        <div class="mb-1">
                            <label class="form-label" for="qaLibEmail">Account email</label>
                            <input class="form-control" type="email" id="qaLibEmail" maxlength="150" autocomplete="off">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a class="qaFullLink me-auto" href="librarymanagement.php#accounts">Open Account Management</a>
                        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn qaPrimary">Make Librarian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

    
    <div class="modal fade qaModal" id="qaAnnModal" tabindex="-1" aria-labelledby="qaAnnLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaAnnLabel">Create Announcement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="qaAnnForm" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="qaAnnTitle">Title</label>
                            <input class="form-control" type="text" id="qaAnnTitle" maxlength="80">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="qaAnnCategory">Category</label>
                                <select class="form-select" id="qaAnnCategory"></select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="qaAnnStatus">Status</label>
                                <select class="form-select" id="qaAnnStatus">
                                    <option value="published">Published</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3" id="qaAnnDateField" hidden>
                            <label class="form-label" for="qaAnnDate">Publish date</label>
                            <input class="form-control" type="date" id="qaAnnDate">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-1">
                            <label class="form-label" for="qaAnnBody">Message</label>
                            <textarea class="form-control" id="qaAnnBody" rows="4" maxlength="240"></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a class="qaFullLink me-auto" href="admin_announcement.php">Open Announcements</a>
                        <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn qaPrimary">Save</button>
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
    <?php render_topbar('staff'); ?>

    <main class="container-fluid py-4">
        <section class="row g-4 mb-5" aria-label="<?= $is_admin ? 'Admin' : 'Librarian' ?> summary">
            <div class="col-12 col-lg-8">
                <div class="darkCard card border-0 shadow rounded-4 h-100">
                    <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-center">
                        <h1 class="display-6 fw-semibold mb-3"><?= e(current_profile()['full_name']) ?></h1>
                        <h2 class="h4 fw-semibold mb-3"><?= $is_admin ? 'System Administrator' : 'Librarian' ?></h2>
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
                            <?php if ($is_admin): ?>
                            <li class="actionItem">
                                <button type="button" class="actionLink actionBtn d-flex align-items-center gap-3"
                                    data-bs-toggle="modal" data-bs-target="#qaLibrarianModal">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>Add New Librarian</span>
                                </button>
                            </li>
                            <?php endif; ?>
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
                            
                            <li class="actionItem">
                                <button type="button" class="actionLink actionBtn d-flex align-items-center gap-3"
                                    data-bs-toggle="modal" data-bs-target="#qaAnnModal">
                                    <span class="actionIcon rounded-1" aria-hidden="true">+</span>
                                    <span>Create Announcement</span>
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
                                <p class="display-6 mb-0" id="statUsers"><?= (int)$dash['stats']['users'] ?></p>
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
                                <p class="display-6 mb-0" id="statInventory"><?= (int)$dash['stats']['inventory'] ?></p>
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
                                <p class="display-6 mb-0" id="statBorrows"><?= (int)$dash['stats']['borrows'] ?></p>
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
                                <p class="display-6 mb-0" id="statAvailable"><?= (int)$dash['stats']['available'] ?></p>
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
                                <p class="display-6 mb-0" id="statLibrarians"><?= (int)$dash['stats']['librarians'] ?></p>
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
                                <p class="display-6 mb-0" id="statRegistration"><?= (int)$dash['stats']['registration'] ?></p>
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
                                <p class="display-6 mb-0" id="statReservations"><?= (int)$dash['stats']['reservations'] ?></p>
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
                                <p class="display-6 mb-0" id="statTotalBorrows"><?= (int)$dash['stats']['total_borrows'] ?></p>
                            </div>
                            <p class="text-end small mb-0 mt-3">Total Borrows To Date</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <?php render_footer('staff'); ?>

    <script>
        // Real dashboard data for admin_popular.js / admin_quickactions.js (rendered by core/dashboard.php)
        window.biblioDashboard = <?= json_encode(['popular' => $dash['popular'], 'users' => $dash['users'], 'logs' => $dash['logs'],
                                                  'categories' => BOOK_CATEGORY_LIST, 'isAdmin' => $is_admin],
                                                 JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>
<?php render_scripts(['admin_popular.js', 'admin_quickactions.js', 'admin_todo.js']); ?>
