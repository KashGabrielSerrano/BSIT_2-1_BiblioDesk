(function () {
    'use strict';

    /* ---------- DATA (replace with a fetch() to your PHP endpoint later) ---------- */
    var CATEGORIES = [
        { name: 'System Update',  cls: 'cat-system' },
        { name: 'Library Policy', cls: 'cat-policy' },
        { name: 'Events',         cls: 'cat-events' },
        { name: 'Collections',    cls: 'cat-collection' }
    ];

    var announcements = [
        { id: 1,  title: 'Scheduled System Maintenance', body: 'BiblioDesk will undergo scheduled maintenance on October 31, 2026, from 5:00 PM to 8:00 PM (GMT+8). Some services may be temporarily unavailable.', category: 'System Update',  status: 'published', date: '2026-10-29T09:30', pinned: true },
        { id: 2,  title: 'Updated Borrowing Policy',     body: 'Students can now borrow up to 3 books per week. Please review the updated borrowing guidelines before placing a request.', category: 'Library Policy', status: 'published', date: '2026-10-27T14:15' },
        { id: 3,  title: 'Annual Book Fest 2026',        body: 'Join our annual Book Fest on November 10. Visit the library portal for event details and announcements.', category: 'Events',         status: 'scheduled', date: '2026-11-05T08:00' },
        { id: 4,  title: 'New Arrivals in Fiction',      body: '12 new titles were added to the Fiction shelf this week.', category: 'Collections',    status: 'published', date: '2026-10-26T10:00' },
        { id: 5,  title: 'Extended Weekend Hours',       body: 'The library reading rooms stay open until 8:00 PM on Saturdays starting this month.', category: 'Library Policy', status: 'published', date: '2026-10-22T16:40' },
        { id: 6,  title: 'Digital Library ID QR',        body: 'Scan your QR code on the Overview page for faster checkout.', category: 'System Update',  status: 'published', date: '2026-10-20T11:05' },
        { id: 7,  title: 'Reading Challenge Sign-ups',   body: 'Register for the November reading challenge at the front desk or through the portal.', category: 'Events',         status: 'published', date: '2026-10-15T09:00' },
        { id: 8,  title: 'Report Logs Now Available',    body: 'Librarians can now review and forward book reports from the Report Logs page.', category: 'System Update',  status: 'published', date: '2026-10-12T13:20' },
        { id: 9,  title: 'Author Talk: Local Writers',   body: 'Meet three local authors at the library hall for a talk and book signing.', category: 'Events',         status: 'scheduled', date: '2026-11-12T08:00' },
        { id: 10, title: 'Holiday Closure Schedule',     body: 'Draft of the December closure dates for review.', category: 'Library Policy', status: 'draft',     date: '2026-11-01T15:10' },
        { id: 11, title: 'Study Room Reservations',      body: 'Draft rules for booking group study rooms.', category: 'Library Policy', status: 'draft',     date: '2026-10-30T10:30' },
        { id: 12, title: 'Book Donation Drive',          body: 'Bring gently used books to the front desk until the end of the month.', category: 'Events',         status: 'published', date: '2026-10-05T09:45' }
    ];

    var PER_PAGE = 3;
    var state = { q: '', status: '', category: '', sort: 'newest', page: 1 };
    var editingId = null;
    var nextId = 13;

    /* ---------- ELEMENTS ---------- */
    var $ = function (id) { return document.getElementById(id); };
    var listEl = $('annList'), pagerEl = $('pager'), infoEl = $('pageInfo');
    var dialog = $('annDialog'), form = $('annForm');

    /* ---------- HELPERS ---------- */
    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function catClass(name) {
        for (var i = 0; i < CATEGORIES.length; i++) if (CATEGORIES[i].name === name) return CATEGORIES[i].cls;
        return 'cat-collection';
    }

    function thumbLabel(a) { return a.pinned ? 'PINN' : a.category.slice(0, 4).toUpperCase(); }

    function fmtDate(iso) {
        return new Date(iso).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
    }

    function fmtTime(iso) {
        return new Date(iso).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function metaLine(a) {
        if (a.status === 'scheduled') return 'Publishes ' + fmtDate(a.date) + ' \u2022 ' + a.category;
        if (a.status === 'draft')     return 'Draft saved ' + fmtDate(a.date) + ' \u2022 ' + a.category;
        return fmtDate(a.date) + ' \u2022 ' + fmtTime(a.date) + ' \u2022 ' + a.category;
    }

    function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    /* ---------- FILTER + SORT ---------- */
    function getFiltered() {
        var q = state.q.toLowerCase();
        var out = announcements.filter(function (a) {
            if (state.status && a.status !== state.status) return false;
            if (state.category && a.category !== state.category) return false;
            if (q && (a.title + ' ' + a.body + ' ' + a.category).toLowerCase().indexOf(q) === -1) return false;
            return true;
        });

        out.sort(function (a, b) {
            switch (state.sort) {
                case 'oldest':     return new Date(a.date) - new Date(b.date);
                case 'title-asc':  return a.title.localeCompare(b.title);
                case 'title-desc': return b.title.localeCompare(a.title);
                default:           return new Date(b.date) - new Date(a.date);
            }
        });
        return out;
    }

    /* ---------- RENDER ---------- */
    function renderStats() {
        function count(s) { return announcements.filter(function (a) { return a.status === s; }).length; }
        $('statTotal').textContent = announcements.length;
        $('statPublished').textContent = count('published');
        $('statScheduled').textContent = count('scheduled');
        $('statDrafts').textContent = count('draft');
    }

    function rowHTML(a) {
        var publishItem = a.status !== 'published'
            ? '<button type="button" data-action="publish" data-id="' + a.id + '">Publish now</button>' : '';
        return '' +
        '<article class="annRow">' +
            '<span class="thumb ' + catClass(a.category) + '" aria-hidden="true">' + esc(thumbLabel(a)) + '</span>' +
            '<div class="annText">' +
                '<h3 class="annTitle">' + esc(a.title) + '</h3>' +
                '<p class="annBody">' + esc(a.body) + '</p>' +
                '<p class="annMeta">' + esc(metaLine(a)) + '</p>' +
            '</div>' +
            '<span class="badge ' + a.status + '">' + cap(a.status) + '</span>' +
            '<div class="rowMenuWrap">' +
                '<button class="btnEdit" type="button" aria-haspopup="true" aria-expanded="false" data-menu="' + a.id + '">Edit &#8942;</button>' +
                '<div class="rowMenu" role="menu">' +
                    '<button type="button" data-action="edit" data-id="' + a.id + '">Edit details</button>' +
                    publishItem +
                    '<button type="button" class="danger" data-action="delete" data-id="' + a.id + '">Delete</button>' +
                '</div>' +
            '</div>' +
        '</article>';
    }

    function notifyChange() {
        window.biblioAnnouncements = announcements;
        document.dispatchEvent(new CustomEvent('announcements:changed', { detail: announcements }));
    }

    function render() {
        var items = getFiltered();
        var total = items.length;
        var pages = Math.max(1, Math.ceil(total / PER_PAGE));
        if (state.page > pages) state.page = pages;

        var start = (state.page - 1) * PER_PAGE;
        var slice = items.slice(start, start + PER_PAGE);

        if (!slice.length) {
            listEl.innerHTML = '<div class="emptyState"><strong>No announcements found</strong>Try a different search or clear the filters.</div>';
            infoEl.textContent = 'Showing 0 of ' + announcements.length;
        } else {
            listEl.innerHTML = slice.map(rowHTML).join('');
            infoEl.textContent = 'Showing ' + (start + 1) + '-' + (start + slice.length) + ' of ' + total;
        }

        renderPager(pages);
        renderStats();
        notifyChange();
    }

    function renderPager(pages) {
        if (pages <= 1) { pagerEl.innerHTML = ''; return; }

        var html = '<button class="pageBtn" type="button" data-page="' + (state.page - 1) + '"' +
                   (state.page === 1 ? ' disabled' : '') + '>Prev</button>';

        for (var i = 1; i <= pages; i++) {
            html += '<button class="pageBtn' + (i === state.page ? ' active' : '') + '" type="button" data-page="' + i + '"' +
                    (i === state.page ? ' aria-current="page"' : '') + '>' + i + '</button>';
        }

        html += '<button class="pageBtn" type="button" data-page="' + (state.page + 1) + '"' +
                (state.page === pages ? ' disabled' : '') + '>Next</button>';

        pagerEl.innerHTML = html;
    }

    /* ---------- TOOLBAR EVENTS ---------- */
    CATEGORIES.forEach(function (c) {
        $('categoryFilter').insertAdjacentHTML('beforeend', '<option value="' + esc(c.name) + '">' + esc(c.name) + '</option>');
        $('fCategory').insertAdjacentHTML('beforeend', '<option value="' + esc(c.name) + '">' + esc(c.name) + '</option>');
    });

    $('searchInput').addEventListener('input', function (e) { state.q = e.target.value.trim(); state.page = 1; render(); });
    $('statusFilter').addEventListener('change', function (e) { state.status = e.target.value; state.page = 1; render(); });
    $('categoryFilter').addEventListener('change', function (e) { state.category = e.target.value; state.page = 1; render(); });
    $('sortSelect').addEventListener('change', function (e) { state.sort = e.target.value; state.page = 1; render(); });

    $('btnReset').addEventListener('click', function () {
        state = { q: '', status: '', category: '', sort: 'newest', page: 1 };
        $('searchInput').value = '';
        $('statusFilter').value = '';
        $('categoryFilter').value = '';
        $('sortSelect').value = 'newest';
        render();
    });

    /* ---------- PAGER ---------- */
    pagerEl.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-page]');
        if (!btn || btn.disabled) return;
        state.page = parseInt(btn.getAttribute('data-page'), 10);
        render();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ---------- ROW MENU ---------- */
    function closeMenus() {
        document.querySelectorAll('.rowMenu.open').forEach(function (m) { m.classList.remove('open'); });
        document.querySelectorAll('.btnEdit[aria-expanded="true"]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
    }

    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-menu]');
        if (toggle) {
            var menu = toggle.nextElementSibling;
            var wasOpen = menu.classList.contains('open');
            closeMenus();
            if (!wasOpen) { menu.classList.add('open'); toggle.setAttribute('aria-expanded', 'true'); }
            return;
        }

        var act = e.target.closest('[data-action]');
        if (act) {
            var id = parseInt(act.getAttribute('data-id'), 10);
            var action = act.getAttribute('data-action');
            closeMenus();
            if (action === 'edit') openDialog(id);
            if (action === 'delete') deleteItem(id);
            if (action === 'publish') publishItem(id);
            return;
        }

        closeMenus();
    });

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeMenus(); });

    function findById(id) {
        for (var i = 0; i < announcements.length; i++) if (announcements[i].id === id) return announcements[i];
        return null;
    }

    function deleteItem(id) {
        var a = findById(id);
        if (!a || !window.confirm('Delete "' + a.title + '"? This cannot be undone.')) return;
        announcements = announcements.filter(function (x) { return x.id !== id; });
        render();
    }

    function publishItem(id) {
        var a = findById(id);
        if (!a) return;
        a.status = 'published';
        a.date = new Date().toISOString().slice(0, 16);
        render();
    }

    /* ---------- DIALOG ---------- */
    function toggleDateField() {
        $('dateField').hidden = $('fStatus').value !== 'scheduled';
    }

    function clearErrors() {
        ['errTitle', 'errDate', 'errBody'].forEach(function (id) { $(id).textContent = ''; });
    }

    function openDialog(id) {
        editingId = id || null;
        clearErrors();
        form.reset();

        if (editingId) {
            var a = findById(editingId);
            $('dialogTitle').textContent = 'Edit Announcement';
            $('fTitle').value = a.title;
            $('fCategory').value = a.category;
            $('fStatus').value = a.status;
            $('fDate').value = a.date.slice(0, 10);
            $('fBody').value = a.body;
        } else {
            $('dialogTitle').textContent = 'Create Announcement';
            $('fStatus').value = 'published';
        }

        toggleDateField();
        dialog.showModal();
        $('fTitle').focus();
    }

    $('btnCreate').addEventListener('click', function () { openDialog(null); });
    $('btnCancel').addEventListener('click', function () { dialog.close(); });
    $('fStatus').addEventListener('change', toggleDateField);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var title = $('fTitle').value.trim();
        var body = $('fBody').value.trim();
        var status = $('fStatus').value;
        var dateVal = $('fDate').value;
        var ok = true;

        if (!title) { $('errTitle').textContent = 'Enter a title.'; ok = false; }
        if (!body)  { $('errBody').textContent = 'Enter a message.'; ok = false; }
        if (status === 'scheduled' && !dateVal) { $('errDate').textContent = 'Pick a publish date.'; ok = false; }
        if (!ok) return;

        var date = status === 'scheduled' ? dateVal + 'T08:00' : new Date().toISOString().slice(0, 16);

        if (editingId) {
            var a = findById(editingId);
            a.title = title; a.body = body; a.category = $('fCategory').value; a.status = status; a.date = date;
        } else {
            announcements.push({ id: nextId++, title: title, body: body, category: $('fCategory').value, status: status, date: date });
        }

        dialog.close();
        state.page = 1;
        render();
    });

    /* ---------- INIT ---------- */
    render();
})();