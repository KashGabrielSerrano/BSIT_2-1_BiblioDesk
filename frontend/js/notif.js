(function () {
    'use strict';

    var menu   = document.getElementById('notifMenu');
    var badge  = document.getElementById('notifBadge');
    var toggle = document.getElementById('notifToggle');
    if (!menu) return;

    var ENDPOINT = 'notification_controller.php';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var CSRF = (csrfMeta && csrfMeta.content) || window.csrfToken || '';

    var items = [];
    var unread = 0;

    // Dot colour: announcement category, otherwise the notification type
    var DOT = {
        'System Update':  'dotRed',
        'Library Policy': 'dotYellow',
        'Events':         'dotBlue',
        'Collections':    'dotGreen'
    };
    var TYPE_DOT = {
        report_status: 'dotTeal',
        new_report: 'dotRed',
        loan: 'dotGreen',
        reservation: 'dotBlue',
        fine: 'dotRed',
        ticket: 'dotYellow'
    };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function dayLabel(iso) {
        var d = new Date(iso), now = new Date();
        if (isNaN(d)) return '';
        var a = new Date(d.getFullYear(), d.getMonth(), d.getDate());
        var b = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var diff = Math.round((b - a) / 86400000);
        if (diff <= 0) return 'Today';
        if (diff === 1) return 'Yesterday';
        if (diff < 7) return diff + ' days ago';
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
    }

    function shorten(text, max) {
        text = String(text || '');
        return text.length > max ? text.slice(0, max).replace(/\s+\S*$/, '') + '…' : text;
    }

    function dotFor(n) {
        if (n.category && DOT[n.category]) return DOT[n.category];
        var prefix = String(n.type || '').split(/[:_]/)[0];
        return TYPE_DOT[n.type] || TYPE_DOT[prefix] || 'dotTan';
    }

    function render() {
        var html = '<li class="notifHead"><span>Notifications</span>' +
                   (unread
                       ? '<button type="button" class="btn btn-link p-0 notifHeadCount text-decoration-none" data-mark-all>' +
                         unread + ' new &middot; mark all read</button>'
                       : '<span class="notifHeadCount">0 new</span>') +
                   '</li>';

        if (!items.length) {
            html += '<li><span class="notifEmpty">No notifications yet.</span></li>';
        } else {
            items.forEach(function (n) {
                html += '<li><a class="dropdown-item notifItem' + (n.read ? ' isRead' : '') + '" href="#" data-id="' + esc(n.id) + '"' +
                            (n.link && n.link.charAt(0) !== '#' ? ' data-link="' + esc(n.link) + '"' : '') + '>' +
                            '<span class="notifDot ' + dotFor(n) + '"></span>' +
                            '<span class="notifText">' +
                                '<span class="notifTitle">' + esc(n.title) + '</span>' +
                                (n.body ? '<span class="notifBody">' + esc(shorten(n.body, 90)) + '</span>' : '') +
                                '<span class="notifDate">' + esc(dayLabel(n.date)) + '</span>' +
                            '</span>' +
                        '</a></li>';
            });
        }
        menu.innerHTML = html;

        if (badge) {
            badge.hidden = unread === 0;
            badge.textContent = unread > 9 ? '9+' : unread;
        }
        if (toggle) {
            toggle.setAttribute('aria-label', unread ? 'Open notifications (' + unread + ' unread)' : 'Open notifications');
        }
    }

    function load() {
        return fetch(ENDPOINT + '?action=list', { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) return;
                items = data.items || [];
                unread = data.unread || 0;
                render();
            })
            .catch(function () { render(); });
    }

    function post(action, extra) {
        var fd = new FormData();
        fd.set('action', action);
        fd.set('csrf_token', CSRF);
        Object.keys(extra || {}).forEach(function (k) { fd.set(k, extra[k]); });
        return fetch(ENDPOINT, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .catch(function () { return null; });
    }

    menu.addEventListener('click', function (e) {
        if (e.target.closest('[data-mark-all]')) {
            e.preventDefault();
            e.stopPropagation();   // keep the dropdown open
            items.forEach(function (n) { n.read = true; });
            unread = 0;
            render();
            post('mark_all_read');
            return;
        }

        var link = e.target.closest('.notifItem');
        if (!link) return;
        e.preventDefault();
        var id = link.getAttribute('data-id');
        var target = link.getAttribute('data-link');
        var n = items.find(function (x) { return String(x.id) === id; });
        if (n && !n.read) {
            n.read = true;
            unread = Math.max(0, unread - 1);
            render();
            post('mark_read', { id: id });
        }
        if (target) window.location.href = target;
    });

    // refresh whenever the bell is opened
    if (toggle) toggle.addEventListener('show.bs.dropdown', load);

    load();
})();
