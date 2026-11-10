(() => {
    'use strict';

    const API = '../backend/user_librarydb_controller.php';
    // Card colours, picked by position so each collection keeps a steady colour
    const COLORS = ['#5bc0de', '#5cb85c', '#9b59b6', '#e05d5d', '#20a39e', '#e67e22', '#34495e', '#d63384'];

    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const coverUrl = (u) => {
        u = String(u || '').trim();
        if (!u) return '../frontend/assets/icons/library-default.png';
        return /^https?:\/\//i.test(u) || u.startsWith('/') ? u : '../' + u.replace(/^\/+/, '');
    };

    async function get(params) {
        const res = await fetch(API + '?' + new URLSearchParams(params), { credentials: 'same-origin' });
        return res.json();
    }
    async function post(params) {
        const body = new FormData();
        Object.entries(params).forEach(([k, v]) => body.append(k, v));
        body.append('csrf_token', window.csrfToken || '');
        const res = await fetch(API, { method: 'POST', body, credentials: 'same-origin' });
        return res.json();
    }
    const show = (el, text, ok) => {
        if (!el) return;
        el.textContent = text;
        el.classList.toggle('text-danger', !ok);
        el.classList.toggle('text-success', !!ok);
        el.hidden = !text;
    };

    document.addEventListener('DOMContentLoaded', () => {
        /* ======================= My Books: the collection cards ======================= */
        const row     = document.getElementById('collectionsRow');
        const addCol  = document.getElementById('addCollectionCol');
        let collections = Array.isArray(window.bdCollections) ? window.bdCollections : [];

        function drawCards() {
            if (!row || !addCol) return;
            row.querySelectorAll('[data-collection-col]').forEach((el) => el.remove());
            collections.forEach((c, i) => {
                const col = document.createElement('div');
                col.className = 'col';
                col.dataset.collectionCol = '';
                col.innerHTML = `
                    <a href="#" class="collection-card ratio shadow position-relative d-block rounded-2 overflow-hidden"
                       style="background-color: ${COLORS[i % COLORS.length]};" data-collection-id="${c.id}"
                       aria-label="Open collection ${esc(c.name)}"></a>
                    <div class="textArea">
                        <h3 class="h6 fw-bold mt-3 mb-0 text-truncate" title="${esc(c.name)}">${esc(c.name)}</h3>
                        <p class="small mb-0 text-muted">${c.books} ${c.books === 1 ? 'book' : 'books'}</p>
                    </div>`;
                row.insertBefore(col, addCol);
            });
        }
        async function reloadCollections() {
            if (!row) return;   // Library view: no cards to redraw
            const data = await get({ action: 'list_collections' }).catch(() => null);
            if (data && data.success) {
                collections = data.collections;
                drawCards();
            }
        }
        drawCards();

        /* ---------------- create (the "+ Add Collections" card) ---------------- */
        const createModalEl = document.getElementById('createCollectionModal');
        const createForm    = document.getElementById('createCollectionForm');
        if (createModalEl && createForm) {
            const input = document.getElementById('collectionNameInput');
            const err   = document.getElementById('collectionNameError');
            const btn   = document.getElementById('saveCollectionBtn');
            createModalEl.addEventListener('show.bs.modal', () => {
                createForm.reset();
                input.classList.remove('is-invalid');
            });
            createModalEl.addEventListener('shown.bs.modal', () => input.focus());
            createForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = input.value.trim();
                if (!name) {
                    err.textContent = 'Enter a collection name.';
                    input.classList.add('is-invalid');
                    return;
                }
                btn.disabled = true;
                const data = await post({ action: 'create_collection', name }).catch(() => null);
                btn.disabled = false;
                if (!data || !data.success) {
                    err.textContent = data?.message || 'Could not create the collection. Please try again.';
                    input.classList.add('is-invalid');
                    return;
                }
                input.classList.remove('is-invalid');
                bootstrap.Modal.getOrCreateInstance(createModalEl).hide();
                await reloadCollections();
            });
            input.addEventListener('input', () => input.classList.remove('is-invalid'));
        }

        /* ---------------- open one collection: books, rename, delete ---------------- */
        const viewEl   = document.getElementById('collectionViewModal');
        const bookEl   = document.getElementById('bookDetailsModal');
        let current    = null;     // the open collection {id, name}
        let reopenId   = null;     // reopen this collection after its book's details modal closes

        if (viewEl) {
            const viewModal   = bootstrap.Modal.getOrCreateInstance(viewEl);
            const titleEl     = document.getElementById('collectionViewTitle');
            const booksEl     = document.getElementById('collectionBooks');
            const msgEl       = document.getElementById('collectionViewMsg');
            const renameBtn   = document.getElementById('collectionRenameBtn');
            const renameForm  = document.getElementById('collectionRenameForm');
            const deleteBtn   = document.getElementById('collectionDeleteBtn');
            const deleteBox   = document.getElementById('collectionDeleteConfirm');

            const resetHeader = () => {
                renameForm.hidden = true;
                deleteBox.hidden = true;
                show(msgEl, '');
            };

            function bookCard(b) {
                const data = `data-id="${b.id}" data-title="${esc(b.title)}" data-author="${esc(b.author)}" data-desc="${esc(b.desc)}"
                    data-isbn="${esc(b.isbn)}" data-genre="${esc(b.genre)}" data-publisher="${esc(b.publisher)}" data-allowed="1"
                    data-hasfile="${b.has_file ? '1' : '0'}" data-cover="${esc(coverUrl(b.cover))}"`;
                return `
                <div class="col position-relative">
                    <a href="#" class="book-trigger d-block ratio eduCover rounded-2 overflow-hidden shadow position-relative text-decoration-none" ${data}>
                        <img src="${esc(coverUrl(b.cover))}" class="w-100 h-100 object-fit-cover" alt="${esc(b.title)}">
                    </a>
                    <button type="button" class="btn btn-sm btn-light rounded-circle shadow collection-remove" data-remove-book="${b.id}"
                            aria-label="Remove ${esc(b.title)} from this collection" title="Remove from this collection">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <a href="#" class="textArea text-decoration-none book-trigger" ${data}>
                        <h3 class="h6 fw-bold mt-3 mb-0 text-truncate">${esc(b.title)}</h3>
                        <p class="small mb-3 text-muted text-truncate">${esc(b.author)}</p>
                    </a>
                </div>`;
            }

            async function loadBooks() {
                booksEl.innerHTML = '<div class="col-12 py-4 text-center text-muted">Loading…</div>';
                const data = await get({ action: 'collection_books', collection_id: current.id }).catch(() => null);
                if (!data || !data.success) {
                    booksEl.innerHTML = `<div class="col-12 py-4 text-center text-danger">${esc(data?.message || 'Could not load this collection.')}</div>`;
                    return;
                }
                current.name = data.name;
                titleEl.textContent = data.name;
                booksEl.innerHTML = data.books.length
                    ? data.books.map(bookCard).join('')
                    : '<div class="col-12 py-4 text-center text-muted fs-6">No books in this collection yet. Open a book and use <strong>Add To Collections</strong>.</div>';
            }

            function openCollection(id) {
                const c = collections.find((x) => x.id === id);
                if (!c) return;
                current = { id: c.id, name: c.name };
                titleEl.textContent = c.name;
                resetHeader();
                viewModal.show();
                loadBooks();
            }

            row?.addEventListener('click', (e) => {
                const card = e.target.closest('[data-collection-id]');
                if (!card) return;
                e.preventDefault();
                openCollection(Number(card.dataset.collectionId));
            });

            // A book in the collection: close this modal, show the book, come back here afterwards
            viewEl.addEventListener('click', async (e) => {
                const rm = e.target.closest('[data-remove-book]');
                if (rm) {
                    e.preventDefault();
                    e.stopPropagation();
                    rm.disabled = true;
                    const data = await post({ action: 'set_collection_book', collection_id: current.id, book_id: rm.dataset.removeBook, in: '0' }).catch(() => null);
                    if (!data || !data.success) {
                        rm.disabled = false;
                        show(msgEl, data?.message || 'Could not remove the book.');
                        return;
                    }
                    await loadBooks();
                    reloadCollections();
                    return;
                }
                if (e.target.closest('.book-trigger')) {
                    reopenId = current.id;
                    viewModal.hide();
                }
            });
            bookEl?.addEventListener('hidden.bs.modal', () => {
                if (reopenId !== null) {
                    const id = reopenId;
                    reopenId = null;
                    openCollection(id);
                }
            });

            renameBtn.addEventListener('click', () => {
                deleteBox.hidden = true;
                renameForm.hidden = false;
                renameForm.elements.name.value = current.name;
                renameForm.elements.name.focus();
            });
            renameForm.querySelector('[data-rename-cancel]').addEventListener('click', resetHeader);
            renameForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = renameForm.elements.name.value.trim();
                if (!name) { show(msgEl, 'Enter a collection name.'); return; }
                const data = await post({ action: 'rename_collection', collection_id: current.id, name }).catch(() => null);
                if (!data || !data.success) { show(msgEl, data?.message || 'Could not rename the collection.'); return; }
                current.name = data.name;
                titleEl.textContent = data.name;
                resetHeader();
                reloadCollections();
            });

            deleteBtn.addEventListener('click', () => {
                renameForm.hidden = true;
                deleteBox.hidden = false;
            });
            deleteBox.querySelector('[data-delete-cancel]').addEventListener('click', resetHeader);
            document.getElementById('collectionDeleteYes').addEventListener('click', async () => {
                const data = await post({ action: 'delete_collection', collection_id: current.id }).catch(() => null);
                if (!data || !data.success) { show(msgEl, data?.message || 'Could not delete the collection.'); return; }
                viewModal.hide();
                reloadCollections();
            });
        }

        /* ======================= Book details modal: "Add To Collections" ======================= */
        const addBtn  = document.getElementById('modalBookAddToCollections');
        const panel   = document.getElementById('modalCollectionPanel');
        const listEl  = document.getElementById('modalCollectionList');
        const newForm = document.getElementById('modalCollectionNew');
        const pMsg    = document.getElementById('modalCollectionMsg');
        const bookId  = () => document.getElementById('modalBookId')?.value || '';
        if (!addBtn || !panel || !bookEl) return;

        const closePanel = () => {
            panel.hidden = true;
            addBtn.setAttribute('aria-expanded', 'false');
        };
        bookEl.addEventListener('show.bs.modal', () => {
            closePanel();
            show(pMsg, '');
            newForm.reset();
        });

        function drawChecklist(list) {
            if (!list.length) {
                listEl.innerHTML = '<p class="small text-muted mb-0">You have no collections yet. Create one below.</p>';
                return;
            }
            listEl.innerHTML = list.map((c) => `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="colPick${c.id}" data-collection="${c.id}" ${c.has_book ? 'checked' : ''}>
                    <label class="form-check-label" for="colPick${c.id}">${esc(c.name)} <span class="text-muted small">(${c.books})</span></label>
                </div>`).join('');
        }
        async function loadChecklist() {
            listEl.innerHTML = '<p class="small text-muted mb-0">Loading…</p>';
            const data = await get({ action: 'list_collections', book_id: bookId() }).catch(() => null);
            if (!data || !data.success) {
                listEl.innerHTML = `<p class="small text-danger mb-0">${esc(data?.message || 'Could not load your collections.')}</p>`;
                return;
            }
            drawChecklist(data.collections);
        }

        addBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!panel.hidden) { closePanel(); return; }
            panel.hidden = false;
            addBtn.setAttribute('aria-expanded', 'true');
            show(pMsg, '');
            loadChecklist();
        });

        listEl.addEventListener('change', async (e) => {
            const box = e.target.closest('[data-collection]');
            if (!box) return;
            box.disabled = true;
            const data = await post({ action: 'set_collection_book', collection_id: box.dataset.collection, book_id: bookId(), in: box.checked ? '1' : '0' }).catch(() => null);
            box.disabled = false;
            if (!data || !data.success) {
                box.checked = !box.checked;
                show(pMsg, data?.message || 'Could not update the collection.');
                return;
            }
            show(pMsg, data.message, true);
            loadChecklist();
            reloadCollections();
        });

        newForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = newForm.elements.name.value.trim();
            if (!name) { show(pMsg, 'Enter a collection name.'); return; }
            const btn = newForm.querySelector('button[type="submit"]');
            btn.disabled = true;
            const data = await post({ action: 'create_collection', name, book_id: bookId() }).catch(() => null);
            btn.disabled = false;
            if (!data || !data.success) { show(pMsg, data?.message || 'Could not create the collection.'); return; }
            newForm.reset();
            show(pMsg, `Created "${data.collection.name}" and added this book.`, true);
            loadChecklist();
            reloadCollections();
        });
    });
})();
