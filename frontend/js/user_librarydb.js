document.addEventListener('DOMContentLoaded', () => {
    /* ---------- Collections ---------- */
    const collectionsBtn = document.getElementById('modalBookAddToCollections');
    if (collectionsBtn) {
        collectionsBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const isAdded = collectionsBtn.classList.toggle('is-added');
            
            if (isAdded) {
                collectionsBtn.textContent = 'Remove From Collections';
                collectionsBtn.style.backgroundColor = 'var(--btn-remove)';
            } else {
                collectionsBtn.textContent = 'Add To Collections';
                collectionsBtn.style.backgroundColor = '';
            }
            
            const bookId = document.getElementById('modalBookId').value;
            console.log(`Book ${bookId} collection status: ${isAdded}`);
        });
    }

    /* ---------- Bookmark ---------- */
    const bookmarkBtn = document.getElementById('modalBookBookmark');
    if (bookmarkBtn) {
        bookmarkBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const isBookmarked = bookmarkBtn.classList.toggle('is-bookmarked');
            const icon = bookmarkBtn.querySelector('i');
            
            if (icon) {
                icon.className = isBookmarked ? 'bi bi-bookmark-fill' : 'bi bi-bookmark';
            }
            
            const bookId = document.getElementById('modalBookId').value;
            console.log(`Book ${bookId} bookmark status: ${isBookmarked}`);
        });
    }

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

        const title = d.title || 'Unknown Title';
        const author = d.author || 'Unknown Author';
        const desc = d.desc || 'No description available.';
        const isbn = d.isbn ? 'ISBN: ' + d.isbn : 'ISBN: N/A';
        const genre = d.genre || 'General';
        const publisher = d.publisher || 'N/A';
        const cover = d.cover || imgSrc || '../frontend/assets/temporary/temporaryimage_1.jpg';
        const bookId = d.id || '';

        setText('modalBookTitle', title);
        setText('modalBookAuthor', 'By ' + author);
        setText('modalBookDesc', desc);
        setText('modalBookIsbn', isbn);
        setText('modalBookPublisherName', publisher);

        // Display category/genre
        const genreEl = document.getElementById('modalBookGenre');
        if (genreEl) {
            genreEl.textContent = genre;
            genreEl.classList.toggle('d-none', !genre);
        }

        toggle('modalBookPublisher', !!d.publisher);

        const imgEl = document.getElementById('modalBookImg');
        if (imgEl) imgEl.src = cover;

        const idEl = document.getElementById('modalBookId');
        if (idEl) idEl.value = bookId;

        // Read Now button
        const read = document.getElementById('modalBookRead');
        if (read) {
            if (d.hasfile === '1' && bookId) {
                read.href = 'read_book.php?id=' + encodeURIComponent(bookId);
                read.target = '_blank';
                read.onclick = null;
                read.classList.remove('d-none');
            } else {
                read.classList.add('d-none');
            }
        }

        if (collectionsBtn) collectionsBtn.classList.remove('d-none');
        if (bookmarkBtn) bookmarkBtn.classList.remove('d-none');

        const modalElement = document.getElementById('bookDetailsModal');
        if (modalElement) {
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }
    };

    /* ---------- Event Delegation for Triggers ---------- */
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.book-trigger, .newarrivals-item');
        if (!trigger) return;

        if (trigger.hasAttribute('data-bs-toggle') || trigger.classList.contains('book-trigger')) {
            e.preventDefault();
        }

        const img = trigger.querySelector('img');
        window.openBookModal(trigger.dataset, img ? img.src : '');
    });

    /* ---------- New Arrivals ---------- */
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
            }
            const img = clickedItem.querySelector('img');
            window.openBookModal(clickedItem.dataset, img ? img.src : '');
        });
    }
});