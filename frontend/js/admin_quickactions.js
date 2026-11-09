document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    
    // Real data rendered by backend/core/dashboard.php (staff_overview.php -> window.biblioDashboard)
    var DASH = window.biblioDashboard || {};
    var CATEGORIES = DASH.categories || ['Educational', 'Non-Educational', 'Research Articles'];
    var ANN_CATEGORIES = ['System Update', 'Library Policy', 'Events', 'Collections'];
    var users = DASH.users || [];
    var logs = DASH.logs || [];

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var CSRF = (csrfMeta && csrfMeta.content) || '';

    /** POST to a backend controller; resolves with the JSON reply (success or not). */
    function postTo(endpoint, fields) {
        var fd = new FormData();
        fd.set('csrf_token', CSRF);
        Object.keys(fields).forEach(function (k) { fd.set(k, fields[k]); });
        return fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .catch(function () { return { success: false, message: 'Server connection error. Please try again.' }; });
    }


    
    function $(id) { return document.getElementById(id); }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function fillSelect(id, list) {
        var el = $(id);
        if (!el) return;
        el.innerHTML = list.map(function (v) {
            return '<option value="' + esc(v) + '">' + esc(v) + '</option>';
        }).join('');
    }

    function setError(input, message) {
        input.classList.toggle('is-invalid', !!message);
        var fb = input.parentElement.querySelector('.invalid-feedback');
        if (fb) fb.textContent = message || '';
        return !message;
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    function todayISO() {
        var d = new Date();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return d.getFullYear() + '-' + m + '-' + day;
    }

    function toast(message) {
        var el = $('qaToast');
        if (!el) return;
        $('qaToastBody').textContent = message;
        bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 }).show();
    }

    function closeModal(id) {
        var el = $(id);
        if (el) bootstrap.Modal.getOrCreateInstance(el).hide();
    }



    function bindForm(formId, handler) {
        var form = $(formId);
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors(form);
            handler(form);
        });
    }

    // reset every popup form when it closes
    document.querySelectorAll('.qaModal').forEach(function (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            var form = modal.querySelector('form');
            if (form) { form.reset(); clearErrors(form); }
            var dateField = $('qaAnnDateField');
            if (modal.id === 'qaAnnModal' && dateField) dateField.hidden = true;
        });
    });

    // ---------- select options ----------
    fillSelect('qaBookCategory', CATEGORIES);
    fillSelect('qaAnnCategory', ANN_CATEGORIES);

    // ---------- Add New Book ----------
    bindForm('qaBookForm', function () {
        var title  = $('qaBookTitle');
        var author = $('qaBookAuthor');
        var copies = $('qaBookCopies');

        var ok = true;
        ok = setError(title,  title.value.trim()  ? '' : 'Please enter the book title.') && ok;
        ok = setError(author, author.value.trim() ? '' : 'Please enter the author.') && ok;
        ok = setError(copies, parseInt(copies.value, 10) >= 1 ? '' : 'Copies must be at least 1.') && ok;
        if (!ok) return;

        // Added as a physical book with copies; cover, ISBN, PDF etc. can be filled in later in Library Management.
        postTo('librarymanagement_controller.php', {
            action: 'add_book',
            title: title.value.trim(),
            author: author.value.trim(),
            category: $('qaBookCategory').value,
            description: 'Added from the overview. Edit this book in Library Management to add details.',
            is_digital: 'No',
            total_copies: parseInt(copies.value, 10)
        }).then(function (d) {
            if (!d.success) { setError(title, d.message || 'Could not add the book.'); return; }
            closeModal('qaBookModal');
            toast('Book "' + title.value.trim() + '" added with ' + parseInt(copies.value, 10) + ' copies.');
        });
    });

    
    bindForm('qaLibrarianForm', function () {
        var email = $('qaLibEmail');
        var value = email.value.trim();
        if (!setError(email, /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? '' : 'Please enter a valid email address.')) return;

        postTo('librarymanagement_controller.php', { action: 'update_user_role', email: value, role: 'Librarian' })
            .then(function (d) {
                if (!d.success) { setError(email, d.message || 'Could not change the account.'); return; }
                closeModal('qaLibrarianModal');
                toast(d.message || 'Account changed to Librarian.');
            });
    });

    // ---------- View Users ----------
    var userClass = { Active: 'qaGreen', Pending: 'qaYellow', Suspended: 'qaBrown' };

    function renderUsers(query) {
        var body = $('qaUserRows');
        if (!body) return;
        var q = (query || '').toLowerCase();
        var rows = users.filter(function (u) {
            return !q || (u.name + ' ' + u.email + ' ' + u.role).toLowerCase().indexOf(q) !== -1;
        });

        body.innerHTML = rows.length ? rows.map(function (u) {
            return '<tr>' +
                '<td class="fw-semibold">' + esc(u.name) + '</td>' +
                '<td>' + esc(u.email) + '</td>' +
                '<td>' + esc(u.role) + '</td>' +
                '<td><span class="qaBadge ' + (userClass[u.status] || 'qaBrown') + '">' + esc(u.status) + '</span></td>' +
                '</tr>';
        }).join('') : '<tr><td colspan="4" class="text-center py-4">No users found.</td></tr>';

        $('qaUserCount').textContent = rows.length + ' of ' + users.length + ' users';
    }

    var userSearch = $('qaUserSearch');
    if (userSearch) userSearch.addEventListener('input', function () { renderUsers(userSearch.value); });
    renderUsers('');

    var usersModal = $('qaUsersModal');
    if (usersModal) {
        usersModal.addEventListener('hidden.bs.modal', function () { renderUsers(''); });
    }

    // ---------- View Logs ----------
    var logClass = { New: 'qaGreen', Reviewing: 'qaRed', Forwarded: 'qaYellow', Resolved: 'qaBrown' };

    var logBody = $('qaLogRows');
    if (logBody && !logs.length) {
        logBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No book reports yet.</td></tr>';
    } else if (logBody) {
        logBody.innerHTML = logs.map(function (l) {
            return '<tr>' +
                '<td class="fw-semibold">' + esc(l.book) + '</td>' +
                '<td>' + esc(l.issue) + '</td>' +
                '<td>' + esc(l.by) + '</td>' +
                '<td>' + esc(l.date) + '</td>' +
                '<td><span class="qaBadge ' + (logClass[l.status] || 'qaBrown') + '">' + esc(l.status) + '</span></td>' +
                '</tr>';
        }).join('');
    }

    // ---------- Generate Reports (CSV download) ----------
    function csvCell(v) {
        v = String(v);
        return /[",\r\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
    }

    function downloadCsv(filename, header, rows) {
        var text = [header].concat(rows).map(function (r) { return r.map(csvCell).join(','); }).join('\r\n');
        var blob = new Blob(['\ufeff' + text], { type: 'text/csv;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 0);
    }

    bindForm('qaReportForm', function () {
        var type = $('qaReportType').value;
        var stamp = todayISO();

        if (type === 'users') {
            downloadCsv('bibliodesk-users-' + stamp + '.csv',
                ['Name', 'Email', 'Role', 'Status'],
                users.map(function (u) { return [u.name, u.email, u.role, u.status]; }));
        } else {
            downloadCsv('bibliodesk-report-logs-' + stamp + '.csv',
                ['Book', 'Issue', 'Reported by', 'Date', 'Status'],
                logs.map(function (l) { return [l.book, l.issue, l.by, l.date, l.status]; }));
        }

        closeModal('qaReportModal');
        toast('Report downloaded.');
    });

    // ---------- Create Announcement ----------
    var annStatus = $('qaAnnStatus');
    var annDateField = $('qaAnnDateField');

    if (annStatus && annDateField) {
        annStatus.addEventListener('change', function () {
            annDateField.hidden = annStatus.value !== 'scheduled';
        });
    }

    bindForm('qaAnnForm', function () {
        var title = $('qaAnnTitle');
        var body  = $('qaAnnBody');
        var date  = $('qaAnnDate');
        var status = annStatus.value;

        var ok = true;
        ok = setError(title, title.value.trim() ? '' : 'Please enter a title.') && ok;
        ok = setError(body,  body.value.trim()  ? '' : 'Please enter the message.') && ok;

        if (status === 'scheduled') {
            var msg = '';
            if (!date.value) msg = 'Please choose a publish date.';
            else if (date.value < todayISO()) msg = 'The publish date cannot be in the past.';
            ok = setError(date, msg) && ok;
        }
        if (!ok) return;

        // Saved on the server (backend/announcement_controller.php); publishing notifies the audience.
        var fd = new FormData();
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        fd.set('action', 'save');
        fd.set('csrf_token', (csrfMeta && csrfMeta.content) || '');
        fd.set('title', title.value.trim());
        fd.set('body', body.value.trim());
        fd.set('category', $('qaAnnCategory').value);
        fd.set('status', status);
        fd.set('date', status === 'scheduled' ? date.value : '');

        fetch('announcement_controller.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    var errs = data.errors || {};
                    if (errs.title) setError(title, errs.title);
                    if (errs.body) setError(body, errs.body);
                    if (errs.date) setError(date, errs.date);
                    if (!errs.title && !errs.body && !errs.date) toast(data.message || 'Could not save the announcement.');
                    return;
                }
                closeModal('qaAnnModal');
                toast(data.message);
            })
            .catch(function () { toast('Server connection error. Please try again.'); });
    });
});