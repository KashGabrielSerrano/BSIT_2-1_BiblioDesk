<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliodesk</title>
    <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../frontend/css/index.css">
</head>

<body data-bs-spy="scroll" data-bs-target=".nav-pills-custom" data-bs-smooth-scroll="true" tabindex="0">
    <!-- ========================================================================== -->
    <!-- NAVBAR                                                                     -->
    <!-- ========================================================================== -->
   <header class="navbar navbar-dark shadow topbar navbar-expand-md">
    <div class="container-fluid px-3 px-md-4">
        <button class="navbar-toggler border-0 shadow-none text-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <a class="navbar-brand m-0" href="#home">
            <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
        </a>

        <div class="collapse navbar-collapse justify-content-between mt-3 mt-md-0" id="navbarNav">
            <nav class="navbar-nav mx-auto align-md-items-center gap-2 nav-pills-custom">
                <a class="nav-link text-light px-3 py-1 rounded-pill" href="#home">Home</a>
                <a class="nav-link text-light px-3 py-1 rounded-pill" href="#about">About</a>
                <a class="nav-link text-light px-3 py-1 rounded-pill" href="#developers">Developers</a>
                <a class="nav-link text-light px-3 py-1 rounded-pill" href="#contactus">Contact Us</a>
            </nav>

            <div class="nav-pills-custom d-flex align-items-center gap-2 mt-3 mt-md-0 pb-2 pb-md-0">
                <a class="nav-link text-light px-3 py-1 rounded-pill" href="login.php">Log In</a>
                <a class="nav-link text-light px-3 py-1 rounded-pill" href="registration.php">Sign Up</a>
            </div>

        </div>
    </div>
</header>

<main>
    <!-- ========================================================================== -->
    <!-- HOME                                                                       -->
    <!-- ========================================================================== -->
    <section id="home" class="container mt-5 mb-5 rounded-3 px-4 py-5" style="background-color: var(--main-color)">
        <div class="row align-items-center justify-content-between gy-5" >
            <div class="col-12 col-lg-6 text-center text-lg-start text-white">
                <h1 class="display-4 fw-bold mb-3">Welcome to BiblioDesk</h1>
                <p class="fw-normal lead mb-4">
                    Your institutional library management platform. Streamline book catalogs, manage announcements, and review user accounts effortlessly.
                </p>
                <a href="login.php" class="btn border border-white text-white btn-lg px-4 rounded-pill shadow-sm">
                    Get Started
                </a>
            </div>

            <div class="col-12 col-lg-6 text-center">
                <img src="../frontend/assets/Home.png" alt="BiblioDesk" class="img-fluid rounded-4 shadow-sm">
            </div>

        </div>
    </section>

    <hr class="flex-grow-1 border-2 my-0 border-dark">

    <!-- ========================================================================== -->
    <!-- ABOUT BIBLIODESK SECTION                                                   -->
    <!-- ========================================================================== -->
    <section id="about" class="container mt-5 mb-5 rounded-3 px-4 py-5 text-white" style="background-color: var(--main-color)">
        <div class="row align-items-center justify-content-between gy-5" >
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-3">About BiblioDesk</h2>
                    <p class="lead mb-4">
                        BiblioDesk is a modern, institution-focused library management system built to streamline administrative workflows, manage student and librarian accounts, and keep campus reading communities connected.
                    </p>
                    <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Real-time book cataloging and tracking</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Seamless user and role verification workflows</li>
                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Instant institutional announcements and updates</li>
                    </ul>
                </div>

                <div class="col-lg-6 text-center">
                    <div class="p-5 rounded-4 border border-dark bg-light border-3 text-dark">
                        <h3 class="h4 fw-bold mb-3">Institutional Efficiency</h3>
                        <p class="small mb-0">
                            Designed specifically for academic environments to minimize manual paperwork and enhance everyday library operations.
                        </p>
                    </div>
                </div>
            </div>
    </section>

    <hr class="flex-grow-1 border-2 my-0 border-dark">

    <!-- ========================================================================== -->
    <!-- DEVELOPERS SECTION                                                         -->
    <!-- ========================================================================== -->
    <section id="developers" class="container mt-5 mb-5 rounded-3 px-4 py-5" style="background-color: var(--main-color);">
    <div class="container">
        <div class="text-center mb-5 text-white">
            <h2 class="display-5 fw-bold mb-3">BiblioDesk Developers</h2>
        </div>

        <div id="developerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000" data-bs-pause="hover">
            <div class="carousel-inner">

                <div class="carousel-item active">
                    <div class="row justify-content-center g-4">
                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                <img src="../frontend/assets/icons/pfp-icon.png" alt="Developer" class="rounded-circle mx-auto mb-3" width="80" height="80">
                                <h4 class="h5 fw-bold mb-1">Gabrielle Calderon</h4>
                                <p class="text-muted small mb-3">Frontend Developer &amp; Designer</p>
                                <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                <img src="../frontend/assets/icons/pfp-icon.png" alt="Developer" class="rounded-circle mx-auto mb-3" width="80" height="80">
                                <h4 class="h5 fw-bold mb-1">Claire Arcino</h4>
                                <p class="text-muted small mb-3">Frontend Developer</p>
                                <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                <img src="../frontend/assets/icons/pfp-icon.png" alt="Developer" class="rounded-circle mx-auto mb-3" width="80" height="80">
                                <h4 class="h5 fw-bold mb-1">Lance Ramos</h4>
                                <p class="text-muted small mb-3">Frontend Developer</p>
                                <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                        <div class="row justify-content-center g-4">
                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                <img src="../frontend/assets/icons/pfp-icon.png" alt="Developer" class="rounded-circle mx-auto mb-3" width="80" height="80">
                                <h4 class="h5 fw-bold mb-1">Lui Ortega</h4>
                                <p class="text-muted small mb-3">Frontend Developer</p>
                                <p class="text-secondary small mb-0">Responsible for user interface design and responsive layouts.</p>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                <img src="../frontend/assets/icons/pfp-icon.png" alt="Developer" class="rounded-circle mx-auto mb-3" width="80" height="80">
                                <h4 class="h5 fw-bold mb-1">Kyle De Castro</h4>
                                <p class="text-muted small mb-3">Backend Developer</p>
                                <p class="text-secondary small mb-0">Handles session management and authentication modules.</p>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                <img src="../frontend/assets/icons/pfp-icon.png" alt="Developer" class="rounded-circle mx-auto mb-3" width="80" height="80">
                                <h4 class="h5 fw-bold mb-1">Kash Serrano</h4>
                                <p class="text-muted small mb-3">Backend Developer</p>
                                <p class="text-secondary small mb-0">Handles session management and authentication modules.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <div class="row justify-content-center g-4">
                        <div class="col-12 col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 text-center p-4 h-100 bg-white">
                                <img src="../frontend/assets/icons/pfp-icon.png" alt="Developer" class="rounded-circle mx-auto mb-3" width="80" height="80">
                                <h4 class="h5 fw-bold mb-1">John Monte</h4>
                                <p class="text-muted small mb-3">Backend Developer</p>
                                <p class="text-secondary small mb-0">Handles session management and authentication modules.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#developerCarousel" data-bs-slide="prev" style="width: 5%;">
                <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#developerCarousel" data-bs-slide="next" style="width: 5%;">
                <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>

    <hr class="flex-grow-1 border-2 my-0 border-dark">

    <!-- ========================================================================== -->
    <!-- CONTACT US                                                                 -->
    <!-- ========================================================================== -->
    <section id="contactus" class="container py-5">
        <div class="row g-5 align-items-start">
            <div class="col-12 col-lg-6" aria-labelledby="contactHeading">
                <h2 id="contactHeading" class="display-5 fw-bold mb-4">Get in touch with us!</h2>
                <p class="fs-4 lh-lg mb-4">
                    Let us know about your suggestions or if there is any problem that you're encountering!
                </p>
                <p class="fs-4 mb-4">
                    Below is our contact information:
                </p>
                <ul class="list-unstyled d-flex flex-column gap-4 mt-4">
                    <li class="d-flex align-items-center gap-3">
                        <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0">
                            <img src="../frontend/assets/icons/business-mail.png" alt="" width="52">
                        </span>
                        <div>
                            <h3 class="h4 mb-1">Business Email:</h3>
                            <a class="fs-5 text-dark text-decoration-none text-break" href="mailto:bibliodesk@gmail.com">bibliodesk@gmail.com</a>
                        </div>
                    </li>
                    <li class="d-flex align-items-center gap-3">
                        <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0">
                            <img src="../frontend/assets/icons/support-mail.png" alt="" width="52">
                        </span>
                        <div>
                            <h3 class="h4 mb-1">Support Email:</h3>
                            <a class="fs-5 text-dark text-decoration-none text-break" href="mailto:bibliodesk.help@gmail.com">bibliodesk.help@gmail.com</a>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="col-12 col-lg-6" aria-labelledby="formHeading">
                <div class="card contactCard text-light border-0 rounded-4 shadow">
                    <div class="card-body p-4 p-xl-5">
                        <h2 id="formHeading" class="text-center fw-normal mb-4">Send us a message here!</h2>
                        <p class="text-center small mb-3 px-xl-4">
                            Tell us more about your <strong>business inquiries</strong> or the <strong>issues/problems</strong> that you're encountering in our website.
                        </p>
                        <p class="text-center small mb-4 px-xl-4">
                            Rest assured that your email and data will not be published anywhere.
                        </p>

                        <form class="formContain mt-4" action="" method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name:</label>
                                <input type="text" id="name" class="name form-control contactInput" placeholder="Victor Magtanggol" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email:</label>
                                <input type="email" id="email" class="email form-control contactInput" placeholder="insert.your.email@gmail.com" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject/Concern:</label>
                                <select name="subject" id="subject" class="form-select contactInput" required>
                                    <option value="placeholder-value">Placeholder Text</option>
                                    <option value="placeholder-value">Placeholder Text</option>
                                    <option value="placeholder-value">Placeholder Text</option>
                                    <option value="others">Others</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message:</label>
                                <textarea id="message" rows="4" class="message form-control contactInput" placeholder="Send us your concern/message here..." required></textarea>
                            </div>
                            <div class="btnContain d-flex justify-content-end mt-4">
                                <button type="submit" class="fw-medium px-4 py-2 sendBtn btn btn-light">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

    <!-- Footer -->
    <footer class="text-light py-5">
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
                                href="#about">More About BiblioDesk</a>
                        </li>
                        <li>
                            <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover"
                                href="#developers">BiblioDesk Developers</a>
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
</body>

</html>