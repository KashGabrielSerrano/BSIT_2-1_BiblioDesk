<?php
require_once __DIR__ . '/core/bootstrap.php';
require_once __DIR__ . '/partials/layout.php';

$user       = require_login_page();
$menu       = menu_for_role($user['role']);
$is_student = $user['role'] === 'Student';

$profile = current_profile();
$p = [
    'name'      => $profile['full_name'],
    'id_masked' => $profile['student_id_masked'] !== '' ? $profile['student_id_masked'] : '**********',
    'first'     => $profile['first_name'],
    'last'      => $profile['last_name'],
    'suffix_ph' => $profile['suffix'] !== '' ? $profile['suffix'] : 'Suffix (I / II / Sr. / Jr.)',
    'id'        => $profile['student_id'],
    'birthday'  => $profile['birthday'],
    'email'     => $profile['email'],
];

render_head('Account', ['accountpage.css']);
render_sidebar($menu);
render_logout_modal();
render_topbar($menu);
?>

        <main>
            <div class="container-fluid px-0 py-5">
                <header class="headerContain d-flex align-items-center gap-4 gap-md-5 px-lg-5">
                    <img src="../frontend/assets/icons/pfp-icon.png" alt="Profile picture" class="accountAvatar rounded-circle object-fit-cover flex-shrink-0">

                    <div class="headerText flex-grow-1">
                        <h1 id="displayName" class="fw-bold mb-1"><?= e($p['name']) ?></h1>
                        <p class="fs-3 mb-0"><?= e($p['id_masked']) ?></p>
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
                                    <input type="text" id="firstName" name="first_name" class="form-control accountInput" value="<?= e($p['first']) ?>" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="lastName" class="form-label fs-5 mb-2">Last Name</label>
                                    <input type="text" id="lastName" name="last_name" class="form-control accountInput" value="<?= e($p['last']) ?>" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="middleName" class="form-label fs-5 mb-2">Middle Name (Optional)</label>
                                    <input type="text" id="middleName" name="middle_name" class="form-control accountInput" placeholder="Middle Name">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="suffix" class="form-label fs-5 mb-2">Suffix (Optional)</label>
                                    <input type="text" id="suffix" name="suffix" class="form-control accountInput" placeholder="<?= e($p['suffix_ph']) ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="studentId" class="form-label fs-5 mb-2"><?= $is_student ? 'Student ID' : 'ID' ?></label>
                                    <input type="text" id="studentId" name="student_id" class="form-control accountInput" value="<?= e($p['id']) ?>" readonly>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="birthday" class="form-label fs-5 mb-2">Birthday (mm/dd/yyyy)</label>
                                    <input type="date" id="birthday" name="birthday" class="form-control accountInput" value="<?= e($p['birthday']) ?>">
                                </div>

                                <?php if ($is_student): ?>
                                <div class="col-12 col-md-6">
                                    <label for="department" class="form-label fs-5 mb-2">Department</label>
                                    <input type="text" id="department" name="department" class="form-control accountInput" value="<?= e($profile['department_label']) ?>">
                                </div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section aria-labelledby="accountHeading" class="mt-5 pt-5">
                            <h2 id="accountHeading" class="visually-hidden">Account information</h2>

                            <div class="row g-4 px-lg-3">
                                <div class="col-12 col-md-6 d-flex flex-column gap-4">
                                    <div>
                                        <label for="schoolEmail" class="form-label fs-5 mb-2"><?= $is_student ? 'School Email' : 'Email' ?></label>
                                        <input type="email" id="schoolEmail" name="school_email" class="form-control accountInput" value="<?= e($p['email']) ?>" required>
                                    </div>

                                    <div>
                                        <label for="password" class="form-label fs-5 mb-2">Password</label>
                                        <div class="position-relative">
                                            <input type="password" id="password" name="password" class="form-control accountInput pe-5" value="***************" autocomplete="new-password">
                                            <button type="button" id="togglePassword" class="position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 bg-transparent" aria-label="Show password">
                                                <i class="bi bi-eye-slash fs-5" aria-hidden="true"></i>
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

<?php
render_footer($menu, 'class="text-light py-5"');
render_scripts(['accountpage.js']);
