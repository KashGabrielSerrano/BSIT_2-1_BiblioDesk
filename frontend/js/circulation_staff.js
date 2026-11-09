/* Library Management -> Circulation tab (staff). Talks to backend/circulation_controller.php.
   Sections: Requests (approve / reject), Borrowed + Overdue (return, lost, damage fine),
   Reservations (hand out when ready, reject), Fines (paid / waive).
   Uses the page's confirm dialog and toasts (window.lmConfirm / window.lmNotify from librarymanagement.js).
*/
(function () {
    'use strict';

    var API = 'circulation_controller.php';
    var rowsEl = document.getElementById('circRows');
    if (!rowsEl) return;

    var headEl = document.getElementById('circHead');
    var info = document.getElementById('circInfo');
    var tabs = document.getElementById('circTabs');
    var section = 'requests';
    var items = [];
    var seq = 0;

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return window.csrfToken || (meta && meta.content) || '';
    }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function peso(n) { return '₱' + Number(n || 0).toFixed(2); }
    function toast(msg, type) { if (window.lmNotify) window.lmNotify(msg, type); else window.alert(msg); }
    function confirmIt(opts) { return window.lmConfirm ? window.lmConfirm(opts) : Promise.resolve(window.confirm(opts.message)); }

    function post(fields) {
        var fd = new FormData();
        fd.set('csrf_token', csrf());
        Object.keys(fields).forEach(function (k) { fd.set(k, fields[k]); });
        return fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (!d.success) throw new Error(d.message || 'Request failed.'); return d; });
    }

    var btn = function (act, label, cls) {
        return '<button type="button" class="btn btn-sm rounded-pill px-3 ' + (cls || 'btn-outline-dark') + '" data-act="' + act + '">' + label + '</button>';
    };
    var who = function (i) { return esc(i.student) + (i.student_id ? '<br><small class="text-muted">' + esc(i.student_id) + '</small>' : ''); };

    var VIEWS = {
        requests: {
            head: ['Book', 'Student', 'Requested', 'Copies free', 'Actions'],
            row: function (i) {
                return '<td><strong>' + esc(i.title) + '</strong><br><small class="text-muted">' + esc(i.author) + '</small></td>' +
                    '<td>' + who(i) + '</td><td>' + esc(i.requested) + '</td>' +
                    '<td>' + (i.available > 0 ? '<span class="badge bg-success">' + i.available + '</span>' : '<span class="badge bg-danger">0</span>') + '</td>' +
                    '<td class="text-nowrap">' + btn('approve', 'Approve', 'btn-success') + ' ' + btn('reject', 'Reject', 'btn-outline-danger') + '</td>';
            },
            empty: 'No borrow requests waiting.'
        },
        borrowed: {
            head: ['Book', 'Student', 'Borrowed', 'Due', 'Actions'],
            row: function (i) {
                var due = esc(i.due) + (i.overdue ? '<br><span class="badge bg-danger">' + i.days_late + ' day(s) late · ' + peso(i.fine_so_far) + '</span>' : '');
                return '<td><strong>' + esc(i.title) + '</strong>' + (i.note ? '<br><small class="text-muted">' + esc(i.note) + '</small>' : '') + '</td>' +
                    '<td>' + who(i) + '</td><td>' + esc(i.borrowed) + '</td><td>' + due + '</td>' +
                    '<td class="text-nowrap">' + btn('return', 'Returned', 'btn-success') + ' ' + btn('lost', 'Lost', 'btn-outline-danger') + ' ' + btn('damaged', 'Damage fine') + '</td>';
            },
            empty: 'No books are out on loan.'
        },
        reservations: {
            head: ['Book', 'Student', 'Reserved', 'Status', 'Actions'],
            row: function (i) {
                var status = i.status === 'Ready'
                    ? '<span class="badge bg-success">Ready</span><br><small class="text-muted">hold until ' + esc(i.hold_until) + '</small>'
                    : '<span class="badge bg-warning text-dark">Waiting #' + i.position + '</span>';
                return '<td><strong>' + esc(i.title) + '</strong></td><td>' + who(i) + '</td><td>' + esc(i.created) + '</td><td>' + status + '</td>' +
                    '<td class="text-nowrap">' + (i.status === 'Ready' ? btn('fulfill', 'Picked up', 'btn-success') + ' ' : '') + btn('reject_reservation', 'Reject', 'btn-outline-danger') + '</td>';
            },
            empty: 'No active reservations.'
        },
        fines: {
            head: ['Book', 'Student', 'Fine', 'Status', 'Actions'],
            row: function (i) {
                var badge = { Unpaid: 'bg-danger', Paid: 'bg-success', Waived: 'bg-secondary' }[i.status];
                return '<td><strong>' + esc(i.title) + '</strong><br><small class="text-muted">' + esc(i.date) + (i.note ? ' · ' + esc(i.note) : '') + '</small></td>' +
                    '<td>' + who(i) + '</td><td><strong>' + peso(i.amount) + '</strong><br><small>' + esc(i.reason) + '</small></td>' +
                    '<td><span class="badge ' + badge + '">' + esc(i.status) + '</span>' + (i.settled ? '<br><small class="text-muted">' + esc(i.settled) + '</small>' : '') + '</td>' +
                    '<td class="text-nowrap">' + (i.status === 'Unpaid' ? btn('paid', 'Paid', 'btn-success') + ' ' + btn('waive', 'Waive') : '') + '</td>';
            },
            empty: 'No fines.'
        }
    };
    VIEWS.overdue = Object.assign({}, VIEWS.borrowed, { empty: 'Nothing is overdue.' });

    function render() {
        var v = VIEWS[section];
        headEl.innerHTML = v.head.map(function (h) { return '<th scope="col">' + h + '</th>'; }).join('');
        rowsEl.innerHTML = items.length
            ? items.map(function (i) { return '<tr data-id="' + i.id + '">' + v.row(i) + '</tr>'; }).join('')
            : '<tr><td colspan="' + v.head.length + '" class="py-4 text-center text-muted">' + v.empty + '</td></tr>';
    }

    function load() {
        var mine = ++seq;
        rowsEl.innerHTML = '<tr><td class="py-4 text-center">Loading…</td></tr>';
        return fetch(API + '?action=staff_list&section=' + section, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (mine !== seq) return;
                if (!d.success) throw new Error(d.message);
                items = d.items || [];
                Object.keys(d.counts || {}).forEach(function (k) {
                    var el = tabs.querySelector('[data-circ-count="' + k + '"]');
                    if (el) el.textContent = d.counts[k];
                });
                info.textContent = section === 'requests' ? 'Approving a request lends the book for ' + d.settings.loan_days + ' days (System settings).'
                    : section === 'fines' ? 'Fines are paid at the counter. Mark them here once paid, or waive them.' : '';
                render();
            })
            .catch(function (err) {
                if (mine !== seq) return;
                rowsEl.innerHTML = '<tr><td class="py-4 text-center text-danger">' + esc(err.message || 'Could not load.') + '</td></tr>';
            });
    }

    tabs.addEventListener('click', function (e) {
        var b = e.target.closest('[data-circ-section]');
        if (!b) return;
        tabs.querySelectorAll('[data-circ-section]').forEach(function (x) {
            var on = x === b;
            x.classList.toggle('active', on);
            x.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        section = b.getAttribute('data-circ-section');
        load();
    });

    rowsEl.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (!b) return;
        var id = b.closest('tr').getAttribute('data-id');
        var item = items.find(function (x) { return String(x.id) === id; });
        var act = b.getAttribute('data-act');
        var run = function (fields, msg) {
            b.disabled = true;
            return post(fields).then(function (d) { toast(msg || d.message); load(); })
                .catch(function (err) { toast(err.message, 'error'); b.disabled = false; });
        };

        if (act === 'approve') return run({ action: 'approve', loan_id: id });
        if (act === 'return') return run({ action: 'return', loan_id: id });
        if (act === 'fulfill') return run({ action: 'fulfill', id: id });
        if (act === 'paid' || act === 'waive') return run({ action: 'settle_fine', id: id, status: act === 'paid' ? 'Paid' : 'Waived' });
        if (act === 'reject') {
            var note = window.prompt('Reason for declining (optional, the student will see it):', '');
            if (note === null) return;
            return run({ action: 'reject', loan_id: id, note: note });
        }
        if (act === 'reject_reservation') {
            return confirmIt({ title: 'Reject this reservation?', message: item.student + ' will be removed from the waiting list for "' + item.title + '".', confirmLabel: 'Reject' })
                .then(function (ok) { if (ok) run({ action: 'reject_reservation', id: id }); });
        }
        if (act === 'lost') {
            return confirmIt({ title: 'Mark as lost?', message: '"' + item.title + '" will be removed from the copy count and ' + item.student + ' will be charged the lost book fee.', confirmLabel: 'Mark as lost' })
                .then(function (ok) { if (ok) run({ action: 'lost', loan_id: id }); });
        }
        if (act === 'damaged') {
            var amount = window.prompt('Damage fine for "' + item.title + '" (e.g. 150 or 150.50):', '');
            if (amount === null) return;
            var why = window.prompt('What was damaged? (optional)', '') || '';
            return run({ action: 'damaged', loan_id: id, amount: amount.trim(), note: why });
        }
    });

    window.loadCirculation = load;
    // opened directly at #circulation: the tab was shown before this script loaded
    if (location.hash === '#circulation') load();
})();
