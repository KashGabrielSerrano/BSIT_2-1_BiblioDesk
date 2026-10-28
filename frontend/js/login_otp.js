document.addEventListener("DOMContentLoaded", function () {
    const modalElement    = document.getElementById('otpModal');
    const otpModal        = modalElement ? new bootstrap.Modal(modalElement) : null;
    const otpAlert        = document.getElementById('otpAlert');
    const otpInput        = document.getElementById('otpCodeInput');
    const otpExpiryTimer  = document.getElementById('otpExpiryTimer');
    const btnVerify       = document.getElementById('btnVerifyOtp');
    const btnResend       = document.getElementById('btnResendOtp');

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

    if (otpModal) {
        otpModal.show();
        if (typeof window.otpExpiresAtMs !== 'undefined') {
            startExpiryTimer(window.otpExpiresAtMs);
        } else {
            startExpiryTimer(null);
        }
        startResendCooldown(60);
        if (otpInput) otpInput.focus();
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