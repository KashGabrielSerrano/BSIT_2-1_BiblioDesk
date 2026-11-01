document.addEventListener('DOMContentLoaded', () => {
/* ---------- Books Modal ---------- */
       window.openBookModal = function (d, imgSrc) {
        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        const toggle = (id, show) => {
            const el = document.getElementById(id);
            if (el) el.classList.toggle('d-none', !show);
        };

        setText('modalBookTitle', d.title || 'Unknown Title');
        setText('modalBookAuthor', 'By ' + (d.author || 'Unknown Author'));
        setText('modalBookDesc', d.desc || 'No description available.');
        setText('modalBookIsbn', 'ISBN: ' + (d.isbn || 'N/A'));
        setText('modalBookGenre', d.genre || '');
        setText('modalBookPublisherName', d.publisher || 'Unknown');

        toggle('modalBookGenre', !!d.genre);
        toggle('modalBookPublisher', !!d.publisher);

        const imgEl = document.getElementById('modalBookImg');
        if (imgEl) imgEl.src = imgSrc || '';

        const idEl = document.getElementById('modalBookId');
        if (idEl) idEl.value = d.id || '';

        const read = document.getElementById('modalBookRead');
        if (read) {
            read.href = 'javascript:void(0);';
            read.onclick = () => alert('Reader backend coming soon!');
            read.classList.remove('d-none');
        }

        const modalElement = document.getElementById('bookDetailsModal');
        if (modalElement) {
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }
    };

/* ---------- Grid cards ---------- */
    document.addEventListener('click', (e) => {
        const card = e.target.closest('.book-trigger');
        if (!card) return;

        e.preventDefault();  

        const img = card.querySelector('img');
        window.openBookModal(card.dataset, img ? img.src : '');
    });

/* ---------- New Arrivals---------- */
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
                    item.style.opacity = '0.60';
                    item.style.zIndex = '3';
                } else {
                    item.style.transform = 'scale(0.65)';
                    item.style.opacity = '0.50';
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

