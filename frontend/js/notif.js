/* Bell dropdown: shows the latest published announcements and an unread count.
   Data sources (first one found wins):
   1. window.biblioAnnouncements / the "announcements:changed" event (admin page)
*/
(function () {
    'use strict';

    var menu  = document.getElementById('notifMenu');
    var badge = document.getElementById('notifBadge');
    var toggle = document.getElementById('notifToggle');
    if (!menu) return;

    var STORE_KEY = 'bibliodesk_read_notifs';
    var MAX_ITEMS = 6;
    var items = [];

    var DOT = {
        'System Update':  'dotRed',
        'Library Policy': 'dotYellow',
        'Events':         'dotBlue',
        'Collections':    'dotGreen'
    };

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function getRead() {
        try { return JSON.parse(localStorage.getItem(STORE_KEY)) || []; }
        catch (e) { return []; }
    }

    function saveRead(ids) {
        try { localStorage.setItem(STORE_KEY, JSON.stringify(ids)); } catch (e) { /* storage unavailable */ }
    }

    function dayLabel(iso) {
        var d = new Date(iso), now = new Date();
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
        return text.length > max ? text.slice(0, max).replace(/\s+\S*$/, '') + '\u2026' : text;
    }

    function setItems(list) {
        items = (list || [])
            .filter(function (a) { return !a.status || a.status === 'published'; })
            .sort(function (a, b) { return new Date(b.date) - new Date(a.date); })
            .slice(0, MAX_ITEMS);
        render();
    }

    function render() {
        var read = getRead();
        var unread = items.filter(function (a) { return read.indexOf(String(a.id)) === -1; }).length;

        var html = '<li class="notifHead"><span>Announcements</span>' +
                   '<span class="notifHeadCount">' + unread + ' new</span></li>';

        if (!items.length) {
            html += '<li><span class="notifEmpty">No announcements yet.</span></li>';
        } else {
            items.forEach(function (a) {
                var isRead = read.indexOf(String(a.id)) !== -1;
                html += '<li><a class="dropdown-item notifItem' + (isRead ? ' isRead' : '') + '" href="#" data-id="' + esc(a.id) + '">' +
                            '<span class="notifDot ' + (DOT[a.category] || 'dotTan') + '"></span>' +
                            '<span class="notifText">' +
                                '<span class="notifTitle">' + esc(a.title) + '</span>' +
                                '<span class="notifBody">' + esc(shorten(a.body, 90)) + '</span>' +
                                '<span class="notifDate">' + esc(dayLabel(a.date)) + '</span>' +
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
            toggle.setAttribute('aria-label', unread ? 'Open announcements (' + unread + ' unread)' : 'Open announcements');
        }
    }

    // clicking an item marks it as read
    menu.addEventListener('click', function (e) {
        var link = e.target.closest('.notifItem');
        if (!link) return;
        e.preventDefault();
        var id = link.getAttribute('data-id');
        var read = getRead();
        if (read.indexOf(id) === -1) { read.push(id); saveRead(read); }
        render();
    });

    // data sources
    document.addEventListener('announcements:changed', function (e) { setItems(e.detail); });

    if (Array.isArray(window.biblioAnnouncements)) {
        setItems(window.biblioAnnouncements);
    } else if (menu.dataset.endpoint) {
        fetch(menu.dataset.endpoint, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(setItems)
            .catch(function () { render(); });
    } else {
        render();
    }
})();