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

        // Check required fields
        if (input.hasAttribute("required") && input.value.trim() === "") {

            alert("Please fill in all required fields before proceeding.");

            input.focus();

            return;
        }

        // Check email
        if (input.type === "email" && input.value.trim() !== "") {

            if (!input.checkValidity()) {

                alert("Please enter a valid email address.");

                input.focus();

                return;
            }
        }
    }

    // Go to next page
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

function register() {

    const inputs = pages[currentPage].querySelectorAll("input");

    // Check required fields
    for (let input of inputs) {

        if (input.hasAttribute("required") && input.value.trim() === "") {

            alert("Please fill in all required fields before registering.");

            input.focus();

            return;
        }
    }

    // Check password and confirm password
    const password = inputs[0].value;
    const confirmPassword = inputs[1].value;

    if (password !== confirmPassword) {

        alert("Passwords do not match.");

        inputs[1].focus();

        return;
    }

    alert("Registration submitted!");
}