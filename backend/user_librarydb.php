<?php
session_start();
require_once "connection/config.php";
require_once "helpers/SystemOperators.php";

$con = connection();
$so  = new SystemOperators();

function renderBookCard($row, $ratioClass = '') {
    $cover = !empty($row['Cover_Image']) ? htmlspecialchars($row['Cover_Image']) : '../frontend/assets/temporary/temporaryimage_1.jpg';
    $drive = !empty($row['Drive_Link']) ? htmlspecialchars($row['Drive_Link']) : '';
    $hasFile = !empty($drive) ? '1' : '0';
    $bookId  = htmlspecialchars($row['Book_ID'] ?? '');
    
    return '
    <div class="col">
        <a href="read_book.php?id=' . $bookId . '"
           class="book-trigger d-block ratio ' . $ratioClass . ' rounded-2 overflow-hidden shadow position-relative text-decoration-none"
           data-id="' . $bookId . '"
           data-title="' . htmlspecialchars($row['Title'] ?? '') . '"
           data-author="' . htmlspecialchars($row['Author'] ?? '') . '"
           data-desc="' . htmlspecialchars($row['Description'] ?? '') . '"
           data-isbn="' . htmlspecialchars($row['ISBN'] ?? '') . '"
           data-genre="' . htmlspecialchars($row['Category'] ?? '') . '"
           data-publisher="' . htmlspecialchars($row['Publisher'] ?? '') . '"
           data-allowed="1"
           data-hasfile="' . $hasFile . '"
           data-cover="' . $cover . '">
            <img src="' . $cover . '" class="w-100 h-100 object-fit-cover" alt="' . htmlspecialchars($row['Title'] ?? '') . '">
        </a>
        <a href="read_book.php?id=' . $bookId . '" class="textArea text-decoration-none">
            <h3 class="h6 fw-bold mt-3 mb-0">' . htmlspecialchars($row['Title'] ?? 'Untitled') . '</h3>
            <p class="small mb-3 text-muted">' . htmlspecialchars($row['Author'] ?? 'Unknown Author') . '</p>
        </a>
    </div>';
}

// Fetch categories from Database
$new_arrivals = $con->query("SELECT * FROM book_table ORDER BY Book_ID DESC LIMIT 7");
$recommendations = $con->query("SELECT * FROM book_table WHERE Category LIKE '%Recommendation%' OR Category LIKE '%Recommend%' ORDER BY Book_ID DESC LIMIT 6");
$educational = $con->query("SELECT * FROM book_table WHERE Category LIKE '%Educational%' ORDER BY Book_ID DESC LIMIT 6");
$non_educational = $con->query("SELECT * FROM book_table WHERE Category LIKE '%Non-Educational%' ORDER BY Book_ID DESC LIMIT 6");
$research = $con->query("SELECT * FROM book_table WHERE Category LIKE '%Research%' ORDER BY Book_ID DESC LIMIT 6");

// Fallback: If category filters return nothing, get recent books
if ($recommendations->num_rows === 0) { $recommendations = $con->query("SELECT * FROM book_table ORDER BY Book_ID DESC LIMIT 6"); }
if ($educational->num_rows === 0) { $educational = $con->query("SELECT * FROM book_table ORDER BY Book_ID DESC LIMIT 6"); }
if ($non_educational->num_rows === 0) { $non_educational = $con->query("SELECT * FROM book_table ORDER BY Book_ID DESC LIMIT 6"); }
if ($research->num_rows === 0) { $research = $con->query("SELECT * FROM book_table ORDER BY Book_ID DESC LIMIT 6"); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliodesk - Digital Library</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/user_librarydb.css">
</head>
<body>

    <!-- SIDEBAR -->
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
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="userdb.php">
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
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="user_librarydb.php" aria-current="page">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library page" width="28">
                            </span>
                            <span class="sideText">Library</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- NAVBAR -->
    <header class="navbar navbar-dark shadow topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0" href="userdb.php">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>
            <div class="d-flex align-items-center gap-4">
                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                    <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                </button>
                <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="rounded-circle" width="44" height="44">
                </button>
            </div>
        </div>
    </header>

    <main>
        <!-- LIBRARY HEADER -->
        <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-4">
            <div class="d-flex align-items-center gap-3">
                <h1 class="fw-bold mb-0 p-1">Digital Library</h1>
            </div>
            <div class="d-flex align-items-center gap-3">
                <form class="searchForm" role="search" onsubmit="return false;">
                    <div class="searchBox">
                        <i class="bi bi-search"></i>
                        <input type="search" class="form-control" placeholder="Search" aria-label="Search library">
                    </div>
                </form>
            </div>
        </div>
    <!-- NEW ARRIVALS -->
    <section class="new-arrivals-section text-center py-5">
        <h2 class="fw-bold mb-4 display-4">NEW ARRIVALS</h2>
        <div class="newarrivals-wrapper mx-auto">
            <div class="newarrivals-card"></div>
            <div class="newarrivals-container" id="newarrivalsContainer">
                <?php if ($new_arrivals && $new_arrivals->num_rows > 0): ?>
                    <?php while ($row = $new_arrivals->fetch_assoc()): ?>
                        <?php 
                            $cover = !empty($row['Cover_Image']) ? htmlspecialchars($row['Cover_Image']) : '../frontend/assets/temporary/temporaryimage_1.jpg';
                            $drive = !empty($row['Drive_Link']) ? htmlspecialchars($row['Drive_Link']) : '';
                        ?>
                        <div class="newarrivals-item" 
                            data-id="<?php echo htmlspecialchars($row['Book_ID'] ?? ''); ?>"
                            data-title="<?php echo htmlspecialchars($row['Title'] ?? ''); ?>"
                            data-author="<?php echo htmlspecialchars($row['Author'] ?? ''); ?>"
                            data-desc="<?php echo htmlspecialchars($row['Description'] ?? ''); ?>"
                            data-isbn="<?php echo htmlspecialchars($row['ISBN'] ?? ''); ?>"
                            data-genre="<?php echo htmlspecialchars($row['Category'] ?? ''); ?>"
                            data-publisher="<?php echo htmlspecialchars($row['Publisher'] ?? ''); ?>"
                            data-allowed="1"
                            data-hasfile="<?php echo !empty($drive) ? '1' : '0'; ?>"
                            data-cover="<?php echo $cover; ?>">
                            <img src="<?php echo $cover; ?>" alt="<?php echo htmlspecialchars($row['Title']); ?>">
                        </div>
                    <?php endwhile; ?>
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
                <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 justify-content-center text-center">
                    <?php while ($row = $recommendations->fetch_assoc()): ?>
                        <?php echo renderBookCard($row, 'recCover'); ?>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
        <hr>

        <!-- EDUCATIONAL BOOKS -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Educational Books</h2>
            </div>
            <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <?php while ($row = $educational->fetch_assoc()): ?>
                    <?php echo renderBookCard($row, 'eduCover'); ?>
                <?php endwhile; ?>
            </div>
        </section>
        <hr>

        <!-- NON-EDUCATIONAL BOOKS -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Non-Educational Books</h2>
            </div>
            <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <?php while ($row = $non_educational->fetch_assoc()): ?>
                    <?php echo renderBookCard($row, 'noneduCover'); ?>
                <?php endwhile; ?>
            </div>
        </section>
        <hr>

        <!-- RESEARCH ARTICLES -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Research Articles</h2>
            </div>
            <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <?php while ($row = $research->fetch_assoc()): ?>
                    <?php echo renderBookCard($row, 'rsCover'); ?>
                <?php endwhile; ?>
            </div>
        </section>
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
                            <img id="modalBookImg" src="" alt="Cover" class="bookModal-cover img-fluid">
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
                            <div class="bookModal-btn-container">
                                <a id="modalBookRead" href="#" target="_blank" class="bookModal-btn-read d-none">Read Now</a>
                                <a id="modalBookAddToCollections" href="#" class="bookModal-btn-addto d-none">Add To Collections</a>
                                <a id="modalBookBookmark" href="#" class="bookModal-btn-bookmark d-none"><i class="bi bi-bookmark"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../frontend/js/user_librarydb.js"></script>
</body>
</html>