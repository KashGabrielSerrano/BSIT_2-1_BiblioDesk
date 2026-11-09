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
                            <form class="formContain" action="" method="post" data-contact-form>
                                <div class="d-none" aria-hidden="true"><label for="website">Leave this empty</label><input type="text" id="website" name="website" tabindex="-1" autocomplete="off"></div>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name:</label>
                                    <input type="text" id="name" name="name" class="name form-control contactInput" value="<?= e($profile['full_name']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email:</label>
                                    <input type="email" id="email" name="email" class="email form-control contactInput" value="<?= e($profile['email']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject/Concern:</label>
                                    <select name="subject" id="subject" class="form-select contactInput" required>
                                        <option value="Account / Login">Account / Login</option>
                                        <option value="Borrowing & Reservations">Borrowing &amp; Reservations</option>
                                        <option value="Digital Books / Reading">Digital Books / Reading</option>
                                        <option value="Fines & Payments">Fines &amp; Payments</option>
                                        <option value="Suggestion">Suggestion</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message:</label>
                                    <textarea id="message" name="message" minlength="10" maxlength="2000" class="message form-control contactInput" placeholder="Send us your concern/message here..." required></textarea>
                                </div>
                                <div class="btnContain d-flex justify-content-end">
									<button type="submit" class=" fw-medium px-4 py-2 sendBtn">Send Message</button>
								</div>
							</form>
                        </div>
                    </section>
				</div>

                <!-- My messages: the user's tickets and the library's replies (frontend/js/contact.js) -->
                <section id="my-messages" class="mt-5 pt-3 px-lg-5" aria-labelledby="myMessagesHeading">
                    <h2 id="myMessagesHeading" class="fw-bold mb-3">My messages</h2>
                    <div id="myMessages"><p class="text-muted mb-0">Loading your messages…</p></div>
                </section>
            </div>
        </main>

         <?php render_footer('student'); ?>

<?php render_scripts(['contact.js']); ?>
