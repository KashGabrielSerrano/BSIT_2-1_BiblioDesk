<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library</title>
    <link rel="stylesheet" href="../frontend/css/librarydb.css">
    <script src="../frontend/js/sidebar_Btn.js" defer></script>
    <script src="../frontend/js/carousel.js" defer></script>
</head>
<body>  
    <aside>
        <div class="logo-contain">
            <img src="../frontend/assets/Bibliodesk_White.png" alt="BiblioDesk Logo" class="logo">
        </div>
        <nav>
            <div class="side-act-container">
                <a class="side-act" href="../backend/studentdb.php">
                    <img src="../frontend/assets/icons/overview-default.png" alt="Overview" class="sidebar-icon">
                    <span class="side-text">Overview</span>
                </a>
                <a class="side-act active" href="../backend/librarydb.php">
                    <img src="../frontend/assets/icons/library-default.png" alt="Library" class="sidebar-icon">
                    <span class="side-text">Library</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/borrowed-default.png" alt="Borrowed" class="sidebar-icon">
                    <span class="side-text">Borrowed</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/reservations-default.png" alt="Reservations" class="sidebar-icon">
                    <span class="side-text">Reservations</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/penalties-default.png" alt="Penalties" class="sidebar-icon">
                    <span class="side-text">Penalties</span>
                </a>
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/fees-default.png" alt="Fees & Account" class="sidebar-icon">
                    <span class="side-text">Fees & Account</span>
                </a>
            </div>
            
            <div class="log-out-container">
                <a class="side-act" href="#">
                    <img src="../frontend/assets/icons/logout.png" alt="log out" class="sidebar-icon">
                    <span class="side-text">Log Out</span>
                </a>
            </div>
        </nav>
    </aside>

    <header class="topbar">
        <button type="button" class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="logo-name-container">
            <img src="../frontend/assets/BiblioDesk_White_name.png" alt="BiblioDesk" class="logo-name">
        </div>
            
        <div class="top-actions">
            <button type="button" class="notification-bell" aria-label="Open Notification Area">
                <img src="../frontend/assets/icons/notifbell.png" alt="notification bell icon" class="button-icon">
                <span class="NotifNum">4</span>
            </button>

            <button type="button" class="settings" aria-label="Open Settings">
                <img src="../frontend/assets/icons/settings.png" alt="settings icon" class="button-icon">
            </button>

            <button type="button" class="accountpfp" aria-label="Open Account Information">
                <img src="../frontend/assets/icons/pfp-icon.png" alt="account profile picture" class="button-icon">
            </button>
        </div>
    </header>

    <main>
        <div class="library-header-row">
            <div class="title-group">
                <img src="../frontend/assets/icons/library-default.png" alt="Library Icon" class="library-icon">
                <h1 class="library-title">Library</h1>
            </div>

            <div class="search-filter-group">
                <form class="search-form" action="#" method="get">
                    <div class="search-input-wrapper">
                        <svg class="search-icon-svg" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="search" placeholder="Search" class="search-input">
                    </div>
                </form>

                <button type="button" class="filter-btn">
                    <svg class="filter-icon-svg" viewBox="0 0 24 24">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    Filter
                </button>
            </div>
        </div>

<!--Recommendations-->
        <section class="book-panel">
            <div class="books-header">
                <h2 class="books-section">Recommendations</h2>
                <a class="Expand" href="More.php">More &gt;</a>
            </div>
            
            <div class="carousel-wrapper">
                <button onclick="toggleScroll(this)" class="floating-arrow-btn" aria-label="Scroll Books">&gt;</button>
                <div class="books">
                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tan-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tan-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<!--BSIT-->
        <section class="book-panel">
            <div class="books-header">
                <h2 class="books-section">BSIT</h2>
                <a class="Expand" href="More.php">More &gt;</a>
            </div>
            
            <div class="carousel-wrapper">
                <button onclick="toggleScroll(this)" class="floating-arrow-btn" aria-label="Scroll Books">&gt;</button>
                <div class="books">
                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tan-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tan-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                    <a class="readonline-pill" href="ReadOnline.php">Read Online</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

<!--Research-->
        <section class="book-panel">
            <div class="books-header">
                <h2 class="books-section">Research</h2>
                <a class="Expand" href="More.php">More &gt;</a>
            </div>
            
            <div class="carousel-wrapper">
                <button onclick="toggleScroll(this)" class="floating-arrow-btn" aria-label="Scroll Books">&gt;</button>
                <div class="books">
                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tan-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tan-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sage-sec">
                        <div class="card-content">
                            <img src="../frontend/assets/icons/library-default.png" alt="Book Cover" class="book-cover">
                            <div class="book-info">
                                <p class="title">Book Title</p>
                                <p class="author">Book Author</p>
                                <div class="card-actions">
                                    <a class="reservation-pill" href="Reservation.php">Reservation</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
</body>
</html>