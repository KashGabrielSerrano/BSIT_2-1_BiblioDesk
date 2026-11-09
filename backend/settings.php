<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';
$user = require_login_page();
$menu = menu_for_role($user['role']);
render_head('Settings | BiblioDesk', ['root.css', 'settings.css', 'admindb.css'],
    '    <script src="../frontend/js/prefs.js"></script>');
?>
        <!-- SIDEBAR For Admin -->
        <?php render_sidebar($menu); ?>

        <!-- LOGOUT CONFIRMATION MODAL -->
        <?php render_logout_modal(); ?>

        <?php render_topbar($menu); ?>

        <main class="container-fluid px-3 px-md-5 py-4">
            <h1 class="pageTitle mt-4 mb-3">Settings</h1>

            <p class="demoNote" id="demoNote" hidden>
                <strong>Demo mode:</strong> your settings are saved in this browser only until the server is connected.
            </p>

            <div class="row g-4 mt-1">
                <!--Section list-->
                <div class="col-lg-3">
                    <nav class="settingsNav" role="tablist" aria-label="Settings sections" aria-orientation="vertical">
                        <button type="button" class="navItem active" role="tab" id="tab-profile" data-panel="profile" aria-controls="panel-profile" aria-selected="true">Profile</button>
                        <button type="button" class="navItem" role="tab" id="tab-password" data-panel="password" aria-controls="panel-password" aria-selected="false">Password</button>
                        <button type="button" class="navItem" role="tab" id="tab-notifications" data-panel="notifications" aria-controls="panel-notifications" aria-selected="false">Notifications</button>
                        <button type="button" class="navItem" role="tab" id="tab-reading" data-panel="reading" aria-controls="panel-reading" aria-selected="false">Reading &amp; appearance</button>
                        <button type="button" class="navItem" role="tab" id="tab-data" data-panel="data" aria-controls="panel-data" aria-selected="false">My data &amp; account</button>
<?php if ($user['role'] === 'Admin'): ?>
                        <button type="button" class="navItem" role="tab" id="tab-system" data-panel="system" aria-controls="panel-system" aria-selected="false">System (admin)</button>
<?php endif; ?>
                    </nav>
                </div>

                <div class="col-lg-9">
                    <!--PROFILE-->
                    <section class="settingsPanel active" id="panel-profile" role="tabpanel" aria-labelledby="tab-profile" tabindex="-1">
                        <div class="panelHead">
                            <h2>Profile</h2>
                            <p>How you appear to librarians and on your Digital Library ID.</p>
                        </div>

                        <div class="d-flex align-items-center flex-wrap gap-3 mb-4">
                            <img id="avatarImg" src="../frontend/assets/icons/pfp-icon.png" alt="Your profile photo" class="avatarBig rounded-circle">
                            <div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <input type="file" id="avatarFile" accept="image/png,image/jpeg,image/webp,image/gif" class="visually-hidden">
                                    <label for="avatarFile" class="btn stBtn mb-0">Change photo</label>
                                    <button type="button" id="avatarRemove" class="btn stBtn">Remove</button>
                                </div>
                                <p class="fieldError" id="avatarError" role="alert"></p>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="pfName" class="fieldLabel">Full name</label>
                                <input type="text" id="pfName" class="form-control stField" maxlength="80" autocomplete="name" aria-describedby="pfNameErr">
                                <p class="fieldError" id="pfNameErr" role="alert"></p>
                            </div>
                            <div class="col-md-6">
                                <label for="pfEmail" class="fieldLabel">Email</label>
                                <input type="email" id="pfEmail" class="form-control stField" autocomplete="email" aria-describedby="pfEmailErr">
                                <p class="fieldError" id="pfEmailErr" role="alert"></p>
                            </div>
                            <div class="col-md-6">
                                <label for="pfPhone" class="fieldLabel">Contact number <span class="optional">(optional)</span></label>
                                <input type="tel" id="pfPhone" class="form-control stField" placeholder="09123456789" autocomplete="tel" aria-describedby="pfPhoneErr">
                                <p class="fieldError" id="pfPhoneErr" role="alert"></p>
                            </div>
                            <div class="col-md-6">
                                <label for="pfStudentNo" class="fieldLabel">Student No.</label>
                                <input type="text" id="pfStudentNo" class="form-control stField" value="R2025******" disabled aria-describedby="pfStudentNoHelp">
                                <p class="helpText" id="pfStudentNoHelp">Only the library can change this.</p>
                            </div>
                            <div class="col-md-6">
                                <label for="pfProgram" class="fieldLabel">Program</label>
                                <select id="pfProgram" class="form-select stField">
                                    <option>BS Information Technology</option>
                                    <option>BS Computer Science</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="pfYear" class="fieldLabel">Year level</label>
                                <select id="pfYear" class="form-select stField">
                                    <option>1st Year</option>
                                    <option>2nd Year</option>
                                    <option>3rd Year</option>
                                    <option>4th Year</option>
                                    <option>5th Year</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!--PASSWORD-->
                    <section class="settingsPanel" id="panel-password" role="tabpanel" aria-labelledby="tab-password" tabindex="-1">
                        <div class="panelHead">
                            <h2>Password</h2>
                            <p>Use a password you don't use on any other site.</p>
                        </div>

                        <form id="pwForm" class="row g-3" novalidate>
                            <div class="col-md-8">
                                <label for="pwCurrent" class="fieldLabel">Current password</label>
                                <div class="pwWrap">
                                    <input type="password" id="pwCurrent" class="form-control stField" autocomplete="current-password">
                                    <button type="button" class="pwToggle" data-target="pwCurrent" aria-pressed="false">Show</button>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <label for="pwNew" class="fieldLabel">New password</label>
                                <div class="pwWrap">
                                    <input type="password" id="pwNew" class="form-control stField" autocomplete="new-password" aria-describedby="pwReqs">
                                    <button type="button" class="pwToggle" data-target="pwNew" aria-pressed="false">Show</button>
                                </div>
                                <div class="meter mt-2" aria-hidden="true"><span id="pwMeterBar" data-level="0"></span></div>
                                <p class="meterLabel" id="pwMeterLabel" aria-live="polite"></p>
                                <ul class="reqList" id="pwReqs">
                                    <li id="reqLen">At least 8 characters</li>
                                    <li id="reqCase">Upper and lower case letters</li>
                                    <li id="reqNum">At least one number</li>
                                </ul>
                            </div>
                            <div class="col-md-8">
                                <label for="pwConfirm" class="fieldLabel">Confirm new password</label>
                                <div class="pwWrap">
                                    <input type="password" id="pwConfirm" class="form-control stField" autocomplete="new-password" aria-describedby="pwConfirmErr">
                                    <button type="button" class="pwToggle" data-target="pwConfirm" aria-pressed="false">Show</button>
                                </div>
                                <p class="fieldError" id="pwConfirmErr" role="alert"></p>
                            </div>
                            <div class="col-12">
                                <button type="submit" id="pwSave" class="btn stBtn primary" disabled>Update password</button>
                            </div>
                        </form>
                    </section>

                    <!--NOTIFICATIONS-->
                    <section class="settingsPanel" id="panel-notifications" role="tabpanel" aria-labelledby="tab-notifications" tabindex="-1">
                        <div class="panelHead">
                            <h2>Notifications</h2>
                            <p>Choose what you hear about. These also control the bell in the top bar.</p>
                        </div>

                        <div class="switchRow">
                            <div>
                                <label for="ntDue" class="switchTitle">Due-date reminders</label>
                                <p class="helpText mb-0">A reminder before a borrowed book is due.</p>
                                <div class="inlineSelect">
                                    <label for="ntDueDays" class="helpText">Remind me</label>
                                    <select id="ntDueDays" class="form-select form-select-sm stField">
                                        <option value="1">1 day before</option>
                                        <option value="2">2 days before</option>
                                        <option value="3">3 days before</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ntDue"></div>
                        </div>
                        <div class="switchRow">
                            <div>
                                <label for="ntRes" class="switchTitle">Reservation updates</label>
                                <p class="helpText mb-0">When a reservation is approved or denied.</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ntRes"></div>
                        </div>
                        <div class="switchRow">
                            <div>
                                <label for="ntAnn" class="switchTitle">Library announcements &amp; events</label>
                                <p class="helpText mb-0">Policy changes, maintenance notices and events like the book fest.</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ntAnn"></div>
                        </div>
                        <div class="switchRow">
                            <div>
                                <label for="ntAct" class="switchTitle">Account activity</label>
                                <p class="helpText mb-0">When your profile or password is changed.</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ntAct"></div>
                        </div>

                        <h3 class="subHead">How to reach you</h3>
                        <div class="switchRow">
                            <div>
                                <span class="switchTitle">In-app (bell icon)</span>
                                <p class="helpText mb-0">Always on.</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" checked disabled aria-label="In-app notifications (always on)"></div>
                        </div>
                        <div class="switchRow">
                            <div>
                                <label for="ntEmail" class="switchTitle">Email</label>
                                <p class="helpText mb-0">Also send these to your email address.</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="ntEmail"></div>
                        </div>
                    </section>

                    <!--READING & APPEARANCE-->
                    <section class="settingsPanel" id="panel-reading" role="tabpanel" aria-labelledby="tab-reading" tabindex="-1">
                        <div class="panelHead">
                            <h2>Reading &amp; appearance</h2>
                            <p>Changes to theme, text size and motion preview right away.</p>
                        </div>

                        <fieldset class="mb-4">
                            <legend class="fieldLabel">Theme</legend>
                            <div class="segment" role="radiogroup">
                                <label><input type="radio" name="theme" value="light"><span>Light</span></label>
                                <label><input type="radio" name="theme" value="dark"><span>Dark</span></label>
                                <label><input type="radio" name="theme" value="system"><span>Match my device</span></label>
                            </div>
                        </fieldset>

                        <fieldset class="mb-4">
                            <legend class="fieldLabel">Text size</legend>
                            <div class="segment" role="radiogroup">
                                <label><input type="radio" name="textSize" value="small"><span>Small</span></label>
                                <label><input type="radio" name="textSize" value="default"><span>Default</span></label>
                                <label><input type="radio" name="textSize" value="large"><span>Large</span></label>
                                <label><input type="radio" name="textSize" value="xlarge"><span>Extra large</span></label>
                            </div>
                        </fieldset>

                        <div class="switchRow">
                            <div>
                                <label for="prMotion" class="switchTitle">Reduce motion</label>
                                <p class="helpText mb-0">Turns off sliding and hover animations.</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="prMotion"></div>
                        </div>
                        <div class="switchRow">
                            <div>
                                <label for="prTrack" class="switchTitle">Track my reading time</label>
                                <p class="helpText mb-0">Powers the Active Reading Time number on your overview.</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="prTrack"></div>
                        </div>

                        <fieldset class="mt-4 mb-4">
                            <legend class="fieldLabel">Default Library view</legend>
                            <div class="segment" role="radiogroup">
                                <label><input type="radio" name="libraryView" value="grid"><span>Grid</span></label>
                                <label><input type="radio" name="libraryView" value="list"><span>List</span></label>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="fieldLabel">Favorite genres</legend>
                            <p class="helpText">Used to pick your Recommendations.</p>
                            <div class="chips">
                                <label class="chip"><input type="checkbox" name="genre" value="Fiction"><span>Fiction</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="Non-fiction"><span>Non-fiction</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="Horror"><span>Horror</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="Romance"><span>Romance</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="Science"><span>Science</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="Technology"><span>Technology</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="History"><span>History</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="Self-help"><span>Self-help</span></label>
                                <label class="chip"><input type="checkbox" name="genre" value="Education"><span>Education</span></label>
                            </div>
                        </fieldset>
                    </section>

                    <!--MY DATA & ACCOUNT-->
                    <section class="settingsPanel" id="panel-data" role="tabpanel" aria-labelledby="tab-data" tabindex="-1">
                        <div class="panelHead">
                            <h2>My data &amp; account</h2>
                            <p>Take a copy of your information, or close your account.</p>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" id="dlHistory" class="btn stBtn">Download borrowing history (CSV)</button>
                            <button type="button" id="dlSettings" class="btn stBtn">Download my profile &amp; settings (JSON)</button>
                        </div>

                        <div class="dangerZone mt-5">
                            <h3>Close my account</h3>
                            <p>This sends a request to the library. Your borrowing history is kept as required by library records, and you will not be able to sign in once it is approved.</p>
                            <button type="button" id="closeAccountBtn" class="btn stBtn danger">Close my account&hellip;</button>
                        </div>
                    </section>

<?php if ($user['role'] === 'Admin'): $sys = settings_all(); $group = ''; ?>
                    <!--SYSTEM (Admin only, A2): global settings in the system_settings table - frontend/js/admin_settings.js -->
                    <section class="settingsPanel" id="panel-system" role="tabpanel" aria-labelledby="tab-system" tabindex="-1">
                        <div class="panelHead">
                            <h2>System settings</h2>
                            <p>Library-wide rules for borrowing, reservations and fines. Changes apply to everyone right away and are recorded in the activity log.</p>
                        </div>
                        <form id="systemSettingsForm" novalidate>
                            <div class="row g-3">
<?php foreach (SETTINGS_SPEC as $key => [$label, $type, $min, $max, $default, $help, $grp]):
        if ($grp !== $group): $group = $grp; ?>
                                <div class="col-12"><h3 class="h5 fw-semibold mt-3 mb-0"><?= e($grp) ?></h3></div>
<?php   endif;
        if ($type === 'bool'): ?>
                                <div class="col-12">
                                    <div class="switchRow">
                                        <div>
                                            <label for="sys_<?= $key ?>" class="switchTitle"><?= e($label) ?></label>
                                            <p class="helpText mb-0"><?= e($help) ?></p>
                                        </div>
                                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch"
                                            id="sys_<?= $key ?>" name="<?= $key ?>" <?= $sys[$key] ? 'checked' : '' ?>></div>
                                    </div>
                                </div>
<?php   else: ?>
                                <div class="col-md-6">
                                    <label for="sys_<?= $key ?>" class="fieldLabel"><?= e($label) ?></label>
                                    <input type="number" id="sys_<?= $key ?>" name="<?= $key ?>" class="form-control stField"
                                        min="<?= $min ?>" max="<?= $max ?>" step="<?= $type === 'money' ? '0.01' : '1' ?>" required
                                        value="<?= $type === 'money' ? number_format((float)$sys[$key], 2, '.', '') : (int)$sys[$key] ?>"
                                        aria-describedby="sys_<?= $key ?>_help sys_<?= $key ?>_err">
                                    <p class="helpText mb-0" id="sys_<?= $key ?>_help"><?= e($help) ?> (<?= $min ?>–<?= $max ?>, default <?= $type === 'money' ? number_format((float)$default, 2) : $default ?>)</p>
                                    <p class="fieldError" id="sys_<?= $key ?>_err" role="alert"></p>
                                </div>
<?php   endif;
      endforeach; ?>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                                <button type="submit" id="systemSave" class="btn stBtn primary">Save system settings</button>
                                <span id="systemResult" class="small" role="status"></span>
                            </div>
                        </form>
                    </section>
<?php endif; ?>
                </div>
            </div>
        </main>

        <!--Unsaved changes bar (Profile, Notifications, Reading & appearance)-->
        <div class="saveBar" id="saveBar" role="region" aria-label="Unsaved changes" hidden>
            <span>You have unsaved changes</span>
            <button type="button" id="discardBtn" class="btn stBtn onDark">Discard</button>
            <button type="button" id="saveBtn" class="btn stBtn light">Save changes</button>
        </div>

        <?php render_footer($menu); ?>

        <!--Close account confirmation-->
        <div class="modal fade closeModal" id="closeModal" tabindex="-1" aria-labelledby="closeTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header modalBar">
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h2 class="h4 fw-bold" id="closeTitle">Close your account?</h2>
                        <p>Type <strong>CLOSE</strong> to confirm. A librarian will review your request.</p>
                        <label for="closeConfirmInput" class="fieldLabel">Confirmation</label>
                        <input type="text" id="closeConfirmInput" class="form-control stField mb-3" autocomplete="off" placeholder="CLOSE">
                        <label for="closeReason" class="fieldLabel">Reason <span class="optional">(optional)</span></label>
                        <textarea id="closeReason" class="form-control stField" rows="3" maxlength="300"></textarea>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn stBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="closeConfirmBtn" class="btn stBtn danger" disabled>Send request</button>
                    </div>
                </div>
            </div>
        </div>

        <!--Toast-->
        <div class="toast-container position-fixed bottom-0 end-0 p-3">
            <div id="stToast" class="toast stToast" role="status" aria-live="polite" aria-atomic="true">
                <div class="d-flex align-items-center">
                    <div class="toast-body" id="stToastText"></div>
                    <button type="button" class="btn-close btn-close-white me-2" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

<?php render_scripts($user['role'] === 'Admin' ? ['settings.js', 'admin_settings.js'] : ['settings.js']); ?>
