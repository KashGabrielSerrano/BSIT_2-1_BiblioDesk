<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
$user = require_role_page(['Librarian', 'Admin']);
render_head('Announcements | BiblioDesk', ['root.css', 'admin_announcement.css']);
?>
    <!-- SIDEBAR -->
    <?php render_sidebar('staff', 'announcements'); ?>

    <?php render_logout_modal(); ?>

    <!-- NAVBAR -->
    <?php render_topbar('staff'); ?>

    <main class="container-fluid px-3 px-md-5 py-4">
        <section class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="display-6 fw-semibold mb-1">Announcements</h1>
            </div>
            <button class="btn btnMain" id="btnCreate" type="button">+ Create Announcement</button>
        </section>

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

    <!-- FOOTER -->
    <?php render_footer('staff'); ?>

<?php render_scripts(['admin_announcement.js']); ?>
