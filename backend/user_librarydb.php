<?php
require_once __DIR__ . "/core/bootstrap.php";

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
        $html .= '
        <div class="col">
            <a href="#" class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                data-id="' . $book_id . '"
                data-title="' . $title . '"
                data-author="' . $author . '"
                data-desc="' . $desc . '"
                data-isbn="' . $isbn . '"
                data-genre="' . $genre . '"
                data-publisher="' . $pub . '"
                data-allowed="1"
                data-hasfile="' . $hasFile . '"
                data-cover="' . $cover . '">
                <img src="' . $cover . '" class="w-100 h-100 object-fit-cover" alt="' . $title . '">
            </a>
            <a href="#" class="textArea text-decoration-none book-trigger"
                data-id="' . $book_id . '"
                data-title="' . $title . '"
                data-author="' . $author . '"
                data-desc="' . $desc . '"
                data-isbn="' . $isbn . '"
                data-genre="' . $genre . '"
                data-publisher="' . $pub . '"
                data-allowed="1"
                data-hasfile="' . $hasFile . '"
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
    $cover = !empty($row['Cover_Image']) ? htmlspecialchars(getCoverImageUrl($row['Cover_Image'])) : '../frontend/assets/temporary/temporaryimage_1.jpg';
    $drive = !empty($row['Drive_Link']) ? htmlspecialchars($row['Drive_Link']) : '';
    $hasFile = !empty($drive) ? '1' : '0';
    $bookId  = htmlspecialchars((string)($row['Book_ID'] ?? ''));
    $title   = htmlspecialchars($row['Title'] ?? 'Untitled');
    $author  = htmlspecialchars($row['Author'] ?? 'Unknown Author');
    
    return '
    <div class="col">
        <a href="#"
           class="book-trigger d-block ratio ' . $ratioClass . ' rounded-2 overflow-hidden shadow position-relative text-decoration-none"
           data-id="' . $bookId . '"
           data-title="' . $title . '"
           data-author="' . $author . '"
           data-desc="' . htmlspecialchars($row['Description'] ?? '') . '"
           data-isbn="' . htmlspecialchars($row['ISBN'] ?? '') . '"
           data-genre="' . htmlspecialchars($row['Category'] ?? '') . '"
           data-publisher="' . htmlspecialchars($row['Publisher'] ?? '') . '"
           data-allowed="1"
           data-hasfile="' . $hasFile . '"
           data-cover="' . $cover . '">
            <img src="' . $cover . '" class="w-100 h-100 object-fit-cover" alt="' . $title . '">
        </a>
        <a href="#" class="textArea text-decoration-none book-trigger"
           data-id="' . $bookId . '"
           data-title="' . $title . '"
           data-author="' . $author . '"
           data-desc="' . htmlspecialchars($row['Description'] ?? '') . '"
           data-isbn="' . htmlspecialchars($row['ISBN'] ?? '') . '"
           data-genre="' . htmlspecialchars($row['Category'] ?? '') . '"
           data-publisher="' . htmlspecialchars($row['Publisher'] ?? '') . '"
           data-allowed="1"
           data-hasfile="' . $hasFile . '"
           data-cover="' . $cover . '">
            <h3 class="h6 fw-bold mt-3 mb-0 text-truncate">' . $title . '</h3>
            <p class="small mb-3 text-muted text-truncate">' . $author . '</p>
        </a>
    </div>';
}

/**
 * Render a whole shelf of book cards, or an empty-state message when the category has no books.
 * @param array<int, array<string, mixed>> $books
 */
function renderShelf(array $books, string $ratioClass): string {
    if (empty($books)) {
        return '<div class="col-12 py-4 text-center text-muted fs-6">No books in this section yet.</div>';
    }
    $html = '';
    foreach ($books as $row) {
        $html .= renderBookCard($row, $ratioClass);
    }
    return $html;
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
            <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <h1 class="fw-bold mb-0 p-1">My Books</h1>
                </div>
            </div>

            <!-- Continue Reading -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">Continue Reading</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <?php echo renderBookGrid($continue_reading_books); ?>
                </div>
            </section>
            <hr class="my-5">

            <!-- My Collections Section -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">My Collections</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center" id="collectionsRow">
                    <div class="col">
                        <a href="#" class="collection-card ratio shadow position-relative d-block rounded-2 overflow-hidden" style="background-color: #5bc0de;"></a>
                        <div class="textArea">
                            <h3 class="h6 fw-bold mt-3 mb-0">School Related</h3>
                        </div>
                    </div>
                    <div class="col">
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
            <hr class="my-5">

            <!-- Bookmarks -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">Bookmarks</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <?php echo renderBookGrid($bookmark_books); ?>
                </div>
            </section>
            <hr class="my-5">

            <!-- Favorites -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">Favorites</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <?php echo renderBookGrid($favorite_books); ?>
                </div>
            </section>
            <hr class="my-5">

            <!-- History -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">History</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <?php echo renderBookGrid($history_books); ?>
                </div>
            </section>

        <?php else: ?>
            <!-- ============================================================================ -->
            <!-- VIEW: DIGITAL LIBRARY                                                        -->
            <!-- ============================================================================ -->
            <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <h1 class="fw-bold mb-0 p-1">Digital Library</h1>
                </div>
            </div>

            <!-- NEW ARRIVALS -->
            <section class="new-arrivals-section text-center py-4">
                <h2 class="fw-bold mb-4 display-5">NEW ARRIVALS</h2>
                <div class="newarrivals-wrapper mx-auto">
                    <div class="newarrivals-container" id="newarrivalsContainer">
                        <?php if (!empty($new_arrivals)): ?>
                            <?php foreach ($new_arrivals as $row): ?>
                                <?php
                                    $cover = !empty($row['Cover_Image']) ? htmlspecialchars(getCoverImageUrl($row['Cover_Image'])) : '../frontend/assets/temporary/temporaryimage_1.jpg';
                                    $drive = !empty($row['Drive_Link']) ? htmlspecialchars($row['Drive_Link']) : '';
                                ?>
                                <div class="newarrivals-item book-trigger"
                                     data-id="<?php echo htmlspecialchars((string)($row['Book_ID'] ?? '')); ?>"
                                     data-title="<?php echo htmlspecialchars($row['Title'] ?? ''); ?>"
                                     data-author="<?php echo htmlspecialchars($row['Author'] ?? ''); ?>"
                                     data-desc="<?php echo htmlspecialchars($row['Description'] ?? ''); ?>"
                                     data-isbn="<?php echo htmlspecialchars($row['ISBN'] ?? ''); ?>"
                                     data-genre="<?php echo htmlspecialchars($row['Category'] ?? ''); ?>"
                                     data-publisher="<?php echo htmlspecialchars($row['Publisher'] ?? ''); ?>"
                                     data-allowed="1"
                                     data-hasfile="<?php echo !empty($drive) ? '1' : '0'; ?>"
                                     data-cover="<?php echo $cover; ?>">
                                    <img src="<?php echo $cover; ?>" alt="<?php echo htmlspecialchars($row['Title'] ?? ''); ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- RECOMMENDATIONS -->
            <section class="recommendations-section">
                <div class="recommendations-wrapper mx-auto">
                    <div class="d-flex justify-content-between align-items-baseline mt-3 mb-3">
                        <h2 class="h3 fw-semibold mb-0 mt-3">Recommendations</h2>
                    </div>
                    <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                        <?php foreach ($recommendations as $row): ?>
                            <?php echo renderBookCard($row, 'recCover'); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <hr class="my-5">

            <!-- EDUCATIONAL BOOKS -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">Educational Books</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <?php echo renderShelf($educational, 'eduCover'); ?>
                </div>
            </section>
            <hr class="my-5">

            <!-- NON-EDUCATIONAL BOOKS -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">Non-Educational Books</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <?php echo renderShelf($non_educational, 'noneduCover'); ?>
                </div>
            </section>
            <hr class="my-5">

            <!-- RESEARCH ARTICLES -->
            <section>
                <div class="d-flex justify-content-between align-items-baseline mt-4 mb-3">
                    <h2 class="h3 fw-semibold mb-0">Research Articles</h2>
                </div>
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                    <?php echo renderShelf($research, 'rsCover'); ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <!-- BOOK DETAILS MODAL -->
    <div class="modal fade bookModal" id="bookDetailsModal" tabindex="-1" aria-labelledby="modalBookTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <img id="modalBookImg" src="" alt="Cover" class="bookModal-cover img-fluid rounded">
                            <p class="Bookisbn mt-3" id="modalBookIsbn">ISBN: N/A</p>
                        </div>
                        <div class="col-md-8">
                            <h5 class="modal-title display-5" id="modalBookTitle">Book Title</h5>
                            <p class="bookModal-author" id="modalBookAuthor">Author Name</p>
                            <hr>
                            <span class="bookGenre" id="modalBookGenre">Category</span>
                            <p class="bookModal-desc" id="modalBookDesc">Book description will appear here.</p>
                            <p class="bookModal-publisher" id="modalBookPublisher">
                                Publisher: <span id="modalBookPublisherName">Publisher Name</span>
                            </p>
                            <input type="hidden" id="modalBookId" value="">
                            
                            <div class="bookModal-btn-container d-flex align-items-center gap-2 flex-wrap mt-4">
                                <button type="button" id="modalBookShare" class="btn border-0 p-2 d-none order-last" aria-label="Share link to this book">
                                    <i class="bi bi-share fs-4"></i>
                                </button>
                                <div id="modalSharePanel" class="d-none order-last w-100 mt-2">
                                    <div class="d-flex flex-column flex-sm-row align-items-center gap-3">
                                        <div id="modalShareQr" class="bg-white p-2 rounded d-inline-block"></div>
                                        <div class="flex-grow-1 w-100">
                                            <label class="small mb-1" for="modalShareLink">Link back to this book (readers need to be logged in)</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="modalShareLink" class="form-control" readonly>
                                                <button type="button" id="modalShareCopy" class="btn btn-outline-dark">Copy</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a id="modalBookRead" href="#" target="_blank" class="bookModal-btn-read d-none">Read Now</a>
                                <a id="modalBookAddToCollections" href="#" class="bookModal-btn-addto d-none">Add To Collections</a>
                                <button type="button" id="modalBookBookmark" class="btn border-0 p-2 d-none" aria-label="Bookmark">
                                    <i class="bi bi-bookmark fs-4"></i>
                                </button>
                                <button type="button" id="modalBookFavorite" class="btn border-0 p-2 d-none" aria-label="Favorite">
                                    <i class="bi bi-heart fs-4"></i>
                                </button>
                                <button type="button" id="modalBookReport" class="btn border-0 p-2" aria-label="Report an issue with this book"
                                    title="Report an issue" data-report-toggle="modalReportPanel" aria-expanded="false" aria-controls="modalReportPanel">
                                    <i class="bi bi-flag fs-4"></i>
                                </button>
                                <form id="modalReportPanel" class="w-100 mt-2 order-last" hidden data-report-panel data-book-source="#modalBookId">
                                    <label class="small mb-1" for="modalReportIssue">What is wrong with this book?</label>
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
                                        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">Send report</button>
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
                            <input type="text" class="form-control" id="collectionNameInput" placeholder="School Related" maxlength="13" autocomplete="off" required>
                            <div class="invalid-feedback">Enter a collection name.</div>
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
    <?php endif; ?>

    <!-- FOOTER -->
    <?php render_footer('student'); ?>

    <script>
        window.csrfToken = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
        window.appBaseUrl = <?php echo json_encode(APP_BASE_URL); ?>;
    </script>
<?php render_scripts(['mybooks.js', 'report_issue.js', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js'], false); ?>
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
            const idInput  = document.getElementById('modalBookId');

            // Only books with a readable file get a link-back; also start closed on every open.
            modal.addEventListener('show.bs.modal', () => {
                panel.classList.add('d-none');
                shareBtn.classList.toggle('d-none', readBtn.classList.contains('d-none'));
            });

            shareBtn.addEventListener('click', () => {
                const id = idInput.value;
                if (!id) return;
                // Public domain from config (APP_BASE_URL) so phones can open it; falls back to this page's URL.
                const url = new URL('read_book.php?id=' + encodeURIComponent(id), window.appBaseUrl || window.location.href).href;
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