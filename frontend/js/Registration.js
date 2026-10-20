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

    // Error modal
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

    // Steps
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

            // Flatpickr hides the original birthday input, so highlight the visible copy
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
            showError("Passwords do not match.", confirmPassword);
        }
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
            eye.classList.toggle("bi-eye", !show);
            eye.classList.toggle("bi-eye-slash", show);
        });
    });

    if (errorModalEl.dataset.message) {
        showServerMessage(
            errorModalEl.dataset.message,
            errorModalEl.dataset.type,
            errorModalEl.dataset.redirect
        );
    }

    showPage();
});