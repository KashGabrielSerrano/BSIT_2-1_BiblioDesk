<!--there's no final font style yet-->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account Management</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="../frontend/css/userdb.css">
        <link rel="stylesheet" href="../frontend/css/accountmgmt.css">
    </head>
    <body>
        <aside class="offcanvas-md offcanvas-start d-flex flex-column text-light" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel" data-bs-scroll="true">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
            </div>

            <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
                <div class="logoContain d-flex align-items-center px-3">
                    <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="48">
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
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="mybooks.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/mybooks-default.png" alt="my books page" width="28">
                                </span>
                                <span class="sideText">My Books</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="user_librarydb.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/lib-default.png" alt="library page" width="28">
                                </span>
                                <span class="sideText">Library</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="librarianAccmanagement.html" aria-current="page">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                    <img src="../frontend/assets/icons/accounts-default.png" alt="account management page" width="28">
                                </span>
                                <span class="sideText">Accounts</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <header class="navbar navbar-dark shadow topbar">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-2">
                    <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <a class="navbar-brand m-0" href="admindb.php">
                        <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                    </a>
                </div>

                <div class="d-flex align-items-center gap-4">
                    <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                        <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">4</span>
                    </button>

                    <a href="admindb.php?tab=settings" aria-label="Open Settings">
                        <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                    </a>

                    <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="rounded-circle" width="44" height="44">
                    </button>
                </div>
            </div>
        </header>

        <main class="container-fluid px-3 px-md-5 py-4">
            <h1 class="pageTitle mt-4 mb-4">Account Management</h1>

            <!--Account category tabs with counts-->
            <ul class="nav amTabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" type="button" role="tab" data-tab="users" aria-selected="true">
                            User Accounts                            <span class="tabBadge green">3</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link " type="button" role="tab" data-tab="pending" aria-selected="false">
                            Pending                            <span class="tabBadge amber">3</span>
                        </button>
                    </li>
                            </ul>

            <!--Search and tools-->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                <input type="search" id="accountSearch" class="form-control searchBox flex-grow-1" placeholder="Search account...." aria-label="Search account" autocomplete="off">

                <div class="d-flex gap-2">
                    <button type="button" id="exportCsv" class="btn toolBtn">Export CSV</button>

                    <div class="dropdown">
                        <button type="button" id="filterBtn" class="btn toolBtn iconOnly" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Filter accounts">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round" aria-hidden="true">
                                <path d="M3 4h18l-7 8.5V19l-4 2v-8.5L3 4z"/>
                            </svg>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end filterMenu p-3">
                            <label for="filterStatus" class="form-label fw-semibold mb-1">Status</label>
                            <select id="filterStatus" class="form-select mb-3">
                                <option value="">All</option>
                                <option value="approved">Approved</option>
                                <option value="denied">Denied</option>
                                <option value="pending">Pending</option>
                                <option value="banned">Banned</option>
                            </select>

                            <label for="filterActive" class="form-label fw-semibold mb-1">Active Status</label>
                            <select id="filterActive" class="form-select mb-3">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="closed">Closed</option>
                                <option value="—">None (—)</option>
                            </select>

                            <button type="button" id="resetFilters" class="btn toolBtn w-100 py-1">Reset filters</button>
                        </div>
                    </div>
                </div>
            </div>

            <p id="resultCount" class="small mb-2" aria-live="polite"></p>

            <!--Bulk actions (Pending tab, shown when rows are ticked)-->
            <div id="bulkBar" class="bulkBar" hidden>
                <strong id="bulkCount"></strong>
                <button type="button" id="bulkApprove" class="actBtn approve">Approve</button>
                <button type="button" id="bulkDeny" class="actBtn deny">Deny</button>
                <button type="button" id="bulkClear" class="linkBtn">Clear</button>
            </div>

            <!--Accounts table-->
            <p class="scrollHint">Swipe sideways to see all columns &rarr;</p>
            <div class="amTableWrap">
                <table class="amTable fiveCol" id="amTable">
                    <thead>
                        <tr>
                            <th scope="col">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="selectBox"><input type="checkbox" id="selectAll" aria-label="Select all pending accounts"></span>
                                    <span>Name</span>
                                </div>
                            </th>
                            <th scope="col" id="noHeader">Student<br>No.</th>
                            <th scope="col">Program</th>
                            <th scope="col">Status</th>
                            <th scope="col" id="lastHeader">Active<br>Status</th>
                        </tr>
                    </thead>
                    <tbody id="accountRows">
                        <tr data-id="101"
                                data-role="users"
                                data-group="users"
                                data-name="User Name"
                                data-email="user1@email.com"
                                data-joined="Oct 23"
                                data-no="R2025XXXXXX"
                                data-program="BS Information Technology"
                                data-status="approved"
                                data-active="active"
                                data-search="user name user1@email.com oct 23 r2025xxxxxx bs information technology approved active"
                                tabindex="0"
                                title="Click to view or edit this account"
                                hidden>
                                <td class="cB">
                                    <div class="d-flex align-items-center gap-4">
                                        <span class="selectBox"><input type="checkbox" class="rowCheck" aria-label="Select User Name"></span>
                                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="userAvatar rounded-circle">
                                        <div>
                                            <p class="userName">User Name</p>
                                            <p class="userEmail">user1@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="cA">R2025XXXXXX</td>
                                <td class="cB">BS Information Technology</td>
                                <td class="cA"><span class="pill pillStatus approved">Approved</span></td>
                                <td class="cB">
                                    <span class="pill pillActive active">Active</span>
                                    <div class="rowActions">
                                        <button type="button" class="actBtn approve" data-act="approve" aria-label="Approve User Name">Approve</button>
                                        <button type="button" class="actBtn deny" data-act="deny" aria-label="Deny User Name">Deny</button>
                                    </div>
                                </td>
                            </tr>
                        <tr data-id="102"
                                data-role="users"
                                data-group="users"
                                data-name="User Name"
                                data-email="user2@email.com"
                                data-joined="Oct 5"
                                data-no="R2025XXXXXX"
                                data-program="BS Information Technology"
                                data-status="denied"
                                data-active="—"
                                data-search="user name user2@email.com oct 5 r2025xxxxxx bs information technology denied —"
                                tabindex="0"
                                title="Click to view or edit this account"
                                hidden>
                                <td class="cB">
                                    <div class="d-flex align-items-center gap-4">
                                        <span class="selectBox"><input type="checkbox" class="rowCheck" aria-label="Select User Name"></span>
                                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="userAvatar rounded-circle">
                                        <div>
                                            <p class="userName">User Name</p>
                                            <p class="userEmail">user2@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="cA">R2025XXXXXX</td>
                                <td class="cB">BS Information Technology</td>
                                <td class="cA"><span class="pill pillStatus denied">Denied</span></td>
                                <td class="cB">
                                    <span class="pill pillActive none">—</span>
                                    <div class="rowActions">
                                        <button type="button" class="actBtn approve" data-act="approve" aria-label="Approve User Name">Approve</button>
                                        <button type="button" class="actBtn deny" data-act="deny" aria-label="Deny User Name">Deny</button>
                                    </div>
                                </td>
                            </tr>
                        <tr data-id="103"
                                data-role="users"
                                data-group="users"
                                data-name="User Name"
                                data-email="user3@email.com"
                                data-joined="Oct 27"
                                data-no="R2025XXXXXX"
                                data-program="BS Information Technology"
                                data-status="banned"
                                data-active="closed"
                                data-search="user name user3@email.com oct 27 r2025xxxxxx bs information technology banned closed"
                                tabindex="0"
                                title="Click to view or edit this account"
                                hidden>
                                <td class="cB">
                                    <div class="d-flex align-items-center gap-4">
                                        <span class="selectBox"><input type="checkbox" class="rowCheck" aria-label="Select User Name"></span>
                                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="userAvatar rounded-circle">
                                        <div>
                                            <p class="userName">User Name</p>
                                            <p class="userEmail">user3@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="cA">R2025XXXXXX</td>
                                <td class="cB">BS Information Technology</td>
                                <td class="cA"><span class="pill pillStatus banned">Banned</span></td>
                                <td class="cB">
                                    <span class="pill pillActive closed">Closed</span>
                                    <div class="rowActions">
                                        <button type="button" class="actBtn approve" data-act="approve" aria-label="Approve User Name">Approve</button>
                                        <button type="button" class="actBtn deny" data-act="deny" aria-label="Deny User Name">Deny</button>
                                    </div>
                                </td>
                            </tr>
                        <tr data-id="301"
                                data-role="users"
                                data-group="pending"
                                data-name="Pending Name"
                                data-email="pending1@email.com"
                                data-joined="Oct 29"
                                data-no="R2025XXXXXX"
                                data-program="BS Computer Science"
                                data-status="pending"
                                data-active="pending"
                                data-search="pending name pending1@email.com oct 29 r2025xxxxxx bs computer science pending pending"
                                tabindex="0"
                                title="Click to view or edit this account"
                                hidden>
                                <td class="cB">
                                    <div class="d-flex align-items-center gap-4">
                                        <span class="selectBox"><input type="checkbox" class="rowCheck" aria-label="Select Pending Name"></span>
                                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="userAvatar rounded-circle">
                                        <div>
                                            <p class="userName">Pending Name</p>
                                            <p class="userEmail">pending1@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="cA">R2025XXXXXX</td>
                                <td class="cB">BS Computer Science</td>
                                <td class="cA"><span class="pill pillStatus pending">Pending</span></td>
                                <td class="cB">
                                    <span class="pill pillActive pending">Pending</span>
                                    <div class="rowActions">
                                        <button type="button" class="actBtn approve" data-act="approve" aria-label="Approve Pending Name">Approve</button>
                                        <button type="button" class="actBtn deny" data-act="deny" aria-label="Deny Pending Name">Deny</button>
                                    </div>
                                </td>
                            </tr>
                        <tr data-id="302"
                                data-role="users"
                                data-group="pending"
                                data-name="Pending Name"
                                data-email="pending2@email.com"
                                data-joined="Oct 30"
                                data-no="R2025XXXXXX"
                                data-program="BS Information Technology"
                                data-status="pending"
                                data-active="pending"
                                data-search="pending name pending2@email.com oct 30 r2025xxxxxx bs information technology pending pending"
                                tabindex="0"
                                title="Click to view or edit this account"
                                hidden>
                                <td class="cB">
                                    <div class="d-flex align-items-center gap-4">
                                        <span class="selectBox"><input type="checkbox" class="rowCheck" aria-label="Select Pending Name"></span>
                                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="userAvatar rounded-circle">
                                        <div>
                                            <p class="userName">Pending Name</p>
                                            <p class="userEmail">pending2@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="cA">R2025XXXXXX</td>
                                <td class="cB">BS Information Technology</td>
                                <td class="cA"><span class="pill pillStatus pending">Pending</span></td>
                                <td class="cB">
                                    <span class="pill pillActive pending">Pending</span>
                                    <div class="rowActions">
                                        <button type="button" class="actBtn approve" data-act="approve" aria-label="Approve Pending Name">Approve</button>
                                        <button type="button" class="actBtn deny" data-act="deny" aria-label="Deny Pending Name">Deny</button>
                                    </div>
                                </td>
                            </tr>
                        <tr data-id="303"
                                data-role="users"
                                data-group="pending"
                                data-name="Pending Name"
                                data-email="pending3@email.com"
                                data-joined="Oct 31"
                                data-no="R2025XXXXXX"
                                data-program="BS Information Technology"
                                data-status="pending"
                                data-active="pending"
                                data-search="pending name pending3@email.com oct 31 r2025xxxxxx bs information technology pending pending"
                                tabindex="0"
                                title="Click to view or edit this account"
                                hidden>
                                <td class="cB">
                                    <div class="d-flex align-items-center gap-4">
                                        <span class="selectBox"><input type="checkbox" class="rowCheck" aria-label="Select Pending Name"></span>
                                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="userAvatar rounded-circle">
                                        <div>
                                            <p class="userName">Pending Name</p>
                                            <p class="userEmail">pending3@email.com</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="cA">R2025XXXXXX</td>
                                <td class="cB">BS Information Technology</td>
                                <td class="cA"><span class="pill pillStatus pending">Pending</span></td>
                                <td class="cB">
                                    <span class="pill pillActive pending">Pending</span>
                                    <div class="rowActions">
                                        <button type="button" class="actBtn approve" data-act="approve" aria-label="Approve Pending Name">Approve</button>
                                        <button type="button" class="actBtn deny" data-act="deny" aria-label="Deny Pending Name">Deny</button>
                                    </div>
                                </td>
                            </tr>
                                                <tr id="emptyRow" class="emptyRow" hidden>
                            <td colspan="5">No accounts match your search.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <footer class="text-light mt-5 py-5">
            <div class="container">
                <div class="row justify-content-around gy-4">
                    <div class="col-md-5">
                        <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56" class="footerLogo">
                        <ul class="list-unstyled lh-lg mt-3 ps-4">
                            <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#">Terms of Use</a></li>
                            <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#">Privacy Policy</a></li>
                            <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#">More About BiblioDesk</a></li>
                            <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#">BiblioDesk Developers</a></li>
                        </ul>
                    </div>
                    <div class="col-md-5">
                        <h2 class="h4 text-uppercase">Contact Us</h2>
                        <p class="lh-lg">bibliodesk@gmail.com<br>bibliodesk.help@gmail.com</p>
                    </div>
                </div>
            </div>
        </footer>

        <!--Edit account modal: view / edit one account-->
        <div class="modal fade accountModal" id="accountModal" tabindex="-1" aria-labelledby="panelTitle" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header modalBar" id="modalBar">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex flex-column gap-3">
                <h2 class="h4 fw-bold mb-0" id="panelTitle">Edit Account</h2>
                <div class="d-flex align-items-center gap-3">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="panelAvatar rounded-circle">
                    <div class="min-w-0">
                        <p class="h5 mb-0" id="panelName"></p>
                        <p class="mb-0" id="panelEmail"></p>
                        <p class="small mb-0 opacity-75" id="panelRole"></p>
                    </div>
                </div>

                <dl class="panelDetails mb-0">
                    <dt>Date joined</dt><dd id="panelJoined"></dd>
                    <dt id="panelNoLabel">Student No.</dt><dd id="panelNo"></dd>
                    <dt>Program</dt><dd id="panelProgram"></dd>
                    <dt>Current status</dt><dd><span id="panelCurrent" class="pill"></span></dd>
                </dl>

                <div>
                    <label for="panelStatus" class="form-label fw-semibold mb-1">Change status to</label>
                    <select id="panelStatus" class="form-select amField">
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="denied">Denied</option>
                        <option value="banned">Banned</option>
                    </select>
                    <p class="small mt-2 mb-0">Active Status will become <strong id="panelActive"></strong>.</p>
                </div>

                <div id="reasonWrap" class="d-none">
                    <label for="panelReason" class="form-label fw-semibold mb-1">Reason <span class="text-danger">(required)</span></label>
                    <textarea id="panelReason" class="form-control amField" rows="3" maxlength="300" placeholder="Why is this account being changed?"></textarea>
                </div>

                <p id="panelSummary" class="panelSummary mb-0" aria-live="polite"></p>

            </div>
            <div class="modal-footer gap-2">
                <button type="button" class="btn toolBtn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="panelSave" class="btn saveBtn" disabled>Save changes</button>
            </div>
            </div>
          </div>
        </div>

        <!--Deny confirmation-->
        <div class="modal fade denyModal" id="denyModal" tabindex="-1" aria-labelledby="denyTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h2 class="modal-title h5" id="denyTitle">Deny this account?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p id="denyText"></p>
                        <label for="denyReason" class="form-label fw-semibold mb-1">Reason <span class="small fw-normal">(optional)</span></label>
                        <textarea id="denyReason" class="form-control amField" rows="3" maxlength="300" placeholder="Add a short note for the record"></textarea>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn toolBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="denyConfirm" class="actBtn deny">Deny</button>
                    </div>
                </div>
            </div>
        </div>

        <!--Toast with Undo-->
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="actionToast" class="toast amToast" role="status" aria-live="polite" aria-atomic="true">
                <div class="d-flex align-items-center">
                    <div class="toast-body" id="toastText"></div>
                    <button type="button" id="toastUndo" class="btn btn-sm undoBtn">Undo</button>
                    <button type="button" class="btn-close btn-close-white me-2" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../frontend/js/accountmgmt.js"></script>
    </body>
</html>