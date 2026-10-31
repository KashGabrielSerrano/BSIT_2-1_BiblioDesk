document.addEventListener('DOMContentLoaded', () => {

    /* ---------- Books Modal (Global) ---------- */
    window.openBookModal = function (d, imgSrc) {
        const titleEl = document.getElementById('modalBookTitle');
        const authorEl = document.getElementById('modalBookAuthor');
        const descEl = document.getElementById('modalBookDesc');
        const imgEl = document.getElementById('modalBookImg');
        const idEl = document.getElementById('modalBookId');

        if (titleEl) titleEl.textContent = d.title || 'Unknown Title';
        if (authorEl) authorEl.textContent = 'By ' + (d.author || 'Unknown Author');
        if (descEl) descEl.textContent = d.desc || 'No description available.';
        if (imgEl) imgEl.src = imgSrc || '';
        if (idEl) idEl.value = d.id || '';

        const read = document.getElementById('modalBookRead');
        if (read) {
            read.href = 'javascript:void(0);'; 
            read.onclick = () => {
                alert('Reader backend coming soon!');
            };
            read.classList.remove('d-none');
        }

        const modalElement = document.getElementById('bookDetailsModal');
        if (modalElement) {
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }
    };

    /* ---------- Grid cards (Recommendations, Educational, etc.) ---------- */
    document.addEventListener('click', (e) => {
        const card = e.target.closest('.book-trigger');
        if (!card) return;

        e.preventDefault();   // Stops anchor jump

        const img = card.querySelector('img');
        window.openBookModal(card.dataset, img ? img.src : '');
    });

    /* ---------- New Arrivals carousel ---------- */
    const container = document.getElementById('newarrivalsContainer');
    const prevBtn = document.getElementById('ccPrevBtn');
    const nextBtn = document.getElementById('ccNextBtn');

    if (container) {
        function getItems() {
            return Array.from(container.querySelectorAll('.newarrivals-item'));
        }

        function updateActiveCenter() {
            const items = getItems();
            const middleIndex = Math.floor(items.length / 2);

            items.forEach((item, index) => {
                const distance = Math.abs(index - middleIndex);
                item.classList.remove('active');

                if (distance === 0) {
                    item.classList.add('active');
                    item.style.transform = 'scale(1.25)';
                    item.style.opacity = '1';
                    item.style.zIndex = '10';
                } else if (distance === 1) {
                    item.style.transform = 'scale(1.05)';
                    item.style.opacity = '0.70';
                    item.style.zIndex = '5';
                } else if (distance === 2) {
                    item.style.transform = 'scale(0.85)';
                    item.style.opacity = '0.70';
                    item.style.zIndex = '3';
                } else {
                    item.style.transform = 'scale(0.65)';
                    item.style.opacity = '0.70';
                    item.style.zIndex = '1';
                }
            });
        }

        nextBtn?.addEventListener('click', () => {
            const items = getItems();
            container.appendChild(items[0]);
            updateActiveCenter();
        });

        prevBtn?.addEventListener('click', () => {
            const items = getItems();
            container.insertBefore(items[items.length - 1], items[0]);
            updateActiveCenter();
        });

        container.addEventListener('click', (e) => {
            const clickedItem = e.target.closest('.newarrivals-item');
            if (!clickedItem) return;

            const items = getItems();
            const clickedIndex = items.indexOf(clickedItem);
            const middleIndex = Math.floor(items.length / 2);

            if (clickedIndex !== middleIndex) {
                const distance = clickedIndex - middleIndex;

                if (distance > 0) {
                    for (let i = 0; i < distance; i++) {
                        container.appendChild(getItems()[0]);
                    }
                } else {
                    for (let i = 0; i < Math.abs(distance); i++) {
                        const current = getItems();
                        container.insertBefore(current[current.length - 1], current[0]);
                    }
                }
                updateActiveCenter();
            } else {
                const img = clickedItem.querySelector('img');
                window.openBookModal(clickedItem.dataset, img ? img.src : '');
            }
        });

        updateActiveCenter();
    }
});