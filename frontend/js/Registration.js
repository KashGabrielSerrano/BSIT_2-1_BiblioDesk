document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registrationForm");
    const pages = document.querySelectorAll(".form-page");
    const errorModalEl = document.getElementById("errorModal");
    const errorMessage = document.getElementById("errorModalMessage");
    let currentPage = 0;
    let fieldToFocus = null;

    if (!form || pages.length === 0) {
        console.error("Registration.js: form or .form-page elements not found.");
        return;
    }

    // Error modal instance
    const errorModal = (typeof bootstrap !== "undefined")
        ? bootstrap.Modal.getOrCreateInstance(errorModalEl)
        : null;

    function showError(message, field) {
        errorMessage.textContent = message;
        fieldToFocus = field || null;
        if (field) field.classList.add("is-invalid");
        if (errorModal) {
            errorModal.show();
        } else {
            alert(message);
            if (field) field.focus();
        }
    }

    function showServerMessage(message, type, redirectUrl) {
        const title = document.getElementById("errorModalTitle");
        const isSuccess = type === "success";
        title.className = "modal-title " + (isSuccess ? "text-success" : "text-danger");
        title.innerHTML = '<i class="bi ' + (isSuccess ? "bi-check-circle-fill" : "bi-exclamation-circle-fill") +
                          ' me-2"></i>' + (isSuccess ? "Success" : "Notice");
        errorMessage.textContent = message;
        fieldToFocus = null;

        if (redirectUrl) {
            errorModalEl.addEventListener("hidden.bs.modal", function () {
                window.location.href = redirectUrl;
            }, { once: true });
        }

        if (errorModal) {
            errorModal.show();
        } else {
            alert(message);
            if (redirectUrl) window.location.href = redirectUrl;
        }
    }

    errorModalEl.addEventListener("hidden.bs.modal", function () {
        if (fieldToFocus) {
            fieldToFocus.focus();
            fieldToFocus = null;
        }
    });

    form.querySelectorAll("input, select").forEach(function (el) {
        el.addEventListener("input", function () { el.classList.remove("is-invalid"); });
        el.addEventListener("change", function () { el.classList.remove("is-invalid"); });
    });

    // Birthday calendar
    const birthdayPicker = flatpickr("#birthday", {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "F j, Y",
        allowInput: false,
        maxDate: "today",
        onChange: function () {
            birthdayPicker.altInput.classList.remove("is-invalid");
        }
    });

    // Page Step Logic
    function showPage() {
        pages.forEach(function (page) { page.classList.remove("active"); });
        pages[currentPage].classList.add("active");
    }

    function validateCurrentPage(actionWord) {
        const inputs = pages[currentPage].querySelectorAll("input, select");
        for (const input of inputs) {
            if (input.tagName === "INPUT" && input.type !== "password") {
                input.value = input.value.trim();
            }

            const target = (input.id === "birthday") ? birthdayPicker.altInput : input;

            if (input.hasAttribute("required") && input.value === "") {
                showError("Please fill in all required fields before " + actionWord + ".", target);
                return false;
            }
            if (input.value !== "" && !input.checkValidity()) {
                showError(input.title || input.validationMessage || "Please check the highlighted field.", target);
                return false;
            }
        }
        return true;
    }

    function nextPage() {
        if (!validateCurrentPage("proceeding")) return;
        if (currentPage < pages.length - 1) {
            currentPage++;
            showPage();
        }
    }

    function previousPage() {
        if (currentPage === 0) {
            window.location.href = "login.php";
            return;
        }
        currentPage--;
        showPage();
    }

    document.querySelectorAll('[data-action="next"]').forEach(function (b) {
        b.addEventListener("click", nextPage);
    });
    document.querySelectorAll('[data-action="prev"]').forEach(function (b) {
        b.addEventListener("click", previousPage);
    });

    document.getElementById("student_id").addEventListener("input", function () {
        this.value = this.value.toUpperCase();
    });

    // Show / hide password
    document.querySelectorAll(".password-toggle-icon").forEach(function (icon) {
        icon.addEventListener("click", function () {
            const input = document.getElementById(icon.dataset.target);
            const eye = icon.querySelector("i");
            const show = input.type === "password";
            input.type = show ? "text" : "password";
            eye.classList.toggle("bi-eye", show);
            eye.classList.toggle("bi-eye-slash", !show);
        });
    });

    // Warn when a password goes past the 64-character limit
    const PASSWORD_MAX = 64;
    ["password", "confirm_password"].forEach(function (id) {
        const input = document.getElementById(id);
        if (!input) return;

        let lastValid = "";
        input.addEventListener("input", function () {
            if (input.value.length > PASSWORD_MAX) {
                input.value = lastValid;   // undo the over-limit typing or paste
                showError("Password must not exceed " + PASSWORD_MAX + " characters.", input);
            } else {
                lastValid = input.value;
            }
        });
    });

    if (errorModalEl && errorModalEl.dataset.message) {
        showServerMessage(
            errorModalEl.dataset.message,
            errorModalEl.dataset.type,
            errorModalEl.dataset.redirect
        );
    }

    showPage();

    // OTP Modal & Timer 
    const modalElement     = document.getElementById('otpModal');
    const otpModal         = modalElement ? new bootstrap.Modal(modalElement) : null;
    const otpAlert         = document.getElementById('otpAlert');
    const otpInput         = document.getElementById('otpCodeInput');
    const otpEmailDisplay  = document.getElementById('otpEmailDisplay');
    const otpExpiryTimer   = document.getElementById('otpExpiryTimer');
    const btnVerify        = document.getElementById('btnVerifyOtp');
    const btnResend        = document.getElementById('btnResendOtp');
    const btnSubmitRegister = document.getElementById('btnSubmitRegister');

    let expiryCountdownInterval = null;
    let resendCountdownInterval = null;

    function showAlert(msg, isSuccess = false) {
        if (!otpAlert) return;
        otpAlert.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'}`;
        otpAlert.textContent = msg;
        otpAlert.classList.remove('d-none');
    }

    function clearAlert() {
        if (otpAlert) {
            otpAlert.classList.add('d-none');
            otpAlert.textContent = '';
        }
    }

    // Expiration Timer synced with backend timestamp
    function startExpiryTimer(expiresAtMs) {
        clearInterval(expiryCountdownInterval);

        const targetTime = expiresAtMs || (Date.now() + 300000);

        function updateTimer() {
            const now = Date.now();
            const remainingSeconds = Math.max(0, Math.floor((targetTime - now) / 1000));

            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;

            if (otpExpiryTimer) {
                otpExpiryTimer.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }

            if (remainingSeconds <= 0) {
                clearInterval(expiryCountdownInterval);
                showAlert('Your verification code has expired. Please request a new one.');
                if (btnVerify) btnVerify.disabled = true;
                if (otpInput) otpInput.disabled = true;
            }
        }

        updateTimer();
        expiryCountdownInterval = setInterval(updateTimer, 1000);
    }

    // 60-second resend cooldown timer
    function startResendCooldown(seconds = 60) {
        clearInterval(resendCountdownInterval);
        if (!btnResend) return;

        btnResend.disabled = true;
        let left = seconds;
        btnResend.innerHTML = `Resend code in <span id="resendTimer">${left}</span>s`;

        resendCountdownInterval = setInterval(() => {
            left--;
            const timerSpan = document.getElementById('resendTimer');
            if (timerSpan) timerSpan.textContent = left;

            if (left <= 0) {
                clearInterval(resendCountdownInterval);
                btnResend.disabled = false;
                btnResend.innerHTML = 'Resend OTP';
            }
        }, 1000);
    }

    // Reset OTP modal state when opened
    function initializeOtpModal(email, expiresAtMs) {
        if (otpEmailDisplay) otpEmailDisplay.textContent = email;
        if (otpInput) {
            otpInput.value = '';
            otpInput.disabled = false;
            otpInput.classList.remove('is-invalid');
        }
        if (btnVerify) {
            btnVerify.disabled = false;
            btnVerify.innerHTML = 'Verify & Complete Registration';
        }
        clearAlert();
        startExpiryTimer(expiresAtMs);
        startResendCooldown(60);

        if (otpModal) {
            otpModal.show();
            modalElement.addEventListener('shown.bs.modal', () => {
                if (otpInput) otpInput.focus();
            }, { once: true });
        }
    }

    // Format OTP input: strictly numbers only
    if (otpInput) {
        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            this.classList.remove('is-invalid');
            clearAlert();

            // Auto-trigger verification upon typing 6th digit
            if (this.value.length === 6 && btnVerify && !btnVerify.disabled) {
                btnVerify.click();
            }
        });
    }

    // 1. Form submission: Trigger registration & update button UI
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (currentPage < pages.length - 1) {
            nextPage();
            return;
        }

        if (!validateCurrentPage("registering")) return;

        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirm_password");
        if (password.value !== confirmPassword.value) {
            showError("Passwords do not match.", confirmPassword);
            return;
        }

        const emailInput = document.getElementById('email');
        const userEmail = emailInput ? emailInput.value.trim() : '';

        // Immediate Feedback: Disable submit button & show spinner
        const originalBtnText = btnSubmitRegister ? btnSubmitRegister.innerHTML : 'Submit Registration';
        if (btnSubmitRegister) {
            btnSubmitRegister.disabled = true;
            btnSubmitRegister.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending OTP...`;
        }

        const formData = new FormData(form);
        formData.append('action', 'submit_registration');

        try {
            const response = await fetch('registration_controller.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                initializeOtpModal(userEmail, data.expires_at);
            } else {
                showError(data.message);
            }
        } catch (err) {
            showError('An unexpected server error occurred. Please try again.');
        } finally {
            if (btnSubmitRegister) {
                btnSubmitRegister.disabled = false;
                btnSubmitRegister.innerHTML = originalBtnText;
            }
        }
    });

    // 2. OTP Verification Handler (With Lockout Timer & Control UI Fix)
    if (btnVerify) {
        btnVerify.addEventListener('click', async () => {
            const otpCode = otpInput ? otpInput.value.trim() : '';

            if (otpCode.length !== 6) {
                showAlert('Please enter the full 6-digit code.');
                if (otpInput) {
                    otpInput.classList.add('is-invalid');
                    otpInput.focus();
                }
                return;
            }

            // Immediate feedback: Disable button & show spinner
            btnVerify.disabled = true;
            btnVerify.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Verifying...`;
            if (otpInput) otpInput.disabled = true;

            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('otp_code', otpCode);

            try {
                const response = await fetch('registration_controller.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showAlert(data.message, true);
                    clearInterval(expiryCountdownInterval);
                    clearInterval(resendCountdownInterval);

                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    showAlert(data.message, false);

                    // Check for max attempts, lockout, or session expiry
                    const isLockout = data.message.includes('Too many failed attempts') || 
                                      data.message.includes('invalidated') || 
                                      data.message.includes('expired');

                    if (isLockout) {
                        // 1. Stop expiry countdown and show EXPIRED status
                        clearInterval(expiryCountdownInterval);
                        if (otpExpiryTimer) otpExpiryTimer.textContent = "EXPIRED";

                        // 2. Keep text input & verify button disabled to block further inputs
                        if (otpInput) {
                            otpInput.disabled = true;
                            otpInput.value = '';
                            otpInput.classList.add('is-invalid');
                        }
                        btnVerify.disabled = true;
                        btnVerify.innerHTML = 'Verify & Complete Registration';

                        // 3. Immediately stop resend cooldown & enable Resend button for recovery
                        clearInterval(resendCountdownInterval);
                        if (btnResend) {
                            btnResend.disabled = false;
                            btnResend.innerHTML = 'Resend OTP';
                        }
                    } else {
                        // Normal wrong code: Re-enable input for next try
                        if (otpInput) {
                            otpInput.disabled = false;
                            otpInput.value = '';
                            otpInput.classList.add('is-invalid');
                            otpInput.focus();
                        }
                        btnVerify.disabled = false;
                        btnVerify.innerHTML = 'Verify & Complete Registration';
                    }
                }
            } catch (err) {
                showAlert('Verification failed due to a server connection error.');
                if (otpInput) otpInput.disabled = false;
                btnVerify.disabled = false;
                btnVerify.innerHTML = 'Verify & Complete Registration';
            }
        });
    }

    // 3. Resend OTP Handler (Includes fallback email parameter)
    if (btnResend) {
        btnResend.addEventListener('click', async () => {
            btnResend.disabled = true;
            const originalText = btnResend.innerHTML;
            btnResend.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending...`;

            const emailInput = document.getElementById('email');
            const userEmail = emailInput ? emailInput.value.trim() : '';

            const formData = new FormData();
            formData.append('action', 'resend_otp');
            formData.append('email', userEmail);

            try {
                const response = await fetch('registration_controller.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                showAlert(data.message, data.success);

                if (data.success) {
                    if (otpInput) {
                        otpInput.value = '';
                        otpInput.disabled = false;
                        otpInput.focus();
                    }
                    if (btnVerify) btnVerify.disabled = false;
                    
                    // Restart both timers synced with backend timestamp
                    startExpiryTimer(data.expires_at);
                    startResendCooldown(60);
                } else {
                    btnResend.disabled = false;
                    btnResend.innerHTML = originalText;
                }
            } catch (err) {
                showAlert('Failed to resend code. Please try again.');
                btnResend.disabled = false;
                btnResend.innerHTML = originalText;
            }
        });
    }
});