<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, inital-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../frontend/css/studentdb.css">
</head>
<body>  
    <aside>
        <div class="logo-contain">
            <img src="../frontend/assets/Bibliodesk_White.png" class="logo">
        </div>
        <nav>
            <div class="side-act-container">
                <a class="side-act" href="../backend/studentdb.php">
                    <img src="../frontend/assets/icons/overview-default.png" alt="Overview" class="sidebar-icon">
                    <span class="side-text">Overview</span>
                </a>

                <a class="side-act" href="#">
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
        <section class="user_preview">
            <div class="user_info">
                <p class="greetings">Welcome Back,</p>
                <h1>Victor Magtanggol</h1>
                <dd>BS Information Technology</dd>
                <dd>R2025XXXXXX</dd>
            </div>

            <div class="recent_activity">
                <h2 class="recent-section">Recent Activity</h2>
                <ul class="recent-list">
                    <li>Penalty paid.</li>
                    <li>4 pending books.</li>
                </ul>
            </div>
        </section>

        <section class="recommendation">
            <h2 class="reco-section">Recommendations</h2>
            <div class="books">
                <div class="sage-sec">
                    <a href="#">
                        <img src="../" alt="" class="book-cover">
                        <div class="book-info">
                            <p class="title">Book Title</p>
                            <p class="author">Book Author</p>
                        </div>
                    </a>
                </div>

                <div class="tan-sec">
                    <a href="#">
                        <img src="../" alt="" class="book-cover">
                        <div class="book-info">
                            <p class="title">Book Title</p>
                            <p class="author">Book Author</p>
                        </div>
                    </a>
                </div>

                <div class="sage-sec">
                    <a href="#">
                        <img src="../" alt="" class="book-cover">
                        <div class="book-info">
                            <p class="title">Book Title</p>
                            <p class="author">Book Author</p>
                        </div>
                    </a>
                </div>

                <div class="tan-sec">
                    <a href="#">
                        <img src="../" alt="" class="book-cover">
                        <div class="book-info">
                            <p class="title">Book Title</p>
                            <p class="author">Book Author</p>
                        </div>
                    </a>
                </div>

                <div class="sage-sec">
                    <a href="#">
                        <img src="../" alt="" class="book-cover">
                        <div class="book-info">
                            <p class="title">Book Title</p>
                            <p class="author">Book Author</p>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <section class="preview">
            <a class="stats-container" href="#">
                <img src="../frontend/assets/icons/library-hover.png" alt="total books icon" class="stats-icon-tbb">
                <div class="stats-text">
                    <span class="stats-num">0</span>
                    <span class="stats-label">Total Books Borrowed</span>
                </div>
            </a>

            <a class="stats-container" href="#">
                <img src="../frontend/assets/icons/due.png" alt="books due icon" class="stats-icon-bd">
                <div class="stats-text">
                    <span class="stats-num">0</span>
                    <span class="stats-label">Books Due This Week</span>
                </div>
            </a>

            <a class="stats-container" href="#">
                <img src="../frontend/assets/icons/pending-reserve.png" alt="pending reservations icon" class="stats-icon-pr">
                <div class="stats-text">
                    <span class="stats-num">0</span>
                    <span class="stats-label">Pending Reservations</span>
                </div>
            </a>
        </section>
    </main>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <script>
        const aside = document.querySelector('aside');
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar(){
            aside.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        hamburgerBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    </script>
</body>
</html>
