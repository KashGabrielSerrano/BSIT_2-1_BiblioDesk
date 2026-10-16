document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("registrationForm");
    const pages = document.querySelectorAll(".form-page");
    const stepIndicator = document.getElementById("stepIndicator");
    let currentPage = 0;

    if (!form || pages.length === 0) {
        console.error("Registration.js: form or .form-page elements not found.");
        return;
    }

    function showPage() {
        pages.forEach(function (page) { page.classList.remove("active"); });
        pages[currentPage].classList.add("active");
        stepIndicator.textContent = "Step " + (currentPage + 1) + " of " + pages.length;
    }

    function validateCurrentPage(actionWord) {
        const inputs = pages[currentPage].querySelectorAll("input, select");
        for (const input of inputs) {
            if (input.tagName === "INPUT" && input.type !== "password") input.value = input.value.trim();

            if (input.hasAttribute("required") && input.value === "") {
                alert("Please fill in all required fields before " + actionWord + ".");
                input.focus();
                return false;
            }
            if (input.value !== "" && !input.checkValidity()) {
                alert(input.title || input.validationMessage || "Please check the highlighted field.");
                input.focus();
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

    // CHANGED: Previous on the first page now goes back to the login page
    function previousPage() {
        if (currentPage === 0) {
            window.location.href = "login.php";
            return;
        }
        currentPage--;
        showPage();
    }

    // Buttons
    document.querySelectorAll('[data-action="next"]').forEach(function (b) {
        b.addEventListener("click", nextPage);
    });
    document.querySelectorAll('[data-action="prev"]').forEach(function (b) {
        b.addEventListener("click", previousPage);
    });

    // Submit (Enter key on earlier pages goes to next step instead of submitting)
    form.addEventListener("submit", function (event) {
        if (currentPage < pages.length - 1) {
            event.preventDefault();
            nextPage();
            return;
        }
        if (!validateCurrentPage("registering")) {
            event.preventDefault();
            return;
        }
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirm_password");
        if (password.value !== confirmPassword.value) {
            event.preventDefault();
            alert("Passwords do not match.");
            confirmPassword.focus();
        }
        // otherwise the normal submit continues so $_POST['btnRegister'] is sent
    });

    // Input formatting
    document.getElementById("student_id").addEventListener("input", function () {
        this.value = this.value.toUpperCase();
    });
    document.getElementById("otp").addEventListener("input", function () {
        this.value = this.value.replace(/\D/g, "").slice(0, 6);
    });

    // Show / hide password (eye icon)
    document.querySelectorAll(".toggle-password").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const input = document.getElementById(btn.dataset.target);
            const show = input.type === "password";
            input.type = show ? "text" : "password";
            btn.classList.toggle("is-showing", show);
            btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
        });
    });

    // NEW (display only): the 6 OTP boxes write their digits into the original #otp field,
    // so validation and the value posted to PHP work exactly as before.
    const otpInput = document.getElementById("otp");
    const otpBoxes = Array.from(document.querySelectorAll(".otp-box"));

    function syncOtp() {
        otpInput.value = otpBoxes.map(function (b) { return b.value; }).join("");
    }

    otpBoxes.forEach(function (box, i) {
        box.addEventListener("focus", function () { box.select(); });
        box.addEventListener("input", function () {
            box.value = box.value.replace(/\D/g, "").slice(-1);
            syncOtp();
            if (box.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
        });
        box.addEventListener("keydown", function (e) {
            if (e.key === "Backspace" && box.value === "" && i > 0) {
                e.preventDefault();
                otpBoxes[i - 1].value = "";
                syncOtp();
                otpBoxes[i - 1].focus();
            }
        });
        box.addEventListener("paste", function (e) {
            e.preventDefault();
            const digits = (e.clipboardData.getData("text") || "").replace(/\D/g, "").slice(0, otpBoxes.length);
            digits.split("").forEach(function (d, k) { otpBoxes[k].value = d; });
            syncOtp();
            otpBoxes[Math.min(digits.length, otpBoxes.length - 1)].focus();
        });
    });

    // If validation focuses the hidden #otp field, send the cursor to the first empty box
    otpInput.addEventListener("focus", function () {
        const target = otpBoxes.find(function (b) { return b.value === ""; }) || otpBoxes[0];
        target.focus();
    });

    showPage();
});