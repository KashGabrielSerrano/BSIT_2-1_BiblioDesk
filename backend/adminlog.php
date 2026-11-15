<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/core/activity.php';
require_once __DIR__ . '/partials/layout.php';

$user = require_role_page(['Admin']);

$entries = activity_entries(1000);

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bibliodesk-activity-log-' . gmdate('Y-m-d') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");   // BOM so Excel reads UTF-8
    fputcsv($out, ['When', 'Activity', 'Category', 'Details', 'Item', 'By', 'Role']);
    $safe = fn($v) => preg_match('/^[=+\-@]/', (string)$v) ? "'" . $v : $v;   // no spreadsheet formulas
    foreach ($entries as $e) {
        fputcsv($out, array_map($safe, [$e['when'], $e['label'], ACTIVITY_CATEGORIES[$e['category']][0], $e['details'],
                                        $e['target'], $e['actor'], $e['role']]));
    }
    fclose($out);
    exit();
}

$sum = activity_summary();
render_head('Activity Log | BiblioDesk', ['root.css', 'adminlog.css']);
?>

<?php render_sidebar('staff', 'activity'); ?>

<?php render_logout_modal(); ?>

<!-- NAVBAR -->
<?php render_topbar('staff'); ?>

<main class="container-fluid px-3 px-md-5 py-4">

    <h1 class="pageTitle">Activity Log</h1>

    <section aria-label="Activity summary">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-4">
<?php foreach ([['greenBox', $sum['today'], 'Activities Today'], ['yellowBox', $sum['week'], 'This Week'],
                ['redBox', $sum['accounts'], 'Account Changes (30 days)'], ['redBox', $sum['settings'], 'Settings Changes (30 days)']] as [$box, $n, $label]): ?>
            <div class="col">
                <div class="cardsColor card border-0 shadow rounded-4 h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="<?= $box ?> d-inline-block p-4 rounded-3"></span>
                            <p class="display-4 mb-0"><?= (int)$n ?></p>
                        </div>
                        <p class="text-end fs-5 mb-0 mt-3"><?= e($label) ?></p>
                    </div>
                </div>
            </div>
<?php endforeach; ?>
        </div>
    </section>

    <section class="toolbar">

        <form class="searchWrap" method="get" action="adminlog.php" role="search">
            <input class="form-control searchInput" type="search" name="q" value=""
                   placeholder="Search activity, person, details..." aria-label="Search the activity log" autocomplete="off">
        </form>

        <a class="btnExport" href="adminlog.php?export=csv">Export CSV</a>

        <div class="dropdown filterDropdown">
            <button class="btnFilter" type="button" id="filterDropdown" data-bs-toggle="dropdown"
                    data-bs-display="static" data-bs-auto-close="outside" aria-expanded="false" aria-label="Open filters">
                <img src="../frontend/assets/icons/filter.png" alt="Filter" width="22">
            </button>

            <div class="dropdown-menu dropdown-menu-end filterPanel" aria-labelledby="filterDropdown">
                <form id="filterForm" method="get" action="adminlog.php">
                    <h5 class="filterTitle">Filter Activity</h5>

                    <div class="mb-3">
                        <label class="form-label" for="sortBy">Sort By</label>
                        <select class="form-select" id="sortBy" name="sort">
                            <option value="default">Newest first</option>
                            <option value="date-oldest">Oldest first</option>
                            <option value="name-asc">Person (A - Z)</option>
                            <option value="name-desc">Person (Z - A)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <!-- id/name "status" is what librarianlog.js filters on (rows carry data-status = category) -->
                        <label class="form-label" for="status">Category</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All categories</option>
<?php foreach (ACTIVITY_CATEGORIES as $key => [$label]): ?>
                            <option value="<?= e($key) ?>"><?= e($label) ?></option>
<?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btnApply" type="submit">Apply Filters</button>
                        <a class="btnReset" href="adminlog.php">Reset</a>
                    </div>
                </form>
            </div>
        </div>

    </section>

    <section>
        <div class="tableScroll">
            <div class="logTable" data-empty-text="No activity found.">

                <div class="tableHead">
                    <span>Activity</span>
                    <span>Category</span>
                    <span>Done by</span>
                    <span>When</span>
                    <span>Item</span>
                    <span>Details</span>
                </div>

<?php foreach ($entries as $e): [$catLabel, $badge] = ACTIVITY_CATEGORIES[$e['category']]; ?>
                <div class="reportRow" data-name="<?= e($e['actor']) ?>" data-date="<?= e($e['iso']) ?>" data-status="<?= e($e['category']) ?>">
                    <div class="cellName">
                        <p class="bookTitle mb-0"><?= e($e['label']) ?></p>
                    </div>
                    <div class="cell"><span class="<?= e($badge) ?>"><?= e($catLabel) ?></span></div>
                    <div class="cell flex-column"><span><?= e($e['actor']) ?></span><?= $e['role'] ? '<small class="text-muted">' . e($e['role']) . '</small>' : '' ?></div>
                    <div class="cell"><?= e($e['when']) ?></div>
                    <div class="cell"><?= e($e['target'] ?: '—') ?></div>
                    <div class="cell text-start"><small><?= e($e['details'] ?: '—') ?></small></div>
                </div>
<?php endforeach; ?>

            </div>
        </div>

        <p class="pageInfo"></p>

        <div class="logPager">
            <ul class="pagination"></ul>
            <form class="perPage" method="get" action="adminlog.php">
                <label for="perPage">Rows Per Page:</label>
                <select class="form-select" id="perPage" name="per_page">
                    <option value="8">8</option>
                    <option value="12" selected>12</option>
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
