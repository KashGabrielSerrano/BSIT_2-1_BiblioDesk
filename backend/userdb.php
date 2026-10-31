<!--there's no final font style yet-->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="../frontend/css/userdb.css">
    </head>
    <body>
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
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="userdb.php" aria-current="page">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
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
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="#">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/lib-default.png" alt="library page" width="28">
                                </span>
                                <span class="sideText">Library</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

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
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">4</span>
                    </button>

                    <a href="userdb.php?tab=settings" aria-label="Open Settings">
                        <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                    </a>

                    <button type="button" class="btn p-0 border-0" aria-label="Open Account Information">
                        <img src="../frontend/assets/icons/pfp-icon.png" alt="" class="rounded-circle" width="44" height="44">
                    </button>
                </div>
            </div>
        </header>

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
        </main>

        <footer class="text-light mt-5 py-5">
            <div class="container">
                <div class="row justify-content-around gy-4">
                    <div class="col-md-5">
                        <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56" class="footerLogo">
                        <ul class="list-unstyled lh-lg mt-3 ps-4">
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="terms_privacy.php?view=terms">Terms of Use</a>
                            </li>
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="terms_privacy.php?view=privacy">Privacy Policy</a>
                            </li>
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#">More About BiblioDesk</a>
                            </li>
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#">BiblioDesk Developers</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-5">
                        <h2 class="h4 text-uppercase">Contact Us</h2>
                        <p class="lh-lg">bibliodesk@gmail.com<br>bibliodesk.help@gmail.com</p>
                    </div>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>