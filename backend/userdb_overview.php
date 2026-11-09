<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
$user = require_login_page();
render_head('User Dashboard', ['userdb.css']);
?>
        <?php render_sidebar('student', 'overview'); ?>

        <?php render_topbar('student'); ?>

        <main class="container-fluid px-3 px-md-5 py-4">
            <!--User info and the recent activity section-->
            <section class="row g-4 mb-5">
                <!--User Info (name, ID, account status, digital library ID in qr form-->
                <div class="col-lg-7">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <div class="card-body p-4 d-flex flex-column flex-sm-row align-items-center gap-4">
                            <div class="flex-grow-1">
                                <h1 class="display-6 fw-semibold">Victor Magtanggol</h1>
                                <p class="fs-4 mb-2">R2025******</p>
                                <p class="mb-0">Account Status: 🟢 <strong>Active</strong></p>
                            </div>
                            <div class="libID text-center pt-3 pt-sm-0 ps-sm-4">
                                <p class="fs-5">Digital Library ID:</p>
                                <img src="../frontend/assets/icons/placeholder-qr.png" alt="Digital library ID QR code" class="img-fluid p-1" width="150">
                            </div>
                        </div>
                    </div>
                </div>
                <!--Recent activity section (idk if it will stay as a list form or not ,,, depends on the verdict)-->
                <div class="col-lg-5">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <h2 class="card-header h4 text-center py-3 bg-transparent border-light border-opacity-25">Recent Activity</h2>
                        <div class="card-body">
                            <ul class="fs-5 mb-0">
                                <li>4 pending books</li>
                                <li>2 books approved for reservation</li>
                                <li>Account information successfully updated.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!--Library updates snippets-->
            <section class="row g-4">
                <!--Library updates part-->
                <div class="col-lg-6">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <h2 class="card-header h3 px-4 py-3 bg-transparent border-light border-opacity-25">Library Updates</h2>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent text-reset border-light border-opacity-25 d-flex align-items-center gap-3 px-4 py-3">
                                <div class="dateBadge rounded-3 d-flex flex-column align-items-center justify-content-center"><strong>17</strong><small>OCT</small></div>
                                <div>Policy Updates<small class="d-block">Users can now borrow 3 books per week!</small></div>
                            </li>
                            <li class="list-group-item bg-transparent text-reset border-0 d-flex align-items-center gap-3 px-4 py-3">
                                <div class="dateBadge rounded-3 d-flex flex-column align-items-center justify-content-center"><strong>31</strong><small>OCT</small></div>
                                <div>System Maintenance on Oct. 31, 2026<small class="d-block">5:00 pm to 8:00 pm (GMT+8). All services will be paused during the maintenance.</small></div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!--Upcoming library events part-->
                <div class="col-lg-6">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <h2 class="card-header h3 px-4 py-3 bg-transparent border-light border-opacity-25">Upcoming Events:</h2>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent text-reset border-0 d-flex align-items-center gap-3 px-4 py-3">
                                <div class="dateBadge rounded-3 d-flex flex-column align-items-center justify-content-center"><strong>10</strong><small>NOV</small></div>
                                <div>The annual book fest this coming <u>November 10.</u></div>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!--User quick stats section-->
            <section>
                <h2 class="h1 fw-semibold mt-5 mb-3">Quick Stats</h2>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">
                    <!--Total books area-->
                    <div class="col">
                        <div class="cardsColor card border-0 shadow rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="neutralBox d-inline-block p-4 rounded-3"></span>
                                    <p class="display-4 mb-0">0</p>
                                </div>
                                <p class="text-end fs-5 mb-0 mt-3">Total Books Borrowed</p>
                            </div>
                        </div>
                    </div>
                    <!--Current borrows (this should be updating per day just in case they borrowed another book)-->
                    <div class="col">
                        <div class="cardsColor card border-0 shadow rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="greenBox d-inline-block p-4 rounded-3"></span>
                                    <p class="display-4 mb-0">0</p>
                                </div>
                                <p class="text-end fs-5 mb-0 mt-3">Current Borrows</p>
                            </div>
                        </div>
                    </div>
                    <!--Books due for the week-->
                    <div class="col">
                        <div class="cardsColor card border-0 shadow rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="yellowBox d-inline-block p-4 rounded-3"></span>
                                    <p class="display-4 mb-0">0</p>
                                </div>
                                <p class="text-end fs-5 mb-0 mt-3">Books Due This Week</p>
                            </div>
                        </div>
                    </div>
                    <!--For this ,, di ko pa knows ano ipapalit kong feature so ung active reading time nalang ng user-->
                    <div class="col">
                        <div class="cardsColor card border-0 shadow rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="greenBox d-inline-block p-4 rounded-3"></span>
                                    <p class="display-4 mb-0">0</p>
                                </div>
                                <p class="text-end fs-5 mb-0 mt-3">Active Reading Time</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!--Bookmarks section-->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                    <h2 class="h1 fw-semibold mb-0">Bookmarks</h2>
                    <a href="#" class="fs-5"><u>Browse Library ></u></a>
                </div>

                <!--Books area-->
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <div class="col">
                        <a href="#" class="d-block ratio bookCover rounded-2 overflow-hidden shadow" aria-label="Add a bookmark"></a>
                        <a href="#" class="textArea">
                            <h3 class="h6 fw-bold mt-3 mb-0">+ Add Book</h3>
                        </a>
                    </div>
                </div>
            </section>

            <!-- LOGOUT CONFIRMATION MODAL -->
            <?php render_logout_modal(); ?>
        </main>

        <?php render_footer('student'); ?>

<?php render_scripts(); ?>
