<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Library</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/user_librarydb.css">
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="offcanvas-md offcanvas-start d-flex flex-column text-light" tabindex="-1" id="sidebar"
        aria-labelledby="sidebarLabel" data-bs-scroll="true">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
            <div class="logoContain d-flex align-items-center px-3">
                <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="48">
            </div>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="userdb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/mybooks-default.png" alt="my books page" width="28">
                            </span>
                            <span class="sideText">My Books</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="user_librarydb.php" aria-current="page">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
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
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0" href="userdb.php">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>

            <div class="d-flex align-items-center gap-4">
                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                    <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                    <span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">4</span>
                </button>
                <a href="userdb.php?tab=settings" aria-label="Open Settings">
                    <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                </a>
                <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="rounded-circle" width="44"
                        height="44">
                </button>
            </div>
        </div>
    </header>

    <main>

        <!-- LIBRARY HEADER -->
        <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-4">
            <div class="d-flex align-items-center gap-3">
                <h1 class="fw-bold mb-0 p-1">Library</h1>
            </div>

            <div class="d-flex align-items-center gap-3">
                <form class="searchForm" role="search" onsubmit="return false;">
                    <div class="searchBox">
                        <i class="bi bi-search"></i>
                        <input type="search" class="form-control" placeholder="Search" aria-label="Search library">
                    </div>
                </form>

                <div class="dropdown">
                    <button class="btn filterBtn d-flex align-items-center gap-2 dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="#">All</a></li>
                        <li><a class="dropdown-item" href="#">Educational Books</a></li>
                        <li><a class="dropdown-item" href="#">Non-Educational Books</a></li>
                        <li><a class="dropdown-item" href="#">Research Articles</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- NEW ARRIVALS (7 Books) -->
        <section class="new-arrivals-section text-center py-5 ">
            <h2 class="fw-bold mb-4 display-4">NEW ARRIVALS</h2>

            <div class="newarrivals-wrapper mx-auto">
                <div class="newarrivals-card"></div>
                <div class="newarrivals-container" id="newarrivalsContainer">
                    <div class="newarrivals-item" data-title="Pride and Prejudice" data-author="Jane Austen"
                        data-desc="A classic novel of manners set in 19th-century England.">
                        <img src="../frontend/assets/temporary/temporaryimage_1.jpg" alt="Pride and Prejudice">
                    </div>
                    <div class="newarrivals-item" data-title="Tales of Norse Mythology" data-author="Helen A. Guerber"
                        data-desc="Stories of gods, giants, and heroes of ancient Scandinavian lore.">
                        <img src="../frontend/assets/temporary/temporaryimage_2.jpg" alt="Tales of Norse Mythology">
                    </div>
                    <div class="newarrivals-item active" data-title="Colorful Life" data-author="Olivia Wilson"
                        data-desc="A captivating modern novel exploring art, passion, and personal discovery.">
                        <img src="../frontend/assets/temporary/temporaryimage_3.jpg" alt="Colorful Life">
                    </div>
                    <div class="newarrivals-item" data-title="How to Be Eaten" data-author="Maria Adelmann"
                        data-desc="A modern reimagining of classic fairy tale characters in a support group.">
                        <img src="../frontend/assets/temporary/temporaryimage_4.jpg" alt="How to Be Eaten">
                    </div>
                    <div class="newarrivals-item" data-title="Dekada '70" data-author="Lualhati Bautista"
                        data-desc="A landmark Philippine historical novel focusing on a family during martial law.">
                        <img src="../frontend/assets/temporary/temporaryimage_5.jpg" alt="Dekada '70">
                    </div>
                    <div class="newarrivals-item" data-title="Dekada '70" data-author="Lualhati Bautista"
                        data-desc="A landmark Philippine historical novel focusing on a family during martial law.">
                        <img src="../frontend/assets/temporary/temporaryimage_5.jpg" alt="Dekada '70">
                    </div>
                    <div class="newarrivals-item" data-title="Dekada '70" data-author="Lualhati Bautista"
                        data-desc="A landmark Philippine historical novel focusing on a family during martial law.">
                        <img src="../frontend/assets/temporary/temporaryimage_5.jpg" alt="Dekada '70">
                    </div>
                </div>
            </div>

            <!-- Controls for the carousel books -->
            <div class="d-flex justify-content-center gap-3 mt-4 newarrivals-controls">
                <button id="ccPrevBtn" class="btn btn-outline-dark btn-sm rounded-circle px-3 py-2" type="button"><i
                        class="bi bi-chevron-left"></i></button>
                <button id="ccNextBtn" class="btn btn-outline-dark btn-sm rounded-circle px-3 py-2" type="button"><i
                        class="bi bi-chevron-right"></i></button>
            </div>
        </section>
        </section>

        <!-- Recommendations (5 books max) -->
        <section class="recommendations-section">
            <div class="recommendations-wrapper mx-auto">
                <div class="d-flex justify-content-between align-items-baseline mt-3 mb-3">
                    <h2 class="h3 fw-semibold mb-0 mt-3">Recommendations</h2>
                </div>

                <div
                    class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 justify-content-center text-center">
                    <div class="col">
                        <a href="#"
                            class="book-trigger d-block ratio recCover rounded-2 overflow-hidden shadow position-relative justify-content-center text-decoration-none"
                            data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                            data-allowed="0" data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_1.jpg"
                                class="w-100 h-100 object-fit-cover" alt="">
                        </a>
                        <a href="#" class="textArea text-decoration-none">
                            <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                            <p class="small mb-3 text-muted">Author Name</p>
                        </a>
                    </div>

                    <div class="col">
                        <a href="#"
                            class="book-trigger d-block ratio recCover rounded-2 overflow-hidden shadow position-relative justify-content-center text-decoration-none"
                            data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                            data-allowed="0" data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_1.jpg"
                                class="w-100 h-100 object-fit-cover" alt="">
                        </a>
                        <a href="#" class="textArea text-decoration-none">
                            <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                            <p class="small mb-3 text-muted">Author Name</p>
                        </a>
                    </div>

                    <div class="col">
                        <a href="#"
                            class="book-trigger d-block ratio recCover rounded-2 overflow-hidden shadow position-relative justify-content-center text-decoration-none"
                            data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                            data-allowed="0" data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_1.jpg"
                                class="w-100 h-100 object-fit-cover" alt="">
                        </a>
                        <a href="#" class="textArea text-decoration-none">
                            <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                            <p class="small mb-3 text-muted">Author Name</p>
                        </a>
                    </div>

                    <div class="col">
                        <a href="#"
                            class="book-trigger d-block ratio recCover rounded-2 overflow-hidden shadow position-relative justify-content-center text-decoration-none"
                            data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                            data-allowed="0" data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_1.jpg"
                                class="w-100 h-100 object-fit-cover" alt="">
                        </a>
                        <a href="#" class="textArea text-decoration-none">
                            <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                            <p class="small mb-3 text-muted">Author Name</p>
                        </a>
                    </div>

                    <div class="col">
                        <a href="#"
                            class="book-trigger d-block ratio recCover rounded-2 overflow-hidden shadow position-relative justify-content-center text-decoration-none"
                            data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                            data-allowed="0" data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_1.jpg"
                                class="w-100 h-100 object-fit-cover" alt="">
                        </a>
                        <a href="#" class="textArea text-decoration-none">
                            <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                            <p class="small mb-3 text-muted">Author Name</p>
                        </a>
                    </div>

                </div>
            </div>
        </section>

        <hr>

        <!-- EDUCATIONAL BOOKS -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Educational Books</h2>
                <a href="#" class="fs-5"><u>More &gt;</u></a>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_2.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>

                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_2.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>

            </div>
        </section>

        <hr>

        <!-- NON-EDUCATIONAL BOOKS -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Non-Educational Books</h2>
                <a href="#" class="fs-5"><u>More &gt;</u></a>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio noneduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_3.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>

                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio noneduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_3.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>

                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio noneduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_3.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>

            </div>
            </div>
        </section>

        <hr>

        <!-- RESEARCH ARTICLES -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Research Articles</h2>
                <a href="#" class="fs-5"><u>More &gt;</u></a>
            </div>

            <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio rsCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_4.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>

                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio rsCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_4.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>

                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio rsCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-allowed="0" data-hasfile="1">
                        <img src="../frontend/assets/temporary/temporaryimage_4.jpg"
                            class="w-100 h-100 object-fit-cover" alt="">
                    </a>
                    <a href="#" class="textArea text-decoration-none">
                        <h3 class="h6 fw-bold mt-3 mb-0">Book Title</h3>
                        <p class="small mb-3 text-muted">Author Name</p>
                    </a>
                </div>
            </div>
        </section>
    </main>

<!-- Temporary UI needs to change Book Details Modal -->
<div class="modal fade" id="bookDetailsModal" tabindex="-1" aria-labelledby="bookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalBookTitle">Book Title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-start">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center mb-3 mb-md-0">
                        <img id="modalBookImg" src="" alt="Cover" class="img-fluid rounded-3 shadow-sm">
                    </div>
                    <div class="col-md-8">
                        <p class="text-muted mb-1" id="modalBookAuthor">Author Name</p>
                        <hr class="my-1">
                        <p class="small text-secondary" id="modalBookDesc">Book description will appear here.</p>
                        
                        <input type="hidden" id="modalBookId" value="">
                        <div class="d-flex flex-wrap gap-2 justify-content-start mt-4">
                            <a id="modalBookRead" href="#" target="_blank" 
                                class="btn btn-sm rounded-pill px-4 text-white shadow-sm fw-bold d-none" 
                                style="background-color: var(--btn);">Read</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- FOOTER -->
    <footer class="text-light mt-5 py-5">
        <div class="container">
            <div class="row justify-content-around gy-4">
                <div class="col-md-5">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56"
                        class="footerLogo">
                    <ul class="list-unstyled lh-lg mt-3 ps-4">
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms.php">Terms of Use</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="privacy.php">Privacy Policy</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="#">More About BiblioDesk</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="#">BiblioDesk Developers</a></li>
                    </ul>
                </div>
                <div class="col-md-5">
                    <h2 class="h4 text-uppercase">Contact Us</h2>
                    <p class="lh-lg">bibliodesk@gmail.com<br>bibliodesk.help@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script src="../frontend/js/user_librarydb.js"></script>
</body>

</html>