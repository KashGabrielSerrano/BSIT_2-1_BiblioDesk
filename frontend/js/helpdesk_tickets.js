(function () {
    'use strict';

    var listEl  = document.getElementById('ticketList');
    var filters = document.getElementById('ticketFilters');
    if (!listEl) return;

    var currentStatus = 'Open';
    var ticketsById   = {};

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : (window.csrfToken || '');
    }

    function post(params) {
        var fd = new FormData();
        Object.keys(params).forEach(function (k) { fd.append(k, params[k]); });
        fd.append('csrf_token', csrfToken());
        return fetch('helpdesk_controller.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); });
    }

    function statusBadge(status) {
        if (status === 'Answered') {
            return '<span class="badge rounded-pill bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Answered</span>';
        }
        if (status === 'Closed') {
            return '<span class="badge rounded-pill bg-secondary px-3 py-2"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Closed</span>';
        }
        return '<span class="badge rounded-pill bg-warning text-dark px-3 py-2"><i class="bi bi-clock-fill me-1" aria-hidden="true"></i>Open</span>';
    }

    function roleBadge(role, isGuest) {
        if (isGuest || role === 'Guest') {
            return '<span class="badge rounded-pill text-bg-light border text-secondary"><i class="bi bi-envelope me-1" aria-hidden="true"></i>Guest</span>';
        }
        return '<span class="badge rounded-pill text-bg-light border text-dark"><i class="bi bi-person-badge me-1" aria-hidden="true"></i>' + esc(role || 'Student') + '</span>';
    }

    function mailto(t) {
        var sub  = encodeURIComponent('Re: ' + t.subject + ' [BiblioDesk Ticket #' + t.id + ']');
        var body = encodeURIComponent(
            'Hi ' + (t.from_name || 'there') + ',\n\n' +
            (t.reply || '') +
            '\n\n---\nYour message:\n' + t.message
        );
        return 'mailto:' + encodeURIComponent(t.from_email || '') + '?subject=' + sub + '&body=' + body;
    }

    function renderBubble(senderRole, senderLabel, metaText, bodyText) {
        var isStaff = senderRole === 'Staff';
        return (
            '<div class="hd-msg-row ' + (isStaff ? 'is-staff' : 'is-user') + '">' +
                '<div class="hd-bubble ' + (isStaff ? 'hd-bubble-staff' : 'hd-bubble-user') + '">' +
                    '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1 small">' +
                        '<span class="fw-semibold">' +
                            '<i class="bi ' + (isStaff ? 'bi-shield-check me-1' : 'bi-person-circle me-1') + '" aria-hidden="true"></i>' +
                            esc(senderLabel) +
                        '</span>' +
                        '<span class="opacity-75" style="font-size: 0.78rem;">' + esc(metaText) + '</span>' +
                    '</div>' +
                    '<p class="mb-0" style="white-space: pre-wrap;">' + esc(bodyText) + '</p>' +
                '</div>' +
            '</div>'
        );
    }

    function renderThreadHtml(t) {
        var userLabel = (t.from_name || 'User') + (t.guest ? ' (Guest)' : '');
        var html = renderBubble('User', userLabel, t.created_at, t.message);

        var msgs = Array.isArray(t.messages) ? t.messages : [];
        msgs.forEach(function (m) {
            if (m.sender_role === 'Staff') {
                var staffLabel = (m.sender_name || 'Library Staff') + (m.staff_role ? ' · ' + m.staff_role : '');
                html += renderBubble('Staff', staffLabel, m.created_at, m.body);
            } else {
                html += renderBubble('User', m.sender_name || userLabel, m.created_at, m.body);
            }
        });
        return html;
    }

    function renderCard(t) {
        var isClosed = t.status === 'Closed';

        var emailBtn = (t.guest && t.reply)
            ? '<a class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold" href="' + mailto(t) + '">' +
                  '<i class="bi bi-envelope-arrow-up me-1" aria-hidden="true"></i>Reply by email' +
              '</a>'
            : '';

        var toggleBtn = isClosed
            ? '<button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold" data-action="reopen" data-id="' + t.id + '">' +
                  '<i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i>Reopen' +
              '</button>'
            : '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold" data-action="close" data-id="' + t.id + '">' +
                  '<i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Close' +
              '</button>';

        var quickReplyHtml = !isClosed
            ? '<form class="hd-quick-reply-form border-top px-4 py-3" data-ticket-id="' + t.id + '" novalidate>' +
                  '<label for="quickReply_' + t.id + '" class="form-label fw-semibold small mb-2">' +
                      '<i class="bi bi-reply-fill me-1" style="color: var(--main-color);" aria-hidden="true"></i>Write a reply' +
                  '</label>' +
                  '<div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-end gap-2">' +
                      '<textarea class="form-control hd-reply-input rounded-3 px-3 py-2 flex-grow-1" id="quickReply_' + t.id + '" rows="2" maxlength="2000" ' +
                          'placeholder="Write a reply to ' + esc(t.from_name || 'the user') + '..." required></textarea>' +
                      '<button type="submit" class="btn rounded-pill px-4 py-2 text-white fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center gap-2 flex-shrink-0" ' +
                          'style="background-color: var(--btn);">' +
                          '<i class="bi bi-send-fill" aria-hidden="true"></i> Reply' +
                      '</button>' +
                  '</div>' +
                  '<div class="invalid-feedback mt-1" data-reply-error></div>' +
              '</form>'
            : '';

        return (
            '<article class="card hd-ticket-card border-0 shadow-sm rounded-4 overflow-hidden">' +
                '<div class="hd-ticket-head px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">' +
                    '<div class="d-flex flex-wrap align-items-center gap-2">' +
                        '<span class="hd-ticket-id badge rounded-pill">#' + t.id + '</span>' +
                        '<h3 class="h5 fw-bold mb-0"><i class="bi bi-tag-fill me-1 opacity-75" aria-hidden="true"></i>' + esc(t.subject) + '</h3>' +
                    '</div>' +
                    statusBadge(t.status) +
                '</div>' +
                '<div class="card-body p-4">' +
                    '<div class="d-flex flex-wrap align-items-center gap-2 small text-muted pb-3 mb-3 border-bottom">' +
                        '<span class="fw-semibold text-dark">' + esc(t.from_name || 'Unknown') + '</span>' +
                        '<span>&middot;</span>' +
                        '<a class="text-decoration-none text-muted" href="mailto:' + esc(t.from_email || '') + '">' + esc(t.from_email || '') + '</a>' +
                        roleBadge(t.from_role, t.guest) +
                        '<span class="ms-md-auto"><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>' + esc(t.created_at) + '</span>' +
                    '</div>' +
                    '<div class="d-flex flex-column gap-3">' +
                        renderThreadHtml(t) +
                    '</div>' +
                '</div>' +
                quickReplyHtml +
                '<div class="card-footer bg-white border-top px-4 py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">' +
                    '<span class="small text-muted">' +
                        (t.guest
                            ? '<i class="bi bi-info-circle me-1" aria-hidden="true"></i>Guest ticket &mdash; your reply is emailed to the visitor. Their email replies go to the support inbox, not this thread.'
                            : '<i class="bi bi-chat-dots me-1" aria-hidden="true"></i>' + ((t.messages ? t.messages.length : 0) + 1) + ' message(s) in thread') +
                    '</span>' +
                    '<div class="d-flex flex-wrap gap-2 ms-auto">' +
                        emailBtn + toggleBtn +
                    '</div>' +
                '</div>' +
            '</article>'
        );
    }

    function renderEmpty() {
        var label = currentStatus ? currentStatus.toLowerCase() : '';
        return (
            '<div class="card border-0 shadow-sm rounded-4 text-center p-5">' +
                '<div class="hd-empty-icon mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">' +
                    '<i class="bi bi-inbox fs-3" aria-hidden="true"></i>' +
                '</div>' +
                '<h3 class="h5 fw-bold mb-1">No ' + esc(label) + ' tickets</h3>' +
                '<p class="text-muted small mb-0">Support messages matching this filter will appear here automatically.</p>' +
            '</div>'
        );
    }

    function load() {
        var url = 'helpdesk_controller.php?action=list' + (currentStatus ? '&status=' + encodeURIComponent(currentStatus) : '');
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    listEl.innerHTML = '<div class="alert alert-danger rounded-4">' + esc(data.message || 'Could not load tickets.') + '</div>';
                    return;
                }
                if (data.counts) {
                    ['Open', 'Answered', 'Closed'].forEach(function (st) {
                        var badge = filters && filters.querySelector('[data-count="' + st + '"]');
                        if (badge) badge.textContent = data.counts[st] != null ? data.counts[st] : 0;
                    });
                }
                ticketsById = {};
                var items = data.items || [];
                items.forEach(function (t) { ticketsById[t.id] = t; });
                listEl.innerHTML = items.length ? items.map(renderCard).join('') : renderEmpty();
            })
            .catch(function () {
                listEl.innerHTML = '<div class="alert alert-danger rounded-4">Network error loading tickets.</div>';
            });
    }

    if (filters) {
        filters.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-status]');
            if (!btn) return;
            filters.querySelectorAll('button[data-status]').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentStatus = btn.getAttribute('data-status') || '';
            load();
        });
    }

    listEl.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-action]');
        if (!btn) return;
        var id  = parseInt(btn.getAttribute('data-id'), 10);
        var act = btn.getAttribute('data-action');
        if (!ticketsById[id]) return;

        if (act === 'close' || act === 'reopen') {
            btn.disabled = true;
            post({ action: 'set_status', id: id, status: act === 'close' ? 'Closed' : 'Open' })
                .then(function () { load(); })
                .finally(function () { btn.disabled = false; });
        }
    });

    listEl.addEventListener('submit', function (e) {
        var qForm = e.target.closest('.hd-quick-reply-form');
        if (!qForm) return;
        e.preventDefault();

        var id = parseInt(qForm.getAttribute('data-ticket-id'), 10);
        var t  = ticketsById[id];
        if (!t) return;

        var text = qForm.querySelector('textarea');
        var err  = qForm.querySelector('[data-reply-error]');
        var send = qForm.querySelector('button[type="submit"]');

        text.classList.remove('is-invalid');
        err.textContent = '';

        if (text.value.trim().length < 2) {
            text.classList.add('is-invalid');
            err.textContent = 'Please write a reply.';
            text.focus();
            return;
        }

        var origHtml = send.innerHTML;
        send.disabled = true;
        text.disabled = true;
        send.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Sending...';

        var replyVal = text.value.trim();
        post({ action: 'reply', id: id, reply: replyVal })
            .then(function (data) {
                if (!data.success) {
                    text.classList.add('is-invalid');
                    err.textContent = data.message || 'Could not send reply.';
                    return;
                }
                if (t.guest && data.emailed === false) {
                    t.reply = replyVal;
                    window.location.href = mailto(t);
                }
                load();
            })
            .catch(function () {
                text.classList.add('is-invalid');
                err.textContent = 'Network error sending reply.';
            })
            .finally(function () {
                send.disabled = false;
                text.disabled = false;
                send.innerHTML = origHtml;
            });
    });

    listEl.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            var qForm = e.target.closest('.hd-quick-reply-form');
            if (qForm) {
                e.preventDefault();
                qForm.requestSubmit();
            }
        }
    });

    load();
    setInterval(function () {
        if (document.hidden) return;
        var active = document.activeElement;
        if (active && listEl.contains(active) && active.tagName === 'TEXTAREA') return;
        load();
    }, 15000);
})();