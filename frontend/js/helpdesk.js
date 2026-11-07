document.addEventListener("DOMContentLoaded", () => {
    // FAQ FILTERING & SEARCH
    const filterButtons = document.querySelectorAll("#faq-pills-tab .nav-link");
    let faqCards = document.querySelectorAll(".faq-card");
    const searchInput = document.getElementById("Search");
    let currentCategory = "all";

    filterButtons.forEach(button => {
        button.addEventListener("click", (e) => {
            e.preventDefault(); 
            filterButtons.forEach(btn => btn.classList.remove("active"));
            button.classList.add("active");
            currentCategory = button.getAttribute("data-filter");
            filterFAQs();
        });
    });

    if (searchInput) {
        searchInput.addEventListener("input", () => {
            filterFAQs();
        });
    }

    function filterFAQs() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
        faqCards = document.querySelectorAll(".faq-card");
        
        faqCards.forEach(card => {
            const category = card.getAttribute("data-category");
            const text = card.textContent.toLowerCase();
            const matchesCategory = (currentCategory === "all" || category === currentCategory);
            const matchesSearch = text.includes(query);
            if (matchesCategory && matchesSearch) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    }

  
    // TUTORIAL EDIT

    const saveTutorialBtn = document.getElementById("saveTutorialBtn");
    
    if (saveTutorialBtn) {
        saveTutorialBtn.addEventListener("click", () => {
            const newTitle = document.getElementById("tutorialInputTitle").value;
            const newDesc = document.getElementById("tutorialInputDesc").value;
            const videoInput = document.getElementById("tutorialInputVideo");

            const titleHeading = document.getElementById("displayTitleHeading");
            if (newTitle.trim() !== "" && titleHeading) {
                titleHeading.textContent = newTitle;
            }

            const descParagraph = document.getElementById("displayDesc");
            if (newDesc.trim() !== "" && descParagraph) {
                descParagraph.textContent = newDesc;
            }

            if (videoInput && videoInput.files && videoInput.files[0]) {
                const fileURL = URL.createObjectURL(videoInput.files[0]);
                const videoElement = document.getElementById("displayVideo");
                const sourceElement = document.getElementById("videoSource");
                
                if (sourceElement && videoElement) {
                    sourceElement.src = fileURL;
                    videoElement.load(); 
                }
            }
        });
    }

    const saveAddFaqBtn = document.getElementById("saveAddFaqBtn");
    if (saveAddFaqBtn) {
        saveAddFaqBtn.addEventListener("click", () => {
            const title = document.getElementById("addInputTitle").value.trim();
            const details = document.getElementById("addInputDetails").value.trim();
            const desc = document.getElementById("addInputDesc").value.trim();

            if (title === "") return;

            const faqContainer = document.querySelector(".faq-container");
            const uniqueId = "dynamicFaq_" + Date.now();

            if (faqContainer) {
                const cardDiv = document.createElement("div");
                cardDiv.className = "card card-body shadow-sm mb-3 cursor-pointer faq-card";
                cardDiv.setAttribute("data-category", "account");
                cardDiv.setAttribute("data-bs-toggle", "modal");
                cardDiv.setAttribute("data-bs-target", `#${uniqueId}`);

                cardDiv.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-semibold mb-0">${escapeHtml(title)}</h5>
                        <span class="fs-5">&gt;</span>
                    </div>
                `;
                faqContainer.appendChild(cardDiv);

                const modalDiv = document.createElement("div");
                modalDiv.className = "modal fade";
                modalDiv.id = uniqueId;
                modalDiv.tabIndex = "-1";
                modalDiv.setAttribute("aria-hidden", "true");

                modalDiv.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header" style="background-color: var(--main-color);">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" style="background-color: var(--main-bg-color);">
                                <h5 class="modal-title fw-bold">${escapeHtml(title)}</h5>
                                <p class="text-muted fw-semibold mb-2 faq-details" style="${details ? '' : 'display:none;'}">${escapeHtml(details)}</p>
                                <p class="faq-desc">${escapeHtml(desc)}</p>
                            </div>
                            <div class="modal-footer border-0 d-flex justify-content-between align-items-center" style="background-color: var(--main-bg-color);">
                                <div class="flex-shrink-0">
                                    <button class="btn btn-sm rounded px-3 text-white" style="background-color: var(--btn);" data-bs-toggle="modal" data-bs-target="#editfaqModal">
                                        <i class="bi bi-pencil-square me-1"></i> Edit content
                                    </button>
                                </div>
                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modalDiv);
                filterFAQs();
            }
        });
    }

    let activeModalId = null;
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('show.bs.modal', () => {
            activeModalId = modal.id;
        });
    });

    const editFaqModalEl = document.getElementById('editfaqModal');
    if (editFaqModalEl) {
        editFaqModalEl.addEventListener('show.bs.modal', () => {
            if (activeModalId && activeModalId !== "tutorialModal" && activeModalId !== "faqModal") {
                const activeFaqModal = document.getElementById(activeModalId);
                if (activeFaqModal) {
                    const titleEl = activeFaqModal.querySelector(".modal-title");
                    const detailsEl = activeFaqModal.querySelector(".faq-details");
                    const descEl = activeFaqModal.querySelector(".faq-desc");

                    document.getElementById("editInputTitle").value = titleEl ? titleEl.textContent.trim() : "";
                    document.getElementById("editInputDetails").value = detailsEl ? detailsEl.textContent.trim() : "";
                    document.getElementById("editInputDesc").value = descEl ? descEl.textContent.trim() : "";
                }
            }
        });
    }

    const saveEditFaqBtn = document.getElementById("saveEditFaqBtn");
    if (saveEditFaqBtn) {
        saveEditFaqBtn.addEventListener("click", () => {
            const updatedTitle = document.getElementById("editInputTitle").value.trim();
            const updatedDetails = document.getElementById("editInputDetails").value.trim();
            const updatedDesc = document.getElementById("editInputDesc").value.trim();

            if (activeModalId && activeModalId !== "tutorialModal" && activeModalId !== "faqModal") {
                const activeFaqModal = document.getElementById(activeModalId);
                if (activeFaqModal) {
                    const activeFaqTitle = activeFaqModal.querySelector(".modal-title");
                    const activeFaqDetails = activeFaqModal.querySelector(".faq-details");
                    const activeFaqDesc = activeFaqModal.querySelector(".faq-desc");
                    const activeCardTitle = document.querySelector(`[data-bs-target="#${activeModalId}"] h5`);

                    if (updatedTitle !== "" && activeCardTitle) {
                        activeCardTitle.textContent = updatedTitle;
                    }
                    if (updatedTitle !== "" && activeFaqTitle) {
                        activeFaqTitle.textContent = updatedTitle;
                    }
                    if (activeFaqDetails) {
                        activeFaqDetails.textContent = updatedDetails;
                        activeFaqDetails.style.display = updatedDetails !== "" ? "block" : "none";
                    }
                    if (updatedDesc !== "" && activeFaqDesc) {
                        activeFaqDesc.textContent = updatedDesc;
                    }
                }
            }
        });
    }
});

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
