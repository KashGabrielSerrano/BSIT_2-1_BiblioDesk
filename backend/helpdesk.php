<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';

$user     = require_login_page();
$menu     = menu_for_role($user['role']);
$is_staff = $menu === 'staff';

render_head($is_staff ? 'BiblioDesk - Admin Helpdesk' : 'BiblioDesk - Helpdesk', [$is_staff ? 'admin_helpdesk.css' : 'helpdesk.css']);
?>
    <!-- ========================================================================== -->
    <!-- SIDEBAR NAVIGATION                             -->
    <!-- ========================================================================== -->
    <?php render_sidebar($menu, 'helpdesk'); ?>

    <!-- ========================================================================== -->
    <!-- TOP NAVBAR                                                              -->
    <!-- ========================================================================== -->
    <?php render_topbar($menu); ?>

    <main>
        <div class="libraryHeader gap-3 mt-4 mb-5">
            <div>
                <h1 class="fw-bold mb-1">Help Desk</h1>
            </div>
        </div>

    <?php if ($is_staff): ?>
            <!-- Support tickets inbox (staff only) - frontend/js/helpdesk_tickets.js -->
            <section id="tickets" class="mx-auto mb-5 px-3 px-md-0" style="max-width: 1000px;" aria-labelledby="ticketsHeading">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="hd-section-icon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                            <i class="bi bi-headset fs-4"></i>
                        </span>
                        <div>
                            <h2 id="ticketsHeading" class="h3 fw-bold mb-0">Support Tickets</h2>
                            <p class="text-muted small mb-0">Review user concerns, view conversation threads, and send replies.</p>
                        </div>
                    </div>
                    <div class="hd-filter-pills d-flex flex-wrap gap-2" role="group" aria-label="Filter tickets by status" id="ticketFilters">
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn active" data-status="Open">
                            Open <span class="badge rounded-pill bg-warning text-dark ms-1" data-count="Open">0</span>
                        </button>
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn" data-status="Answered">
                            Answered <span class="badge rounded-pill bg-success ms-1" data-count="Answered">0</span>
                        </button>
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn" data-status="Closed">
                            Closed <span class="badge rounded-pill bg-secondary ms-1" data-count="Closed">0</span>
                        </button>
                        <button type="button" class="btn rounded-pill px-3 py-1 fw-semibold hd-filter-btn" data-status="">
                            All
                        </button>
                    </div>
                </div>
                <div id="ticketList" class="d-flex flex-column gap-4">
                    <p class="text-muted mb-0">Loading tickets&hellip;</p>
                </div>
            </section>

            <!-- Reply modal -->
            <div class="modal fade" id="ticketReplyModal" tabindex="-1" aria-labelledby="ticketReplyLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header text-white px-4 py-3 border-0" style="background-color: var(--main-color);">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-reply-fill fs-5 text-warning" aria-hidden="true"></i>
                                <h5 class="modal-title fw-bold mb-0" id="ticketReplyLabel">Reply to ticket</h5>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="ticketReplyForm" novalidate>
                            <div class="modal-body p-4" style="background-color: var(--main-bg-color);">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
                                    <p class="small text-muted mb-0" id="ticketReplyFrom"></p>
                                    <span id="ticketReplyBadge"></span>
                                </div>
                                <div id="ticketReplyThread" class="hd-thread-box d-flex flex-column gap-3 mb-4"></div>
                                <div>
                                    <label for="ticketReplyText" class="form-label fw-semibold small">
                                        <i class="bi bi-pencil-fill me-1 text-secondary" aria-hidden="true"></i>Your reply
                                    </label>
                                    <textarea class="form-control rounded-3 p-3" id="ticketReplyText" rows="4" maxlength="2000"
                                        placeholder="Write a helpful response to the user..." required></textarea>
                                    <div class="invalid-feedback" id="ticketReplyError"></div>
                                    <div class="form-text small">Up to 2,000 characters.</div>
                                </div>
                            </div>
                            <div class="modal-footer border-top px-4 py-3" style="background-color: var(--main-bg-color);">
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn rounded-pill px-4 text-white fw-semibold shadow-sm d-inline-flex align-items-center gap-2"
                                    style="background-color: var(--btn);" id="ticketReplySend">
                                    <i class="bi bi-send-fill" aria-hidden="true"></i> Send reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
    <?php endif; ?>

        <!-- Tutorial -->
        <section class="tutorial-section">
            <div class="tutorial-wrapper mx-auto" style="max-width: 800px;">

                <div class="d-flex align-items-center gap-3 mt-5 mb-5">
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                    <h2 class="<?= $is_staff ? '' : 'h1 ' ?>fw-semibold mb-0">Tutorial</h2>
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                </div>


                <?php if ($is_staff): ?>
<div class="text-center my-3 position-relative">
                    <div class="d-flex justify-content-end mx-auto mb-2">
                        <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                            data-bs-toggle="modal" data-bs-target="#tutorialModal">
                            <i class="bi bi-pencil-square me-1"></i> Edit content
                        </button>
                    </div>
                </div>
<?php endif; ?>
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
                            Open <strong>Library</strong> to browse the catalog. Digital books open in the reader with
                            <strong>Read Now</strong>; physical books can be <strong>borrowed</strong> or
                            <strong>reserved</strong> and are picked up at the library counter. Track loans, due dates
                            and fines under <strong>My Books</strong>, and watch the bell for approvals and reminders.
                        </p>
                    </div>
                </div>
        </section>

        <!--FAQ-->
        <section class="faq-section">
            <div class="faq-wrapper mx-auto">
                <div class="d-flex align-items-center gap-3 mt-5 mb-5">
                    <hr class="flex-grow-1 border-top border-2 my-0 border-black">
                    <h2 class="<?= $is_staff ? '' : 'h1 ' ?>fw-semibold mb-0">Frequently Asked Questions</h2>
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

                        <?php if ($is_staff): ?>
<div class="flex-shrink-0">
                            <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                                data-bs-toggle="modal" data-bs-target="#faqModal">
                                <i class="bi bi-pencil-square me-1"></i> Edit content
                            </button>
                        </div>
<?php endif; ?>

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
        <?php render_logout_modal(); ?>

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
                        <?php if ($is_staff): ?>
<div class="flex-shrink-0">
                            <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);"
                                data-bs-toggle="modal" data-bs-target="#editfaqModal">
                                <i class="bi bi-pencil-square me-1"></i> Edit content
                            </button>
                        </div>
<?php endif; ?>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tutorial edit Modal -->
        <?php if ($is_staff): ?>
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
        <?php endif; ?>

        <!-- FAQ Add Modal -->
        <?php if ($is_staff): ?>
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
        <?php endif; ?>

        <!-- FAQ Edit Modal -->
        <?php if ($is_staff): ?>
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
        <?php endif; ?>
    </main>


    <!-- ========================================================================== -->
    <!-- FOOTER                                                                  -->
    <!-- ========================================================================== -->
    <?php render_footer($menu, 'class="text-light py-5" style="margin-top: 100px;"'); ?>

<?php render_scripts($is_staff ? ['helpdesk.js', 'helpdesk_tickets.js'] : ['helpdesk.js']); ?>
