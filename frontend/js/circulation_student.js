/* Student side of borrowing (physical books) -> backend/circulation_controller.php
   1. Book details modal: availability + Borrow / Reserve / Cancel, or the student's loan status.
   2. My Books (#myLoans): active loans, reservations, fines and history.
*/
(function () {
    'use strict';

    var API = 'circulation_controller.php';

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return (meta && meta.content) || window.csrfToken || '';
    }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function peso(n) { return '₱' + Number(n || 0).toFixed(2); }
    function get(q) { return fetch(API + '?' + q, { credentials: 'same-origin', cache: 'no-store' }).then(function (r) { return r.json(); }); }
    function post(fields) {
        var fd = new FormData();
        fd.set('csrf_token', csrf());
        Object.keys(fields).forEach(function (k) { fd.set(k, fields[k]); });
        return fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    /* ---------------- 1. book details modal ---------------- */
    var modal = document.getElementById('bookDetailsModal');
    var circBtn = document.getElementById('modalBookBorrow');
    var circInfo = document.getElementById('modalCircInfo');
    var current = null;   // { bookId, action, id }

    function setInfo(html, tone, icon) {
        circInfo.hidden = !html;
        if (!html) {
            circInfo.innerHTML = '';
            return;
        }
        var iconHtml = icon
            ? '<i class="bi ' + esc(icon) + ' flex-shrink-0 fs-5 lh-1" aria-hidden="true"></i>'
            : '';
        circInfo.innerHTML = iconHtml + '<div class="flex-grow-1 lh-sm">' + html + '</div>';
        circInfo.className = 'small w-100 mb-3 d-flex align-items-center gap-2 ' +
            (tone === 'bad' ? 'text-danger' : tone === 'good' ? 'text-success' : 'text-muted');
    }

    function setButton(label, action, id, icon, isCancel) {
        var show = Boolean(label);
        circBtn.classList.toggle('d-none', !show);
        circBtn.classList.toggle('d-inline-flex', show);
        circBtn.classList.toggle('align-items-center', show);
        circBtn.classList.toggle('justify-content-center', show);
        circBtn.classList.toggle('flex-fill', show);

        // Primary theme button for Borrow/Reserve; matching-height outline-danger pill for Cancel states
        circBtn.classList.toggle('bookModal-btn-read', !isCancel);
        circBtn.classList.toggle('border-0', !isCancel);
        circBtn.classList.toggle('btn', Boolean(isCancel));
        circBtn.classList.toggle('btn-outline-danger', Boolean(isCancel));
        circBtn.classList.toggle('rounded-pill', Boolean(isCancel));
        circBtn.classList.toggle('py-2', Boolean(isCancel));
        circBtn.classList.toggle('px-4', Boolean(isCancel));
        circBtn.classList.toggle('fw-semibold', Boolean(isCancel));

        circBtn.disabled = false;
        circBtn.innerHTML = show
            ? (icon ? '<i class="bi ' + esc(icon) + ' me-2 lh-1" aria-hidden="true"></i>' : '') + '<span>' + esc(label) + '</span>'
            : '';
        current.action = action || null;
        current.id = id || null;
    }

    function renderStatus(st) {
        if (!st.physical) { setButton(null); setInfo(''); return; }
        if (st.loan && st.loan.status === 'Requested') {
            setButton('Cancel request', 'cancel_request', st.loan.id, 'bi-x-circle', true);
            return setInfo('Requested. Waiting for the library to approve it at the counter.', '', 'bi-hourglass-split');
        }
        if (st.loan && st.loan.status === 'Borrowed') {
            setButton(null);
            return setInfo('You borrowed this book. Due <strong>' + esc(st.loan.due) + '</strong>' +
                           (st.loan.overdue ? ' (overdue: please return it)' : '') + '.',
                           st.loan.overdue ? 'bad' : 'good', 'bi-book');
        }
        if (st.reservation) {
            setButton('Cancel reservation', 'cancel_reservation', st.reservation.id, 'bi-calendar-x', true);
            return st.reservation.status === 'Ready'
                ? setInfo('Ready for pickup at the counter until <strong>' + esc(st.reservation.hold_until) + '</strong>.', 'good', 'bi-check-circle')
                : setInfo('Reserved. You are <strong>#' + st.reservation.position + '</strong> in line. We will notify you when it is ready.', '', 'bi-people');
        }
        if (st.can_borrow) {
            setButton('Borrow', 'request', null, 'bi-bookmark-check', false);
            return setInfo(st.available + ' of ' + st.total + ' copies available. Pick it up at the counter after approval.', 'good', 'bi-check2-circle');
        }
        if (st.can_reserve) {
            setButton('Reserve', 'reserve', null, 'bi-calendar-plus', false);
            return setInfo('All ' + st.total + ' copies are borrowed. Reserve it to join the waiting list.', '', 'bi-clock');
        }
        setButton(null);
        setInfo(st.reason ? esc(st.reason) : '', st.reason && /unpaid|limit/.test(st.reason) ? 'bad' : '', st.reason ? 'bi-info-circle' : '');
    }

    function loadStatus(bookId) {
        current = { bookId: bookId };
        setButton(null);
        setInfo('');
        if (!bookId) return;
        get('action=book_status&book_id=' + encodeURIComponent(bookId)).then(function (d) {
            if (!current || current.bookId !== bookId) return;   // another book was opened meanwhile
            if (d && d.success) renderStatus(d.status);
        });
    }

    if (modal && circBtn && circInfo) {
        modal.addEventListener('show.bs.modal', function () {
            var idInput = document.getElementById('modalBookId');
            loadStatus(idInput ? idInput.value : '');
        });
        circBtn.addEventListener('click', function () {
            if (!current || !current.action) return;
            var fields = { action: current.action };
            if (current.action === 'request' || current.action === 'reserve') fields.book_id = current.bookId;
            else if (current.action === 'cancel_request') fields.loan_id = current.id;
            else fields.id = current.id;
            circBtn.disabled = true;
            post(fields).then(function (d) {
                if (d.success) {
                    var fallbackMsg = d.message || '';
                    get('action=book_status&book_id=' + encodeURIComponent(current.bookId)).then(function (s) {
                        if (s && s.success) {
                            renderStatus(s.status);
                            // Only show the toast/confirmation message if renderStatus didn't already show a status box
                            if (circInfo.hidden && fallbackMsg) {
                                setInfo(esc(fallbackMsg), 'good', 'bi-check-circle');
                            }
                        }
                    });
                    loadMine();
                } else {
                    setInfo(esc(d.message || 'Action failed.'), 'bad', 'bi-exclamation-circle');
                    circBtn.disabled = false;
                }
            }).catch(function () {
                setInfo('Server connection error. Please try again.', 'bad', 'bi-exclamation-circle');
                circBtn.disabled = false;
            });
        });
    }

    /* ---------------- 2. My Books: loans, reservations, fines ---------------- */
    var mineEl = document.getElementById('myLoans');

    function table(head, rows) {
        return '<div class="table-responsive rounded-4 shadow-sm mb-4"><table class="table align-middle mb-0 bg-white">' +
               '<thead class="table-dark"><tr>' + head.map(function (h) { return '<th scope="col">' + h + '</th>'; }).join('') + '</tr></thead>' +
               '<tbody>' + rows.join('') + '</tbody></table></div>';
    }

    function loadMine() {
        if (!mineEl) return;
        get('action=mine').then(function (d) {
            if (!d || !d.success) { mineEl.innerHTML = '<p class="text-muted">Could not load your loans.</p>'; return; }
            var s = d.summary;
            var active = d.loans.filter(function (l) { return l.status === 'Requested' || l.status === 'Borrowed'; });
            var history = d.loans.filter(function (l) { return l.status !== 'Requested' && l.status !== 'Borrowed'; });
            var unpaid = d.fines.filter(function (f) { return f.status === 'Unpaid'; });

            var html = '<div class="d-flex flex-wrap gap-2 mb-3">' +
                '<span class="badge rounded-pill bg-dark p-2">Borrowed / requested: ' + s.active_loans + ' of ' + s.max_borrows + '</span>' +
                '<span class="badge rounded-pill bg-secondary p-2">Reservations: ' + s.active_reservations + ' of ' + s.max_reservations + '</span>' +
                (s.unpaid > 0 ? '<span class="badge rounded-pill bg-danger p-2">Unpaid fines: ' + peso(s.unpaid) + '</span>' : '') +
                '</div>';

            html += active.length ? table(['Book', 'Status', 'Due', ''], active.map(function (l) {
                var status = l.status === 'Requested' ? '<span class="badge bg-warning text-dark">Requested</span>'
                    : l.overdue ? '<span class="badge bg-danger">Overdue · ' + l.days_late + ' day(s)</span><br><small class="text-danger">Fine so far ' + peso(l.fine_so_far) + '</small>'
                    : '<span class="badge bg-success">Borrowed</span>';
                return '<tr><td><strong>' + esc(l.title) + '</strong><br><small class="text-muted">' + esc(l.author) + '</small></td><td>' + status + '</td>' +
                       '<td>' + esc(l.due || '—') + '</td><td class="text-end">' +
                       (l.status === 'Requested' ? '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-cancel-loan="' + l.id + '">Cancel</button>' : '') + '</td></tr>';
            })) : '<p class="text-muted">You have no borrowed books. Open a physical book in the Library to borrow it.</p>';

            if (d.reservations.length) {
                html += '<h3 class="h5 fw-semibold mt-4">Reservations</h3>' + table(['Book', 'Status', ''], d.reservations.map(function (r) {
                    var st = r.status === 'Ready' ? '<span class="badge bg-success">Ready for pickup</span><br><small>until ' + esc(r.hold_until) + '</small>'
                        : '<span class="badge bg-warning text-dark">Waiting #' + r.position + '</span>';
                    return '<tr><td><strong>' + esc(r.title) + '</strong></td><td>' + st + '</td><td class="text-end">' +
                           '<button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-cancel-res="' + r.id + '">Cancel</button></td></tr>';
                }));
            }
            if (d.fines.length) {
                html += '<h3 class="h5 fw-semibold mt-4">Fines</h3>' + table(['Book', 'Fine', 'Status'], d.fines.map(function (f) {
                    var b = { Unpaid: 'bg-danger', Paid: 'bg-success', Waived: 'bg-secondary' }[f.status];
                    return '<tr><td><strong>' + esc(f.title) + '</strong><br><small class="text-muted">' + esc(f.date) + (f.note ? ' · ' + esc(f.note) : '') + '</small></td>' +
                           '<td>' + peso(f.amount) + '<br><small>' + esc(f.reason) + '</small></td><td><span class="badge ' + b + '">' + esc(f.status) + '</span></td></tr>';
                })) + (unpaid.length ? '<p class="small text-muted">Fines are paid at the library counter.</p>' : '');
            }
            if (history.length) {
                html += '<details class="mt-3"><summary class="fw-semibold">History (' + history.length + ')</summary><div class="mt-2">' +
                    table(['Book', 'Borrowed', 'Returned', 'Result'], history.map(function (l) {
                        return '<tr><td>' + esc(l.title) + '</td><td>' + esc(l.borrowed || '—') + '</td><td>' + esc(l.returned || '—') + '</td>' +
                               '<td>' + esc(l.note === 'Lost' ? 'Lost' : l.status) + '</td></tr>';
                    })) + '</div></details>';
            }
            mineEl.innerHTML = html;
        }).catch(function () { mineEl.innerHTML = '<p class="text-muted">Could not load your loans.</p>'; });
    }

    if (mineEl) {
        mineEl.addEventListener('click', function (e) {
            var loan = e.target.closest('[data-cancel-loan]');
            var res = e.target.closest('[data-cancel-res]');
            if (!loan && !res) return;
            if (!window.confirm(loan ? 'Cancel this borrow request?' : 'Cancel this reservation?')) return;
            (loan ? post({ action: 'cancel_request', loan_id: loan.getAttribute('data-cancel-loan') })
                  : post({ action: 'cancel_reservation', id: res.getAttribute('data-cancel-res') }))
                .then(loadMine);
        });
        loadMine();
    }
})();
