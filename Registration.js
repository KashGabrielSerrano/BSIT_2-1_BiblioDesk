let currentPage = 0;

const pages = document.querySelectorAll(".page");

function showPage() {
    pages.forEach(function(page) {
        page.classList.remove("active");
    });

    pages[currentPage].classList.add("active");
}

function nextPage() {
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
    alert("Registration submitted!");
}