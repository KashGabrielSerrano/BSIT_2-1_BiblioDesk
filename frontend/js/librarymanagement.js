(() => {
    'use strict';

    const API = 'librarymanagement_controller.php';
    const CSRF = window.csrfToken || '';
    const FALLBACK_COVER = '../frontend/assets/icons/library-default.png';
    const FALLBACK_AVATAR = '../frontend/assets/icons/pfp-icon.png';
    const MAIN_TABS = ['catalog', 'accounts', 'reports', 'circulation'];

    const $ = (id) => document.getElementById(id);

    const state = {
        mainTab: 'catalog',
        catalogSort: 'newest',
        accountTab: 'users',
        canChangeRoles: false,   // set from get_accounts (true for Admins)
        reportPage: 1,
        reportPerPage: 8,
        // monotonically increasing ids so a slow, stale response never overwrites a newer one
        seq: { catalog: 0, accounts: 0, reports: 0 }
    };

    /* ====================================================================== */
    /* Utilities                                                              */
    /* ====================================================================== */

    const ESC = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (ch) => ESC[ch]);
    }

    /** Mirrors getCoverImageUrl() in user_librarydb.php; blocks javascript:/data: URLs. */
    function coverSrc(url) {
        const v = String(url || '').trim();
        if (!v) return FALLBACK_COVER;
        if (/^https?:\/\//i.test(v)) return v;
        if (/^[a-z][a-z0-9+.-]*:/i.test(v)) return FALLBACK_COVER; // any other scheme
        return v.startsWith('/') ? v : '../' + v.replace(/^\/+/, '');
    }

    function avatarSrc(url) {
        const v = String(url || '').trim();
        if (!v) return FALLBACK_AVATAR;
        if (/^https?:\/\//i.test(v)) return v;
        if (/^[a-z][a-z0-9+.-]*:/i.test(v)) return FALLBACK_AVATAR;
        return v.startsWith('/') ? v : '../' + v.replace(/^\/+/, '');
    }

    function debounce(fn, ms = 300) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    function parseDate(str) {
        if (!str) return null;
        const d = new Date(String(str).replace(' ', 'T'));
        return isNaN(d) ? null : d;
    }

    function formatRelativeDate(str) {
        const d = parseDate(str);
        if (!d) return '—';
        const dayKey = (x) => x.getFullYear() * 10000 + x.getMonth() * 100 + x.getDate();
        const now = new Date();
        const yest = new Date(now);
        yest.setDate(now.getDate() - 1);
        if (dayKey(d) === dayKey(now)) return 'Today';
        if (dayKey(d) === dayKey(yest)) return 'Yesterday';
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
    }

    function setBusy(btn, busy) {
        if (!btn) return;
        btn.disabled = busy;
        if (btn.dataset.label === undefined) btn.dataset.label = btn.innerHTML;
        btn.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Please wait…'
            : btn.dataset.label;
    }

    function notify(message, type = 'success') {
        const el = $('appToast');
        if (!el) return;
        $('toastIcon').className = type === 'error'
            ? 'bi bi-exclamation-triangle-fill text-warning me-2'
            : 'bi bi-check-circle-fill text-success me-2';
        $('toastText').textContent = message;
        bootstrap.Toast.getOrCreateInstance(el, { delay: 4500 }).show();
    }

    /** Fetch wrapper: always JSON, throws Error(message) on HTTP or {success:false} failures. */
    async function api(action, { method = 'GET', params = {}, form = null } = {}) {
        const opts = { method, credentials: 'same-origin', cache: 'no-store' };
        let url = API;

        if (method === 'GET') {
            const qs = new URLSearchParams({ action });
            Object.entries(params).forEach(([k, v]) => {
                if (v !== '' && v != null) qs.set(k, v);
            });
            url += '?' + qs.toString();
        } else {
            const fd = form ? new FormData(form) : new FormData();
            fd.set('action', action);
            fd.set('csrf_token', CSRF);
            Object.entries(params).forEach(([k, v]) => fd.set(k, v));
            opts.body = fd;
        }

        const res = await fetch(url, opts);
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch {
            throw new Error('Unexpected server response. Check the PHP error log.');
        }
        if (!res.ok || !data.success) {
            throw new Error((data.message || 'Request failed.') + (data.debug ? ' [' + data.debug + ']' : ''));
        }
        return data;
    }

    function confirmAction({ title, message, confirmLabel = 'Confirm', variant = 'danger', icon = 'bi-exclamation-triangle-fill', iconColor = '' }) {
        return new Promise((resolve) => {
            const el = $('confirmModal');
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            const okBtn = $('confirmOkBtn');
            const iconWrap = $('confirmIconWrap');
            const brand = variant === 'brand';
            let decided = false;

            $('confirmIcon').className = 'bi ' + icon;
            iconWrap.className = 'mb-3 fs-1' + (iconColor ? '' : ' text-danger');
            iconWrap.style.color = iconColor;
            $('confirmTitle').textContent = title;
            $('confirmMessage').textContent = message;
            okBtn.textContent = confirmLabel;
            okBtn.className = 'btn px-4 py-2 rounded-3 fw-semibold ' + (brand ? 'text-white' : 'btn-' + variant);
            okBtn.style.backgroundColor = brand ? 'var(--btn)' : '';

            const onOk = () => {
                decided = true;
                modal.hide();
                resolve(true);
            };
            const onHidden = () => {
                okBtn.removeEventListener('click', onOk);
                if (!decided) resolve(false);
            };
            okBtn.addEventListener('click', onOk, { once: true });
            el.addEventListener('hidden.bs.modal', onHidden, { once: true });
            modal.show();
        });
    }

    function promptAction({ title, message = '', fields = [], confirmLabel = 'Save', icon = 'bi-pencil-square', variant = 'brand' }) {
        return new Promise((resolve) => {
            const el = $('promptModal');
            const modal = bootstrap.Modal.getOrCreateInstance(el);
            const form = $('promptForm');
            const box = $('promptFields');
            const okBtn = $('promptOkBtn');
            const iconWrap = $('promptIconWrap');
            const danger = variant === 'danger';
            let decided = false;

            $('promptIcon').className = 'bi ' + icon;
            iconWrap.className = 'text-center mb-3 fs-1' + (danger ? ' text-danger' : '');
            iconWrap.style.color = danger ? '' : 'var(--btn)';
            $('promptTitle').textContent = title;
            const msg = $('promptMessage');
            msg.textContent = message;
            msg.classList.toggle('d-none', !message);
            okBtn.textContent = confirmLabel;
            okBtn.className = 'btn px-4 py-2 fw-semibold rounded-3 ' + (danger ? 'btn-danger' : 'text-white');
            okBtn.style.backgroundColor = danger ? '' : 'var(--btn)';

            box.innerHTML = fields.map((f, i) => {
                const attrs = `id="promptField${i}" name="${escapeHtml(f.name)}" class="form-control" ` +
                    `placeholder="${escapeHtml(f.placeholder || '')}" maxlength="${f.maxlength || 255}" autocomplete="off"` +
                    (f.inputmode ? ` inputmode="${escapeHtml(f.inputmode)}"` : '');
                const control = f.type === 'textarea'
                    ? `<textarea ${attrs} rows="3"></textarea>`
                    : `<input type="text" ${attrs}>`;
                const error = `<div class="invalid-feedback" data-error-for="${i}"></div>`;
                const field = f.prefix
                    ? `<div class="input-group"><span class="input-group-text">${escapeHtml(f.prefix)}</span>${control}${error}</div>`
                    : control + error;
                return `<div>
                    <label class="form-label fw-semibold" for="promptField${i}">${escapeHtml(f.label)}${f.required ? '' : ' <span class="fw-normal text-muted">(optional)</span>'}</label>
                    ${field}
                    ${f.hint ? `<div class="form-text">${escapeHtml(f.hint)}</div>` : ''}
                </div>`;
            }).join('');

            const onSubmit = (e) => {
                e.preventDefault();
                const values = {};
                let firstBad = null;
                fields.forEach((f, i) => {
                    const input = $('promptField' + i);
                    const value = input.value.trim();
                    let error = '';
                    if (f.required && !value) error = f.requiredMessage || `${f.label} is required.`;
                    else if (value && f.validate) error = f.validate(value) || '';
                    input.classList.toggle('is-invalid', !!error);
                    box.querySelector(`[data-error-for="${i}"]`).textContent = error;
                    if (error && !firstBad) firstBad = input;
                    values[f.name] = value;
                });
                if (firstBad) { firstBad.focus(); return; }
                decided = true;
                modal.hide();
                resolve(values);
            };
            const onHidden = () => {
                form.removeEventListener('submit', onSubmit);
                if (!decided) resolve(null);
            };
            form.addEventListener('submit', onSubmit);
            el.addEventListener('hidden.bs.modal', onHidden, { once: true });
            el.addEventListener('shown.bs.modal', () => {
                const first = box.querySelector('input, textarea');
                if (first) first.focus();
            }, { once: true });
            modal.show();
        });
    }

    function emptyState(message) {
        return `<div class="emptyState">${escapeHtml(message)}</div>`;
    }

    /* ====================================================================== */
    /* Main tab switching (SPA)                                               */
    /* ====================================================================== */

    function switchMainTab(target) {
        if (!MAIN_TABS.includes(target)) target = 'catalog';
        state.mainTab = target;

        document.querySelectorAll('[data-main-panel]').forEach((panel) => {
            panel.classList.toggle('d-none', panel.dataset.mainPanel !== target);
        });

        document.querySelectorAll('[data-main-tab]').forEach((el) => {
            const on = el.dataset.mainTab === target;
            if (el.classList.contains('sideAct')) {
                // sidebar link: same active styling as template.php
                if (on) el.setAttribute('aria-current', 'page');
                else el.removeAttribute('aria-current');
                const icon = el.querySelector('.sideIcon');
                if (icon) icon.classList.toggle('bg-light', on);
            } else {
                el.classList.toggle('active', on);
                el.setAttribute('aria-selected', on ? 'true' : 'false');
            }
        });

        if (location.hash !== '#' + target) {
            history.replaceState(null, '', '#' + target);
        }

        // close the mobile sidebar after navigating
        const off = bootstrap.Offcanvas.getInstance($('sidebar'));
        if (off) off.hide();

        if (target === 'catalog') loadCatalog();
        else if (target === 'accounts') loadAccountTab();
        else if (target === 'circulation') { if (window.loadCirculation) window.loadCirculation(); }
        else loadReports();
    }

    /* ====================================================================== */
    /* Catalog                                                                */
    /* ====================================================================== */

    const CATEGORY_PILL = {
        'Educational':       { cls: 'catEd', icon: 'bi-mortarboard-fill',       label: 'Educational' },
        'Non-Educational':   { cls: 'catNe', icon: 'bi-journal-bookmark-fill',  label: 'Non-Educational' },
        'Research Articles': { cls: 'catRa', icon: 'bi-file-earmark-text-fill', label: 'Research' }
    };

    function categoryBadge(category) {
        const cat = (category || '').trim();
        const hit = CATEGORY_PILL[cat];
        if (!hit) return `<span class="pill auto neutral">${escapeHtml(cat || '—')}</span>`;
        return `<span class="pill auto ${hit.cls}"><i class="bi ${hit.icon} me-1" aria-hidden="true"></i>${hit.label}</span>`;
    }

    const GENRE_ICONS = {
        'Fiction': 'bi-magic',
        'Non-Fiction': 'bi-journal-text',
        'Horror': 'bi-moon-stars',
        'Romance': 'bi-heart',
        'Science': 'bi-lightbulb',
        'Technology': 'bi-cpu',
        'History': 'bi-hourglass-split',
        'Self-help': 'bi-emoji-smile',
        'Education': 'bi-mortarboard'
    };

    function genreBadge(genre) {
        const g = (genre || '').trim();
        if (!g) return '<p class="rowSub mt-1"><i class="bi bi-bookmarks me-1" aria-hidden="true"></i>No genre</p>';
        const icon = GENRE_ICONS[g] || 'bi-bookmarks';
        return `<p class="rowSub mt-1"><i class="bi ${icon} me-1" aria-hidden="true"></i>${escapeHtml(g)}</p>`;
    }

    function digitalBadge(book) {
        const pill = (kind, icon, label) =>
            `<span class="pill auto ${kind}"><i class="bi ${icon} me-1" aria-hidden="true"></i>${label}</span>`;
        if (book.is_digital === 'Yes' && book.drive_link) return pill('ok', 'bi-file-earmark-pdf-fill', 'PDF Linked');
        if (book.is_digital === 'Yes') return pill('bad', 'bi-exclamation-triangle-fill', 'Link Missing');
        if (book.total_copies > 0) {
            const avail = book.available == null ? book.total_copies : book.available;
            return pill(avail > 0 ? 'warn' : 'bad', 'bi-box-seam', `Physical · ${avail}/${book.total_copies}`);
        }
        return pill('warn', 'bi-book', 'Not Digital');
    }

    function catalogRowHtml(b) {
        const isbn = b.isbn
            ? `<p class="rowSub font-monospace"><i class="bi bi-upc-scan me-1" aria-hidden="true"></i>${escapeHtml(b.isbn)}</p>` : '';
        const publisher = b.publisher
            ? `<p class="rowSub"><i class="bi bi-building me-1" aria-hidden="true"></i>${escapeHtml(b.publisher)}</p>` : '';

        return `
        <tr data-id="${b.book_id}">
            <td class="cB">
                <div class="d-flex align-items-center gap-3">
                    <img src="${escapeHtml(coverSrc(b.cover_image))}" alt="" class="catalogThumb flex-shrink-0">
                    <div class="text-start">
                        <p class="rowName">${escapeHtml(b.title)}</p>
                        ${isbn}
                    </div>
                </div>
            </td>
            <td class="cA">
                <p class="rowName">${escapeHtml(b.author)}</p>
                ${publisher}
            </td>
            <td class="cB">${categoryBadge(b.category)}${genreBadge(b.genre)}</td>
            <td class="cA">${digitalBadge(b)}</td>
            <td class="cB">
                <div class="acctActions">
                    <button type="button" class="actBtn neutral js-edit-book" data-id="${b.book_id}" title="Edit book details">
                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                    </button>
                    <button type="button" class="actBtn remove js-delete-book" data-id="${b.book_id}" data-title="${escapeHtml(b.title)}" title="Delete book">
                        <i class="bi bi-trash3-fill me-1" aria-hidden="true"></i>Delete
                    </button>
                </div>
            </td>
        </tr>`;
    }

    async function loadCatalog() {
        const seq = ++state.seq.catalog;
        const body = $('catalogBody');
        body.innerHTML = '<tr class="emptyRow"><td colspan="5">Loading catalog…</td></tr>';

        try {
            const data = await api('get_catalog', {
                params: { search: $('catalogSearch').value.trim(), sort: state.catalogSort }
            });
            if (seq !== state.seq.catalog) return;

            $('catalogCount').textContent = data.count + (data.count === 1 ? ' book' : ' books');
            body.innerHTML = data.books.length
                ? data.books.map(catalogRowHtml).join('')
                : '<tr class="emptyRow"><td colspan="5">No books found. Try a different search, or add a new book above.</td></tr>';
        } catch (err) {
            if (seq !== state.seq.catalog) return;
            body.innerHTML = `<tr class="emptyRow"><td colspan="5" class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>${escapeHtml(err.message)}</td></tr>`;
        }
    }

    /** Mirrors the server-side upload rules (the server is still the authority). */
    const FILE_RULES = {
        cover_file: { max: 5 * 1024 * 1024, types: ['image/jpeg', 'image/png', 'image/webp'], label: 'Cover image', hint: 'JPG, PNG or WEBP up to 5 MB' },
        pdf_file: { max: 20 * 1024 * 1024, types: ['application/pdf'], label: 'PDF', hint: 'PDF up to 20 MB' }
    };

    /** PDF link is only mandatory for digital books that have no PDF file chosen;
     *  the physical-copies field only shows (and is required) for physical books. */
    function syncDigitalRequired(form) {
        const digital = form.elements.is_digital.value === 'Yes';
        const hasPdf = !!(form.elements.pdf_file && form.elements.pdf_file.files.length);
        form.elements.drive_link.required = digital && !hasPdf;
        const copies = form.querySelector('[data-copies-field]');
        if (copies) {
            copies.hidden = digital;
            form.elements.total_copies.required = !digital;
        }
        // Physical books have no PDF: hide the PDF upload + link and clear anything chosen in them
        const pdf = form.querySelector('[data-pdf-field]');
        if (pdf) {
            pdf.hidden = !digital;
            [form.elements.pdf_file, form.elements.drive_link].forEach((el) => {
                if (!el) return;
                el.disabled = !digital;
                if (!digital) el.value = '';
            });
        }
    }

    /** Friendly pre-check of chosen files; returns false (and toasts) if one is unacceptable. */
    function validateFiles(form) {
        for (const [name, rule] of Object.entries(FILE_RULES)) {
            const input = form.elements[name];
            const file = input && input.files[0];
            if (!file) continue;
            if (file.size > rule.max || (file.type && !rule.types.includes(file.type))) {
                notify(`${rule.label} must be ${rule.hint}.`, 'error');
                return false;
            }
        }
        return true;
    }

    async function handleAddBook(e) {
        e.preventDefault();
        const form = e.currentTarget;
        syncDigitalRequired(form);
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        if (!validateFiles(form)) return;
        const btn = form.querySelector('[type="submit"]');
        setBusy(btn, true);
        try {
            await api('add_book', { method: 'POST', form });
            notify('Book published to the catalog.');
            form.reset();
            syncDigitalRequired(form);
            loadCatalog();
        } catch (err) {
            notify(err.message, 'error');
        } finally {
            setBusy(btn, false);
        }
    }

    /** Edit modal: show the cover the book has currently, and follow the URL field as it is edited. */
    function updateCoverPreview(url) {
        const img = $('editCoverPreview');
        if (!img) return;
        delete img.dataset.fallback;
        img.src = coverSrc(url);
    }

    async function openEditModal(bookId) {
        try {
            const { book } = await api('get_book', { params: { book_id: bookId } });
            const form = $('editBookForm');
            form.reset();
            ['book_id', 'title', 'author', 'isbn', 'publisher', 'category', 'genre', 'description', 'cover_image', 'drive_link', 'is_digital', 'total_copies']
                .forEach((name) => {
                    form.elements[name].value = book[name] ?? '';
                });
            if (form.elements.category.selectedIndex < 0) form.elements.category.value = '';
            syncDigitalRequired(form);
            updateCoverPreview(form.elements.cover_image.value);
            bootstrap.Modal.getOrCreateInstance($('editBookModal')).show();
        } catch (err) {
            notify(err.message, 'error');
        }
    }

    async function handleEditBook(e) {
        e.preventDefault();
        const form = e.currentTarget;
        syncDigitalRequired(form);
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        if (!validateFiles(form)) return;
        const btn = $('editBookSaveBtn');
        setBusy(btn, true);
        try {
            await api('update_book', { method: 'POST', form });
            bootstrap.Modal.getOrCreateInstance($('editBookModal')).hide();
            notify('Book updated.');
            loadCatalog();
        } catch (err) {
            notify(err.message, 'error');
        } finally {
            setBusy(btn, false);
        }
    }

    async function deleteBook(bookId, title) {
        const ok = await confirmAction({
            title: 'Delete this book?',
            message: `“${title}” will be removed from the catalog, along with its bookmarks, favorites, reading history and any reports filed about it. Its cover and PDF are also deleted from storage. This cannot be undone.`,
            confirmLabel: 'Delete'
        });
        if (!ok) return;
        try {
            await api('delete_book', { method: 'POST', params: { book_id: bookId } });
            notify('Book deleted.');
            loadCatalog();
        } catch (err) {
            notify(err.message, 'error');
        }
    }

    /* ====================================================================== */
    /* Account management                                                     */
    /* ====================================================================== */

    // Admins only (A3): switch an active account between Student and Librarian
    function roleActionHtml(a) {
        if (!state.canChangeRoles || a.status !== 'Active') return '';
        const to = a.role === 'Librarian' ? 'Student' : 'Librarian';
        return `<button type="button" class="actBtn approve js-account-role" data-id="${a.user_id}"
                    data-name="${escapeHtml(a.name)}" data-role="${to}">Make ${to}</button>`;
    }

    function accountActionsHtml(a) {
        const attrs = `data-id="${a.user_id}" data-name="${escapeHtml(a.name)}"`;
        // Librarian accounts (listed for Admins only) can change role, but suspension stays student-only
        if (a.role === 'Librarian') return roleActionHtml(a);
        if (a.status === 'Pending') {
            return `
                <button type="button" class="actBtn approve js-account-status" ${attrs} data-status="Active">Approve</button>
                <button type="button" class="actBtn deny js-account-status" ${attrs} data-status="Suspended" data-confirm="1">Deny</button>`;
        }
        if (a.status === 'Active') {
            return `<button type="button" class="actBtn deny js-account-status" ${attrs} data-status="Suspended" data-confirm="1">Suspend</button>` + roleActionHtml(a);
        }
        return `<button type="button" class="actBtn approve js-account-status" ${attrs} data-status="Active">Reactivate</button>`;
    }

    function accountRowHtml(a) {
        const pill = a.status === 'Active' ? 'active' : a.status === 'Pending' ? 'pending' : 'suspended';
        const joined = parseDate(a.created_at);
        const title = joined ? 'Joined ' + joined.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
        return `
        <tr data-id="${a.user_id}" title="${escapeHtml(title)}">
            <td class="cB">
                <div class="d-flex align-items-center gap-4">
                    <img src="${escapeHtml(avatarSrc(a.avatar))}" alt="" class="userAvatar rounded-circle">
                    <div class="text-start">
                        <p class="userName">${escapeHtml(a.name || 'Unnamed student')}</p>
                        <p class="userEmail">${escapeHtml(a.email)}</p>
                        ${state.canChangeRoles ? `<span class="badge rounded-pill ${a.role === 'Librarian' ? 'bg-dark' : 'bg-secondary'} mt-1">${escapeHtml(a.role)}</span>` : ''}
                    </div>
                </div>
            </td>
            <td class="cA">${escapeHtml(a.student_id || '—')}</td>
            <td class="cB">${escapeHtml(a.department || '—')}</td>
            <td class="cA"><span class="pill ${pill}">${escapeHtml(a.status)}</span></td>
            <td class="cB"><div class="acctActions">${accountActionsHtml(a)}</div></td>
        </tr>`;
    }

    async function loadAccountTab(tab = state.accountTab) {
        state.accountTab = tab === 'pending' ? 'pending' : 'users';
        const seq = ++state.seq.accounts;
        const rows = $('accountRows');

        document.querySelectorAll('[data-account-tab]').forEach((btn) => {
            const on = btn.dataset.accountTab === state.accountTab;
            btn.classList.toggle('active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        $('accountStatusFilter').classList.toggle('d-none', state.accountTab === 'pending');
        rows.innerHTML = `<tr><td colspan="5" class="py-4">Loading accounts…</td></tr>`;

        try {
            const data = await api('get_accounts', {
                params: {
                    tab: state.accountTab,
                    search: $('accountSearch').value.trim(),
                    status: state.accountTab === 'users' ? $('accountStatusFilter').value : ''
                }
            });
            if (seq !== state.seq.accounts) return;

            state.canChangeRoles = !!data.can_change_roles;
            $('countUsers').textContent = data.counts.users;
            $('countPending').textContent = data.counts.pending;
            $('accountResultCount').textContent =
                `Showing ${data.accounts.length} account${data.accounts.length === 1 ? '' : 's'}`;

            rows.innerHTML = data.accounts.length
                ? data.accounts.map(accountRowHtml).join('')
                : `<tr class="emptyRow"><td colspan="5">${state.accountTab === 'pending'
                    ? 'No pending accounts.' : 'No accounts match your search.'}</td></tr>`;
        } catch (err) {
            if (seq !== state.seq.accounts) return;
            rows.innerHTML = `<tr class="emptyRow"><td colspan="5" class="text-danger">${escapeHtml(err.message)}</td></tr>`;
        }
    }

    async function updateAccountStatus(userId, status, name = 'Account') {
        try {
            await api('update_account_status', { method: 'POST', params: { user_id: userId, status } });
            const verb = { Active: 'is now active', Suspended: 'has been suspended', Pending: 'was moved back to pending' }[status];
            notify(`${name} ${verb}.`);
            loadAccountTab();
        } catch (err) {
            notify(err.message, 'error');
        }
    }

    async function updateUserRole(btn) {
        const { id, role, name } = btn.dataset;
        const ok = await confirmAction({
            title: `Make ${name || 'this user'} a ${role}?`,
            message: role === 'Librarian'
                ? 'They will be able to manage the catalog, accounts and reports. Their saved logins are signed out.'
                : 'They will lose access to Library Management. Their saved logins are signed out.',
            confirmLabel: `Make ${role}`,
            variant: 'dark'
        });
        if (!ok) return;
        try {
            const data = await api('update_user_role', { method: 'POST', params: { user_id: id, role } });
            notify(data.message);
            loadAccountTab();
        } catch (err) {
            notify(err.message, 'error');
        }
    }

    async function handleAccountClick(e) {
        const roleBtn = e.target.closest('.js-account-role');
        if (roleBtn) { updateUserRole(roleBtn); return; }
        const btn = e.target.closest('.js-account-status');
        if (!btn) return;
        const { id, status, name } = btn.dataset;

        if (btn.dataset.confirm) {
            const ok = await confirmAction({
                title: status === 'Suspended' ? 'Suspend this account?' : 'Confirm',
                message: `${name || 'This student'} will not be able to log in until the account is reactivated.`,
                confirmLabel: btn.textContent.trim()
            });
            if (!ok) return;
        }
        updateAccountStatus(id, status, name || 'Account');
    }

    /* ====================================================================== */
    /* Report logs                                                            */
    /* ====================================================================== */

    const ISSUE_BADGE = {
        'Broken Link': 'issue-badge-pink',
        'Missing Page/s': 'issue-badge-amber',
        'Wrong File': 'issue-badge-red',
        'License Concern': 'issue-badge-blue'
    };
    const STATUS_BADGE = {
        New: 'status-badge-green',
        Reviewing: 'status-badge-red',
        Forwarded: 'status-badge-amber',
        Resolved: 'status-badge-darkred'
    };

    function reportRowHtml(r) {
        // Next step(s) in the workflow New -> Reviewing -> Forwarded -> Resolved
        const NEXT = {
            New:       [['Reviewing', 'Review']],
            Reviewing: [['Forwarded', 'Forward'], ['Resolved', 'Resolve']],
            Forwarded: [['Resolved', 'Resolve']],
            Resolved:  []
        };
        const steps = NEXT[r.status] || [];
        const action = steps.length
            ? '<div class="d-flex flex-wrap gap-1 justify-content-center">' + steps.map(([status, label]) =>
                `<button type="button" class="action-btn" data-report-status="${status}">${label}</button>`).join('') + '</div>'
            : '<span class="action-badge-green">Resolved</span>';
        return `
        <div class="reportRow" data-id="${r.report_id}">
            <div class="cellName">
                <p class="bookTitle mb-0">${escapeHtml(r.book_title)}</p>
                <p class="bookAuthor mb-0">${escapeHtml(r.book_author)}</p>
            </div>
            <div class="cell"><span class="${ISSUE_BADGE[r.issue_type] || 'issue-badge-blue'}">${escapeHtml(r.issue_type)}</span></div>
            <div class="cell">${escapeHtml(r.reporter)}</div>
            <div class="cell">${escapeHtml(formatRelativeDate(r.created_at))}</div>
            <div class="cell"><span class="${STATUS_BADGE[r.status] || 'status-badge-amber'}">${escapeHtml(r.status)}</span></div>
            <div class="cell">${action}</div>
        </div>`;
    }

    function renderPager({ page, pages, total, per_page }) {
        const from = total === 0 ? 0 : (page - 1) * per_page + 1;
        const to = Math.min(page * per_page, total);
        $('reportInfo').textContent = `Showing ${from}-${to} of ${total}`;

        const start = Math.max(1, Math.min(page - 2, pages - 4));
        const end = Math.min(pages, start + 4);
        const item = (label, target, { disabled = false, active = false } = {}) =>
            `<li class="page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}">
                <a class="page-link" href="#" data-page="${target}">${label}</a></li>`;

        let html = item('Prev', page - 1, { disabled: page <= 1 });
        for (let p = start; p <= end; p++) html += item(p, p, { active: p === page });
        html += item('Next', page + 1, { disabled: page >= pages });
        $('reportPagination').innerHTML = html;
    }

    async function loadReports(page = state.reportPage) {
        state.reportPage = page;
        const seq = ++state.seq.reports;
        const list = $('reportRows');
        list.innerHTML = emptyState('Loading reports…');

        try {
            const data = await api('get_reports', {
                params: {
                    search: $('reportSearch').value.trim(),
                    status: $('reportStatus').value,
                    sort: $('reportSort').value,
                    page,
                    per_page: state.reportPerPage
                }
            });
            if (seq !== state.seq.reports) return;

            $('statCurrent').textContent = data.stats.current;
            $('statForwarded').textContent = data.stats.forwarded;
            $('statBroken').textContent = data.stats.broken_links;
            $('statResolved').textContent = data.stats.resolved_week;

            state.reportPage = data.pagination.page;
            renderPager(data.pagination);

            if (data.table_missing) {
                list.innerHTML = emptyState('Report tickets are not available yet. The report_table has not been created in the database.');
            } else if (!data.reports.length) {
                list.innerHTML = emptyState('No reports match your filters.');
            } else {
                list.innerHTML = data.reports.map(reportRowHtml).join('');
            }
        } catch (err) {
            if (seq !== state.seq.reports) return;
            list.innerHTML = emptyState(err.message);
        }
    }

    /* ====================================================================== */
    /* Wiring                                                                 */
    /* ====================================================================== */

    function init() {
        // Main tabs (top strip + sidebar)
        document.querySelectorAll('[data-main-tab]').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                switchMainTab(el.dataset.mainTab);
            });
        });
        window.addEventListener('hashchange', () => switchMainTab(location.hash.slice(1)));

        // Catalog
        $('addBookForm').addEventListener('submit', handleAddBook);
        $('editBookForm').addEventListener('submit', handleEditBook);
        document.querySelectorAll('form[data-book-form]').forEach((form) => {
            form.elements.is_digital.addEventListener('change', () => syncDigitalRequired(form));
            form.elements.pdf_file.addEventListener('change', () => syncDigitalRequired(form));
            form.addEventListener('reset', () => setTimeout(() => syncDigitalRequired(form), 0));
            syncDigitalRequired(form);
        });
        $('catalogSearch').addEventListener('input', debounce(loadCatalog));
        document.querySelectorAll('.js-catalog-sort').forEach((a) => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                state.catalogSort = a.dataset.sort;
                $('catalogSortLabel').textContent = a.textContent.trim();
                loadCatalog();
            });
        });
        $('catalogBody').addEventListener('click', (e) => {
            const edit = e.target.closest('.js-edit-book');
            if (edit) return openEditModal(edit.dataset.id);
            const del = e.target.closest('.js-delete-book');
            if (del) deleteBook(del.dataset.id, del.dataset.title);
        });
        // <img> error events do not bubble, so listen in the capture phase
        $('catalogBody').addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG' && !e.target.dataset.fallback) {
                e.target.dataset.fallback = '1';
                e.target.src = FALLBACK_COVER;
            }
        }, true);

        // Edit modal: cover preview (falls back to the default cover when the URL/Image does not load)
        const coverPreview = $('editCoverPreview');
        coverPreview.addEventListener('error', () => {
            if (!coverPreview.dataset.fallback) {
                coverPreview.dataset.fallback = '1';
                coverPreview.src = FALLBACK_COVER;
            }
        });
        $('editCover').addEventListener('input', debounce((e) => updateCoverPreview(e.target.value), 400));

        // Accounts
        document.querySelectorAll('[data-account-tab]').forEach((btn) => {
            btn.addEventListener('click', () => loadAccountTab(btn.dataset.accountTab));
        });
        $('accountSearch').addEventListener('input', debounce(() => loadAccountTab()));
        $('accountStatusFilter').addEventListener('change', () => loadAccountTab());
        $('accountRows').addEventListener('click', handleAccountClick);
        $('accountRows').addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG' && !e.target.dataset.fallback) {
                e.target.dataset.fallback = '1';
                e.target.src = FALLBACK_AVATAR;
            }
        }, true);

        // Reports
        $('reportSearch').addEventListener('input', debounce(() => loadReports(1)));
        $('reportFilterForm').addEventListener('submit', (e) => {
            e.preventDefault();
            bootstrap.Dropdown.getOrCreateInstance($('reportFilterToggle')).hide();
            loadReports(1);
        });
        $('reportFilterReset').addEventListener('click', () => {
            $('reportFilterForm').reset();
            bootstrap.Dropdown.getOrCreateInstance($('reportFilterToggle')).hide();
            loadReports(1);
        });
        $('reportPerPage').addEventListener('change', (e) => {
            state.reportPerPage = parseInt(e.target.value, 10) || 8;
            loadReports(1);
        });
        $('reportRows').addEventListener('click', async (e) => {
            const btn = e.target.closest('button[data-report-status]');
            if (!btn) return;
            const row = btn.closest('.reportRow');
            setBusy(btn, true);
            try {
                const data = await api('update_report_status', {
                    method: 'POST',
                    params: { report_id: row.dataset.id, status: btn.dataset.reportStatus }
                });
                notify(data.message);
                loadReports();
            } catch (err) {
                notify(err.message, 'error');
                setBusy(btn, false);
            }
        });

        $('reportPagination').addEventListener('click', (e) => {
            e.preventDefault();
            const link = e.target.closest('a[data-page]');
            if (!link || link.closest('.disabled') || link.closest('.active')) return;
            loadReports(parseInt(link.dataset.page, 10));
        });

        switchMainTab(location.hash.slice(1) || 'catalog');
    }

    Object.assign(window, { switchMainTab, loadCatalog, loadAccountTab, updateAccountStatus, loadReports });
    window.lmConfirm = confirmAction;
    window.lmPrompt = promptAction;
    window.lmNotify = notify;

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
