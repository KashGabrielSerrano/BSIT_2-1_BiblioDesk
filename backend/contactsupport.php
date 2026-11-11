<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
$user    = require_login_page();
$profile = current_profile();
render_head('Contact Support', ['contact.css']);
?>
    <?php render_sidebar('student', 'contact'); ?>
    <?php render_logout_modal(); ?>

    <?php render_topbar('student'); ?>

    <main>
        <div class="container-fluid px-4 px-lg-5 py-5">
            <div class="row g-5 align-items-start">

                <!-- contact details -->
                <section class="col-12 col-lg-6 ps-lg-5" aria-labelledby="contactHeading">
                    <h1 id="contactHeading" class="display-5 fw-bold mb-4">Get in touch with us!</h1>
                    <p class="fs-5 mb-5">
                        Let us know about your suggestions or if there is any problem that you're encountering!
                    </p>
                    <p class="fs-5 fw-semibold mb-3">Below is our contact information:</p>

                    <ul class="list-unstyled d-flex flex-column gap-3 mb-4">
                        <li class="contactItem card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-4 p-3">
                                <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                                    <i class="bi bi-briefcase-fill"></i>
                                </span>
                                <div>
                                    <h2 class="h5 mb-1">Business Email</h2>
                                    <a class="fs-5 text-dark text-decoration-none stretched-link" href="mailto:bibliodesk@gmail.com">bibliodesk@gmail.com</a>
                                </div>
                            </div>
                        </li>
                        <li class="contactItem card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-4 p-3">
                                <span class="contactIcon rounded-4 d-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                                    <i class="bi bi-headset"></i>
                                </span>
                                <div>
                                    <h2 class="h5 mb-1">Support Email</h2>
                                    <a class="fs-5 text-dark text-decoration-none stretched-link" href="mailto:bibliodesk.help@gmail.com">bibliodesk.help@gmail.com</a>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <p class="small mb-0">
                        <i class="bi bi-reply-fill me-1" aria-hidden="true"></i>
                        Our replies to your messages appear under <a href="#my-messages" class="text-dark">My messages</a> below.
                    </p>
                </section>

                <!-- Right: message form (frontend/js/contact.js -> helpdesk_controller.php) -->
                <section class="col-12 col-lg-6" aria-labelledby="formHeading">
                    <div class="card contactCard text-light border-0 rounded-4 shadow">
                        <div class="card-body p-4 p-xl-5 pb-0 pb-xl-0">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <span class="formIcon rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">
                                    <i class="bi bi-chat-dots-fill"></i>
                                </span>
                                <h2 id="formHeading" class="h3 fw-semibold mb-0">Send us a message here!</h2>
                            </div>
                            <p class="small mb-2">
                                Tell us more about your <strong>business inquiries</strong> or the <strong>issues/problems</strong>
                                that you're encountering in our website.
                            </p>
                            <p class="small mb-4 text-white-50">
                                <i class="bi bi-shield-lock-fill me-1" aria-hidden="true"></i>
                                Rest assured that your email and data will not be published anywhere.
                            </p>
                            <hr class="opacity-25 mt-0 mb-4">
                        </div>

                        <form class="formContain" action="" method="post" data-contact-form>
                            <div class="d-none" aria-hidden="true"><label for="website">Leave this empty</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>

                            <!-- Name + email values come from the account -->
                            <div class="mb-3">
                                <label for="name" class="form-label fw-medium">Name</label>
                                <div class="position-relative">
                                    <i class="bi bi-person-fill fieldIcon" aria-hidden="true"></i>
                                    <input type="text" id="name" name="name" class="name form-control contactInput withIcon withLock"
                                        value="<?= e($profile['full_name']) ?>" disabled aria-describedby="accountHint">
                                    <i class="bi bi-lock-fill fieldLock" aria-hidden="true"></i>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label fw-medium">Email</label>
                                <div class="position-relative">
                                    <i class="bi bi-envelope-fill fieldIcon" aria-hidden="true"></i>
                                    <input type="email" id="email" name="email" class="email form-control contactInput withIcon withLock"
                                        value="<?= e($profile['email']) ?>" disabled aria-describedby="accountHint">
                                    <i class="bi bi-lock-fill fieldLock" aria-hidden="true"></i>
                                </div>
                                <div id="accountHint" class="form-text text-white-50">
                                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Name and Email that will be read by the staff are taken from your account details. 
                                    If you want to change them, please update your <a href="./account.php" class="text-white text-decoration-underline">account details</a>.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label fw-medium">Subject / Concern</label>
                                <div class="position-relative">
                                    <i class="bi bi-tag-fill fieldIcon" aria-hidden="true"></i>
                                    <select name="subject" id="subject" class="form-select contactInput withIcon" required>
                                        <option value="" selected disabled>Choose a subject…</option>
                                        <option value="Account / Login">Account / Login</option>
                                        <option value="Borrowing & Reservations">Borrowing &amp; Reservations</option>
                                        <option value="Digital Books / Reading">Digital Books / Reading</option>
                                        <option value="Fines & Payments">Fines &amp; Payments</option>
                                        <option value="Suggestion">Suggestion</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label fw-medium">Message</label>
                                <div class="position-relative">
                                    <i class="bi bi-pencil-fill fieldIcon top" aria-hidden="true"></i>
                                    <textarea id="message" name="message" rows="5" minlength="10" maxlength="2000"
                                        class="message form-control contactInput withIcon"
                                        placeholder="Send us your concern/message here..." required></textarea>
                                </div>
                                <div class="form-text text-white-50">10 to 2,000 characters.</div>
                            </div>

                            <div class="btnContain d-flex justify-content-end">
                                <button type="submit" class="btn fw-semibold px-4 py-2 sendBtn d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-send-fill" aria-hidden="true"></i> Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <!-- My messages tab: the user's tickets and the librarian/admin replies (frontend/js/contact.js) -->
            <section id="my-messages" class="mt-5 pt-3 px-lg-5" aria-labelledby="myMessagesHeading">
                <h2 id="myMessagesHeading" class="fw-bold mb-3">
                    <i class="bi bi-chat-left-text-fill me-2" aria-hidden="true"></i>My messages
                </h2>
                <div id="myMessages"><p class="text-muted mb-0">Loading your messages…</p></div>
            </section>
        </div>
    </main>

    <?php render_footer('student'); ?>

<?php render_scripts(['contact.js']); ?>
