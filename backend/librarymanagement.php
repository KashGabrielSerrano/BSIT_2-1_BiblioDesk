<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = $_SESSION['role'] ?? 'librarian'; 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk - Library Management</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/librarymanagement.css">
</head>

<body>

    <!-- SIDEBAR -->
    <aside class="offcanvas-md offcanvas-start d-flex flex-column text-light" tabindex="-1" id="sidebar"
        aria-labelledby="sidebarLabel" data-bs-scroll="true">
        <div class="offcanvas-header py-3 px-3 d-md-none">
            <h5 class="offcanvas-title d-flex align-items-center m-0" id="sidebarLabel">
                <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk Logo" height="24">
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                data-bs-target="#sidebar" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column flex-grow-1 p-0">
            <div
                class="logoContain d-flex align-items-center justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
                <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="49">
            </div>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">

                    <?php if ($userRole === 'admin'): ?>
                    <!-- ================= ADMIN LINKS ================= -->
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admindb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="librarymanagement.php" aria-current="page">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library management" width="28">
                            </span>
                            <span class="sideText">Library Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="Admin_librarian_help.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="help center page" width="28">
                            </span>
                            <span class="sideText">Help Center</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="AdminAccManagedb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="accounts management page"
                                    width="28">
                            </span>
                            <span class="sideText">Accounts Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="Admin_reportlogs.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="report logs page"
                                    width="28">
                            </span>
                            <span class="sideText">Report Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="create_announcements.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="create announcements page"
                                    width="28">
                            </span>
                            <span class="sideText">Create Announcements</span>
                        </a>
                    </li>

                    <?php else: ?>
                    <!-- ================= LIBRARIAN LINKS ================= -->
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="librariandb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="librarymanagement.php" aria-current="page">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library management page"
                                    width="28">
                            </span>
                            <span class="sideText">Library Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="Admin_librarian_help.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="help center page" width="28">
                            </span>
                            <span class="sideText">Help Center</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="LibrarianAccManagedb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/help-default.png" alt="accounts management page"
                                    width="28">
                            </span>
                            <span class="sideText">Accounts Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="Librarian_reportlogs.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="report logs page"
                                    width="28">
                            </span>
                            <span class="sideText">Report Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="create_announcements.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/support-default.png" alt="create announcements page"
                                    width="28">
                            </span>
                            <span class="sideText">Create Announcements</span>
                        </a>
                    </li>
                    <?php endif; ?>

                </ul>
            </nav>

            <!-- Logout Pinned to Bottom -->
            <div class="mt-auto pb-4">
                <ul class="nav flex-column m-0 p-0">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#"
                            data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/logout-default.png" alt="logout" width="28">
                            </span>
                            <span class="sideText">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- NAVBAR -->
    <?php
$dashboardLink = 'userdb.php'; 

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        $dashboardLink = 'admindb.php';
    } elseif ($_SESSION['role'] === 'librarian') {
        $dashboardLink = 'librariandb.php';
    }
}
?>
    <header class="navbar navbar-dark shadow topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="userdb.php">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>

            <div class="d-flex align-items-center gap-4">
                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                    <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                    <span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">4</span>
                </button>
                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Settings">
                    <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                </button>
                <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile" class="rounded-circle" width="44"
                        height="44">
                </button>
            </div>
        </div>
    </header>

    <main>
        <div class="libraryHeader d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 mb-4">
            <div class="d-flex align-items-center gap-3">
                <h1 class="fw-bold mb-0 p-1">Library Management</h1>
            </div>
        </div>

    <!--CRUD-->
        <section class="my-4">
            <div class="panelCard card shadow border-secondary rounded-4 overflow-hidden">
                <div class="card-body p-4" style="background-color: var(--main-color);">
                    <h2 class="card-header text-white border-bottom border-secondary mb-4 bg-transparent px-0">Add New
                        Digital Book</h2>

                    <form id="addBookForm">
                        <input type="hidden" name="action" value="add_book">
                        <div class="row g-3">
                            <div class="form-group col-md-6 text-white fw-semibold">
                                <label class="form-label">Book Title:</label>
                                <input type="text" name="title" class="form-control" required placeholder="Title">
                            </div>

                            <div class="form-group col-md-6 text-white fw-semibold">
                                <label class="form-label">Author:</label>
                                <input type="text" name="author" class="form-control" required placeholder="Author">
                            </div>

                            <div class="form-group col-md-6 text-white fw-semibold">
                                <label class="form-label">ISBN:</label>
                                <input type="text" name="isbn" class="form-control" placeholder="978-3-16-148410-0">
                            </div>

                            <div class="form-group col-md-6 text-white">
                                <label class="form-label">Category:</label>
                                <input type="text" name="category" class="form-control" placeholder="Research Articles">
                            </div>

                            <div class="form-field col-md-4 text-white fw-semibold">
                                <label for="genreSelect" class="form-label">Genre:</label>
                                <select class="form-select" id="genreSelect" name="genre" required>
                                    <option value="" selected disabled>Choose One</option>
                                    <option value="Horror">Horror</option>
                                    <option value="Romance">Romance</option>
                                    <option value="Thriller">Thriller</option>
                                </select>
                            </div>
                            <div class="form-group col-12 text-white fw-semibold">
                                <label class="form-label">Description:</label>
                                <textarea name="description" class="form-control" rows="3" required
                                    placeholder="Enter book summary..."></textarea>
                            </div>

                            <div class="form-group col-md-6 text-white fw-semibold">
                                <label class="form-label">Cover Image URL:</label>
                                <input type="url" name="cover_drive_link" class="form-control" required
                                    placeholder="https://drive.google.com/file/d/.../view">
                            </div>

                            <div class="form-group col-md-6 text-white fw-semibold">
                                <label class="form-label">PDF Document URL:</label>
                                <input type="url" name="pdf_drive_link" class="form-control" required
                                    placeholder="https://drive.google.com/file/d/.../view">
                            </div>

                            <div class="form-group col-12 pt-3 d-flex justify-content-end">
                                <button type="submit"
                                    class="submitBookBtn px-4 py-2 fw-semibold rounded-3 text-white border-0"
                                    style="background-color: var(--btn);">Save &amp; Publish Book</button>
                            </div>
                    </form>
                </div>
            </div>
        </section>

        <!-- Catalog -->
        <section class="catalog-container my-4 mt-5">
            <div class="card shadow border-0 rounded-4 overflow-hidden mb-5"
                style="background-color: var(--main-color);">
                <div class="card-body p-4 p-md-4">
                    <div class="border-bottom border-secondary pb-3 mb-4">
                        <h2 class="text-white fw-bold mb-0">Catalog List</h2>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                        <form class="searchForm flex-grow-1" role="search" onsubmit="return false;"
                            style="min-width: 200px;">
                            <div class="searchBox w-100">
                                <i class="bi bi-search"></i>
                                <input type="search" class="form-control rounded-3 py-2" placeholder="Search"
                                    aria-label="Search library">
                            </div>
                        </form>

                        <div class="dropdown">
                            <button
                                class="btn filterBtn d-flex align-items-center gap-2 dropdown-toggle text-white rounded-3 py-2 px-3"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                style="background-color: var(--btn);">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><a class="dropdown-item" href="#">All</a></li>
                                <li><a class="dropdown-item" href="#">Oldest To Newest</a></li>
                                <li><a class="dropdown-item" href="#">Newest To Oldest</a></li>
                            </ul>
                        </div>

                        <button type="button"
                            class="btn btn-danger btn-sm d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-nowrap"
                            data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                            <i class="bi bi-trash"></i> Delete Selected
                        </button>
                    </div>

                    <div class="table-responsive table-container rounded-3 overflow-hidden shadow-sm">
                        <table class="table table-hover align-middle mb-0 text-center"
                            style="background-color: var(--fcolor-secondary);">
                            <thead>
                                <tr>
                                    <th class="border-end border-muted" style="width: 50px;">
                                        <input class="form-check-input" type="checkbox" id="selectAllCheckbox"
                                            aria-label="Select all rows">
                                    </th>
                                    <th class="border-end border-muted">Cover</th>
                                    <th class="border-end border-muted">Title</th>
                                    <th class="border-end border-muted">Author</th>
                                    <th class="border-end border-muted">Category</th>
                                    <th class="border-end border-muted">Digital Access</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Static Book Row -->
                                <tr>
                                    <td class="border-end border-muted">
                                        <input class="form-check-input rowCheckbox" type="checkbox"
                                            aria-label="Select row">
                                    </td>
                                    <td class="border-end border-muted">
                                        <img src="../frontend/assets/temporary/temporaryimage_1.jpg" alt="Cover"
                                            class="rounded" style="width: 40px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td class="border-end border-muted">Sample Book Title</td>
                                    <td class="border-end border-muted">Sample Author</td>
                                    <td class="border-end border-muted">BSIT</td>
                                    <td class="border-end border-muted">
                                        <span class="status-badge approved">PDF Linked</span>
                                    </td>
                                    <td>
                                        <button type="button" class="action-btn deny-btn btn btn-danger btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- SUCCESS MODAL -->
    <div class="modal fade" id="successBookModal" tabindex="-1" aria-labelledby="successBookModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-secondary shadow-lg rounded-4">
                <div class="modal-body text-center p-4 p-md-5">
                    <div class="mb-3 text-success fs-1">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-2" id="successBookModalLabel">Successfully Published!</h3>
                    <p class="text-light opacity-75 mb-4">Your digital book has been successfully added to the library
                        catalog.</p>
                    <button type="button" class="btn btn-success px-4 py-2 rounded-3 fw-semibold"
                        data-bs-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-secondary shadow-lg rounded-4"
                style="background-color: var(--main-bg-color);">
                <div class="modal-body text-center p-4 p-md-5">
                    <div class="mb-3 text-danger fs-1">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-2" id="confirmDeleteModalLabel">Are you sure?</h3>
                    <p class="mb-4">Do you want to delete this book from the catalog? This
                        action cannot be undone.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn px-4 py-2 fw-semibold rounded-3 text-white"
                            style="background-color: var(--muted);" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger px-4 py-2 rounded-3 fw-semibold"
                            id="confirmDeleteBtn">Yes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="successDeleteModal" tabindex="-1" aria-labelledby="successDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-secondary shadow-lg rounded-4"
                style="background-color: var(--main-bg-color);">
                <div class="modal-body text-center p-4 p-md-5">
                    <div class="mb-3 text-success fs-1">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h3 class="fw-bold mb-2" id="successDeleteModalLabel">Successfully Deleted!</h3>
                    <button type="button" class="btn px-4 py-2 rounded-3 fw-semibold mt-3 text-white"
                        style="background-color: var(--btn);" data-bs-dismiss="modal">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <!-- LOGOUT CONFIRMATION MODAL -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background-color: var(--main-bg-color);">
                <div class="modal-header text-white border-secondary" style="background-color: var(--main-color);">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body justify-content-center text-center m-5 fs-4">
                    Are you sure you want to log out?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="login.php" class="btn btn-danger">Log Out</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-light mt-5 py-5">
        <div class="container">
            <div class="row justify-content-around gy-4">
                <div class="col-12 col-md-5 ps-0">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56"
                        class="footerLogo">
                    <ul class="list-unstyled lh-lg mt-3 ps-4 mb-0">
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=terms">Terms of Use</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=privacy">Privacy Policy</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="more.php">More About BiblioDesk</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="aboutus.php">BiblioDesk Developers</a>
                        </li>
                    </ul>
                </div>
                <div class="col-12 col-md-5 ps-4 ps-md-0">
                    <h2 class="h4 text-uppercase">Contact Us</h2>
                    <p class="lh-lg mb-0">bibliodesk@gmail.com<br>bibliodesk.help@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../frontend/js/librarymanagementdb.js"></script>
</body>
</html>