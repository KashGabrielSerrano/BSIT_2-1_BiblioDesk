
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var LIST_KEY  = 'bibliodesk_announcements';
    var PAGE_SIZE = 5;

    var CATEGORIES = [
        { name: 'System Update',  cls: 'cat-system',     abbr: 'SYS' },
        { name: 'Library Policy', cls: 'cat-policy',     abbr: 'POL' },
        { name: 'Events',         cls: 'cat-events',     abbr: 'EVT' },
        { name: 'Collections',    cls: 'cat-collection', abbr: 'COL' }
    ];

    var STATUS_LABEL = { published: 'Published', scheduled: 'Scheduled', draft: 'Draft' };

    function $(id) { return document.getElementById(id); }

    var listEl = $('annList');
    if (!listEl) return;   // not the announcements page

    var items = load();
    var page = 1;
    var editingId = null;

    // ---------- helpers ----------
    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function todayISO() {
        var d = new Date();
        return d.getFullYear() + '-' +
               String(d.getMonth() + 1).padStart(2, '0') + '-' +
               String(d.getDate()).padStart(2, '0');
    }

    function niceDate(iso) {
        var d = new Date(iso + 'T00:00:00');
        if (isNaN(d)) return '';
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function catInfo(name) {
        for (var i = 0; i < CATEGORIES.length; i++) {
            if (CATEGORIES[i].name === name) return CATEGORIES[i];
        }
        return { name: name, cls: 'cat-collection', abbr: String(name).slice(0, 3).toUpperCase() };
    }

    
    function load() {
        var raw;
        try { raw = JSON.parse(localStorage.getItem(LIST_KEY)); } catch (e) { raw = null; }
        if (!Array.isArray(raw)) return [];

        var today = todayISO();
        return raw.filter(function (a) { return a && typeof a.title === 'string'; }).map(function (a) {
            var status = STATUS_LABEL[a.status] ? a.status : 'published';
            var date = a.date || today;
            if (status === 'scheduled' && date <= today) status = 'published';   // its day has come
            return {
                id: String(a.id || ('a-' + Date.now() + Math.random().toString(36).slice(2, 6))),
                title: a.title,
                body: a.body || '',
                category: a.category || CATEGORIES[0].name,
                status: status,
                date: date
            };
        });
    }

    function save() {
        try { localStorage.setItem(LIST_KEY, JSON.stringify(items)); } catch (e) { /* storage unavailable */ }
        // tell the bell (notif.js) the list changed
        document.dispatchEvent(new CustomEvent('announcements:changed', { detail: items }));
    }

    
    function visibleRows() {
        var q = $('searchInput').value.trim().toLowerCase();
        var status = $('statusFilter').value;
        var cat = $('categoryFilter').value;
        var sort = $('sortSelect').value;

        var rows = items.map(function (a, i) { return { a: a, i: i }; }).filter(function (r) {
            if (status && r.a.status !== status) return false;
            if (cat && r.a.category !== cat) return false;
            if (q && (r.a.title + ' ' + r.a.body + ' ' + r.a.category).toLowerCase().indexOf(q) === -1) return false;
            return true;
        });

        rows.sort(function (x, y) {
            if (sort === 'oldest') return x.a.date.localeCompare(y.a.date) || x.i - y.i;
            if (sort === 'title-asc') return x.a.title.localeCompare(y.a.title);
            if (sort === 'title-desc') return y.a.title.localeCompare(x.a.title);
            return y.a.date.localeCompare(x.a.date) || y.i - x.i;   // newest first
        });

        return rows.map(function (r) { return r.a; });
    }

    
    function renderStats() {
        $('statTotal').textContent = items.length;
        $('statPublished').textContent = items.filter(function (a) { return a.status === 'published'; }).length;
        $('statScheduled').textContent = items.filter(function (a) { return a.status === 'scheduled'; }).length;
        $('statDrafts').textContent = items.filter(function (a) { return a.status === 'draft'; }).length;
    }

    function rowHtml(a) {
        var c = catInfo(a.category);
        var statusAction = a.status === 'published'
            ? '<button type="button" role="menuitem" data-act="draft">Move to draft</button>'
            : '<button type="button" role="menuitem" data-act="publish">Publish now</button>';

        return '<div class="annRow" data-id="' + esc(a.id) + '">' +
            '<span class="thumb ' + c.cls + '" aria-hidden="true">' + esc(c.abbr) + '</span>' +
            '<div class="annText">' +
                '<div class="annTitle">' + esc(a.title) + '</div>' +
                '<div class="annBody">' + esc(a.body) + '</div>' +
                '<div class="annMeta">' + esc(a.category) + ' &middot; ' + esc(niceDate(a.date)) + '</div>' +
            '</div>' +
            '<span class="badge ' + a.status + '">' + STATUS_LABEL[a.status] + '</span>' +
            '<div class="rowMenuWrap">' +
                '<button type="button" class="btnEdit" aria-haspopup="true" aria-expanded="false">Edit</button>' +
                '<div class="rowMenu" role="menu">' +
                    '<button type="button" role="menuitem" data-act="edit">Edit details</button>' +
                    statusAction +
                    '<button type="button" role="menuitem" class="danger" data-act="delete">Delete</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function render() {
        renderStats();

        var rows = visibleRows();
        var total = rows.length;
        var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (page > pages) page = pages;
        var start = (page - 1) * PAGE_SIZE;
        var slice = rows.slice(start, start + PAGE_SIZE);

        if (!items.length) {
            listEl.innerHTML = '<div class="emptyState"><strong>No announcements yet</strong>' +
                'Click &ldquo;+ Create Announcement&rdquo; to post your first one.</div>';
        } else if (!total) {
            listEl.innerHTML = '<div class="emptyState"><strong>No announcements found</strong>' +
                'Try a different search or filter.</div>';
        } else {
            listEl.innerHTML = slice.map(rowHtml).join('');
        }

        $('pageInfo').textContent = total
            ? 'Showing ' + (start + 1) + '-' + (start + slice.length) + ' of ' + total
            : '';

        renderPager(pages);
    }

    function renderPager(pages) {
        var pager = $('pager');
        if (pages <= 1) { pager.innerHTML = ''; return; }

        var html = '<button type="button" class="pageBtn" data-page="' + (page - 1) + '"' +
                   (page === 1 ? ' disabled' : '') + '>Prev</button>';
        for (var p = 1; p <= pages; p++) {
            html += '<button type="button" class="pageBtn' + (p === page ? ' active' : '') +
                    '" data-page="' + p + '"' + (p === page ? ' aria-current="page"' : '') + '>' + p + '</button>';
        }
        html += '<button type="button" class="pageBtn" data-page="' + (page + 1) + '"' +
                (page === pages ? ' disabled' : '') + '>Next</button>';
        pager.innerHTML = html;
    }

    
    CATEGORIES.forEach(function (c) {
        var f = document.createElement('option');
        f.value = c.name; f.textContent = c.name;
        $('categoryFilter').appendChild(f);

        var o = document.createElement('option');
        o.value = c.name; o.textContent = c.name;
        $('fCategory').appendChild(o);
    });

    
    ['searchInput'].forEach(function (id) {
        $(id).addEventListener('input', function () { page = 1; render(); });
    });
    ['statusFilter', 'categoryFilter', 'sortSelect'].forEach(function (id) {
        $(id).addEventListener('change', function () { page = 1; render(); });
    });

    $('btnReset').addEventListener('click', function () {
        $('searchInput').value = '';
        $('statusFilter').value = '';
        $('categoryFilter').value = '';
        $('sortSelect').value = 'newest';
        page = 1;
        render();
    });

    $('pager').addEventListener('click', function (e) {
        var btn = e.target.closest('.pageBtn');
        if (!btn || btn.disabled) return;
        page = parseInt(btn.getAttribute('data-page'), 10) || 1;
        render();
    });

    
    function closeMenus() {
        listEl.querySelectorAll('.rowMenu.open').forEach(function (m) { m.classList.remove('open'); });
        listEl.querySelectorAll('.btnEdit[aria-expanded="true"]').forEach(function (b) {
            b.setAttribute('aria-expanded', 'false');
        });
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.rowMenuWrap')) closeMenus();
    });

    function findItem(id) {
        for (var i = 0; i < items.length; i++) if (items[i].id === id) return items[i];
        return null;
    }

    listEl.addEventListener('click', function (e) {
        var toggle = e.target.closest('.btnEdit');
        if (toggle) {
            var menu = toggle.parentElement.querySelector('.rowMenu');
            var wasOpen = menu.classList.contains('open');
            closeMenus();
            if (!wasOpen) {
                menu.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
            return;
        }

        var actBtn = e.target.closest('[data-act]');
        if (!actBtn) return;

        var row = actBtn.closest('.annRow');
        var item = row && findItem(row.getAttribute('data-id'));
        if (!item) return;

        var act = actBtn.getAttribute('data-act');
        closeMenus();

        if (act === 'edit') {
            openDialog(item);
        } else if (act === 'publish') {
            item.status = 'published';
            item.date = todayISO();
            save(); render();
        } else if (act === 'draft') {
            item.status = 'draft';
            save(); render();
        } else if (act === 'delete') {
            if (window.confirm('Delete "' + item.title + '"? This cannot be undone.')) {
                items = items.filter(function (a) { return a.id !== item.id; });
                save(); render();
            }
        }
    });

   
    var dialog = $('annDialog');
    var form = $('annForm');

    function clearErrors() {
        $('errTitle').textContent = '';
        $('errDate').textContent = '';
        $('errBody').textContent = '';
    }

    function syncDateField() {
        $('dateField').hidden = $('fStatus').value !== 'scheduled';
    }

    function openDialog(item) {
        clearErrors();
        editingId = item ? item.id : null;
        $('dialogTitle').textContent = item ? 'Edit Announcement' : 'Create Announcement';

        $('fTitle').value = item ? item.title : '';
        $('fBody').value = item ? item.body : '';
        $('fCategory').value = item ? item.category : CATEGORIES[0].name;
        $('fStatus').value = item ? item.status : 'published';
        $('fDate').value = item && item.status === 'scheduled' ? item.date : '';
        syncDateField();

        dialog.showModal();
        $('fTitle').focus();
    }

    $('btnCreate').addEventListener('click', function () { openDialog(null); });
    $('btnCancel').addEventListener('click', function () { dialog.close(); });
    $('fStatus').addEventListener('change', syncDateField);

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var title = $('fTitle').value.trim();
        var body = $('fBody').value.trim();
        var status = $('fStatus').value;
        var dateVal = $('fDate').value;
        var ok = true;

        if (!title) { $('errTitle').textContent = 'Please enter a title.'; ok = false; }
        if (!body) { $('errBody').textContent = 'Please enter the message.'; ok = false; }
        if (status === 'scheduled') {
            if (!dateVal) { $('errDate').textContent = 'Please choose a publish date.'; ok = false; }
            else if (dateVal < todayISO()) { $('errDate').textContent = 'The publish date cannot be in the past.'; ok = false; }
        }
        if (!ok) return;

        var existing = editingId ? findItem(editingId) : null;
        var date;
        if (status === 'scheduled') date = dateVal;
        else if (existing && existing.status === status) date = existing.date;   // keep original date
        else date = todayISO();

        if (existing) {
            existing.title = title;
            existing.body = body;
            existing.category = $('fCategory').value;
            existing.status = status;
            existing.date = date;
        } else {
            items.push({
                id: 'a-' + Date.now() + Math.random().toString(36).slice(2, 6),
                title: title,
                body: body,
                category: $('fCategory').value,
                status: status,
                date: date
            });
            page = 1;
        }

        save();
        render();
        dialog.close();
    });

    
    window.addEventListener('storage', function (e) {
        if (e.key === LIST_KEY) {
            items = load();
            render();
        }
    });

    
    render();
    document.dispatchEvent(new CustomEvent('announcements:changed', { detail: items }));
});