<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk - My Books</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/mybooks.css">
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
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="mybooks.php" aria-current="page">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/mybooks-default.png" alt="my books page" width="28">
                            </span>
                            <span class="sideText">My Books</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="user_librarydb.php">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
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

        <!-- My Books Header -->
        <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-4">
            <div class="d-flex align-items-center gap-3">
                <h1 class="fw-bold mb-0 p-1">My Books</h1>
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
                        <li><a class="dropdown-item" href="#">Collections</a></li>
                        <li><a class="dropdown-item" href="#">Continue Reading</a></li>
                        <li><a class="dropdown-item" href="#">Bookmarks</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Continue reading -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Continue Reading</h2>
                <a href="#" class="fs-5"><u>More &gt;</u></a>
            </div>

            <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
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
                        class="book-trigger d-block ratio rsCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_3.jpg"
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

<!-- Collections Section -->
<section>
    <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
        <h2 class="h3 fw-semibold mb-0">My Collections</h2>
        <a href="#" class="fs-5"><u>More &gt;</u></a>
    </div>

    <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center" id="collectionsRow">
        <div class="col">
            <a href="#" class="collection-card ratio shadow position-relative d-block rounded-2 overflow-hidden"
                 style="background-color: #5bc0de;">
                </a>
            <div class="textArea">
                <h3 class="h6 fw-bold mt-3 mb-0">School Related</h3>
            </div>
        </div>
        
        <div class="col">
            <a href="#" class="collection-card ratio shadow position-relative d-block rounded-2 overflow-hidden"
                 style="background-color: #5bc0de;">
                </a>
            <div class="textArea">
                <h3 class="h6 fw-bold mt-3 mb-0">School Related</h3>
            </div>
        </div>
        
        <!-- This is for adding-->
        <div class="col">
            <a href="#" class="collection-card ratio rounded-2 overflow-hidden shadow position-relative d-block text-white"
                 style="background-color: #ce861a; cursor: pointer;"
                 data-bs-toggle="modal" data-bs-target="#createCollectionModal">
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
        <hr>

        <!-- Bookmarks -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Bookmarks</h2>
                <a href="#" class="fs-5"><u>More &gt;</u></a>
            </div>

            <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
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
                        class="book-trigger d-block ratio noneduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_5.jpg"
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
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_1.jpg"
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

        <!-- Favorites -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">Favorites</h2>
                <a href="#" class="fs-5"><u>More &gt;</u></a>
            </div>

            <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
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
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
                            <img src="../frontend/assets/temporary/temporaryimage_5.jpg"
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
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
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
<hr>
          
            <!-- History -->
        <section>
            <div class="d-flex justify-content-between align-items-baseline mt-5 mb-3">
                <h2 class="h3 fw-semibold mb-0">History</h2>
                <a href="#" class="fs-5"><u>More &gt;</u></a>
            </div>

            <div class="bookRow row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-4 text-center">
                <div class="col">
                    <a href="#"
                        class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
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
                        class="book-trigger d-block ratio noneduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
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
                        class="book-trigger d-block ratio rsCover rounded-2 overflow-hidden shadow position-relative text-decoration-none"
                        data-id="1" data-title="Book Title" data-author="Author Name" data-desc="Short description."
                        data-isbn="978-1234567890" data-genre="Fiction" data-publisher="Publisher Name" data-allowed="0"
                        data-hasfile="1">
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

    <!-- Book Modal -->
        <div class="modal fade bookModal" id="bookDetailsModal" tabindex="-1" aria-labelledby="modalBookTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <img id="modalBookImg" src="" alt="Cover" class="bookModal-cover img-fluid">
                            <p class="Bookisbn mt-3" id="modalBookIsbn">ISBN: 1234567890</p>
                        </div>
                        <div class="col-md-8">
                            <h5 class="modal-title display-5" id="modalBookTitle">Book Title</h5>
                            <p class="bookModal-author" id="modalBookAuthor">Author Name</p>
                            <hr>
                            <span class="bookGenre" id="modalBookGenre">New</span>
                            <p class="bookModal-desc" id="modalBookDesc">Book description will appear here.</p>
                            <p class="bookModal-publisher" id="modalBookPublisher">Publisher: <span
                                    id="modalBookPublisherName">Publisher Name</span> </p>


                            <input type="hidden" id="modalBookId" value="">
                            <div class="bookModal-btn-container">
                                <a id="modalBookRead" href="#" target="_blank" class="bookModal-btn-read d-none">Read</a>
                                <a id="modalBookAddToCollections" href="#" class="bookModal-btn-addto d-none">Add To Collections</a>
                                <a id="modalBookBookmark" href="#" class="bookModal-btn-bookmark d-none"> <i class="bi bi-bookmark"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- CREATE MODAL -->
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
                        <input type="text" class="form-control" id="collectionNameInput"
                               placeholder="School Related" maxlength="13" autocomplete="off" required>
                        <div class="invalid-feedback">Enter a collection name.</div>
                    </div>

                    <div class="mb-2">
                        <span class="form-label fw-semibold d-block">Choose Cover Color</span>

                        <div class="swatchGroup" id="colorSwatches" role="radiogroup" aria-label="Cover color">
                            <button type="button" class="swatch active" style="--swatch:#87CEEB" data-color="#00b7ff" aria-label="Sky blue"></button>
                            <button type="button" class="swatch" style="--swatch:#ff006a" data-color="#ff006a" aria-label="Magenta"></button>
                            <button type="button" class="swatch" style="--swatch:#22ff00" data-color="#22ff00" aria-label="Green"></button>
                            <button type="button" class="swatch" style="--swatch:#ff0000" data-color="#ff0000" aria-label="Red"></button>
                            <button type="button" class="swatch" style="--swatch:#6600ff" data-color="#6600ff" aria-label="Purple"></button>
                            <button type="button" class="swatch" style="--swatch:#ffcc00" data-color="#ffcc00" aria-label="Yellow"></button>
                            <button type="button" class="swatch" style="--swatch:#ff9100" data-color="#ff9100" aria-label="Brown"></button>
                            <button type="button" class="swatch" style="--swatch:#00ffe5" data-color="#00ffe5" aria-label="Teal"></button>

                            
                            <label class="swatch swatchCustom" title="Custom color">
                                <i class="bi bi-plus-lg"></i>
                                <input type="color" id="collectionCustomColor" class="d-none" value="#00b7ff">
                            </label>
                        </div>
                        <input type="hidden" id="collectionColorInput" value="#00b7ff">
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveCollectionBtn" class="btn btn-dark rounded-pill px-4">Create Collection</button>
                </div>
            </form>
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
    <script src="../frontend/js/mybooks.js"></script>
</body>

</html>