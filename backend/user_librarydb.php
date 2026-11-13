<?php
require_once __DIR__ . "/core/bootstrap.php";
require_once __DIR__ . "/core/booklink.php";

// Logged in and still Active (re-checked against the DB), otherwise -> login.php
require_login_page();

$con = db();
$so  = so();
$user_id = (int)$_SESSION['user_id'];

// Show either: 'library' or 'mybooks'
$view = $_GET['view'] ?? 'library';
if (!in_array($view, ['library', 'mybooks'], true)) {
    $view = 'library';
}

$continue_reading_books = [];
$bookmark_books         = [];
$favorite_books         = [];
$collections            = [];
$history_books          = [];

$new_arrivals           = [];
$recommendations        = [];
$educational            = [];
$non_educational        = [];
$research               = [];

/**
 * Returns the public cover image URL (Supabase, external HTTP URL, or local fallback if Book Cover won't load).
 *
 * @param string|null $url
 * @return string
 */
function getCoverImageUrl(?string $url): string {
    $url = trim($url ?? '');
    
    if (empty($url)) {
        return '../frontend/assets/icons/library-default.png';
    }
    
    if (filter_var($url, FILTER_VALIDATE_URL) || str_starts_with($url, '/')) {
        return $url;
    }

    return '../' . ltrim($url, '/');
}

/**
 * Latest books of ONE category (exact match).
 * Each shelf has its own query, so a shelf never borrows books from another category, and an older
 * book is never crowded out of its shelf by newer books of a different category.
 *
 * @return array<int, array<string, mixed>>
 */
function fetchShelf(mysqli $con, string $category, int $limit = 6): array {
    $stmt = $con->prepare(
        "SELECT Book_ID, Title, Author, Description, ISBN, Category, Publisher, Cover_Image, Drive_Link
           FROM book_table
          WHERE Category = ?
          ORDER BY Book_ID DESC
          LIMIT ?"
    );
    $stmt->bind_param("si", $category, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// ============================================================================
// MY BOOKS DATA FETCHING & RENDERING HELPERS
// ============================================================================
if ($view === 'mybooks') {
    // 1. Fetch Continue Reading Books
    $cont_query = "SELECT b.*, p.Current_Page, p.Total_Pages
                    FROM user_reading_progress p
                    JOIN book_table b ON p.Book_ID = b.Book_ID
                    WHERE p.User_ID = ? AND p.Is_Finished = 0
                    ORDER BY p.Updated_At DESC";
    $stmt = $con->prepare($cont_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $continue_reading_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 2. Fetch Bookmarks
    $bm_query = "SELECT b.*
                  FROM user_bookmarks bm
                  JOIN book_table b ON bm.Book_ID = b.Book_ID
                  WHERE bm.User_ID = ?
                  ORDER BY bm.Created_At DESC";
    $stmt = $con->prepare($bm_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $bookmark_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 3. Fetch Favorites
    $fav_query = "SELECT b.*
                   FROM user_favorites f
                  JOIN book_table b ON f.Book_ID = b.Book_ID
                  WHERE f.User_ID = ?
                  ORDER BY f.Created_At DESC";
    $stmt = $con->prepare($fav_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $favorite_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // 3b. My Collections (user_collections, migration 002): rendered by frontend/js/collections.js
    $collections = array_map(fn($r) => ['id' => (int)$r['Collection_ID'], 'name' => $r['Name'], 'books' => (int)$r['Books']],
        db_all($con, "SELECT c.Collection_ID, c.Name, COUNT(cb.Book_ID) AS Books
                        FROM user_collections c
                        LEFT JOIN user_collection_books cb ON cb.Collection_ID = c.Collection_ID
                       WHERE c.User_ID = ?
                       GROUP BY c.Collection_ID, c.Name
                       ORDER BY c.Created_At, c.Collection_ID", 'i', [$user_id]));

    // 4. Fetch History
    $hist_query = "SELECT b.*, h.Last_Opened
                    FROM user_reading_history h
                   JOIN book_table b ON h.Book_ID = b.Book_ID
                   WHERE h.User_ID = ?
                   ORDER BY h.Last_Opened DESC";
    $stmt = $con->prepare($hist_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $history_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

function renderBookGrid(array $books): string {
    if (empty($books)) {
        return '<div class="col-12 py-4 text-center text-muted fs-6">No books in this section yet.</div>';
    }
    $html = '';
    foreach ($books as $b) {
        $cover = !empty($b['Cover_Image']) ? htmlspecialchars(getCoverImageUrl($b['Cover_Image'])) : '../frontend/assets/icons/library-default.png';
        $hasFile = (!empty($b['Drive_Link'])) ? '1' : '0';
        $title = htmlspecialchars($b['Title'] ?? 'Untitled');
        $author = htmlspecialchars($b['Author'] ?? 'Unknown Author');
        $desc = htmlspecialchars($b['Description'] ?? '');
        $isbn = htmlspecialchars($b['ISBN'] ?? 'N/A');
        $genre = htmlspecialchars($b['Category'] ?? 'General');
        $pub = htmlspecialchars($b['Publisher'] ?? 'N/A');
        $book_id = (int)($b['Book_ID'] ?? 0);
        $token = htmlspecialchars(book_token($book_id));   // opaque id for the reader / share link
        $html .= '
        <div class="col">
            <a href="#" class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                data-title="' . $title . '"
                data-author="' . $author . '"
                data-desc="' . $desc . '"
                data-isbn="' . $isbn . '"
                data-genre="' . $genre . '"
                data-publisher="' . $pub . '"
                data-allowed="1"
                data-hasfile="' . $hasFile . '"
                data-token="' . $token . '"
                data-cover="' . $cover . '">
                <img src="' . $cover . '" class="w-100 h-100 object-fit-cover" alt="' . $title . '">
            </a>
            <a href="#" class="textArea text-decoration-none book-trigger"
                data-title="' . $title . '"
                data-author="' . $author . '"
                data-desc="' . $desc . '"
                data-isbn="' . $isbn . '"
                data-genre="' . $genre . '"
                data-publisher="' . $pub . '"
                data-allowed="1"
                data-hasfile="' . $hasFile . '"
                data-token="' . $token . '"
                data-cover="' . $cover . '">
                <h3 class="h6 fw-bold mt-3 mb-0 text-truncate">' . $title . '</h3>
                <p class="small mb-3 text-muted text-truncate">' . $author . '</p>
            </a>
        </div>';
    }
    return $html;
}

// ============================================================================
// LIBRARY DATA FETCHING & RENDERING HELPERS
// ============================================================================
if ($view === 'library') {
    // 1. Fetch recent books into memory in a single query
    $query = "SELECT Book_ID, Title, Author, Description, ISBN, Category, Publisher, Cover_Image, Drive_Link 
              FROM book_table 
              ORDER BY Book_ID DESC 
              LIMIT 50";
    $result = $con->query($query);

    $all_books = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $all_books[] = $row;
        }
    }

    $new_arrivals = array_slice($all_books, 0, 7);

    // Recommendations: legacy keyword match, otherwise simply the newest books.
    $recommendations_arr = [];
    foreach ($all_books as $book) {
        if (count($recommendations_arr) < 6 && stripos($book['Category'] ?? '', 'Recommend') !== false) {
            $recommendations_arr[] = $book;
        }
    }
    $recommendations = !empty($recommendations_arr) ? $recommendations_arr : array_slice($all_books, 0, 6);

    // Category shelves: exact match only. An empty shelf stays empty (no fallback to other categories).
    $educational     = fetchShelf($con, 'Educational');
    $non_educational = fetchShelf($con, 'Non-Educational');
    $research        = fetchShelf($con, 'Research Articles');
}

/**
 * Render a single book card
 * @param array<string, mixed> $row
 * @param string $ratioClass
 * @return string
 */
function renderBookCard(array $row, string $ratioClass = ''): string {
    $cover = htmlspecialchars(getCoverImageUrl($row['Cover_Image'] ?? null));   // library icon when there is no cover
    $drive = !empty($row['Drive_Link']) ? htmlspecialchars($row['Drive_Link']) : '';
    $hasFile = !empty($drive) ? '1' : '0';
    $token   = htmlspecialchars(book_token((int)($row['Book_ID'] ?? 0)));   // opaque id for the reader / share link
    $title   = htmlspecialchars($row['Title'] ?? 'Untitled');
    $author  = htmlspecialchars($row['Author'] ?? 'Unknown Author');
    
    return '
    <div class="col">
        <a href="#"
           class="book-trigger d-block ratio ' . $ratioClass . ' rounded-2 overflow-hidden shadow position-relative text-decoration-none"
           data-title="' . $title . '"
           data-author="' . $author . '"
           data-desc="' . htmlspecialchars($row['Description'] ?? '') . '"
           data-isbn="' . htmlspecialchars($row['ISBN'] ?? '') . '"
           data-genre="' . htmlspecialchars($row['Category'] ?? '') . '"
           data-publisher="' . htmlspecialchars($row['Publisher'] ?? '') . '"
           data-allowed="1"
           data-hasfile="' . $hasFile . '"
           data-token="' . $token . '"
           data-cover="' . $cover . '">
            <img src="' . $cover . '" class="w-100 h-100 object-fit-cover" alt="' . $title . '">
        </a>
        <a href="#" class="textArea text-decoration-none book-trigger"
           data-title="' . $title . '"
           data-author="' . $author . '"
           data-desc="' . htmlspecialchars($row['Description'] ?? '') . '"
           data-isbn="' . htmlspecialchars($row['ISBN'] ?? '') . '"
           data-genre="' . htmlspecialchars($row['Category'] ?? '') . '"
           data-publisher="' . htmlspecialchars($row['Publisher'] ?? '') . '"
           data-allowed="1"
           data-hasfile="' . $hasFile . '"
           data-token="' . $token . '"
           data-cover="' . $cover . '">
            <h3 class="h6 fw-bold mt-3 mb-0 text-truncate">' . $title . '</h3>
            <p class="small mb-3 text-muted text-truncate">' . $author . '</p>
        </a>
    </div>';
}

function renderEmptyState(string $icon, string $title, string $text): string {
    return '<div class="lib-empty text-center">'
        . '<span class="lib-empty-icon" aria-hidden="true"><i class="bi ' . htmlspecialchars($icon) . '"></i></span>'
        . '<h3 class="h6 fw-bold mb-1">' . htmlspecialchars($title) . '</h3>'
        . '<p class="small text-muted mb-0">' . htmlspecialchars($text) . '</p>'
        . '</div>';
}

function renderSectionHeader(string $icon, string $title, string $subtitle, string $headingId = ''): string {
    $idAttr = $headingId !== '' ? ' id="' . htmlspecialchars($headingId) . '"' : '';
    return '<div class="lib-section-head d-flex align-items-center gap-3 mb-4 text-start">'
        . '<span class="lib-section-icon" aria-hidden="true"><i class="bi ' . htmlspecialchars($icon) . '"></i></span>'
        . '<div><h2' . $idAttr . ' class="h4 fw-bold mb-0">' . htmlspecialchars($title) . '</h2>'
        . '<p class="small text-muted mb-0">' . htmlspecialchars($subtitle) . '</p></div>'
        . '</div>';
}

/**
 * Render a whole shelf of book cards, or an empty-state message when the category has no books.
 * @param array<int, array<string, mixed>> $books
 */
function renderShelf(array $books, string $ratioClass, string $emptyText = 'No books in this section yet.'): string {
    if (empty($books)) {
        return renderEmptyState('bi-journal-bookmark', 'Nothing on this shelf yet', $emptyText);
    }
    $html = '<div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">';
    foreach ($books as $row) {
        $html .= renderBookCard($row, $ratioClass);
    }
    return $html . '</div>';
}

function renderBookShelf(array $books, string $icon, string $emptyTitle, string $emptyText): string {
    if (empty($books)) {
        return renderEmptyState($icon, $emptyTitle, $emptyText);
    }
    return '<div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">' . renderBookGrid($books) . '</div>';
}
?>
<?php
require_once __DIR__ . '/partials/layout.php';
render_head('BiblioDesk - ' . ($view === 'mybooks' ? 'My Books' : 'Digital Library'), [$view === 'mybooks' ? 'mybooks.css' : 'user_librarydb.css']);
?>
    <!-- SIDEBAR -->
    <?php render_sidebar('student', $view === 'mybooks' ? 'mybooks' : 'library'); ?>
    <?php render_logout_modal(); ?>

    <!-- NAVBAR -->
    <?php render_topbar('student'); ?>

    <main class="container-fluid px-3 px-md-5 py-4">
        <?php render_preview_banner(); ?>

        <?php if ($view === 'mybooks'): ?>
            <!-- ============================================================================ -->
            <!-- VIEW: MY BOOKS                                                               -->
            <!-- ============================================================================ -->
            <header class="lib-hero mt-2">
                <p class="lib-eyebrow mb-2"><i class="bi bi-collection me-2" aria-hidden="true"></i>BiblioDesk</p>
                <h1 class="fw-bold display-5 mb-2">My Books</h1>
                <p class="lib-hero-lead mb-4">Your loans, reading progress, collections, bookmarks and favorites, all in one place.</p>
                <nav class="lib-jump-row" aria-label="Jump to a section">
                    <a class="lib-jump" href="#my-loans"><i class="bi bi-card-checklist me-1" aria-hidden="true"></i>Loans</a>
                    <a class="lib-jump" href="#lib-continue"><i class="bi bi-play-circle me-1" aria-hidden="true"></i>Continue Reading</a>
                    <a class="lib-jump" href="#lib-collections"><i class="bi bi-collection me-1" aria-hidden="true"></i>Collections</a>
                    <a class="lib-jump" href="#lib-bookmarks"><i class="bi bi-bookmark me-1" aria-hidden="true"></i>Bookmarks</a>
                    <a class="lib-jump" href="#lib-favorites"><i class="bi bi-heart me-1" aria-hidden="true"></i>Favorites</a>
                    <a class="lib-jump" href="#lib-history"><i class="bi bi-clock-history me-1" aria-hidden="true"></i>History</a>
                </nav>
            </header>

            <!-- My Loans & Reservations - frontend/js/circulation_student.js -->
            <section class="lib-panel" id="my-loans" aria-labelledby="myLoansHeading">
                <?= renderSectionHeader('bi-card-checklist', 'My Loans & Reservations', 'Books you have borrowed or reserved', 'myLoansHeading') ?>
                <div id="myLoans"><p class="text-muted">Loading your loans…</p></div>
            </section>

            <!-- Continue Reading -->
            <section class="lib-panel" id="lib-continue">
                <?= renderSectionHeader('bi-play-circle', 'Continue Reading', 'Pick up where you left off') ?>
                <?php echo renderBookShelf($continue_reading_books, 'bi-play-circle', 'Nothing in progress', 'Books you start reading will show up here.'); ?>
            </section>

            <!-- My Collections Section -->
            <section class="lib-panel" id="lib-collections">
                <?= renderSectionHeader('bi-collection', 'My Collections', 'Your own shelves, grouped your way') ?>
                <!-- the user's collections are drawn here by frontend/js/collections.js (before the "+ Add" card) -->
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center" id="collectionsRow">
                    <div class="col" id="addCollectionCol">
                        <a href="#" class="collection-card ratio rounded-2 overflow-hidden shadow position-relative d-block text-white" style="background-color: #ce861a; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#createCollectionModal">
                            <div class="d-flex flex-column justify-content-center align-items-center">
                                <i class="bi bi-plus-circle fs-1 mb-1"></i>
                            </div>
                        </a>
                        <div class="textArea">
                            <h3 class="h6 fw-bold mt-3 mb-0">+ Add Collections</h3>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bookmarks -->
            <section class="lib-panel" id="lib-bookmarks">
                <?= renderSectionHeader('bi-bookmark', 'Bookmarks', 'Books you saved for later') ?>
                <?php echo renderBookShelf($bookmark_books, 'bi-bookmark', 'No bookmarks yet', 'Bookmark a book from its details window to find it here.'); ?>
            </section>

            <!-- Favorites -->
            <section class="lib-panel" id="lib-favorites">
                <?= renderSectionHeader('bi-heart', 'Favorites', 'The books you love most') ?>
                <?php echo renderBookShelf($favorite_books, 'bi-heart', 'No favorites yet', 'Tap the heart on a book to add it here.'); ?>
            </section>

            <!-- History -->
            <section class="lib-panel" id="lib-history">
                <?= renderSectionHeader('bi-clock-history', 'History', 'Books you opened recently') ?>
                <?php echo renderBookShelf($history_books, 'bi-clock-history', 'No reading history yet', 'Books you open will be listed here.'); ?>
            </section>

        <?php else: ?>
            <!-- ============================================================================ -->
            <!-- VIEW: DIGITAL LIBRARY                                                        -->
            <!-- ============================================================================ -->
            <header class="lib-hero mt-2">
                <p class="lib-eyebrow mb-2"><i class="bi bi-collection me-2" aria-hidden="true"></i>BiblioDesk</p>
                <h1 class="fw-bold display-5 mb-2">Digital Library</h1>
                <p class="lib-hero-lead mb-4">Browse the newest arrivals, find your next read, and save books to your own shelves.</p>
                <nav class="lib-jump-row" aria-label="Jump to a shelf">
                    <a class="lib-jump" href="#lib-new"><i class="bi bi-stars me-1" aria-hidden="true"></i>New Arrivals</a>
                    <a class="lib-jump" href="#lib-rec"><i class="bi bi-hand-thumbs-up me-1" aria-hidden="true"></i>Recommendations</a>
                    <a class="lib-jump" href="#lib-edu"><i class="bi bi-mortarboard me-1" aria-hidden="true"></i>Educational</a>
                    <a class="lib-jump" href="#lib-nonedu"><i class="bi bi-cup-hot me-1" aria-hidden="true"></i>Non-Educational</a>
                    <a class="lib-jump" href="#lib-research"><i class="bi bi-journal-text me-1" aria-hidden="true"></i>Research</a>
                </nav>
            </header>

            <!-- NEW ARRIVALS -->
            <section class="new-arrivals-section lib-panel text-center" id="lib-new">
                <?= renderSectionHeader('bi-stars', 'New Arrivals', 'The latest books added to the library') ?>
                <div class="newarrivals-wrapper mx-auto">
                    <div class="newarrivals-container<?= empty($new_arrivals) ? ' d-none' : '' ?>" id="newarrivalsContainer">
                        <?php if (!empty($new_arrivals)): ?>
                            <?php foreach ($new_arrivals as $row): ?>
                                <?php
                                    $cover = htmlspecialchars(getCoverImageUrl($row['Cover_Image'] ?? null));
                                    $drive = !empty($row['Drive_Link']) ? htmlspecialchars($row['Drive_Link']) : '';
                                ?>
                                <div class="newarrivals-item book-trigger"
                                     data-title="<?php echo htmlspecialchars($row['Title'] ?? ''); ?>"
                                     data-author="<?php echo htmlspecialchars($row['Author'] ?? ''); ?>"
                                     data-desc="<?php echo htmlspecialchars($row['Description'] ?? ''); ?>"
                                     data-isbn="<?php echo htmlspecialchars($row['ISBN'] ?? ''); ?>"
                                     data-genre="<?php echo htmlspecialchars($row['Category'] ?? ''); ?>"
                                     data-publisher="<?php echo htmlspecialchars($row['Publisher'] ?? ''); ?>"
                                     data-allowed="1"
                                     data-hasfile="<?php echo !empty($drive) ? '1' : '0'; ?>"
                                     data-token="<?php echo htmlspecialchars(book_token((int)($row['Book_ID'] ?? 0))); ?>"
                                     data-cover="<?php echo $cover; ?>">
                                    <img src="<?php echo $cover; ?>" alt="<?php echo htmlspecialchars($row['Title'] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($new_arrivals)): ?>
                        <?= renderEmptyState('bi-inbox', 'No new arrivals yet', 'New books will show up here as soon as the librarians add them.') ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- RECOMMENDATIONS -->
            <section class="recommendations-section lib-panel lib-panel-rec" id="lib-rec">
                <?= renderSectionHeader('bi-hand-thumbs-up', 'Recommendations', 'Picks from the BiblioDesk collection') ?>
                <?php
                    $rec_html = '';
                    foreach ($recommendations as $row) {
                        $rec_html .= renderBookCard($row, 'recCover');
                    }
                    if ($rec_html === '') {
                        echo renderEmptyState('bi-hand-thumbs-up', 'No recommendations yet', 'Recommended books will appear here once the library has some titles.');
                    } else {
                        echo '<div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">' . $rec_html . '</div>';
                    }
                ?>
            </section>

            <!-- EDUCATIONAL BOOKS -->
            <section class="lib-panel" id="lib-edu">
                <?= renderSectionHeader('bi-mortarboard', 'Educational Books', 'Textbooks and study material') ?>
                <?php echo renderShelf($educational, 'eduCover', 'No educational books have been added yet.'); ?>
            </section>

            <!-- NON-EDUCATIONAL BOOKS -->
            <section class="lib-panel" id="lib-nonedu">
                <?= renderSectionHeader('bi-cup-hot', 'Non-Educational Books', 'Fiction, hobbies and leisure reading') ?>
                <?php echo renderShelf($non_educational, 'noneduCover', 'No non-educational books have been added yet.'); ?>
            </section>

            <!-- RESEARCH ARTICLES -->
            <section class="lib-panel" id="lib-research">
                <?= renderSectionHeader('bi-journal-text', 'Research Articles', 'Papers, studies and journals') ?>
                <?php echo renderShelf($research, 'rsCover', 'No research articles have been added yet.'); ?>
            </section>
        <?php endif; ?>

    </main>

    <!-- BOOK DETAILS MODAL -->
    <div class="modal fade bookModal" id="bookDetailsModal" tabindex="-1" aria-labelledby="modalBookTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header px-4 py-3 border-0 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-book-half fs-5" style="color: var(--accent-soft);"></i>
                        <h6 class="modal-title fw-bold mb-0 text-white tracking-wide text-uppercase" style="letter-spacing: 0.75px; font-size: 0.85rem;">Book Details</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-md-4">
                    <div class="row g-4 align-items-start">
                        <div class="col-md-4 text-center">
                            <div class="bookModal-cover-wrap mx-auto">
                                <img id="modalBookImg" src="" alt="Cover" class="bookModal-cover img-fluid rounded-3 shadow">
                            </div>
                            <div class="mt-3">
                                <span class="badge bookModal-isbn-badge d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-upc-scan text-muted"></i>
                                    <span id="modalBookIsbn">ISBN: N/A</span>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <span class="bookGenre badge rounded-pill px-3 py-1 fw-semibold" id="modalBookGenre">Category</span>
                            </div>

                            <h3 class="fw-bold mb-1 text-dark bookModal-title-text" id="modalBookTitle">Book Title</h3>
                            <div class="d-flex align-items-center gap-1 text-muted mb-3">
                                <i class="bi bi-person text-secondary"></i>
                                <span class="bookModal-author fw-semibold" id="modalBookAuthor">Author Name</span>
                            </div>

                            <hr class="bookModal-divider my-2">

                            <div class="bookModal-desc-box p-3 rounded-3 mb-3">
                                <div class="d-flex align-items-center gap-1 small fw-bold text-uppercase text-muted mb-1">
                                    <i class="bi bi-card-text text-secondary"></i>
                                    <span>Description</span>
                                </div>
                                <p class="bookModal-desc mb-0" id="modalBookDesc">Book description will appear here.</p>
                            </div>

                            <div class="d-flex align-items-center gap-1 small text-muted mb-3" id="modalBookPublisher">
                                <i class="bi bi-building text-secondary"></i>
                                <span>Publisher:</span>
                                <strong class="text-dark" id="modalBookPublisherName">Publisher Name</strong>
                            </div>

                            <!-- Holds the opaque book token (never the numeric Book_ID); filled by mybooks.js -->
                            <input type="hidden" id="modalBookId" value="">

                            <div class="bookModal-btn-container d-flex align-items-center gap-2 flex-wrap mt-3 pt-1">
                                <a id="modalBookRead" href="#" target="_blank" class="bookModal-btn-read d-none">
                                    <i class="bi bi-book-half me-1"></i>Read Now
                                </a>

                                <button type="button" id="modalBookBorrow" class="bookModal-btn-read border-0 d-none">
                                    <i class="bi bi-bookmark-check me-1"></i>Borrow
                                </button>

                                <a id="modalBookAddToCollections" href="#" class="bookModal-btn-addto d-none" role="button"
                                   aria-expanded="false" aria-controls="modalCollectionPanel">
                                    <i class="bi bi-folder-plus me-1"></i>Add To Collections
                                </a>

                                <div class="bookModal-utility-btns d-flex align-items-center gap-2 ms-auto">
                                    <button type="button" id="modalBookBookmark" class="btn bookModal-icon-btn d-none" aria-label="Bookmark" title="Bookmark">
                                        <i class="bi bi-bookmark"></i>
                                    </button>
                                    <button type="button" id="modalBookFavorite" class="btn bookModal-icon-btn d-none" aria-label="Favorite" title="Favorite">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                    <button type="button" id="modalBookShare" class="btn bookModal-icon-btn d-none" aria-label="Share link to this book" title="Share link">
                                        <i class="bi bi-share"></i>
                                    </button>
                                    <button type="button" id="modalBookReport" class="btn bookModal-icon-btn" aria-label="Report an issue with this book"
                                        title="Report an issue" data-report-toggle="modalReportPanel" aria-expanded="false" aria-controls="modalReportPanel">
                                        <i class="bi bi-flag"></i>
                                    </button>
                                </div>

                                <p id="modalCircInfo" class="small w-100 mb-0 order-last" hidden role="status"></p>

                                <div id="modalSharePanel" class="bookModal-panel d-none order-last w-100 mt-2">
                                    <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                                        <div id="modalShareQr" class="bg-white p-2 rounded border shadow-sm d-inline-block"></div>
                                        <div class="flex-grow-1 w-100">
                                            <label class="small fw-semibold mb-1" for="modalShareLink">
                                                <i class="bi bi-link-45deg me-1"></i>Link to this book (readers need to be logged in)
                                            </label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="modalShareLink" class="form-control" readonly>
                                                <button type="button" id="modalShareCopy" class="btn btn-dark px-3">
                                                    <i class="bi bi-clipboard me-1"></i>Copy
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- My Collections: tick the collections this book belongs to (frontend/js/collections.js) -->
                                <div id="modalCollectionPanel" class="bookModal-panel collection-panel w-100 mt-2 order-last" hidden>
                                    <p class="small fw-bold mb-2 text-uppercase tracking-wide text-muted">
                                        <i class="bi bi-collection me-1"></i>Add this book to:
                                    </p>
                                    <div id="modalCollectionList" class="collection-panel-list mb-2"></div>
                                    <form id="modalCollectionNew" class="input-group input-group-sm" novalidate>
                                        <input type="text" name="name" class="form-control" maxlength="30" autocomplete="off"
                                               placeholder="New collection name" aria-label="New collection name" required>
                                        <button type="submit" class="btn btn-dark px-3">
                                            <i class="bi bi-plus-lg me-1"></i>Create &amp; add
                                        </button>
                                    </form>
                                    <p id="modalCollectionMsg" class="small mt-1 mb-0" hidden role="status"></p>
                                </div>

                                <form id="modalReportPanel" class="bookModal-panel w-100 mt-2 order-last" hidden data-report-panel data-book-source="#modalBookId">
                                    <div class="d-flex align-items-center gap-1 small fw-bold text-uppercase text-muted mb-2">
                                        <i class="bi bi-exclamation-triangle text-danger me-1"></i>
                                        <span>Report an issue</span>
                                    </div>
                                    <label class="small mb-1 text-muted" for="modalReportIssue">What is wrong with this book?</label>
                                    <select id="modalReportIssue" name="issue_type" class="form-select form-select-sm mb-2" required>
                                        <option value="">Choose an issue…</option>
                                        <option value="Broken Link">Broken Link</option>
                                        <option value="Missing Page/s">Missing Page/s</option>
                                        <option value="Wrong File">Wrong File</option>
                                        <option value="License Concern">License Concern</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <textarea name="description" class="form-control form-control-sm mb-2" rows="2" maxlength="1000"
                                        placeholder="Details (optional), e.g. which page"></textarea>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">
                                            <i class="bi bi-send me-1"></i>Send report
                                        </button>
                                        <span class="small" data-report-result hidden role="status"></span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($view === 'mybooks'): ?>
    <!-- COLLECTION MODAL -->
    <div class="modal fade collectionModal" id="createCollectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Create New Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createCollectionForm" novalidate>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="collectionNameInput" class="form-label fw-semibold">Collection Name</label>
                            <input type="text" class="form-control" id="collectionNameInput" placeholder="School Related" maxlength="30" autocomplete="off" required>
                            <div class="invalid-feedback" id="collectionNameError">Enter a collection name.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="saveCollectionBtn" class="btn btn-success rounded-pill px-4">Create Collection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ONE COLLECTION: its books, rename, delete (frontend/js/collections.js) -->
    <div class="modal fade collectionModal" id="collectionViewModal" tabindex="-1" aria-labelledby="collectionViewTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 flex-wrap gap-2">
                    <h5 class="modal-title fw-bold me-auto text-break" id="collectionViewTitle">Collection</h5>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3" id="collectionRenameBtn">Rename</button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="collectionDeleteBtn">Delete</button>
                    <button type="button" class="btn-close ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                    <form id="collectionRenameForm" class="input-group input-group-sm w-100" hidden novalidate>
                        <input type="text" name="name" class="form-control" maxlength="30" autocomplete="off" aria-label="New collection name" required>
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-rename-cancel>Cancel</button>
                    </form>
                    <div id="collectionDeleteConfirm" class="w-100 small" hidden>
                        Delete this collection? The books stay in the library.
                        <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 ms-2" id="collectionDeleteYes">Delete</button>
                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-delete-cancel>Cancel</button>
                    </div>
                    <p id="collectionViewMsg" class="small text-danger w-100 mb-0" hidden role="status"></p>
                </div>
                <div class="modal-body">
                    <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center" id="collectionBooks"></div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // The user's collections from the DB (drawn by frontend/js/collections.js)
        window.bdCollections = <?= json_encode($collections, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <?php endif; ?>

    <!-- FOOTER -->
    <?php render_footer('student'); ?>

    <script>
        window.csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
        window.appBaseUrl = <?php echo json_encode(APP_BASE_URL); ?>;
    </script>
    <!-- QR code is drawn in the browser (no third-party service sees the book link) -->
<?php render_scripts(['mybooks.js', 'collections.js', 'report_issue.js', 'circulation_student.js', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'], false); ?>
    <script>
        (() => {
            const modal    = document.getElementById('bookDetailsModal');
            if (!modal) return;
            const shareBtn = document.getElementById('modalBookShare');
            const panel    = document.getElementById('modalSharePanel');
            const qrBox    = document.getElementById('modalShareQr');
            const linkBox  = document.getElementById('modalShareLink');
            const copyBtn  = document.getElementById('modalShareCopy');
            const readBtn  = document.getElementById('modalBookRead');
            const idInput  = document.getElementById('modalBookId');   // holds the book token

            // Only books with a readable file get a link-back; also start closed on every open.
            modal.addEventListener('show.bs.modal', () => {
                panel.classList.add('d-none');
                shareBtn.classList.toggle('d-none', readBtn.classList.contains('d-none'));
            });

            shareBtn.addEventListener('click', () => {
                const token = idInput.value;
                if (!token) return;
                // Public domain from config (APP_BASE_URL) so phones can open it; falls back to this page's URL.
                const url = new URL('read_book.php?b=' + encodeURIComponent(token), window.appBaseUrl || window.location.href).href;
                linkBox.value = url;
                qrBox.replaceChildren();
                if (typeof QRCode !== 'undefined') {
                    new QRCode(qrBox, { text: url, width: 128, height: 128, correctLevel: QRCode.CorrectLevel.M });
                    qrBox.classList.remove('d-none');
                } else {
                    qrBox.classList.add('d-none'); // QR library did not load: the link still works
                }
                panel.classList.toggle('d-none');
            });

            copyBtn.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(linkBox.value);
                } catch {
                    linkBox.select();
                    document.execCommand('copy');
                }
                copyBtn.textContent = 'Copied';
                setTimeout(() => { copyBtn.textContent = 'Copy'; }, 1500);
            });
        })();
    </script>
</body>
</html>