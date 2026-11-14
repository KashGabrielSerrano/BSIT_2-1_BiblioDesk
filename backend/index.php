<?php
require_once __DIR__ . '/core/bootstrap.php';

$con = db();
$so  = so();
$so->checkRememberMe($con);
$user = current_user();
$dashboard_url = $user ? dashboard_for_role($user['role']) : null;

$catalog_stats = ['total' => 0, 'digital' => 0, 'physical' => 0];
try {
    $row = db_one(
        "SELECT
            COUNT(*) AS total,
            SUM(Is_digital = 'Yes') AS digital,
            SUM(Is_digital = 'No') AS physical
         FROM book_table"
    );
    if ($row) {
        $catalog_stats['total']    = (int)($row['total'] ?? 0);
        $catalog_stats['digital']  = (int)($row['digital'] ?? 0);
        $catalog_stats['physical'] = (int)($row['physical'] ?? 0);
    }
} catch (Throwable $e) {

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiblioDesk — Your Campus Library, Reimagined</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/root.css">
    <link rel="stylesheet" href="../frontend/css/index.css">
</head>
<body data-bs-spy="scroll" data-bs-target=".nav-pills-custom" data-bs-smooth-scroll="true" tabindex="0">

    <!-- ========================================================================== -->
    <!-- NAVBAR                                                                     -->
    <!-- ========================================================================== -->
    <header class="navbar navbar-dark shadow topbar navbar-expand-md sticky-top">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand m-0 d-flex align-items-center gap-2" href="#home">
                <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="28">
            </a>

            <button class="navbar-toggler border-0 shadow-none text-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between mt-3 mt-md-0" id="navbarNav">
                <nav class="navbar-nav mx-auto align-items-md-center gap-1 gap-lg-2 nav-pills-custom" aria-label="Primary navigation">
                    <a class="nav-link text-light px-3 py-1 rounded-pill" href="#home">Home</a>
                    <a class="nav-link text-light px-3 py-1 rounded-pill" href="#about">Features</a>
                    <a class="nav-link text-light px-3 py-1 rounded-pill" href="#how-it-works">How It Works</a>
                    <a class="nav-link text-light px-3 py-1 rounded-pill" href="#developers">Developers</a>
                    <a class="nav-link text-light px-3 py-1 rounded-pill" href="#contactus">Contact Us</a>
                </nav>

                <!-- Desktop Auth Actions -->
                <div class="d-none d-md-flex nav-pills-custom align-items-center gap-2">
                    <?php if ($dashboard_url): ?>
                        <a class="btn btn-light text-dark rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-2" href="<?= htmlspecialchars($dashboard_url, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-speedometer2" aria-hidden="true"></i> Open Dashboard
                        </a>
                    <?php else: ?>
                        <a class="nav-link text-light px-3 py-1 rounded-pill" href="login.php">Log In</a>
                        <a class="btn btn-light text-dark rounded-pill px-3 py-1 fw-semibold" href="registration.php">Sign Up</a>
                    <?php endif; ?>
                </div>

                <!-- Mobile Auth Actions -->
                <div class="d-flex gap-2 pb-3 mt-3 d-md-none w-100">
                    <?php if ($dashboard_url): ?>
                        <a class="btn btn-light text-dark rounded-pill py-2 text-center w-100 fw-semibold" href="<?= htmlspecialchars($dashboard_url, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i> Open Dashboard
                        </a>
                    <?php else: ?>
                        <a class="btn btn-outline-light rounded-pill py-2 text-center w-50" href="login.php">Log In</a>
                        <a class="btn btn-light text-dark rounded-pill py-2 text-center w-50 fw-semibold" href="registration.php">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- ========================================================================== -->
        <!-- HERO / HOME                                                                -->
        <!-- ========================================================================== -->
        <section id="home" class="container mt-4 mt-md-5 mb-5 rounded-4 px-4 px-md-5 py-5 shadow" style="background-color: var(--main-color);">
            <div class="row align-items-center justify-content-between gy-5">
                <div class="col-12 col-lg-6 text-center text-lg-start text-white">
                    <span class="badge rounded-pill bg-white bg-opacity-10 text-warning border border-light border-opacity-25 px-3 py-2 mb-3 fw-semibold">
                        <i class="bi bi-book-half me-1" aria-hidden="true"></i> Read &bull; Archive &bull; Advance &bull; Knowledge
                    </span>
                    <h1 class="display-4 fw-bold mb-3">Welcome to BiblioDesk</h1>
                    <p class="fw-normal lead text-white-50 mb-4">
                        Your modern gateway to the campus library. Explore the catalog, read select books online anytime, and reserve physical copies before you even step through the door.
                    </p>

                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start gap-3 mb-4">
                        <?php if ($dashboard_url): ?>
                            <a href="<?= htmlspecialchars($dashboard_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light text-dark btn-lg px-4 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="bi bi-arrow-right-circle-fill" aria-hidden="true"></i> Go to My Dashboard
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-light text-dark btn-lg px-4 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Start Exploring
                            </a>
                            <a href="registration.php" class="btn btn-outline-light btn-lg px-4 rounded-pill fw-medium d-inline-flex align-items-center gap-2">
                                <i class="bi bi-person-plus" aria-hidden="true"></i> Create Free Account
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Live Catalog Snapshot -->
                    <div class="row g-2 pt-3 border-top border-light border-opacity-25 text-center text-lg-start">
                        <div class="col-4">
                            <div class="fw-bold fs-4 text-white"><?= $catalog_stats['total'] > 0 ? number_format($catalog_stats['total']) : '24/7' ?></div>
                            <div class="small text-white-50"><?= $catalog_stats['total'] > 0 ? 'Catalog Titles' : 'Catalog Discovery' ?></div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold fs-4 text-warning"><?= $catalog_stats['digital'] > 0 ? number_format($catalog_stats['digital']) : 'Online' ?></div>
                            <div class="small text-white-50"><?= $catalog_stats['digital'] > 0 ? 'Digital Titles' : 'Instant Reading' ?></div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold fs-4 text-white"><?= $catalog_stats['physical'] > 0 ? number_format($catalog_stats['physical']) : '1-Click' ?></div>
                            <div class="small text-white-50"><?= $catalog_stats['physical'] > 0 ? 'Print Volumes' : 'Shelf Holds' ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6 text-center">
                    <div class="p-2 rounded-4 bg-white bg-opacity-10 border border-light border-opacity-10 shadow-sm">
                        <img src="../frontend/assets/Home.png" alt="BiblioDesk Library Platform Preview" class="img-fluid rounded-4">
                    </div>
                </div>
            </div>
        </section>

        <hr class="border-2 my-0 border-dark opacity-25">

        <!-- ========================================================================== -->
        <!-- ABOUT & PLATFORM CAPABILITIES                                              -->
        <!-- ========================================================================== -->
        <section id="about" class="container my-5 rounded-4 px-4 px-md-5 py-5 text-white shadow" style="background-color: var(--main-color);">
            <div class="row align-items-start justify-content-between gy-4 mb-5">
                <div class="col-lg-7">
                    <span class="text-uppercase small fw-semibold text-warning tracking-wide">
                        <i class="bi bi-stars me-1" aria-hidden="true"></i> A Smarter Way to Use Your Library
                    </span>
                    <h2 class="display-5 fw-bold mt-1 mb-3">About BiblioDesk</h2>
                    <p class="lead text-white-50 mb-0">
                        No more wandering aisles wondering if a title is in stock. BiblioDesk brings your campus collection online—so you can read select works from your screen, queue up popular books, and keep track of every loan effortlessly.
                    </p>
                </div>
                <div class="col-lg-5">
                    <div class="p-4 rounded-4 bg-black bg-opacity-25 border border-light border-opacity-10">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="rounded-3 bg-warning text-dark d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                <i class="bi bi-laptop fs-5" aria-hidden="true"></i>
                            </span>
                            <h3 class="h5 fw-bold mb-0">Read Online, Borrow On Campus</h3>
                        </div>
                        <p class="small text-white-50 mb-0">
                            Switch seamlessly between reading available digital books straight on the platform and picking up physical editions at the library desk.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Dual Role Feature Grid -->
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="card h-100 border-0 rounded-4 shadow-sm bg-light text-dark">
                        <div class="card-body p-4 p-xl-5">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="rounded-3 text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px; background-color: var(--main-color);">
                                    <i class="bi bi-mortarboard-fill fs-4 text-warning" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="h4 fw-bold mb-0">For Students &amp; Readers</h3>
                                    <p class="small text-muted mb-0">Built for late-night study sessions and casual reading</p>
                                </div>
                            </div>
                            <ul class="list-unstyled d-flex flex-column gap-3 mb-0 mt-4">
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-book-fill text-primary mt-1 flex-shrink-0" aria-hidden="true"></i>
                                    <span><strong>Read Select Books Online:</strong> Dive into digital study materials, references, and leisure reads right from your account—and pick up right where you left off.</span>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-journal-bookmark-fill text-success mt-1 flex-shrink-0" aria-hidden="true"></i>
                                    <span><strong>Instant Holds &amp; Waitlists:</strong> Check live shelf availability, reserve high-demand titles in advance, and never miss a return deadline.</span>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-collection-fill text-warning mt-1 flex-shrink-0" aria-hidden="true"></i>
                                    <span><strong>Personal Shelves &amp; Quick Check-In:</strong> Curate custom reading lists, bookmark favorites, and breeze through the counter with your digital library pass.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card h-100 border-0 rounded-4 shadow-sm bg-light text-dark">
                        <div class="card-body p-4 p-xl-5">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="rounded-3 text-white d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 46px; height: 46px; background-color: var(--main-color);">
                                    <i class="bi bi-kanban-fill fs-4 text-warning" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="h4 fw-bold mb-0">For Librarians &amp; Staff</h3>
                                    <p class="small text-muted mb-0">Less paperwork, faster counter service</p>
                                </div>
                            </div>
                            <ul class="list-unstyled d-flex flex-column gap-3 mb-0 mt-4">
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-journals text-primary mt-1 flex-shrink-0" aria-hidden="true"></i>
                                    <span><strong>Unified Catalog Management:</strong> Keep print inventories and online-readable collections organized with real-time copy tracking.</span>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-arrow-left-right text-success mt-1 flex-shrink-0" aria-hidden="true"></i>
                                    <span><strong>Effortless Circulation:</strong> Handle borrow requests, hold pickups, and returns in seconds without juggling spreadsheets.</span>
                                </li>
                                <li class="d-flex align-items-start gap-2">
                                    <i class="bi bi-megaphone-fill text-warning mt-1 flex-shrink-0" aria-hidden="true"></i>
                                    <span><strong>Campus Announcements &amp; Support:</strong> Share library updates, respond to student inquiries, and keep daily operations running smoothly.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================================================== -->
        <!-- HOW IT WORKS                                                               -->
        <!-- ========================================================================== -->
        <section id="how-it-works" class="container my-5 py-3">
            <div class="text-center mb-4">
                <span class="badge rounded-pill px-3 py-2 text-white mb-2" style="background-color: var(--main-color);">
                    <i class="bi bi-compass me-1" aria-hidden="true"></i> Quick Start
                </span>
                <h2 class="display-6 fw-bold mb-2">How BiblioDesk Works</h2>
                <p class="text-muted mx-auto mb-0" style="max-width: 600px;">
                    Join in minutes and unlock the entire campus collection from your phone or laptop.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 text-white d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--main-color);">
                                <i class="bi bi-person-plus-fill fs-5 text-warning" aria-hidden="true"></i>
                            </span>
                            <span class="fw-bold text-muted small">STEP 01</span>
                        </div>
                        <h3 class="h5 fw-bold mb-2">Set Up Your Student Pass</h3>
                        <p class="small text-muted mb-0">
                            Create your account with your student details to unlock personalized shelves, reservations, and online reading access.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 text-white d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--main-color);">
                                <i class="bi bi-search-heart fs-5 text-warning" aria-hidden="true"></i>
                            </span>
                            <span class="fw-bold text-muted small">STEP 02</span>
                        </div>
                        <h3 class="h5 fw-bold mb-2">Discover &amp; Read or Reserve</h3>
                        <p class="small text-muted mb-0">
                            Search by title, author, or topic—start reading available digital editions right away or place a hold on a print copy.
                        </p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="rounded-3 text-white d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--main-color);">
                                <i class="bi bi-bell-fill fs-5 text-warning" aria-hidden="true"></i>
                            </span>
                            <span class="fw-bold text-muted small">STEP 03</span>
                        </div>
                        <h3 class="h5 fw-bold mb-2">Pick Up &amp; Stay Notified</h3>
                        <p class="small text-muted mb-0">
                            Get notified the moment your book is ready at the desk and keep all your active loans organized in one dashboard.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <hr class="border-2 my-0 border-dark opacity-25">

        <!-- ========================================================================== -->
        <!-- DEVELOPERS SECTION                                                         -->
        <!-- ========================================================================== -->
        <section id="developers" class="container my-5 rounded-4 px-4 px-md-5 py-5 shadow" style="background-color: var(--main-color);">
            <div class="text-center mb-5 text-white">
                <span class="badge rounded-pill bg-white bg-opacity-10 text-warning border border-light border-opacity-25 px-3 py-2 mb-2 fw-semibold">
                    <i class="bi bi-code-slash me-1" aria-hidden="true"></i> Project Team
                </span>
                <h2 class="display-5 fw-bold mb-2">BiblioDesk Developers</h2>
                <p class="text-white-50 mb-0">The team behind BiblioDesk's interface, architecture, and library experience.</p>
            </div>

            <div id="developerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3500" data-bs-pause="hover">
                <div class="carousel-inner pb-4">
                    <div class="carousel-item active">
                        <div class="row justify-content-center g-4">
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Gabrielle Calderon" class="rounded-circle mx-auto mb-3 border border-2 border-light shadow-sm" width="80" height="80">
                                    <h3 class="h5 fw-bold mb-1">Gabrielle Calderon</h3>
                                    <span class="badge rounded-pill text-bg-light border mx-auto mb-3 px-3 py-1">Frontend Developer &amp; Designer</span>
                                    <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Claire Arcino" class="rounded-circle mx-auto mb-3 border border-2 border-light shadow-sm" width="80" height="80">
                                    <h3 class="h5 fw-bold mb-1">Claire Arcino</h3>
                                    <span class="badge rounded-pill text-bg-light border mx-auto mb-3 px-3 py-1">Frontend Developer</span>
                                    <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Lance Ramos" class="rounded-circle mx-auto mb-3 border border-2 border-light shadow-sm" width="80" height="80">
                                    <h3 class="h5 fw-bold mb-1">Lance Ramos</h3>
                                    <span class="badge rounded-pill text-bg-light border mx-auto mb-3 px-3 py-1">Frontend Developer</span>
                                    <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="row justify-content-center g-4">
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Lui Ortega" class="rounded-circle mx-auto mb-3 border border-2 border-light shadow-sm" width="80" height="80">
                                    <h3 class="h5 fw-bold mb-1">Lui Ortega</h3>
                                    <span class="badge rounded-pill text-bg-light border mx-auto mb-3 px-3 py-1">Frontend Developer</span>
                                    <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Kyle De Castro" class="rounded-circle mx-auto mb-3 border border-2 border-light shadow-sm" width="80" height="80">
                                    <h3 class="h5 fw-bold mb-1">Kyle De Castro</h3>
                                    <span class="badge rounded-pill text-bg-dark mx-auto mb-3 px-3 py-1">Backend Developer</span>
                                    <p class="text-secondary small mb-0">Handles core platform logic and system architecture.</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Kash Serrano" class="rounded-circle mx-auto mb-3 border border-2 border-light shadow-sm" width="80" height="80">
                                    <h3 class="h5 fw-bold mb-1">Kash Serrano</h3>
                                    <span class="badge rounded-pill text-bg-dark mx-auto mb-3 px-3 py-1">Backend Developer</span>
                                    <p class="text-secondary small mb-0">Handles core platform logic and system architecture.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="carousel-item">
                        <div class="row justify-content-center g-4">
                            <div class="col-12 col-md-4">
                                <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                    <img src="../frontend/assets/icons/pfp-icon.png" alt="John Monte" class="rounded-circle mx-auto mb-3 border border-2 border-light shadow-sm" width="80" height="80">
                                    <h3 class="h5 fw-bold mb-1">John Monte</h3>
                                    <span class="badge rounded-pill text-bg-dark mx-auto mb-3 px-3 py-1">Backend Developer</span>
                                    <p class="text-secondary small mb-0">Handles core platform logic and system architecture.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center align-items-center gap-3 mt-2">
                    <button class="btn btn-sm btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" type="button" data-bs-target="#developerCarousel" data-bs-slide="prev" aria-label="Previous developers">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>
                    <div class="carousel-indicators position-static m-0">
                        <button type="button" data-bs-target="#developerCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                        <button type="button" data-bs-target="#developerCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" data-bs-target="#developerCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                    </div>
                    <button class="btn btn-sm btn-outline-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" type="button" data-bs-target="#developerCarousel" data-bs-slide="next" aria-label="Next developers">
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>

        <hr class="border-2 my-0 border-dark opacity-25">

        <!-- ========================================================================== -->
        <!-- CONTACT US                                                                 -->
        <!-- ========================================================================== -->
        <section id="contactus" class="container py-5">
            <div class="row g-5 align-items-start">
                <div class="col-12 col-lg-6" aria-labelledby="contactHeading">
                    <span class="badge rounded-pill px-3 py-2 text-white mb-2" style="background-color: var(--main-color);">
                        <i class="bi bi-envelope-paper-fill me-1" aria-hidden="true"></i> Helpdesk &amp; Inquiries
                    </span>
                    <h2 id="contactHeading" class="display-5 fw-bold mb-3">Get in touch with us!</h2>
                    <p class="fs-5 text-muted mb-4">
                        Have a question about library access, account setup, or bringing BiblioDesk to your campus? Reach out directly to our librarians and support team.
                    </p>

                    <ul class="list-unstyled d-flex flex-column gap-3 mb-4">
                        <li class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-3 p-3">
                                <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0 text-white" style="width: 54px; height: 54px; background-color: var(--main-color);" aria-hidden="true">
                                    <i class="bi bi-briefcase-fill fs-4 text-warning"></i>
                                </span>
                                <div>
                                    <h3 class="h6 fw-bold text-uppercase text-muted mb-1">Business &amp; Institutional Email</h3>
                                    <a class="fs-5 fw-semibold text-dark text-decoration-none text-break stretched-link" href="mailto:bibliodesk@gmail.com">bibliodesk@gmail.com</a>
                                </div>
                            </div>
                        </li>
                        <li class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-3 p-3">
                                <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0 text-white" style="width: 54px; height: 54px; background-color: var(--main-color);" aria-hidden="true">
                                    <i class="bi bi-headset fs-4 text-warning"></i>
                                </span>
                                <div>
                                    <h3 class="h6 fw-bold text-uppercase text-muted mb-1">Student &amp; Technical Support</h3>
                                    <a class="fs-5 fw-semibold text-dark text-decoration-none text-break stretched-link" href="mailto:bibliodesk.portal@gmail.com">bibliodesk.portal@gmail.com</a>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle-fill me-1" aria-hidden="true"></i>
                        Already have an account? <a href="login.php" class="text-dark fw-semibold">Log in</a> to view replies from our library staff right inside your dashboard.
                    </p>
                </div>

                <div class="col-12 col-lg-6" aria-labelledby="formHeading">
                    <div class="card contactCard text-light border-0 rounded-4 shadow">
                        <div class="card-body p-4 p-xl-5">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="rounded-3 bg-white bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;" aria-hidden="true">
                                    <i class="bi bi-chat-dots-fill fs-4 text-warning"></i>
                                </span>
                                <div>
                                    <h2 id="formHeading" class="h4 fw-bold mb-0">Send us a message</h2>
                                    <p class="small text-white-50 mb-0">We'll route your note straight to our support desk</p>
                                </div>
                            </div>

                            <p class="small text-white-50 mb-4">
                                <i class="bi bi-check-circle-fill me-1 text-warning" aria-hidden="true"></i>
                                Your contact details stay private and are only used to follow up on your inquiry.
                            </p>

                            <div id="contactFeedback" class="alert d-none rounded-3 small mb-3" role="alert"></div>

                        <form class="formContain" action="helpdesk_controller.php" method="post" data-contact-form>
                            <input type="hidden" name="action" value="submit">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                            <!-- Honeypot anti-spam trap -->
                            <div class="d-none" aria-hidden="true">
                                <label for="website">Leave this empty</label>
                                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label fw-medium">Full Name</label>
                                <input type="text" id="name" name="name" maxlength="100" class="name form-control contactInput bg-white text-dark" placeholder="Juan Dela Cruz" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <input type="email" id="email" name="email" maxlength="150" class="email form-control contactInput bg-white text-dark" placeholder="juan.delacruz@example.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label fw-medium">Subject / Concern</label>
                                <select name="subject" id="subject" class="form-select contactInput bg-white text-dark" required>
                                    <option value="" selected disabled>Choose a topic or concern...</option>
                                    <option value="Account / Login">Account / Login</option>
                                    <option value="Borrowing & Reservations">Borrowing &amp; Reservations</option>
                                    <option value="Digital Books / Reading">Digital Books / Reading</option>
                                    <option value="Fines & Payments">Fines &amp; Payments</option>
                                    <option value="Suggestion">Suggestion</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label fw-medium">Message</label>
                                <textarea id="message" name="message" rows="4" minlength="10" maxlength="2000" class="message form-control contactInput bg-white text-dark" placeholder="Tell us how we can help (10–2,000 characters)..." required></textarea>
                            </div>

                            <div class="btnContain d-flex justify-content-end mt-4">
                                <button type="submit" class="fw-semibold px-4 py-2 sendBtn btn btn-light d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-send-fill" aria-hidden="true"></i> Send Message
                                </button>
                            </div>
                        </form> 
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ========================================================================== -->
    <!-- FOOTER                                                                     -->
    <!-- ========================================================================== -->
    <footer class="text-light py-5">
        <div class="container">
            <div class="row justify-content-between gy-4">
                <div class="col-12 col-md-5">
                    <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="48" class="footerLogo mb-3">
                    <p class="small text-white-50 mb-3">
                        Read, Archive, Advance, Knowledge. Your campus library for online reading and effortless borrowing.
                    </p>
                    <p class="small text-white-50 mb-0">&copy; <?= gmdate('Y') ?> BiblioDesk. Licensed under GNU GPLv3.</p>
                </div>

                <div class="col-6 col-md-3">
                    <h2 class="h6 text-uppercase fw-bold text-warning mb-3">Platform</h2>
                    <ul class="list-unstyled lh-lg mb-0">
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#about">Features &amp; Roles</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#how-it-works">How It Works</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="#developers">Developers</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="login.php">Portal Login</a></li>
                    </ul>
                </div>

                <div class="col-6 col-md-4">
                    <h2 class="h6 text-uppercase fw-bold text-warning mb-3">Legal &amp; Support</h2>
                    <ul class="list-unstyled lh-lg mb-3">
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="terms_privacy.php?view=terms">Terms of Service</a></li>
                        <li><a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="terms_privacy.php?view=privacy">Privacy Policy</a></li>
                    </ul>
                    <p class="small text-white-50 mb-0">
                        <i class="bi bi-envelope me-1" aria-hidden="true"></i> bibliodesk@gmail.com<br>
                        <i class="bi bi-headset me-1" aria-hidden="true"></i> bibliodesk.help@gmail.com
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="../frontend/js/contact.js"></script>
</body>
</html>