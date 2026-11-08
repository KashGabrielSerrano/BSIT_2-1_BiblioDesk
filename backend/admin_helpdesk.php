<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk - Admin Helpdesk </title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/admin_helpdesk.css">
</head>

<body>
    <!-- ========================================================================== -->
    <!-- SIDEBAR NAVIGATION                             -->
    <!-- ========================================================================== -->
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
            <div class="logoContain justify-content-center justify-content-md-start px-3 pt-2 d-none d-md-flex">
                <img src="../frontend/assets/BiblioDesk_IconW.png" alt="Bibliodesk Logo" width="49">
            </div>

            <nav aria-label="Main">
                <ul class="nav flex-column gap-3 pt-4">
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admindb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                            </span>
                            <span class="sideText">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admin_announcement.php" aria-current="page">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/announcement.png" alt="create announcements page"
                                    width="28">
                            </span>
                            <span class="sideText">Announcements</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="librarymanagement.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/lib-default.png" alt="library management page"
                                    width="28">
                            </span>
                            <span class="sideText">Library Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="AdminAccManagedb.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/business-mail.png" alt="accounts management page"
                                    width="28">
                            </span>
                            <span class="sideText">User Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="adminlog.php">
                            <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                <img src="../frontend/assets/icons/due.png" alt="report logs page" width="40">
                            </span>
                            <span class="sideText">Report Logs</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light"
                            href="admin_helpdesk.php">
                            <span
                                class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                <img src="../frontend/assets/icons/help-default.png" alt="helpdesk page" width="28">
                            </span>
                            <span class="sideText">Helpdesk</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="mt-auto pb-4">
                <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#"
                    data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                        <img src="../frontend/assets/icons/logout-default.png" alt="logout" width="28">
                    </span>
                    <span class="sideText">Logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- ========================================================================== -->
    <!-- TOP NAVBAR                                                              -->
    <!-- ========================================================================== -->
    <header class="navbar navbar-dark shadow topbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand m-0 d-none d-md-block ms-2" href="librarymanagement.php">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                </a>
            </div>
            <div class="d-flex align-items-center gap-4">
                <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                    <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                    <span
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">4</span>
                </button>
               <a href="settings.php?tab=settings" class="btn p-0 border-0 position-relative" aria-label="Open Settings">
                      <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                </a>
                <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile" class="rounded-circle" width="44"
                        height="44">
                </button>
            </div>
        </div>
    </header>

    <main>
        <div class="libraryHeader gap-3 mt-4 mb-5">
            <div>
                <h1 class="fw-bold mb-1">Help Desk</h1>
            </div>
        </div>

        <!-- Tutorial -->
        <section class="tutorial-section">
            <div class="tutorial-wrapper mx-auto" style="max-width: 800px;">

                <div class="d-flex align-items-center gap-3 mt-5 mb-5">
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                    <h2 class="fw-semibold mb-0">Tutorial</h2>
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                </div>


                <div class="text-center my-3 position-relative">
                    <div class="d-flex justify-content-end mx-auto mb-2">
                        <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                            data-bs-toggle="modal" data-bs-target="#tutorialModal">
                            <i class="bi bi-pencil-square me-1"></i> Edit content
                        </button>
                    </div>
                </div>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden text-white"
                    style="background-color: var(--main-color);">
                    <div class="ratio ratio-16x9 bg-black">
                        <video controls class="w-100 h-100">
                            <source src="../frontend/assets/temporary/3_sec.mp4" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>

                    <div class="card-body p-4 p-md-5 text-center">
                        <h3 class="h4 fw-bold mb-3">Getting Started Guide</h3>
                        <p class="mx-auto mb-0" style="max-width: 600px; line-height: 1.7;">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Non mollitia consequatur excepturi
                            et facilis ut qui mollitia similique odio. Accusamus fugiat placeat deleniti eos qui lorem
                            sunt.
                        </p>
                    </div>
                </div>
        </section>

        <!--FAQ-->
        <section class="faq-section">
            <div class="faq-wrapper mx-auto">
                <div class="d-flex align-items-center gap-3 mt-5 mb-5">
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                    <h2 class="fw-semibold mb-0">Frequently Asked Questions</h2>
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                </div>

                <!-- FAQ Categories -->
                <div class="faq-wrapper mx-auto mb-4">
                    <ul class="nav nav-pills gap-2 flex-nowrap overflow-x-auto pb-2 faq-pills-wrapper"
                        id="faq-pills-tab" role="tablist">
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link active rounded-pill px-4 py-2" id="pill-all-tab" data-filter="all"
                                type="button" role="tab">All</button>
                        </li>
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link rounded-pill px-4 py-2" id="pill-account-tab" data-filter="account"
                                type="button" role="tab">Account</button>
                        </li>
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link rounded-pill px-4 py-2" id="pill-library-tab" data-filter="library"
                                type="button" role="tab">Library</button>
                        </li>
                        <li class="nav-item flex-shrink-0" role="presentation">
                            <button class="nav-link rounded-pill px-4 py-2" id="pill-library-tab" data-filter="mybooks"
                                type="button" role="tab">My Books</button>
                        </li>
                    </ul>
                </div>

                <div class="faq-container">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 w-100">
                        <div class="searchForm flex-grow-1">
                            <div class="FAQsearchBox w-100">
                                <i class="bi bi-search"></i>
                                <input type="search" id="Search" class="form-control rounded-3 py-2"
                                    placeholder="Search Questions" aria-label="Search FAQ" autocomplete="off">
                            </div>
                        </div>

                        <div class="flex-shrink-0">
                            <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                                data-bs-toggle="modal" data-bs-target="#faqModal">
                                <i class="bi bi-pencil-square me-1"></i> Edit content
                            </button>
                        </div>

                    </div>


                    <!-- FAQ Cards -->
                    <div class="card card-body shadow-sm mb-3 cursor-pointer faq-card text-white"
                        data-category="account" style="background-color: var(--main-color);" data-bs-toggle="modal"
                        data-bs-target="#faqModal1">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-semibold mb-0">How do I reset my account password?</h5>
                            <span class="fs-5">&gt;</span>
                        </div>
                    </div>

                    <div class="card card-body shadow-sm mb-3 cursor-pointer faq-card text-white"
                        data-category="library" style="background-color: var(--main-color);" data-bs-toggle="modal"
                        data-bs-target="#faqModal2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-semibold mb-0">How do I update my profile details?</h5>
                            <span class="fs-5">&gt;</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>


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

        <!-- FAQ Modal 1 -->
        <div class="modal fade" id="faqModal1" tabindex="-1" aria-labelledby="faqModalLabel1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: var(--main-color);">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: var(--main-bg-color);">
                        <h5 class="modal-title fw-bold" id="faqModalLabel1">How do I reset my account password?</h5>

                        <p class="text-muted fw-semibold mb-2 faq-details">To reset your password, follow these simple
                            steps:</p>
                        <div class="faq-desc">
                            <p>To reset your password, follow these simple steps:</p>
                            <ol class="mb-0">
                                <li>Go to the login page and click on **"Forgot Password?"**.</li>
                                <li>Enter your registered email address.</li>
                                <li>Check your inbox for the password reset link and follow the instructions to create a
                                    new secure password.</li>
                            </ol>
                        </div>
                    </div>
                    <div class="modal-footer border-0 d-flex justify-content-between align-items-center"
                        style="background-color: var(--main-bg-color);">
                        <div class="flex-shrink-0">
                            <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                                data-bs-toggle="modal" data-bs-target="#editfaqModal">
                                <i class="bi bi-pencil-square me-1"></i> Edit content
                            </button>
                        </div>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tutorial edit Modal -->
        <div class="modal fade" id="tutorialModal" tabindex="-1" aria-labelledby="tutorialModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: var(--main-color);">
                        <h5 class="modal-title fw-bold" id="tutorialModalLabel">Edit Tutorial Content</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: var(--main-bg-color);">
                        <div class="mb-3">
                            <label for="tutorialInputVideo" class="form-label fw-semibold">Select Video File
                                (.mp4)</label>
                            <input type="file" class="form-control" id="tutorialInputVideo" accept="video/mp4">
                        </div>
                        <div class="mb-3">
                            <label for="tutorialInputTitle" class="form-label fw-semibold">Tutorial Title</label>
                            <input type="text" class="form-control" id="tutorialInputTitle" value="Tutorial">
                        </div>
                        <div class="mb-3">
                            <label for="tutorialInputDesc" class="form-label fw-semibold">Tutorial Description</label>
                            <textarea class="form-control" id="tutorialInputDesc" rows="4">...</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="background-color: var(--main-bg-color);">
                        <button type="button" class="btn rounded-pill px-4 border border-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn rounded-pill px-4 text-white" id="saveTutorialBtn"
                            style="background-color: var(--btn);" data-bs-dismiss="modal">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Add Modal -->
        <div class="modal fade" id="faqModal" tabindex="-1" aria-labelledby="faqModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: var(--main-color);">
                        <h5 class="modal-title fw-bold" id="faqModalLabel">Add FAQ</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: var(--main-bg-color);">
                        <div class="mb-3">
                            <label for="addInputTitle" class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" id="addInputTitle" value="FAQ">
                        </div>
                        <div class="mb-3">
                            <label for="addInputDetails" class="form-label fw-semibold">Additional Details</label>
                            <input type="text" class="form-control" id="addInputDetails" value="FAQ">
                        </div>
                        <div class="mb-3">
                            <label for="addInputDesc" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="addInputDesc" rows="4">...</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="background-color: var(--main-bg-color);">
                        <button type="button" class="btn rounded-pill px-4 border border-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-dark rounded-pill px-4" id="saveAddFaqBtn"
                            style="background-color: var(--btn);" data-bs-dismiss="modal">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Edit Modal -->
        <div class="modal fade" id="editfaqModal" tabindex="-1" aria-labelledby="editfaqModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white" style="background-color: var(--main-color);">
                        <h5 class="modal-title fw-bold" id="editfaqModalLabel">Edit FAQ</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="background-color: var(--main-bg-color);">
                        <div class="mb-3">
                            <label for="editInputTitle" class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" id="editInputTitle" value="editFAQ">
                        </div>
                        <div class="mb-3">
                            <label for="editInputDetails" class="form-label fw-semibold">Additional Details</label>
                            <input type="text" class="form-control" id="editInputDetails" value="editFAQ">
                        </div>
                        <div class="mb-3">
                            <label for="editInputDesc" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="editInputDesc" rows="4">...</textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="background-color: var(--main-bg-color);">
                        <button type="button" class="btn rounded-pill px-4 border border-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-dark rounded-pill px-4" id="saveEditFaqBtn"
                            style="background-color: var(--btn);" data-bs-dismiss="modal">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <!-- ========================================================================== -->
    <!-- FOOTER                                                                  -->
    <!-- ========================================================================== -->
    <footer class="text-light py-5" style="margin-top: 100px;">
        <div class="container">
            <div class="row justify-content-around gy-4">
                <div class="col-12 col-md-5 ps-0">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56"
                        class="footerLogo">
                    <ul class="list-unstyled lh-lg mt-3 ps-4 mb-0">
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=terms">Terms of Use</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="terms_privacy.php?view=privacy">Privacy Policy</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="more.php">More About BiblioDesk</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="aboutus.php">BiblioDesk Developers</a></li>
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
    <script src="../frontend/js/helpdesk.js"></script>
</body>

</html>