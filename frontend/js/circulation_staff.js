/* Library Management -> Circulation tab (staff). Talks to backend/circulation_controller.php.
   Sections: Requests (approve / reject), Borrowed + Overdue (return, lost, damage fine),
   Reservations (hand out when ready, reject), Fines (paid / waive).
   Uses the page's dialogs and toasts (window.lmConfirm / lmPrompt / lmNotify from librarymanagement.js).
   The table reuses the Account Management look (.amTable, .actBtn, .pill in librarian.css).
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
    function promptIt(opts) {
        if (window.lmPrompt) return window.lmPrompt(opts);
        var out = {};
        for (var i = 0; i < opts.fields.length; i++) {
            var v = window.prompt(opts.fields[i].label, '');
            if (v === null) return Promise.resolve(null);
            out[opts.fields[i].name] = v.trim();
        }
        return Promise.resolve(out);
    }

    function post(fields) {
        var fd = new FormData();
        fd.set('csrf_token', csrf());
        Object.keys(fields).forEach(function (k) { fd.set(k, fields[k]); });
        return fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (!d.success) throw new Error(d.message || 'Request failed.'); return d; });
    }

    /* ---------- cell helpers (markup matches the Account Management table) ---------- */

    // kind: approve (green, check) | deny (red, cross) | neutral (dark brown)
    var btn = function (act, label, kind) {
        return '<button type="button" class="actBtn ' + kind + '" data-act="' + act + '">' + label + '</button>';
    };
    var actions = function (html) { return '<div class="acctActions">' + html + '</div>'; };
    var titleCell = function (i, sub) {
        return '<p class="circName">' + esc(i.title) + '</p>' + (sub ? '<p class="circSub">' + esc(sub) + '</p>' : '');
    };
    var who = function (i) {
        return '<p class="circName">' + esc(i.student) + '</p>' + (i.student_id ? '<p class="circSub">' + esc(i.student_id) + '</p>' : '');
    };
    // cells alternate the two row tones (cB, cA, cB ...) like the account rows
    var cells = function (list) {
        return list.map(function (html, n) { return '<td class="' + (n % 2 ? 'cA' : 'cB') + '">' + html + '</td>'; }).join('');
    };

    var VIEWS = {
        requests: {
            head: ['Book', 'Student', 'Requested', 'Copies free', 'Actions'],
            row: function (i) {
                return cells([
                    titleCell(i, i.author),
                    who(i),
                    esc(i.requested),
                    '<span class="pill auto ' + (i.available > 0 ? 'ok' : 'bad') + '">' + (i.available > 0 ? i.available : 0) + '</span>',
                    actions(btn('approve', 'Approve', 'approve') + btn('reject', 'Reject', 'deny'))
                ]);
            },
            empty: 'No borrow requests waiting.'
        },
        borrowed: {
            head: ['Book', 'Student', 'Borrowed', 'Due', 'Actions'],
            row: function (i) {
                var due = esc(i.due) + (i.overdue
                    ? '<br><span class="pill bad auto mt-1">' + i.days_late + ' day(s) late · ' + peso(i.fine_so_far) + '</span>' : '');
                return cells([
                    titleCell(i, i.note),
                    who(i),
                    esc(i.borrowed),
                    due,
                    actions(btn('return', 'Returned', 'approve') + btn('lost', 'Lost', 'deny') + btn('damaged', 'Damage fine', 'neutral'))
                ]);
            },
            empty: 'No books are out on loan.'
        },
        reservations: {
            head: ['Book', 'Student', 'Reserved', 'Status', 'Actions'],
            row: function (i) {
                var status = i.status === 'Ready'
                    ? '<span class="pill ok">Ready</span><p class="circSub">hold until ' + esc(i.hold_until) + '</p>'
                    : '<span class="pill warn">Waiting #' + i.position + '</span>';
                return cells([
                    titleCell(i),
                    who(i),
                    esc(i.created),
                    status,
                    actions((i.status === 'Ready' ? btn('fulfill', 'Picked up', 'approve') : '') + btn('reject_reservation', 'Reject', 'deny'))
                ]);
            },
            empty: 'No active reservations.'
        },
        fines: {
            head: ['Book', 'Student', 'Fine', 'Status', 'Actions'],
            row: function (i) {
                var kind = { Unpaid: 'bad', Paid: 'ok', Waived: 'neutral' }[i.status] || 'neutral';
                return cells([
                    titleCell(i, i.date + (i.note ? ' · ' + i.note : '')),
                    who(i),
                    '<strong>' + peso(i.amount) + '</strong><p class="circSub">' + esc(i.reason) + '</p>',
                    '<span class="pill ' + kind + '">' + esc(i.status) + '</span>' + (i.settled ? '<p class="circSub">' + esc(i.settled) + '</p>' : ''),
                    i.status === 'Unpaid' ? actions(btn('paid', 'Paid', 'approve') + btn('waive', 'Waive', 'neutral')) : ''
                ]);
            },
            empty: 'No fines.'
        }
    };
    VIEWS.overdue = Object.assign({}, VIEWS.borrowed, { empty: 'Nothing is overdue.' });

    function messageRow(text, cls) {
        return '<tr class="emptyRow"><td colspan="5"' + (cls ? ' class="' + cls + '"' : '') + '>' + text + '</td></tr>';
    }

    function render() {
        var v = VIEWS[section];
        headEl.innerHTML = v.head.map(function (h) { return '<th scope="col">' + h + '</th>'; }).join('');
        rowsEl.innerHTML = items.length
            ? items.map(function (i) { return '<tr data-id="' + i.id + '">' + v.row(i) + '</tr>'; }).join('')
            : messageRow(v.empty);
    }

    function load() {
        var mine = ++seq;
        rowsEl.innerHTML = messageRow('Loading…');
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
                rowsEl.innerHTML = messageRow(esc(err.message || 'Could not load.'), 'text-danger');
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
        if (act === 'return') {
            var late = item.overdue
                ? ' It is ' + item.days_late + ' day(s) late, so a ' + peso(item.fine_so_far) + ' overdue fine will be added.'
                : '';
            return confirmIt({
                title: 'Mark as returned?',
                message: '"' + item.title + '" from ' + item.student + ' will be marked as returned and the copy goes back on the shelf.' + late,
                confirmLabel: 'Mark returned',
                variant: 'brand',
                icon: 'bi-check-circle-fill',
                iconColor: 'var(--btn)'
            }).then(function (ok) { if (ok) run({ action: 'return', loan_id: id }); });
        }
        if (act === 'fulfill') return run({ action: 'fulfill', id: id });
        if (act === 'paid' || act === 'waive') return run({ action: 'settle_fine', id: id, status: act === 'paid' ? 'Paid' : 'Waived' });
        if (act === 'reject') {
            return promptIt({
                title: 'Decline this request?',
                message: item.student + ' asked to borrow "' + item.title + '".',
                icon: 'bi-x-circle',
                variant: 'danger',
                confirmLabel: 'Decline',
                fields: [{
                    name: 'note', label: 'Reason', type: 'textarea', maxlength: 255,
                    placeholder: 'e.g. All copies are on hold for a class',
                    hint: 'The student will see this in their notification.'
                }]
            }).then(function (v) { if (v) run({ action: 'reject', loan_id: id, note: v.note }); });
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
            return promptIt({
                title: 'Charge a damage fine',
                message: '"' + item.title + '" borrowed by ' + item.student + '.',
                icon: 'bi-cash-coin',
                confirmLabel: 'Add fine',
                fields: [
                    {
                        name: 'amount', label: 'Fine amount', prefix: '₱', required: true, maxlength: 9,
                        inputmode: 'decimal', placeholder: '150 or 150.50',
                        // same rule as the controller: up to 2 decimals, more than 0, at most 100,000
                        validate: function (v) {
                            return /^\d+(\.\d{1,2})?$/.test(v) && parseFloat(v) > 0 && parseFloat(v) <= 100000
                                ? '' : 'Enter an amount like 150 or 150.50 (up to ₱100,000).';
                        }
                    },
                    { name: 'note', label: 'What was damaged?', maxlength: 255, placeholder: 'e.g. Water damage on pages 40-60' }
                ]
            }).then(function (v) { if (v) run({ action: 'damaged', loan_id: id, amount: v.amount, note: v.note }); });
        }
    });

    window.loadCirculation = load;
    // opened directly at #circulation: the tab was shown before this script loaded
    if (location.hash === '#circulation') load();
})();
