/* Staff helpdesk inbox (helpdesk.php, Librarian + Admin) -> backend/helpdesk_controller.php
   Filter by status, view threaded conversations, reply to open/answered tickets, close / reopen,
   and "Reply by email" for guests.
*/
(function () {
    'use strict';

    var API = 'helpdesk_controller.php';
    var list = document.getElementById('ticketList');
    if (!list) return;

    var meta = document.querySelector('meta[name="csrf-token"]');
    var CSRF = (meta && meta.content) || '';
    var filters = document.getElementById('ticketFilters');
    var modalEl = document.getElementById('ticketReplyModal');
    var form = document.getElementById('ticketReplyForm');
    var current = 'Open';
    var items = [];
    var replyingTo = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function post(fields) {
        var fd = new FormData();
        fd.set('csrf_token', CSRF);
        Object.keys(fields).forEach(function (k) { fd.set(k, fields[k]); });
        return fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    function mailto(t) {
        var body = 'Hi ' + t.from_name + ',\n\n' + (t.reply || '') + '\n\n---\nYour message (' + t.created_at + '):\n' + t.message;
        return 'mailto:' + encodeURIComponent(t.from_email) +
               '?subject=' + encodeURIComponent('Re: ' + t.subject + ' (BiblioDesk)') +
               '&body=' + encodeURIComponent(body);
    }

    function renderThreadHtml(t) {
        var msgs = Array.isArray(t.messages) ? t.messages : [];
        if (msgs.length) {
            return msgs.map(function (m) {
                var isStaff = m.sender_role === 'Staff';
                var borderClass = isStaff ? 'border-success' : 'border-secondary';
                var who = isStaff
                    ? ('Reply by ' + esc(m.sender_name || 'staff'))
                    : ('Follow-up by ' + esc(t.from_name || 'user'));
                return '<div class="border-start border-3 ' + borderClass + ' ps-3 mb-2">' +
                    '<small class="text-muted d-block">' + who + ' &middot; ' + esc(m.created_at) + '</small>' +
                    '<span style="white-space: pre-line;">' + esc(m.body) + '</span>' +
                '</div>';
            }).join('');
        }
        if (t.reply) {
            return '<div class="border-start border-3 border-success ps-3 mb-2">' +
                '<small class="text-muted d-block">Reply by ' + esc(t.replied_by || 'staff') + ' &middot; ' + esc(t.replied_at) + '</small>' +
                '<span style="white-space: pre-line;">' + esc(t.reply) + '</span>' +
            '</div>';
        }
        return '';
    }

    function card(t) {
        var badge = { Open: 'bg-warning text-dark', Answered: 'bg-success', Closed: 'bg-secondary' }[t.status] || 'bg-secondary';
        var actions = [];
        if (t.status !== 'Closed') {
            actions.push('<button type="button" class="btn btn-sm rounded-pill px-3 text-white" style="background-color: var(--btn);" data-act="reply">Reply</button>');
        }
        if (t.guest && t.reply) {
            actions.push('<a class="btn btn-sm btn-outline-dark rounded-pill px-3" href="' + esc(mailto(t)) + '">Reply by email</a>');
        }
        actions.push(t.status === 'Closed'
            ? '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-act="reopen">Reopen</button>'
            : '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-act="close">Close</button>');

        return '<div class="card border-0 shadow-sm rounded-4 mb-3" data-id="' + t.id + '"><div class="card-body">' +
            '<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">' +
                '<div><strong>' + esc(t.subject) + '</strong><br>' +
                    '<small class="text-muted">' + esc(t.from_name) + ' &middot; ' + esc(t.from_email) +
                    ' &middot; ' + esc(t.from_role) + ' &middot; ' + esc(t.created_at) + '</small></div>' +
                '<span class="badge rounded-pill ' + badge + '">' + esc(t.status) + '</span>' +
            '</div>' +
            '<p class="mb-2" style="white-space: pre-line;">' + esc(t.message) + '</p>' +
            renderThreadHtml(t) +
            '<div class="d-flex flex-wrap gap-2 justify-content-end">' + actions.join('') + '</div>' +
        '</div></div>';
    }

    function load() {
        fetch(API + '?action=list' + (current ? '&status=' + encodeURIComponent(current) : ''), { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) throw new Error(data.message);
                items = data.items || [];
                Object.keys(data.counts || {}).forEach(function (k) {
                    var el = filters.querySelector('[data-count="' + k + '"]');
                    if (el) el.textContent = data.counts[k];
                });
                list.innerHTML = items.length ? items.map(card).join('')
                    : '<p class="text-muted">No ' + (current ? current.toLowerCase() + ' ' : '') + 'tickets.</p>';
            })
            .catch(function (err) { list.innerHTML = '<p class="text-danger">' + esc(err.message || 'Could not load tickets.') + '</p>'; });
    }

    filters.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-status]');
        if (!btn) return;
        filters.querySelectorAll('[data-status]').forEach(function (b) { b.classList.toggle('active', b === btn); });
        current = btn.getAttribute('data-status');
        load();
    });

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-act]');
        if (!btn) return;
        var id = btn.closest('[data-id]').getAttribute('data-id');
        var t = items.find(function (x) { return String(x.id) === id; });
        var act = btn.getAttribute('data-act');
        if (act === 'reply') {
            replyingTo = t;
            document.getElementById('ticketReplyFrom').textContent = t.from_name + ' (' + t.from_email + ') · ' + t.subject;

            // Render original message + full conversation thread inside the modal
            var msgBox = document.getElementById('ticketReplyThread') || document.getElementById('ticketReplyMessage');
            if (msgBox) {
                msgBox.innerHTML =
                    '<div class="mb-2"><small class="text-muted d-block">' + esc(t.from_name) + ' &middot; ' + esc(t.created_at) + '</small>' +
                    '<div style="white-space: pre-line;">' + esc(t.message) + '</div></div>' +
                    renderThreadHtml(t);
            }

            document.getElementById('ticketReplyText').value = '';
            document.getElementById('ticketReplyText').classList.remove('is-invalid');
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            btn.disabled = true;
            post({ action: 'set_status', id: id, status: act === 'close' ? 'Closed' : 'Open' })
                .then(load)
                .catch(function () { btn.disabled = false; });
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = document.getElementById('ticketReplyText');
        var err = document.getElementById('ticketReplyError');
        if (text.value.trim().length < 2) {
            text.classList.add('is-invalid'); err.textContent = 'Please write a reply.';
            return;
        }
        var send = document.getElementById('ticketReplySend');
        send.disabled = true;
        post({ action: 'reply', id: replyingTo.id, reply: text.value.trim() })
            .then(function (data) {
                if (!data.success) { text.classList.add('is-invalid'); err.textContent = data.message; return; }
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                // guests have no account: open their email app with the reply ready to send
                if (replyingTo.guest) {
                    replyingTo.reply = text.value.trim();
                    window.location.href = mailto(replyingTo);
                }
                load();
            })
            .then(function () { send.disabled = false; });
    });

    load();
    setInterval(function () {
        if (document.hidden) return;
        if (modalEl && modalEl.classList.contains('show')) return;
        load();
    }, 8000);


})();