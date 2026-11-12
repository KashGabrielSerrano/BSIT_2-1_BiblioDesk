/* Contact / helpdesk forms -> backend/helpdesk_controller.php (support tickets).
   Used on index.php (public, guests give name + email) and contactsupport.php (logged in).
   On contactsupport.php it also lists the user's own messages and threaded replies
   in #myMessages.
*/
(function () {
    'use strict';

    var API = 'helpdesk_controller.php';

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function csrf(form) {
        var input = form && form.querySelector('input[name="csrf_token"]');
        var meta = document.querySelector('meta[name="csrf-token"]');
        return (input && input.value) || (meta && meta.content) || '';
    }

    function showResult(form, ok, message) {
        var box = form.querySelector('[data-contact-result]');
        if (!box) {
            box = document.createElement('div');
            box.setAttribute('data-contact-result', '');
            box.setAttribute('role', 'status');
            form.insertBefore(box, form.firstChild);
        }
        box.className = 'alert py-2 small ' + (ok ? 'alert-success' : 'alert-danger');
        box.textContent = message;
    }

    document.querySelectorAll('form[data-contact-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!form.reportValidity()) return;

            var btn = form.querySelector('button[type="submit"]');
            var label = btn ? btn.innerHTML : '';   // HTML, so an icon inside the button comes back after sending
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending…';
            }

            var fd = new FormData();
            fd.set('action', 'submit');
            fd.set('csrf_token', csrf(form));
            ['name', 'email', 'subject', 'message', 'website'].forEach(function (k) {
                var el = form.querySelector('[name="' + k + '"]');
                if (el) fd.set(k, el.value);
            });

            fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    showResult(form, data.success, data.message || (data.success ? 'Sent.' : 'Could not send your message.'));
                    if (data.success) {
                        var msg = form.querySelector('[name="message"]');
                        if (msg) msg.value = '';
                        loadMyMessages();
                    }
                })
                .catch(function () { showResult(form, false, 'Server connection error. Please try again.'); })
                .then(function () { if (btn) { btn.disabled = false; btn.innerHTML = label; } });
        });
    });

    // ---- "My messages" (logged-in contact page with threaded replies) ----
    var list = document.getElementById('myMessages');

    function renderBubble(opts) {
        if (opts.isStaff) {
            return (
                '<div class="d-flex flex-column align-items-start">' +
                    '<div class="bg-white border border-start border-4 border-success rounded-4 p-3 shadow-sm" style="max-width: 85%;">' +
                        '<div class="d-flex align-items-center gap-2 small fw-semibold text-success mb-1">' +
                            '<i class="bi bi-headset" aria-hidden="true"></i>' +
                            '<span>' + esc(opts.senderLabel) + '</span>' +
                            '<span class="text-muted fw-normal">&bull; ' + esc(opts.timestamp) + '</span>' +
                        '</div>' +
                        '<p class="mb-0 text-dark" style="white-space: pre-line;">' + esc(opts.body) + '</p>' +
                    '</div>' +
                '</div>'
            );
        }
        return (
            '<div class="d-flex flex-column align-items-end">' +
                '<div class="text-white rounded-4 p-3 shadow-sm" style="background-color: var(--main-color); max-width: 85%;">' +
                    '<div class="d-flex align-items-center justify-content-end gap-2 small text-white-50 mb-1">' +
                        '<span>' + esc(opts.senderLabel) + ' &bull; ' + esc(opts.timestamp) + '</span>' +
                        '<i class="bi bi-person-circle" aria-hidden="true"></i>' +
                    '</div>' +
                    '<p class="mb-0" style="white-space: pre-line;">' + esc(opts.body) + '</p>' +
                '</div>' +
            '</div>'
        );
    }

    function renderTicketCard(t) {
        var badge = { Open: 'bg-warning text-dark', Answered: 'bg-success', Closed: 'bg-secondary' };
        var isClosed = t.status === 'Closed';

        var threadHtml = renderBubble({
            senderLabel: 'You',
            timestamp: t.created_at,
            body: t.message,
            isStaff: false
        });

        var msgs = Array.isArray(t.messages) ? t.messages : [];
        if (msgs.length) {
            threadHtml += msgs.map(function (m) {
                var isStaff = m.sender_role === 'Staff';
                var label = isStaff
                    ? (m.sender_name + (m.staff_role ? ' (' + m.staff_role + ')' : ' (Library Staff)'))
                    : 'You';
                return renderBubble({
                    senderLabel: label,
                    timestamp: m.created_at,
                    body: m.body,
                    isStaff: isStaff
                });
            }).join('');
        } else if (t.reply) {
            threadHtml += renderBubble({
                senderLabel: 'Library Staff',
                timestamp: t.replied_at,
                body: t.reply,
                isStaff: true
            });
        }

        var footerHtml = isClosed
            ? '<div class="card-footer border-0 px-4 py-3 text-muted small d-flex align-items-center gap-2" style="background-color: var(--main-bg-color);">' +
                  '<i class="bi bi-lock-fill" aria-hidden="true"></i>' +
                  '<span>This ticket is closed. Need more help? Submit a new message above.</span>' +
              '</div>'
            : '<div class="card-footer border-top px-4 py-3" style="background-color: var(--main-bg-color);">' +
                  '<form class="js-ticket-reply-form" data-ticket-id="' + esc(t.id) + '" novalidate>' +
                      '<label for="replyInput-' + esc(t.id) + '" class="form-label small fw-semibold mb-1">Write a reply</label>' +
                      '<div class="d-flex flex-column flex-sm-row gap-2 align-items-stretch align-items-sm-end">' +
                          '<textarea id="replyInput-' + esc(t.id) + '" name="reply" rows="2" minlength="2" maxlength="2000" ' +
                              'class="form-control rounded-3" placeholder="Add more details or reply to library staff..." required></textarea>' +
                          '<button type="submit" class="btn text-white rounded-pill px-4 py-2 fw-semibold flex-shrink-0 d-inline-flex align-items-center justify-content-center gap-2" ' +
                              'style="background-color: var(--btn);">' +
                              '<i class="bi bi-send-fill" aria-hidden="true"></i> Reply' +
                          '</button>' +
                      '</div>' +
                      '<div class="small text-danger mt-1 d-none js-reply-err" role="alert"></div>' +
                  '</form>' +
              '</div>';

        return (
            '<article class="card border-0 shadow rounded-4 overflow-hidden mb-3">' +
                '<div class="card-header text-white px-4 py-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2" ' +
                     'style="background-color: var(--main-color);">' +
                    '<div>' +
                        '<span class="small text-white-50 d-block">Ticket #' + esc(t.id) + ' &bull; ' + esc(t.created_at) + '</span>' +
                        '<h3 class="h5 fw-bold mb-0">' + esc(t.subject) + '</h3>' +
                    '</div>' +
                    '<span class="badge rounded-pill px-3 py-2 ' + (badge[t.status] || 'bg-secondary') + '">' + esc(t.status) + '</span>' +
                '</div>' +
                '<div class="card-body p-4 d-flex flex-column gap-3" style="background-color: var(--main-bg-color);">' +
                    threadHtml +
                '</div>' +
                footerHtml +
            '</article>'
        );
    }

    function loadMyMessages() {
        if (!list) return;
        fetch(API + '?action=my_tickets', { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var items = (data && data.items) || [];
                if (!items.length) {
                    list.innerHTML = '<p class="text-muted mb-0">You have not sent any messages yet.</p>';
                    return;
                }
                list.innerHTML = items.map(renderTicketCard).join('');
            })
            .catch(function () { list.innerHTML = '<p class="text-muted mb-0">Could not load your messages.</p>'; });
    }

    if (list) {
        list.addEventListener('submit', function (e) {
            var form = e.target.closest('.js-ticket-reply-form');
            if (!form) return;
            e.preventDefault();

            var ticketId = form.getAttribute('data-ticket-id');
            var textarea = form.querySelector('textarea[name="reply"]');
            var btn = form.querySelector('button[type="submit"]');
            var errBox = form.querySelector('.js-reply-err');
            var reply = (textarea ? textarea.value : '').trim();

            errBox.classList.add('d-none');
            if (reply.length < 2 || reply.length > 2000) {
                errBox.textContent = 'Please enter a reply between 2 and 2,000 characters.';
                errBox.classList.remove('d-none');
                return;
            }

            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending…';

            var fd = new FormData();
            fd.set('action', 'reply');
            fd.set('id', ticketId);
            fd.set('reply', reply);
            fd.set('csrf_token', csrf(document.querySelector('form[data-contact-form]')));

            fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.success) {
                        errBox.textContent = data.message || 'Could not send your reply.';
                        errBox.classList.remove('d-none');
                        return;
                    }
                    loadMyMessages();
                })
                .catch(function () {
                    errBox.textContent = 'Server connection error. Please try again.';
                    errBox.classList.remove('d-none');
                })
                .then(function () {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                });
        });
    }

loadMyMessages();

    setInterval(function () {
        if (document.hidden || !list) return;
        var hasDraft = Array.from(list.querySelectorAll('textarea[name="reply"]'))
            .some(function (ta) { return ta.value.trim() !== ''; });
        if (hasDraft) return;
        loadMyMessages();
    }, 8000);
})();