let currentPage = 0;

const pages = document.querySelectorAll(".page");

function showPage() {
    pages.forEach(function(page) {
        page.classList.remove("active");
    });

    pages[currentPage].classList.add("active");
}

function nextPage() {

    const inputs = pages[currentPage].querySelectorAll("input");

    for (let input of inputs) {

        if (input.hasAttribute("required") && input.value.trim() === "") {

            alert("Please fill in all required fields before proceeding.");

            input.focus();

            return;
        }

        if (input.type === "email" && input.value.trim() !== "") {

            if (!input.checkValidity()) {

                alert("Please enter a valid email address.");

                input.focus();

                return;
            }
        }
    }

    if (currentPage < pages.length - 1) {

        currentPage++;

        showPage();
    }
}

function previousPage() {

    if (currentPage > 0) {

        currentPage--;

        showPage();
    }
}

function register(event) {

    const inputs = pages[currentPage].querySelectorAll("input");

    for (let input of inputs) {

        if (input.hasAttribute("required") && input.value.trim() === "") {

            event.preventDefault();
            alert("Please fill in all required fields before registering.");

            input.focus();

            return;
        }
    }

    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    if (password !== confirmPassword) {

        event.preventDefault();
        alert("Passwords do not match.");

        document.getElementById("confirmPassword").focus();

        return;
    }
    document.getElementById("registrationForm").submit();
}