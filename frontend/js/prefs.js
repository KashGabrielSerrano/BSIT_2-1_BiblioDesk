/*
 * BiblioDesk shared preferences.
 * Include this in the <head> of EVERY page so saved Settings (theme, text size, reduced motion,
 * profile photo) apply everywhere, not just on the Settings page:
 *
 *   <script src="../frontend/js/prefs.js"></script>
 *
 * That one line is all a page needs. When the theme is Dark, this file also loads
 * ../frontend/css/darkmode.css by itself (it finds it next to prefs.js).
 *
 * To show the saved profile photo, add data-user-avatar to the topbar <img>.
 */
(function () {
    'use strict';

    var KEY = 'bd_settings_v1';
    var SIZES = { small: '90%', 'default': '100%', large: '112.5%', xlarge: '125%' };
    var MOTION_CSS = '*,*::before,*::after{transition:none!important;animation:none!important;scroll-behavior:auto!important}';

    // darkmode.css lives in .../css/, next to this file's .../js/ folder
    var me = document.currentScript;
    var DARK_CSS = (me && me.src)
        ? me.src.replace(/js\/prefs\.js(\?.*)?$/, 'css/darkmode.css')
        : '../frontend/css/darkmode.css';

    var media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
    var current = {};

    function load() {
        try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch (e) { return {}; }
    }

    function resolveTheme(theme) {
        if (theme === 'dark') return 'dark';
        if (theme === 'system') return (media && media.matches) ? 'dark' : 'light';
        return 'light';
    }

    function setTheme(theme, firstLoad) {
        var t = resolveTheme(theme);
        var root = document.documentElement;
        root.setAttribute('data-theme', t);
        root.setAttribute('data-bs-theme', t);   // makes Bootstrap's own parts (dropdown arrows, close buttons) dark too

        var link = document.getElementById('bd-dark-css');
        if (t === 'dark' && !link) {
            if (firstLoad && document.readyState === 'loading') {
                // written while the page is still loading, so the page never flashes light first
                document.write('<link id="bd-dark-css" rel="stylesheet" href="' + DARK_CSS + '">');
            } else {
                link = document.createElement('link');
                link.id = 'bd-dark-css';
                link.rel = 'stylesheet';
                link.href = DARK_CSS;
                (document.head || root).appendChild(link);
            }
        } else if (t === 'light' && link) {
            link.remove();
        }
    }

    function apply(prefs, firstLoad) {
        prefs = prefs || {};
        current = prefs;

        setTheme(prefs.theme, !!firstLoad);

        var size = SIZES[prefs.textSize];
        document.documentElement.style.fontSize = (size && prefs.textSize !== 'default') ? size : '';

        var el = document.getElementById('bd-reduce-motion');
        if (prefs.reduceMotion) {
            if (!el) {
                el = document.createElement('style');
                el.id = 'bd-reduce-motion';
                el.textContent = MOTION_CSS;
                (document.head || document.documentElement).appendChild(el);
            }
        } else if (el) {
            el.remove();
        }
    }

    function applyAvatar(src) {
        var imgs = document.querySelectorAll('[data-user-avatar]');
        for (var i = 0; i < imgs.length; i++) {
            if (!imgs[i].getAttribute('data-default-src')) {
                imgs[i].setAttribute('data-default-src', imgs[i].getAttribute('src') || '');
            }
            imgs[i].src = src || imgs[i].getAttribute('data-default-src');
        }
    }

    // "Match my device": follow the device if it switches between light and dark
    if (media) {
        var onChange = function () { if (current.theme === 'system') setTheme('system', false); };
        if (media.addEventListener) media.addEventListener('change', onChange);
        else if (media.addListener) media.addListener(onChange);
    }

    var saved = load();
    apply(saved.prefs, true);
    document.addEventListener('DOMContentLoaded', function () {
        applyAvatar((saved.profile || {}).avatar);
    });

    window.BDPrefs = { KEY: KEY, SIZES: SIZES, load: load, apply: apply, applyAvatar: applyAvatar };
})();