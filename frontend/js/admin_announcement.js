document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // Announcements are stored on the server (backend/announcement_controller.php).
    var API       = 'announcement_controller.php';
    var PAGE_SIZE = 5;
    var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
    var CSRF      = (csrfMeta && csrfMeta.content) || '';

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

    var items = [];
    var loaded = false;
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


    /** Ask the server for every announcement (all statuses). */
    function load() {
        return fetch(API + '?action=list', { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || 'Could not load announcements.');
                items = data.items || [];
                loaded = true;
                render();
            })
            .catch(function (err) {
                loaded = true;
                listEl.innerHTML = '<div class="emptyState"><strong>Could not load announcements</strong>' + esc(err.message) + '</div>';
            });
    }

    /** POST an action; resolves with the JSON reply, rejects with an Error carrying the message. */
    function api(action, fields) {
        var fd = new FormData();
        fd.set('action', action);
        fd.set('csrf_token', CSRF);
        Object.keys(fields || {}).forEach(function (k) { fd.set(k, fields[k]); });
        return fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    var err = new Error(data.message || 'Request failed.');
                    err.errors = data.errors || {};
                    throw err;
                }
                return data;
            });
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

        if (!loaded) {
            listEl.innerHTML = '<div class="emptyState"><strong>Loading announcements\u2026</strong></div>';
        } else if (!items.length) {
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
        } else if (act === 'publish' || act === 'draft') {
            api('set_status', { id: item.id, status: act === 'publish' ? 'published' : 'draft' })
                .then(load)
                .catch(function (err) { window.alert(err.message); });
        } else if (act === 'delete') {
            if (window.confirm('Delete "' + item.title + '"? This cannot be undone.')) {
                api('delete', { id: item.id })
                    .then(load)
                    .catch(function (err) { window.alert(err.message); });
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

        var saveBtn = $('btnSave');
        if (saveBtn) saveBtn.disabled = true;
        var fields = { title: title, body: body, category: $('fCategory').value, status: status, date: status === 'scheduled' ? dateVal : '' };
        if (editingId) fields.id = editingId;

        api('save', fields)
            .then(function () {
                if (!editingId) page = 1;
                dialog.close();
                return load();
            })
            .catch(function (err) {
                // show server validation messages next to the fields
                var map = { title: 'errTitle', body: 'errBody', date: 'errDate' };
                var shown = false;
                Object.keys(err.errors || {}).forEach(function (k) {
                    if (map[k]) { $(map[k]).textContent = err.errors[k]; shown = true; }
                });
                if (!shown) window.alert(err.message);
            })
            .then(function () { if (saveBtn) saveBtn.disabled = false; });
    });

    
    render();   // "Loading..." until the server answers
    load();
});