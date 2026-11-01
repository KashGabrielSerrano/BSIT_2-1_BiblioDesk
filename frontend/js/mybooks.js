document.addEventListener('DOMContentLoaded', () => {
    /* ---------- Create Collection ---------- */
    function textColorFor(hex) {
        const h = hex.replace('#', '');
        const r = parseInt(h.substr(0, 2), 16);
        const g = parseInt(h.substr(2, 2), 16);
        const b = parseInt(h.substr(4, 2), 16);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance > 0.6 ? '#2C251D' : '#FFFFFF';
    }

    const createModalEl = document.getElementById('createCollectionModal');
    const createForm = document.getElementById('createCollectionForm');
    const nameInput = document.getElementById('collectionNameInput');
    const colorInput = document.getElementById('collectionColorInput');
    const customColor = document.getElementById('collectionCustomColor');
    const swatchBox = document.getElementById('colorSwatches');
    const previewCard = document.getElementById('collectionPreview');
    const collectionsRow = document.getElementById('collectionsRow');

    const DEFAULT_COLOR = '#87CEEB';

    function applyColor(hex) {
        colorInput.value = hex;
        previewCard.style.backgroundColor = hex;
        previewCard.style.color = textColorFor(hex);
    }

    if (createModalEl && createForm) {

        swatchBox.addEventListener('click', (e) => {
            const swatch = e.target.closest('.swatch[data-color]');
            if (!swatch) return;

            swatchBox.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
            swatch.classList.add('active');
            applyColor(swatch.dataset.color);
        });

        customColor.addEventListener('input', () => {
            swatchBox.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
            customColor.closest('.swatch').classList.add('active');
            customColor.closest('.swatch').style.setProperty('--swatch', customColor.value);
            applyColor(customColor.value);
        });

        const rainbowSwatch = document.querySelector('.swatch-rainbow');
        if (rainbowSwatch && customColor) {
            rainbowSwatch.addEventListener('click', () => {
                customColor.click(); 
            });
        }

        nameInput.addEventListener('input', () => {
            nameInput.classList.remove('is-invalid');
            updatePreview();
        });

        createForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const name = nameInput.value.trim();
            const color = colorInput.value || DEFAULT_COLOR;

            if (!name) {
                nameInput.classList.add('is-invalid');
                nameInput.focus();
                return;
            }

            const addTrigger = document.querySelector('[data-bs-target="#createCollectionModal"]');
            const addCol = addTrigger ? addTrigger.closest('.col') : null;
            const row = collectionsRow || (addCol && addCol.parentElement);

            if (!row) {
                console.error('Collections row not found.');
                return;
            }

            const col = document.createElement('div');
            col.className = 'col';

            const card = document.createElement('div');
            card.className = 'collection-card ratio shadow position-relative';
            card.style.backgroundColor = color;
            card.style.color = textColorFor(color);

            const textArea = document.createElement('div');
            textArea.className = 'textArea';
            const h3 = document.createElement('h3');
            h3.className = 'h6 fw-bold mt-3 mb-0';
            h3.textContent = name;
            textArea.append(h3);

            col.append(card, textArea);

            if (addCol && addCol.parentElement === row) {
                row.insertBefore(col, addCol);
            } else {
                row.appendChild(col);
            }

            bootstrap.Modal.getOrCreateInstance(createModalEl).hide();
        });

        createModalEl.addEventListener('hidden.bs.modal', () => {
            createForm.reset();
            nameInput.classList.remove('is-invalid');
            swatchBox.querySelectorAll('.swatch').forEach(s => s.classList.remove('active'));
            const first = swatchBox.querySelector('.swatch[data-color]');
            if (first) first.classList.add('active');
            customColor.closest('.swatch').style.setProperty('--swatch', '#ffffff');
            applyColor(DEFAULT_COLOR);
            updatePreview();
        });

        createModalEl.addEventListener('shown.bs.modal', () => nameInput.focus());

        applyColor(DEFAULT_COLOR);
    }

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

        setText('modalBookTitle', d.title || 'Unknown Title');
        setText('modalBookAuthor', 'By ' + (d.author || 'Unknown Author'));
        setText('modalBookDesc', d.desc || 'No description available.');
        setText('modalBookIsbn', 'ISBN: ' + (d.isbn || 'N/A'));
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

        if (collectionsBtn) collectionsBtn.classList.remove('d-none');
        if (bookmarkBtn) bookmarkBtn.classList.remove('d-none');

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



});