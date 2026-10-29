document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('forgotModal');
    if (!modalEl) return;

    const alertBox = document.getElementById('fpAlert');
    const emailInput = document.getElementById('fpEmail');
    const otpInput = document.getElementById('fpOtpInput');
    const emailDisplay = document.getElementById('fpEmailDisplay');
    const expiryTimer = document.getElementById('fpExpiryTimer');
    const newPassword = document.getElementById('fpNewPassword');
    const confirmPassword = document.getElementById('fpConfirmPassword');
    const titleIcon = document.getElementById('fpTitleIcon');
    const titleText = document.getElementById('fpTitleText');

    const btnSend = document.getElementById('fpSendBtn');
    const btnVerify = document.getElementById('fpVerifyBtn');
    const btnReset = document.getElementById('fpResetBtn');
    const btnResend = document.getElementById('fpResendBtn');
    const btnChangeEmail = document.getElementById('fpChangeEmail');

    const ruleLength = document.getElementById('fpRuleLength');
    const ruleMatch = document.getElementById('fpRuleMatch');

    const titles = {
        1: {  text: 'Forgot Password' },
        2: {  text: 'Email Verification' },
        3: { text: 'Reset Password' }
    };

    let currentStep = 1;
    let expiryInterval = null;
    let resendInterval = null;

    function fakeRequest(ms = 800) {
        return new Promise(resolve => setTimeout(() => resolve({ success: true }), ms));
    }

    function showAlert(msg, isSuccess = false) {
        alertBox.className = `alert ${isSuccess ? 'alert-success' : 'alert-danger'} py-2 px-3 small rounded-3`;
        alertBox.textContent = msg;
    }

    function clearAlert() {
        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    function setLoading(btn, text) {
        btn.dataset.original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${text}`;
    }
    
    function resetBtn(btn) {
        btn.disabled = false;
        if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
        btn.blur(); 
    }

    function goToStep(step) {
        currentStep = step;

        modalEl.querySelectorAll('[data-step]').forEach(el => {
            el.classList.toggle('d-none', Number(el.dataset.step) !== step);
        });

        modalEl.querySelectorAll('[data-dot]').forEach(dot => {
            dot.classList.toggle('active', Number(dot.dataset.dot) <= step);
        });

        titleIcon.className = `bi ${titles[step].icon} me-2 fs-4`;
        titleText.textContent = titles[step].text;
        clearAlert();

        const target = { 1: emailInput, 2: otpInput, 3: newPassword }[step];
        setTimeout(() => target && target.focus(), 100);
    }

    // Timers
    function startExpiryTimer(seconds = 300) {
        clearInterval(expiryInterval);
        const target = Date.now() + seconds * 1000;

        function tick() {
            const remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));
            const m = String(Math.floor(remaining / 60)).padStart(2, '0');
            const s = String(remaining % 60).padStart(2, '0');
            expiryTimer.textContent = `${m}:${s}`;

            if (remaining <= 0) {
                clearInterval(expiryInterval);
                showAlert('Your verification code has expired. Please request a new one.');
                btnVerify.disabled = true;
                otpInput.disabled = true;
            }
        }
        tick();
        expiryInterval = setInterval(tick, 1000);
    }

    function startResendCooldown(seconds = 60) {
        clearInterval(resendInterval);
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
        emailInput.value = '';
        otpInput.value = '';
        otpInput.disabled = false;
        newPassword.value = '';
        confirmPassword.value = '';
        [emailInput, otpInput, newPassword, confirmPassword].forEach(el => el.classList.remove('is-invalid'));
        [btnSend, btnVerify, btnReset].forEach(resetBtn);
        btnVerify.disabled = false;
        updateRules();
        goToStep(1);
    }

    modalEl.addEventListener('shown.bs.modal', () => emailInput.focus());
    modalEl.addEventListener('hidden.bs.modal', resetModal);

    // Send code
    btnSend.addEventListener('click', async () => {
        const email = emailInput.value.trim();
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

        if (!valid) {
            emailInput.classList.add('is-invalid');
            showAlert('Please enter a valid email address.');
            emailInput.focus();
            return;
        }

        setLoading(btnSend, 'Sending code...');

        const data = await fakeRequest();

        resetBtn(btnSend);

        if (data.success) {
            emailDisplay.textContent = email;
            otpInput.value = '';
            otpInput.disabled = false;
            btnVerify.disabled = false;
            startExpiryTimer(300);
            startResendCooldown(60);
            goToStep(2);
        } else {
            showAlert('Failed to send code. Please try again.');
        }
    });

    emailInput.addEventListener('input', () => {
        emailInput.classList.remove('is-invalid');
        clearAlert();
    });

    emailInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') btnSend.click();
    });

    // Verify OTP
    otpInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
        this.classList.remove('is-invalid');
        clearAlert();

        if (this.value.length === 6 && !btnVerify.disabled) {
            btnVerify.click();
        }
    });

    btnVerify.addEventListener('click', async () => {
        const code = otpInput.value.trim();

        if (code.length !== 6) {
            showAlert('Please enter the full 6-digit code.');
            otpInput.classList.add('is-invalid');
            otpInput.focus();
            return;
        }

        setLoading(btnVerify, 'Verifying...');
        otpInput.disabled = true;

        const data = await fakeRequest();

        resetBtn(btnVerify);

        if (data.success) {
            stopTimers();
            goToStep(3);
        } else {
            otpInput.disabled = false;
            otpInput.value = '';
            otpInput.classList.add('is-invalid');
            showAlert('Invalid or expired OTP code.');
            otpInput.focus();
        }
    });

    btnResend.addEventListener('click', async () => {
        btnResend.disabled = true;
        btnResend.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending...`;

        const data = await fakeRequest();

        if (data.success) {
            showAlert('A new code has been sent to your email.', true);
            otpInput.value = '';
            otpInput.disabled = false;
            btnVerify.disabled = false;
            otpInput.focus();
            startExpiryTimer(300);
            startResendCooldown(60);
        } else {
            showAlert('Failed to resend code. Please try again.');
            btnResend.disabled = false;
            btnResend.innerHTML = 'Resend OTP';
        }
    });

    btnChangeEmail.addEventListener('click', () => {
        stopTimers();
        goToStep(1);
    });

    // New password
    function setRule(el, ok) {
        el.classList.toggle('ok', ok);
        el.querySelector('i').className = `bi ${ok ? 'bi-check-circle-fill' : 'bi-circle'} me-2`;
    }

    function updateRules() {
        const pw = newPassword.value;
        const cf = confirmPassword.value;
        setRule(ruleLength, pw.length >= 8);
        setRule(ruleMatch, pw.length > 0 && pw === cf);
    }

    function onPasswordInput() {
        newPassword.classList.remove('is-invalid');
        confirmPassword.classList.remove('is-invalid');
        clearAlert();
        updateRules();
    }

    newPassword.addEventListener('input', onPasswordInput);
    confirmPassword.addEventListener('input', onPasswordInput);

    btnReset.addEventListener('click', async () => {
        const pw = newPassword.value;
        const cf = confirmPassword.value;

        if (pw.length < 8) {
            newPassword.classList.add('is-invalid');
            showAlert('Password must be at least 8 characters.');
            newPassword.focus();
            return;
        }
        if (pw !== cf) {
            confirmPassword.classList.add('is-invalid');
            showAlert('Passwords do not match.');
            confirmPassword.focus();
            return;
        }

        setLoading(btnReset, 'Saving...');

        const data = await fakeRequest();

        if (data.success) {
            showAlert('Password updated! You can now log in.', true);
            setTimeout(() => bootstrap.Modal.getInstance(modalEl).hide(), 1500);
        } else {
            resetBtn(btnReset);
            showAlert('Failed to reset password. Please try again.');
        }
    });

    // Show / hide password
    document.querySelectorAll('.login-container .password-toggle-icon, #forgotModal .password-toggle-icon').forEach(icon => {
        icon.addEventListener('click', () => {
            const input = document.getElementById(icon.dataset.target);
            if (!input) return;
            const eye = icon.querySelector('i');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            eye.classList.toggle('bi-eye', show);
            eye.classList.toggle('bi-eye-slash', !show);
        });
    });

    goToStep(1);
});