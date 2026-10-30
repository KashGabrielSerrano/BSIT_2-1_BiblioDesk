/**
 * BiblioDesk LMS - Portal Login, Forgot Password & OTP Verification
 * Consolidated client-side authentication controller.
 */

document.addEventListener('DOMContentLoaded', () => {

    /* =========================================================================
     * MODULE 1: MAIN USER LOGIN FUNCTIONALITY
     * ========================================================================= */
    (() => {
        const loginForm     = document.getElementById('loginForm');
        if (!loginForm) return;

        const emailInput    = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const rememberInput = document.getElementById('checkLabel');
        const btnLogin      = document.getElementById('btnLogin');
        const alertBox      = document.getElementById('loginAlert');
        const alertText     = document.getElementById('loginAlertText');

        function showAlert(msg) {
            if (!alertText || !alertBox) return;
            alertText.textContent = msg;
            alertBox.classList.remove('d-none');
            alertBox.classList.add('d-flex');
        }

        function hideAlert() {
            if (!alertBox) return;
            alertBox.classList.add('d-none');
            alertBox.classList.remove('d-flex');
        }

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();

            const email    = emailInput ? emailInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value : '';

            if (!email || !password) {
                showAlert('Please fill in all security fields.');
                return;
            }

            if (btnLogin) {
                btnLogin.disabled = true;
                btnLogin.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Logging in...`;
            }

            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);
            if (rememberInput && rememberInput.checked) {
                formData.append('remember', '1');
            }

            try {
                const res  = await fetch('login_controller.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    if (data.requires_otp) {
                        window.otpExpiresAtMs = data.expires_at_ms;
                        const displayEl = document.getElementById('otpEmailDisplay');
                        if (displayEl) displayEl.textContent = data.email;

                        const otpModalEl = document.getElementById('otpModal');
                        if (otpModalEl && typeof bootstrap !== 'undefined') {
                            const modal = bootstrap.Modal.getOrCreateInstance(otpModalEl);
                            modal.show();

                            if (typeof window.startLoginOtpTimer === 'function') {
                                window.startLoginOtpTimer(data.expires_at_ms);
                            }
                        }
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    showAlert(data.message || 'Login failed. Please check your credentials.');
                }
            } catch (err) {
                showAlert('Server connection error. Please try again.');
            } finally {
                if (btnLogin) {
                    btnLogin.disabled = false;
                    btnLogin.textContent = 'Login';
                }
            }
        });
    })();


    /* =========================================================================
     * MODULE 2: FORGOT PASSWORD FLOW (EMAIL OTP VERIFICATION & PASSWORD RESET)
     * ========================================================================= */
    (() => {
        const modalEl = document.getElementById('forgotModal');
        if (!modalEl) return;

        const alertBox        = document.getElementById('fpAlert');
        const emailInput      = document.getElementById('fpEmail');
        const otpInput        = document.getElementById('fpOtpInput');
        const emailDisplay    = document.getElementById('fpEmailDisplay');
        const expiryTimer     = document.getElementById('fpExpiryTimer');
        const newPassword     = document.getElementById('fpNewPassword');
        const confirmPassword = document.getElementById('fpConfirmPassword');
        const titleIcon       = document.getElementById('fpTitleIcon');
        const titleText       = document.getElementById('fpTitleText');

        const btnSend         = document.getElementById('fpSendBtn');
        const btnVerify       = document.getElementById('fpVerifyBtn');
        const btnReset        = document.getElementById('fpResetBtn');
        const btnResend       = document.getElementById('fpResendBtn');
        const btnChangeEmail  = document.getElementById('fpChangeEmail');

        const ruleLength      = document.getElementById('fpRuleLength');
        const ruleMatch       = document.getElementById('fpRuleMatch');

        const titles = {
            1: { text: 'Forgot Password', icon: 'bi-key-fill' },
            2: { text: 'Email Verification', icon: 'bi-shield-check' },
            3: { text: 'Reset Password', icon: 'bi-lock-fill' }
        };

        let currentStep    = 1;
        let expiryInterval = null;
        let resendInterval = null;

        async function sendControllerRequest(action, extraData = {}) {
            const formData = new FormData();
            formData.append('action', action);
            for (const key in extraData) {
                formData.append(key, extraData[key]);
            }
            try {
                const response = await fetch('login_controller.php', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (err) {
                return { success: false, message: 'Server connection error. Please try again.' };
            }
        }

        function showAlert(msg, isSuccess = false) {
            if (!alertBox) return;
            alertBox.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'} py-2 px-3 small rounded-3`;
            alertBox.textContent = msg;
            alertBox.classList.remove('d-none');
        }

        function clearAlert() {
            if (!alertBox) return;
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }

        function setLoading(btn, text) {
            if (!btn) return;
            btn.dataset.original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${text}`;
        }

        function resetBtn(btn) {
            if (!btn) return;
            btn.disabled = false;
            if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
            btn.blur();
        }

        function goToStep(step) {
            currentStep = step;

            modalEl.querySelectorAll('[data-step]').forEach(el => {
                el.classList.toggle('d-none', Number(el.dataset.step) !== step);
            });

            if (btnSend) btnSend.classList.toggle('d-none', step !== 1);
            if (btnVerify) btnVerify.classList.toggle('d-none', step !== 2);
            if (btnReset) btnReset.classList.toggle('d-none', step !== 3);

            if (titleIcon && titles[step]) titleIcon.className = `bi ${titles[step].icon} me-2 fs-4`;
            if (titleText && titles[step]) titleText.textContent = titles[step].text;
            clearAlert();

            const target = { 1: emailInput, 2: otpInput, 3: newPassword }[step];
            setTimeout(() => target && target.focus(), 100);
        }

        function startExpiryTimer(expiryTimestampMs) {
            clearInterval(expiryInterval);

            function tick() {
                const remaining = Math.max(0, Math.floor((expiryTimestampMs - Date.now()) / 1000));
                const m = String(Math.floor(remaining / 60)).padStart(2, '0');
                const s = String(remaining % 60).padStart(2, '0');
                if (expiryTimer) expiryTimer.textContent = `${m}:${s}`;

                if (remaining <= 0) {
                    clearInterval(expiryInterval);
                    showAlert('Your verification code has expired. Please request a new one.');
                    if (btnVerify) btnVerify.disabled = true;
                    if (otpInput) otpInput.disabled = true;
                }
            }
            tick();
            expiryInterval = setInterval(tick, 1000);
        }

        function startResendCooldown(seconds = 60) {
            clearInterval(resendInterval);
            if (!btnResend) return;

            btnResend.disabled = true;
            let left = seconds;
            btnResend.innerHTML = `Resend code in <span id="fpResendTimer">${left}</span>s`;

            resendInterval = setInterval(() => {
                left--;
                const span = document.getElementById('fpResendTimer');
                if (span) span.textContent = left;
                if (left <= 0) {
                    clearInterval(resendInterval);
                    btnResend.disabled = false;
                    btnResend.innerHTML = 'Resend OTP';
                }
            }, 1000);
        }

        function stopTimers() {
            clearInterval(expiryInterval);
            clearInterval(resendInterval);
        }

        function resetModal() {
            stopTimers();
            if (emailInput) emailInput.value = '';
            if (otpInput) {
                otpInput.value = '';
                otpInput.disabled = false;
            }
            if (newPassword) newPassword.value = '';
            if (confirmPassword) confirmPassword.value = '';
            [emailInput, otpInput, newPassword, confirmPassword].forEach(el => el && el.classList.remove('is-invalid'));
            [btnSend, btnVerify, btnReset].forEach(resetBtn);
            if (btnVerify) btnVerify.disabled = false;
            updateRules();
            goToStep(1);
        }

        modalEl.addEventListener('shown.bs.modal', () => emailInput && emailInput.focus());
        modalEl.addEventListener('hidden.bs.modal', resetModal);

        // Step 1: Send OTP
        if (btnSend) {
            btnSend.addEventListener('click', async () => {
                const email = emailInput ? emailInput.value.trim() : '';
                const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

                if (!valid) {
                    if (emailInput) {
                        emailInput.classList.add('is-invalid');
                        emailInput.focus();
                    }
                    showAlert('Please enter a valid email address.');
                    return;
                }

                setLoading(btnSend, 'Sending code...');
                const data = await sendControllerRequest('fp_send_otp', { email });
                resetBtn(btnSend);

                if (data.success) {
                    if (emailDisplay) emailDisplay.textContent = email;
                    if (otpInput) {
                        otpInput.value = '';
                        otpInput.disabled = false;
                    }
                    if (btnVerify) btnVerify.disabled = false;
                    startExpiryTimer(data.expires_at || Date.now() + 300000);
                    startResendCooldown(60);
                    goToStep(2);
                    showAlert(data.message || 'Verification code sent.', true);
                } else {
                    showAlert(data.message || 'Failed to send code. Please try again.');
                }
            });
        }

        if (emailInput) {
            emailInput.addEventListener('input', () => {
                emailInput.classList.remove('is-invalid');
                clearAlert();
            });

            emailInput.addEventListener('keydown', e => {
                if (e.key === 'Enter' && btnSend) btnSend.click();
            });
        }

        // Step 2: Verify OTP
        if (otpInput) {
            otpInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                this.classList.remove('is-invalid');
                clearAlert();

                if (this.value.length === 6 && btnVerify && !btnVerify.disabled) {
                    btnVerify.click();
                }
            });
        }

        if (btnVerify) {
            btnVerify.addEventListener('click', async () => {
                const code = otpInput ? otpInput.value.trim() : '';

                if (code.length !== 6) {
                    showAlert('Please enter the full 6-digit code.');
                    if (otpInput) {
                        otpInput.classList.add('is-invalid');
                        otpInput.focus();
                    }
                    return;
                }

                setLoading(btnVerify, 'Verifying...');
                if (otpInput) otpInput.disabled = true;

                const data = await sendControllerRequest('fp_verify_otp', { otp_code: code });
                resetBtn(btnVerify);

                if (data.success) {
                    stopTimers();
                    goToStep(3);
                } else {
                    if (otpInput) {
                        otpInput.disabled = false;
                        otpInput.classList.add('is-invalid');
                        otpInput.focus();
                    }
                    showAlert(data.message || 'Invalid or expired verification code.');
                }
            });
        }

        // Step 2: Resend OTP
        if (btnResend) {
            btnResend.addEventListener('click', async () => {
                btnResend.disabled = true;
                btnResend.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending...`;

                const data = await sendControllerRequest('fp_resend_otp');

                if (data.success) {
                    showAlert(data.message || 'A new code has been sent to your email.', true);
                    if (otpInput) {
                        otpInput.value = '';
                        otpInput.disabled = false;
                        otpInput.focus();
                    }
                    if (btnVerify) btnVerify.disabled = false;
                    startExpiryTimer(data.expires_at || Date.now() + 300000);
                    startResendCooldown(60);
                } else {
                    showAlert(data.message || 'Failed to resend code. Please try again.');
                    btnResend.disabled = false;
                    btnResend.innerHTML = 'Resend OTP';
                }
            });
        }

        if (btnChangeEmail) {
            btnChangeEmail.addEventListener('click', () => {
                stopTimers();
                goToStep(1);
            });
        }

        // Step 3.5: Password Rules Validation
        function setRule(el, ok) {
            if (!el) return;
            el.classList.toggle('ok', ok);
            const icon = el.querySelector('i');
            if (icon) icon.className = `bi ${ok ? 'bi-check-circle-fill' : 'bi-circle'} me-2`;
        }

        function updateRules() {
            const pw = newPassword ? newPassword.value : '';
            const cf = confirmPassword ? confirmPassword.value : '';
            setRule(ruleLength, pw.length >= 8);
            setRule(ruleMatch, pw.length > 0 && pw === cf);
        }

        function onPasswordInput() {
            if (newPassword) newPassword.classList.remove('is-invalid');
            if (confirmPassword) confirmPassword.classList.remove('is-invalid');
            clearAlert();
            updateRules();
        }

        if (newPassword) newPassword.addEventListener('input', onPasswordInput);
        if (confirmPassword) confirmPassword.addEventListener('input', onPasswordInput);

        // Step 4: Reset Password Submission
        if (btnReset) {
            btnReset.addEventListener('click', async () => {
                const pw = newPassword ? newPassword.value : '';
                const cf = confirmPassword ? confirmPassword.value : '';

                if (pw.length < 8) {
                    if (newPassword) {
                        newPassword.classList.add('is-invalid');
                        newPassword.focus();
                    }
                    showAlert('Password must be at least 8 characters.');
                    return;
                }
                if (pw !== cf) {
                    if (confirmPassword) {
                        confirmPassword.classList.add('is-invalid');
                        confirmPassword.focus();
                    }
                    showAlert('Passwords do not match.');
                    return;
                }

                setLoading(btnReset, 'Saving...');
                const data = await sendControllerRequest('fp_reset_password', {
                    new_password: pw,
                    confirm_password: cf
                });

                if (data.success) {
                    showAlert(data.message || 'Password updated! You can now log in.', true);
                    setTimeout(() => {
                        if (typeof bootstrap !== 'undefined') {
                            const modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) modalInstance.hide();
                        }
                    }, 1500);
                } else {
                    resetBtn(btnReset);
                    showAlert(data.message || 'Failed to reset password. Please try again.');
                }
            });
        }
        goToStep(1);
    })();


    /* =========================================================================
     * MODULE 3: UNVERIFIED USER OTP VERIFICATION MODAL
     * ========================================================================= */
    (() => {
        const modalElement    = document.getElementById('otpModal');
        const otpModal        = modalElement && typeof bootstrap !== 'undefined'
            ? bootstrap.Modal.getOrCreateInstance(modalElement)
            : null;
        const otpAlert        = document.getElementById('otpAlert');
        const otpInput        = document.getElementById('otpCodeInput');
        const otpExpiryTimer  = document.getElementById('otpExpiryTimer');
        const otpEmailDisplay = document.getElementById('otpEmailDisplay');
        const btnVerify       = document.getElementById('btnVerifyOtp');
        const btnResend       = document.getElementById('btnResendOtp');

        let expiryCountdownInterval = null;
        let resendCountdownInterval = null;

        function showAlert(msg, isSuccess = false) {
            if (!otpAlert) return;
            otpAlert.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'} py-2 px-3 small rounded-3`;
            otpAlert.textContent = msg;
            otpAlert.classList.remove('d-none');
        }

        function clearAlert() {
            if (!otpAlert) return;
            otpAlert.classList.add('d-none');
            otpAlert.textContent = '';
        }

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

        // Export helper for login modal auto-launch
        window.startLoginOtpTimer = (expiresAtMs) => {
            clearAlert();
            if (otpInput) {
                otpInput.value = '';
                otpInput.disabled = false;
            }
            if (btnVerify) btnVerify.disabled = false;
            startExpiryTimer(expiresAtMs);
            startResendCooldown(60);
            if (otpInput) setTimeout(() => otpInput.focus(), 150);
        };

        // Auto-launch modal ONLY if user is strictly marked as pending with a valid session email
        if (otpModal && window.isPendingUser === true && window.pendingEmail) {
            if (otpEmailDisplay) {
                otpEmailDisplay.textContent = window.pendingEmail;
            }
            otpModal.show();
            const expiryMs = typeof window.otpExpiresAtMs !== 'undefined' ? window.otpExpiresAtMs : null;
            startExpiryTimer(expiryMs);
            startResendCooldown(60);
            if (otpInput) setTimeout(() => otpInput.focus(), 150);
        }

        if (otpInput) {
            otpInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                this.classList.remove('is-invalid');
                clearAlert();

                if (this.value.length === 6 && btnVerify && !btnVerify.disabled) {
                    btnVerify.click();
                }
            });
        }

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
                        const isLockout = data.message.includes('Too many failed attempts') ||
                                          data.message.includes('invalidated') ||
                                          data.message.includes('expired');

                        if (isLockout) {
                            clearInterval(expiryCountdownInterval);
                            if (otpExpiryTimer) otpExpiryTimer.textContent = "EXPIRED";
                            if (otpInput) {
                                otpInput.disabled = true;
                                otpInput.value = '';
                                otpInput.classList.add('is-invalid');
                            }
                            btnVerify.disabled = true;
                            btnVerify.innerHTML = 'Verify & Complete Registration';
                            clearInterval(resendCountdownInterval);
                            if (btnResend) {
                                btnResend.disabled = false;
                                btnResend.innerHTML = 'Resend OTP';
                            }
                        } else {
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

        if (btnResend) {
            btnResend.addEventListener('click', async () => {
                btnResend.disabled = true;
                const originalText = btnResend.innerHTML;
                btnResend.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending...`;

                const emailInput = document.getElementById('email');
                const userEmail = (emailInput && emailInput.value.trim()) || window.pendingEmail || '';

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
    })();


    /* =========================================================================
     * MODULE 4: PASSWORD VISIBILITY TOGGLE (GLOBAL HELPER) (Gawa ni Claire Princess Arcino)
     * ========================================================================= */
    (() => {
        document.querySelectorAll('.password-toggle-icon').forEach(icon => {
            icon.addEventListener('click', () => {
                const targetId = icon.dataset.target;
                const input    = targetId ? document.getElementById(targetId) : icon.closest('.password-container')?.querySelector('input');
                if (!input) return;

                const eye  = icon.querySelector('i');
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';

                if (eye) {
                    eye.classList.toggle('bi-eye', show);
                    eye.classList.toggle('bi-eye-slash', !show);
                }
            });
        });
    })();
});
