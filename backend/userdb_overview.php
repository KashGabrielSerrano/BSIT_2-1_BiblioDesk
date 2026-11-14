<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
require_once __DIR__ . '/core/announcements.php';
require_once __DIR__ . '/core/circulation.php';
require_once __DIR__ . '/core/dashboard.php';
$user    = require_login_page();
$profile = current_profile();
$userPrefs = get_user_preferences((int)$user['user_id']);
$readingSeconds = (int)$userPrefs['reading_seconds'];
circulation_upkeep();                       
$dash    = student_dashboard($user['user_id']);   
$updates = published_announcements_for($user['role'], 3, 'Events', true);
$events  = published_announcements_for($user['role'], 3, 'Events');

function overview_ann_item(array $a, bool $last): void {
    $d = DateTime::createFromFormat('Y-m-d', $a['date']) ?: new DateTime(); ?>
                            <li class="list-group-item bg-transparent text-reset <?= $last ? 'border-0' : 'border-light border-opacity-25' ?> d-flex align-items-center gap-3 px-4 py-3">
                                <div class="dateBadge rounded-3 d-flex flex-column align-items-center justify-content-center"><strong><?= $d->format('d') ?></strong><small><?= strtoupper($d->format('M')) ?></small></div>
                                <div><?= e($a['title']) ?><small class="d-block"><?= e($a['body']) ?></small></div>
                            </li>
<?php }
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
                                <h1 class="display-6 fw-semibold"><?= e($profile['full_name']) ?></h1>
                                <p class="fs-4 mb-2"><?= e($profile['student_id_masked'] ?: '—') ?></p>
                                <p class="mb-0">Account Status: 🟢 <strong><?= e($profile['status']) ?></strong></p>
                            </div>
                            <div class="libID text-center pt-3 pt-sm-0 ps-sm-4">
                                <p class="fs-5">Digital Library ID:</p>
                                <!-- Digital library ID: QR of the student ID, drawn in the browser (placeholder image if the QR library fails to load) -->
                                <div id="libraryIdQr" class="d-inline-block bg-white p-2 rounded" data-qr="<?= e($profile['student_id']) ?>">
                                    <img src="../frontend/assets/icons/placeholder-qr.png" alt="Digital library ID QR code" class="img-fluid p-1" width="150">
                                </div>
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
<?php if (!$dash['activity']): ?>
                                <li>No recent activity yet.</li>
<?php endif; foreach ($dash['activity'] as $a): ?>
                                <li><?= e($a['title']) ?> <small class="opacity-75">· <?= e($a['when']) ?></small></li>
<?php endforeach; ?>
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
<?php if (!$updates): ?>
                            <li class="list-group-item bg-transparent text-reset border-0 px-4 py-3">No library updates yet.</li>
<?php endif; foreach ($updates as $i => $a) overview_ann_item($a, $i === count($updates) - 1); ?>
                        </ul>
                    </div>
                </div>
                <!--Upcoming library events part-->
                <div class="col-lg-6">
                    <div class="cardsColor card border-0 shadow rounded-4 h-100">
                        <h2 class="card-header h3 px-4 py-3 bg-transparent border-light border-opacity-25">Upcoming Events:</h2>
                        <ul class="list-group list-group-flush">
<?php if (!$events): ?>
                            <li class="list-group-item bg-transparent text-reset border-0 px-4 py-3">No upcoming events yet.</li>
<?php endif; foreach ($events as $i => $a) overview_ann_item($a, $i === count($events) - 1); ?>
                        </ul>
                    </div>
                </div>
            </section>

            <!--User quick stats section-->
            <section>
                <div class="d-flex flex-wrap align-items-baseline justify-content-between mt-5 mb-3">
                    <h2 class="h1 fw-semibold mb-0">Quick Stats</h2>
                    <div id="activeReadingTimeStat" class="fs-6 text-muted d-flex align-items-center gap-2">
                        <i class="bi bi-stopwatch text-success fs-5" aria-hidden="true"></i>
                        <span>Active Reading Time: <strong id="readingTimeValue" class="text-reset"><?=
                            !$userPrefs['track_reading'] ? 'Tracking paused in Settings' : (
                                $readingSeconds < 60 ? ($readingSeconds > 0 ? $readingSeconds . 's' : '0 mins') : (
                                    $readingSeconds < 3600 ? floor($readingSeconds / 60) . ' mins' : number_format($readingSeconds / 3600, 1) . ' hrs'
                                )
                            )
                        ?></strong></span>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">
                    <!--Total books area-->
                    <div class="col">
                        <div class="cardsColor card border-0 shadow rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="neutralBox d-inline-block p-4 rounded-3"></span>
                                    <p class="display-4 mb-0"><?= (int)$dash['stats']['total_borrowed'] ?></p>
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
                                    <p class="display-4 mb-0"><?= (int)$dash['stats']['current'] ?></p>
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
                                    <p class="display-4 mb-0"><?= (int)$dash['stats']['due_week'] ?></p>
                                </div>
                                <p class="text-end fs-5 mb-0 mt-3">Books Due This Week</p>
                            </div>
                        </div>
                    </div>
                    <!--Books finished in the reader-->
                    <div class="col">
                        <div class="cardsColor card border-0 shadow rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="greenBox d-inline-block p-4 rounded-3"></span>
                                    <p class="display-4 mb-0"><?= (int)$dash['stats']['finished'] ?></p>
                                </div>
                                <p class="text-end fs-5 mb-0 mt-3">Books Finished</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!--Bookmarks section-->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                    <h2 class="h1 fw-semibold mb-0">Bookmarks</h2>
                    <a href="user_librarydb.php?view=library" class="fs-5"><u>Browse Library ></u></a>
                </div>

                <!--Books area-->
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
<?php foreach ($dash['bookmarks'] as $b):
        $cover = trim((string)$b['Cover_Image']) !== '' ? (filter_var($b['Cover_Image'], FILTER_VALIDATE_URL) ? $b['Cover_Image'] : '../' . ltrim($b['Cover_Image'], '/'))
                                                        : '../frontend/assets/icons/library-default.png'; ?>
                    <div class="col">
                        <a href="user_librarydb.php?view=mybooks" class="d-block ratio bookCover rounded-2 overflow-hidden shadow" aria-label="<?= e($b['Title']) ?>">
                            <img src="<?= e($cover) ?>" alt="" class="w-100 h-100 object-fit-cover" loading="lazy">
                        </a>
                        <a href="user_librarydb.php?view=mybooks" class="textArea">
                            <h3 class="h6 fw-bold mt-3 mb-0 text-truncate"><?= e($b['Title']) ?></h3>
                        </a>
                    </div>
<?php endforeach; ?>
                    <div class="col">
                        <a href="user_librarydb.php?view=library" class="d-block ratio bookCover rounded-2 overflow-hidden shadow" aria-label="Add a bookmark"></a>
                        <a href="user_librarydb.php?view=library" class="textArea">
                            <h3 class="h6 fw-bold mt-3 mb-0">+ Add Book</h3>
                        </a>
                    </div>
                </div>
            </section>

            <!-- LOGOUT CONFIRMATION MODAL -->
            <?php render_logout_modal(); ?>
        </main>

        <?php render_footer('student'); ?>

<?php render_scripts(['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'], false); ?>
    <script>
        (() => {
            const box = document.getElementById('libraryIdQr');
            const value = box && box.dataset.qr;
            if (value && typeof QRCode !== 'undefined') {
                box.replaceChildren();
                new QRCode(box, { text: value, width: 134, height: 134, correctLevel: QRCode.CorrectLevel.M });
            }
        })();
    </script>
</body>

</html>
