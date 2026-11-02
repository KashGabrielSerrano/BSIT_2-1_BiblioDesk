<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Contact Support</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="../frontend/css/contact.css">
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
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="userdb_overview.php">
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
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="user_librarydb.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/lib-default.png" alt="library page" width="28">
                                </span>
                                <span class="sideText">Library</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="contactsupport.php" aria-current="page">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center bg-light">
                                    <img src="../frontend/assets/icons/contact-default.png" alt="contact support page" width="28">
                                </span>
                                <span class="sideText">Contact Support</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="helpdesk.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/help-default.png" alt="helpdesk page" width="28">
                                </span>
                                <span class="sideText">Helpdesk</span>
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

        <main>
            <div class="container-fluid px-4 px-lg-5 py-5">
				<div class="row g-5 align-items-start">
                    <section class="col-12 col-lg-6 ps-lg-5" aria-labelledby="contactHeading">
                        <h1 id="contactHeading" class="display-5 fw-bold mb-4">Get in touch with us!</h1>
                        <p  class="fs-4 lh-lg mb-5">
                            Let us know about your suggestions or if there<br>
                            is any problem that you're encountering!
                        </p>
                        <p class="fs-4 mb-4">
                            Below is our contact information:
                        </p>
                         <ul class="list-unstyled d-flex flex-column gap-5 ms-lg-5 mt-5">
                            <li class="d-flex align-items-center gap-4">
                                <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <img src="../frontend/assets/icons/business-mail.png" alt="" width="52">
                                </span>
                                <div>
                                    <h2 class="h4 mb-1">Business Email:</h2>
                                    <a class="fs-5 text-dark text-decoration-none" href="mailto:bibliodesk@gmail.com">bibliodesk@gmail.com</a>
                                </div>
                            </li>
                            <li class="d-flex align-items-center gap-4">
                                <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0">
                                    <img src="../frontend/assets/icons/support-mail.png" alt="" width="52">
                                </span>
                                <div>
                                    <h2 class="h4 mb-1">Support Email:</h2>
                                    <a class="fs-5 text-dark text-decoration-none" href="mailto:bibliodesk.help@gmail.com">bibliodesk.help@gmail.com</a>
                                </div>
                            </li>
                        </ul>
                    </section>

                	<section class="col-12 col-lg-6" aria-labelledby="formHeading">
                        <div class="card contactCard text-light border-0 rounded-4 shadow">
                            <div class="card-body p-4 p-xl-5">
                                <h2 id="formHeading" class="text-center fw-normal mb-4">Send us a message here!</h2>
                                <p class="text-center small mb-3 px-xl-4">
                                    Tell us more about your <strong>business inquiries</strong> or the <strong>issues/</strong><br>
                                    <strong>problems</strong> that you're encountering in our website.
                                </p>
                                <p class="text-center small mb-4 px-xl-4">
                                    Rest assured that your email and data will not be published <br>
                                    anywhere.
                                </p>
                            </div>
                            <form class="formContain" action="" method="post">
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
                                    <select name="subject" id="subject" class="form-select contactInput">
                                        <option value="placeholder-value">Placeholder Text</option>
                                        <option value="placeholder-value">Placeholder Text</option>
                                        <option value="placeholder-value">Placeholder Text</option>
                                        <option value="others">Others</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message:</label>
                                    <textarea type="text" id="message" class="message form-control contactInput" placeholder="Send us your concern/message here..." required></textarea>
                                </div>
                                <div class="btnContain d-flex justify-content-end">
									<button type="submit" class=" fw-medium px-4 py-2 sendBtn">Send Message</button>
								</div>
							</form>
                        </div>
                    </section>
				</div>
            </div>
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