<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account</title>
        <link rel="icon" type="image/png" href="../frontend/assets/BiblioDesk_Icon.png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <link rel="stylesheet" href="../frontend/css/accountpage.css">
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
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="admindb.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/ow-default.png" alt="overview page" width="28">
                                </span>
                                <span class="sideText">Overview</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="admin_announcement.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/announcement.png" alt="my books page" width="28">
                                </span>
                                <span class="sideText">Announcements</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="librarymanagement.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/lib-default.png" alt="library page" width="28">
                                </span>
                                <span class="sideText">Library Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="AdminAccManagedb.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/contact-default.png" alt="contact support page" width="28">
                                </span>
                                <span class="sideText">Account Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link sideAct d-flex align-items-center gap-3 px-3 py-0 text-light" href="adminlog.php">
                                <span class="sideIcon rounded-circle d-flex align-items-center justify-content-center">
                                    <img src="../frontend/assets/icons/help-default.png" alt="helpdesk page" width="28">
                                </span>
                                <span class="sideText">Report Logs</span>
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

        <header class="navbar navbar-dark shadow topbar">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-2">
                    <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Toggle menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand m-0 d-none d-md-block ms-2" href="userdb.php">
                        <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" height="26">
                    </a>
                </div>

                <div class="d-flex align-items-center gap-4">
                    <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Notification Area">
                        <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" width="34">
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">4</span>
                    </button>
                    <button type="button" class="btn p-0 border-0 position-relative" aria-label="Open Settings">
                        <img src="../frontend/assets/icons/settings.png" alt="settings icon" width="34">
                    </button>
                    <a type="button" class="btn p-0 border-0" aria-label="Open Account Information" href="admindb_accountpage.php">
                        <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile" class="rounded-circle" width="44" height="44">
                    </a>
                </div>
            </div>
        </header>

        <main>
            <div class="container-fluid px-0 py-5">
                <header class="headerContain d-flex align-items-center gap-4 gap-md-5 px-lg-5">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile picture" class="accountAvatar rounded-circle object-fit-cover flex-shrink-0">

                    <div class="headerText flex-grow-1">
                        <h1 id="displayName" class="fw-bold mb-1">Sofia Baltazar-Winslow I</h1>
                        <p class="fs-3 mb-0">**********</p>
                    </div>

                    <button type="button" id="editBtn" class="btn border-0 p-0 align-self-start flex-shrink-0" aria-label="Edit profile">
                        <img src="../frontend/assets/icons/edit.png" alt="../frontend/assets/icons/edit.png">
                    </button>
                </header>

                <hr class="my-5 mx-lg-5 border-dark opacity-100">

                <div id="saveMsg" class="alert alert-success d-none mx-lg-5" role="alert">
                    Changes saved.
                </div>

                <form id="profileForm" action="" method="post" class="px-lg-5">
                    <fieldset id="profileFields" disabled>
                        <section aria-labelledby="personalHeading">
                            <h2 id="personalHeading" class="visually-hidden">Personal information</h2>

                            <div class="row g-4 px-lg-3">
                                <div class="col-12 col-md-6">
                                    <label for="firstName" class="form-label fs-5 mb-2">First Name</label>
                                    <input type="text" id="firstName" name="first_name" class="form-control accountInput" value="Sofia" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="lastName" class="form-label fs-5 mb-2">Last Name</label>
                                    <input type="text" id="lastName" name="last_name" class="form-control accountInput" value="Baltazar-Winslow" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="middleName" class="form-label fs-5 mb-2">Middle Name (Optional)</label>
                                    <input type="text" id="middleName" name="middle_name" class="form-control accountInput" placeholder="Middle Name">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="suffix" class="form-label fs-5 mb-2">Suffix (Optional)</label>
                                    <input type="text" id="suffix" name="suffix" class="form-control accountInput" placeholder="I">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="studentId" class="form-label fs-5 mb-2">ID</label>
                                    <input type="text" id="studentId" name="student_id" class="form-control accountInput" value="XXXXXXXXXXX" readonly>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="birthday" class="form-label fs-5 mb-2">Birthday (mm/dd/yyyy)</label>
                                    <input type="date" id="birthday" name="birthday" class="form-control accountInput" value="2018-05-01">
                                </div>
                            </div>
                        </section>

                        <section aria-labelledby="accountHeading" class="mt-5 pt-5">
                            <h2 id="accountHeading" class="visually-hidden">Account information</h2>

                            <div class="row g-4 px-lg-3">
                                <div class="col-12 col-md-6 d-flex flex-column gap-4">
                                    <div>
                                        <label for="schoolEmail" class="form-label fs-5 mb-2">Email</label>
                                        <input type="email" id="schoolEmail" name="school_email" class="form-control accountInput" value="your.email@gmail.com" required>
                                    </div>

                                    <div>
                                        <label for="password" class="form-label fs-5 mb-2">Password</label>
                                        <div class="position-relative">
                                            <input type="password" id="password" name="password" class="form-control accountInput pe-5" value="***************" autocomplete="new-password">
                                            <button type="button" id="togglePassword" class="position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 bg-transparent" aria-label="Show password">
                                                <img src="../frontend/assets/icons/eye-off.png" alt="" width="24">
                                            </button>
                                        </div>
                                    </div>

                                    <div id="confirmField" hidden>
                                        <label for="confirmPassword" class="form-label fs-5 mb-2">Confirm Password</label>
                                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control accountInput" placeholder="Confirm password" autocomplete="new-password">
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="altEmail" class="form-label fs-5 mb-2">Alternative Email</label>
                                    <input type="email" id="altEmail" name="alt_email" class="form-control accountInput" placeholder="+ add your email here">
                                </div>
                            </div>
                        </section>
                    </fieldset>

                    <div id="formActions" hidden>
                        <div class="d-flex justify-content-end gap-3 mt-5 px-lg-3">
                            <button type="button" id="cancelBtn" class="btn accountCancel px-4 py-2">Cancel</button>
                            <button type="submit" id="saveBtn" class="btn btn-primary px-4 py-2">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </main>

        <footer class="text-light py-5">
            <div class="container">
                <div class="row justify-content-around gy-4">
                    <div class="col-12 col-md-5 ps-0">
                        <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk logo" height="56" class="footerLogo">
                        <ul class="list-unstyled lh-lg mt-3 ps-4 mb-0">
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="terms_privacy.php?view=terms">Terms of Use</a>
                            </li>
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="terms_privacy.php?view=privacy">Privacy Policy</a>
                            </li>
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="more.php">More About BiblioDesk</a>
                            </li>
                            <li>
                                <a class="link-light link-underline-opacity-0 link-underline-opacity-100-hover" href="aboutus.php">BiblioDesk Developers</a>
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
        <script src="../frontend/js/accountpage.js"></script>
    </body>
</html>