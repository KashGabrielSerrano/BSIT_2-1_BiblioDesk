/* Contact / helpdesk forms -> backend/helpdesk_controller.php (support tickets).
   Used on index.php (public, guests give name + email) and contactsupport.php (logged in).
   On contactsupport.php it also lists the user's own messages and the library's replies
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
        var input = form.querySelector('input[name="csrf_token"]');
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

    // ---- "My messages" (logged-in contact page) ----
    var list = document.getElementById('myMessages');

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
                var badge = { Open: 'bg-warning text-dark', Answered: 'bg-success', Closed: 'bg-secondary' };
                list.innerHTML = items.map(function (t) {
                    return '<div class="card border-0 shadow-sm rounded-4 mb-3">' +
                        '<div class="card-body">' +
                            '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">' +
                                '<strong>' + esc(t.subject) + '</strong>' +
                                '<span class="badge rounded-pill ' + (badge[t.status] || 'bg-secondary') + '">' + esc(t.status) + '</span>' +
                            '</div>' +
                            '<p class="mb-1" style="white-space: pre-line;">' + esc(t.message) + '</p>' +
                            '<small class="text-muted">Sent ' + esc(t.created_at) + '</small>' +
                            (t.reply
                                ? '<div class="border-start border-3 border-success ps-3 mt-3">' +
                                      '<small class="text-muted d-block mb-1">Library reply · ' + esc(t.replied_at) + '</small>' +
                                      '<p class="mb-0" style="white-space: pre-line;">' + esc(t.reply) + '</p>' +
                                  '</div>'
                                : '') +
                        '</div></div>';
                }).join('');
            })
            .catch(function () { list.innerHTML = '<p class="text-muted mb-0">Could not load your messages.</p>'; });
    }

    loadMyMessages();
})();
