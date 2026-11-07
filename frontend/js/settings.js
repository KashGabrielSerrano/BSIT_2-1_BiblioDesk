(function () {
    'use strict';

    /*
     * Set this to your PHP endpoint once it exists, e.g. 'save_settings.php'.
     * While it is null the page runs in demo mode: settings are kept in this browser only.
     *
     * Every save is POSTed as JSON, and the endpoint should check the user is logged in:
     *   { type: 'settings',      data: { profile, notify, prefs } }
     *   { type: 'password',      current, new }
     *   { type: 'close_account', reason }
     */
    const API_URL = null;

    // Fill this from the server. While it is empty, "Download borrowing history" says there is nothing yet.
    // Each item: { title, author, borrowed, due, returned }
    const borrowHistory = [];

    const DEFAULTS = {
        profile: { name: 'Victor Magtanggol', email: 'victor.magtanggol@email.com', phone: '', program: 'BS Information Technology', year: '1st Year', avatar: null },
        notify:  { due: true, dueDays: '1', reservations: true, announcements: true, activity: true, email: false },
        prefs:   { theme: 'light', textSize: 'default', reduceMotion: false, libraryView: 'grid', genres: [], trackReading: true }
    };

    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const PHONE_RE = /^(\+63|63|0)9\d{9}$/;
    const DEFAULT_AVATAR = '../frontend/assets/icons/pfp-icon.png';

    const $ = function (id) { return document.getElementById(id); };
    const P = window.BDPrefs;

    const navItems = Array.from(document.querySelectorAll('.navItem'));
    const panels   = Array.from(document.querySelectorAll('.settingsPanel'));
    const saveBar  = $('saveBar');

    let avatar = null;     // data URL of the chosen photo, or null
    let saved  = null;     // last saved settings

    /* ---------- storage ---------- */
    function merge(base, extra) {
        const out = JSON.parse(JSON.stringify(base));
        Object.keys(extra || {}).forEach(function (section) {
            if (out[section] && typeof extra[section] === 'object') {
                Object.keys(extra[section]).forEach(function (k) {
                    if (k in out[section]) out[section][k] = extra[section][k];
                });
            }
        });
        return out;
    }

    function store(data) {
        try { localStorage.setItem(P.KEY, JSON.stringify(data)); } catch (e) { /* storage may be blocked; page still works */ }
    }

    /* ---------- reading / writing the form ---------- */
    function checkedValue(name, fallback) {
        const el = document.querySelector('input[name="' + name + '"]:checked');
        return el ? el.value : fallback;
    }

    function readForm() {
        return {
            profile: {
                name: $('pfName').value.trim(),
                email: $('pfEmail').value.trim(),
                phone: $('pfPhone').value.trim(),
                program: $('pfProgram').value,
                year: $('pfYear').value,
                avatar: avatar
            },
            notify: {
                due: $('ntDue').checked,
                dueDays: $('ntDueDays').value,
                reservations: $('ntRes').checked,
                announcements: $('ntAnn').checked,
                activity: $('ntAct').checked,
                email: $('ntEmail').checked
            },
            prefs: {
                theme: checkedValue('theme', 'light'),
                textSize: checkedValue('textSize', 'default'),
                reduceMotion: $('prMotion').checked,
                libraryView: checkedValue('libraryView', 'grid'),
                genres: Array.from(document.querySelectorAll('input[name="genre"]:checked')).map(function (c) { return c.value; }),
                trackReading: $('prTrack').checked
            }
        };
    }

    function setRadio(name, value) {
        const el = document.querySelector('input[name="' + name + '"][value="' + value + '"]');
        if (el) el.checked = true;
    }

    function fillForm(s) {
        $('pfName').value = s.profile.name;
        $('pfEmail').value = s.profile.email;
        $('pfPhone').value = s.profile.phone;
        $('pfProgram').value = s.profile.program;
        $('pfYear').value = s.profile.year;
        avatar = s.profile.avatar || null;
        showAvatar();

        $('ntDue').checked = s.notify.due;
        $('ntDueDays').value = s.notify.dueDays;
        $('ntRes').checked = s.notify.reservations;
        $('ntAnn').checked = s.notify.announcements;
        $('ntAct').checked = s.notify.activity;
        $('ntEmail').checked = s.notify.email;
        syncDueDays();

        setRadio('theme', s.prefs.theme);
        setRadio('textSize', s.prefs.textSize);
        $('prMotion').checked = s.prefs.reduceMotion;
        setRadio('libraryView', s.prefs.libraryView);
        $('prTrack').checked = s.prefs.trackReading;
        document.querySelectorAll('input[name="genre"]').forEach(function (c) {
            c.checked = s.prefs.genres.indexOf(c.value) !== -1;
        });

        P.apply(s.prefs);
    }

    function refreshDirty() {
        saveBar.hidden = JSON.stringify(readForm()) === JSON.stringify(saved);
    }

    function syncDueDays() {
        const on = $('ntDue').checked;
        $('ntDueDays').disabled = !on;
        $('ntDueDays').closest('.inlineSelect').classList.toggle('off', !on);
    }

    /* ---------- toast ---------- */
    function showToast(text) {
        $('stToastText').textContent = text;
        bootstrap.Toast.getOrCreateInstance($('stToast'), { delay: 6000 }).show();
    }

    function persist(payload) {
        if (!API_URL) return Promise.resolve();
        return fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (res) { if (!res.ok) throw new Error('Request failed'); });
    }

    /* ---------- section list ---------- */
    function showPanel(id, updateHash) {
        if (!document.getElementById('panel-' + id)) id = 'profile';
        panels.forEach(function (p) { p.classList.toggle('active', p.id === 'panel-' + id); });
        navItems.forEach(function (n) {
            const on = n.dataset.panel === id;
            n.classList.toggle('active', on);
            n.setAttribute('aria-selected', on ? 'true' : 'false');
            // on phones the section list scrolls sideways: keep the chosen section in view
            if (on && n.scrollIntoView) {
                try { n.scrollIntoView({ inline: 'center', block: 'nearest' }); } catch (e) { /* ignore */ }
            }
        });
        if (updateHash) {
            try { history.replaceState(null, '', '#' + id); } catch (e) { /* ignore */ }
        }
    }

    navItems.forEach(function (n) {
        n.addEventListener('click', function () { showPanel(n.dataset.panel, true); });
    });
    window.addEventListener('hashchange', function () { showPanel(location.hash.slice(1), false); });

    /* ---------- profile: validation ---------- */
    function setErr(inputId, errId, msg) {
        const input = $(inputId);
        $(errId).textContent = msg;
        input.classList.toggle('is-invalid', !!msg);
        if (msg) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
    }

    function validateProfile(show, focusFirst) {
        const v = readForm().profile;
        let ok = true, first = null;

        function check(cond, inputId, errId, msg) {
            if (!cond) {
                ok = false;
                if (!first) first = inputId;
                if (show) setErr(inputId, errId, msg);
            } else {
                setErr(inputId, errId, '');
            }
        }

        check(v.name.length >= 2 && v.name.length <= 80, 'pfName', 'pfNameErr', 'Enter your full name (2 to 80 characters).');
        check(EMAIL_RE.test(v.email), 'pfEmail', 'pfEmailErr', 'Enter a valid email address.');
        check(v.phone === '' || PHONE_RE.test(v.phone.replace(/[\s\-()]/g, '')), 'pfPhone', 'pfPhoneErr', 'Use a PH mobile number like 09123456789 or +639123456789.');

        if (first && focusFirst) $(first).focus();
        return ok;
    }

    [['pfName', 'pfNameErr'], ['pfEmail', 'pfEmailErr'], ['pfPhone', 'pfPhoneErr']].forEach(function (pair) {
        const input = $(pair[0]);
        input.addEventListener('blur', function () { validateProfile(true, false); });
        input.addEventListener('input', function () {
            if (input.classList.contains('is-invalid')) validateProfile(true, false);
        });
    });

    /* ---------- profile photo ---------- */
    function showAvatar() {
        $('avatarImg').src = avatar || DEFAULT_AVATAR;
        $('avatarRemove').disabled = !avatar;
    }

    $('avatarFile').addEventListener('change', function (e) {
        const file = e.target.files[0];
        const err = $('avatarError');
        err.textContent = '';
        if (!file) return;

        if (!/^image\/(png|jpe?g|webp|gif)$/.test(file.type)) {
            err.textContent = 'Choose a PNG, JPG, WEBP or GIF image.';
            e.target.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            err.textContent = 'The image must be under 5 MB.';
            e.target.value = '';
            return;
        }

        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = function () {
            // crop to a centred square and shrink, so it is small enough to store
            const size = 256;
            const c = document.createElement('canvas');
            c.width = c.height = size;
            const side = Math.min(img.width, img.height);
            c.getContext('2d').drawImage(img, (img.width - side) / 2, (img.height - side) / 2, side, side, 0, 0, size, size);
            avatar = c.toDataURL('image/jpeg', 0.85);
            URL.revokeObjectURL(url);
            showAvatar();
            refreshDirty();
        };
        img.onerror = function () {
            URL.revokeObjectURL(url);
            err.textContent = 'That file could not be read as an image.';
        };
        img.src = url;
        e.target.value = '';
    });

    $('avatarRemove').addEventListener('click', function () {
        avatar = null;
        $('avatarError').textContent = '';
        showAvatar();
        refreshDirty();
    });

    /* ---------- live preview + dirty tracking ---------- */
    document.querySelectorAll('input[name="theme"], input[name="textSize"], #prMotion').forEach(function (el) {
        el.addEventListener('change', function () { P.apply(readForm().prefs); });
    });

    $('ntDue').addEventListener('change', syncDueDays);

    ['panel-profile', 'panel-notifications', 'panel-reading'].forEach(function (id) {
        const panel = $(id);
        panel.addEventListener('input', refreshDirty);
        panel.addEventListener('change', refreshDirty);
    });

    $('saveBtn').addEventListener('click', function () {
        if (!validateProfile(true, true)) {
            showPanel('profile', true);
            validateProfile(true, true);
            return;
        }
        const data = readForm();
        persist({ type: 'settings', data: data }).then(function () {
            saved = data;
            store(data);
            P.apply(data.prefs);
            P.applyAvatar(data.profile.avatar);
            refreshDirty();
            showToast(API_URL ? 'Settings saved.' : 'Settings saved on this device (demo mode).');
        }).catch(function () {
            showToast('Could not save your settings. Please try again.');
        });
    });

    $('discardBtn').addEventListener('click', function () {
        fillForm(saved);
        ['pfName', 'pfEmail', 'pfPhone'].forEach(function (id) { $(id).classList.remove('is-invalid'); $(id).removeAttribute('aria-invalid'); });
        ['pfNameErr', 'pfEmailErr', 'pfPhoneErr', 'avatarError'].forEach(function (id) { $(id).textContent = ''; });
        refreshDirty();
    });

    window.addEventListener('beforeunload', function (e) {
        if (!saveBar.hidden) { e.preventDefault(); e.returnValue = ''; }
    });

    /* ---------- password ---------- */
    const pwCur = $('pwCurrent'), pwNew = $('pwNew'), pwCon = $('pwConfirm');
    const LEVEL_LABELS = { 0: '', 1: 'Weak', 2: 'Fair', 3: 'Good', 4: 'Strong' };

    function evalPassword() {
        const v = pwNew.value;
        const rules = {
            len: v.length >= 8,
            caseMix: /[a-z]/.test(v) && /[A-Z]/.test(v),
            num: /\d/.test(v)
        };
        $('reqLen').classList.toggle('ok', rules.len);
        $('reqCase').classList.toggle('ok', rules.caseMix);
        $('reqNum').classList.toggle('ok', rules.num);

        let score = 0;
        if (rules.len) score++;
        if (rules.caseMix) score++;
        if (rules.num) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        if (v.length >= 12) score++;
        const level = !v ? 0 : score <= 2 ? 1 : score === 3 ? 2 : score === 4 ? 3 : 4;
        $('pwMeterBar').setAttribute('data-level', String(level));
        $('pwMeterLabel').textContent = level ? 'Strength: ' + LEVEL_LABELS[level] : '';

        let confirmMsg = '';
        if (pwCon.value && v !== pwCon.value) confirmMsg = 'The passwords do not match.';
        else if (v && pwCur.value && v === pwCur.value) confirmMsg = 'The new password must be different from the current one.';
        $('pwConfirmErr').textContent = confirmMsg;
        pwCon.classList.toggle('is-invalid', !!confirmMsg);

        const ok = !!pwCur.value && rules.len && rules.caseMix && rules.num && v === pwCon.value && v !== pwCur.value;
        $('pwSave').disabled = !ok;
        return ok;
    }

    [pwCur, pwNew, pwCon].forEach(function (el) { el.addEventListener('input', evalPassword); });

    document.querySelectorAll('.pwToggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = $(btn.dataset.target);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });

    $('pwForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!evalPassword()) return;
        persist({ type: 'password', current: pwCur.value, new: pwNew.value }).then(function () {
            // never keep passwords anywhere in the page or in storage
            pwCur.value = pwNew.value = pwCon.value = '';
            evalPassword();
            showToast(API_URL
                ? 'Password updated.'
                : 'Password checks passed. Nothing was changed because the server is not connected yet (demo mode).');
        }).catch(function () {
            showToast('Could not update your password. Check your current password and try again.');
        });
    });

    /* ---------- downloads ---------- */
    function csvCell(value) {
        let s = String(value == null ? '' : value);
        if (/^[=+\-@]/.test(s)) s = "'" + s;   // stop spreadsheets reading text as a formula
        return '"' + s.replace(/"/g, '""') + '"';
    }

    function download(filename, text, type) {
        const blob = new Blob([text], { type: type });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }

    $('dlHistory').addEventListener('click', function () {
        if (!borrowHistory.length) {
            showToast('You have no borrowing history yet.');
            return;
        }
        const lines = [['Title', 'Author', 'Borrowed', 'Due', 'Returned'].map(csvCell).join(',')];
        borrowHistory.forEach(function (b) {
            lines.push([b.title, b.author, b.borrowed, b.due, b.returned].map(csvCell).join(','));
        });
        // BOM so Excel reads UTF-8 correctly
        download('bibliodesk-borrowing-history-' + new Date().toISOString().slice(0, 10) + '.csv', '﻿' + lines.join('\r\n'), 'text/csv;charset=utf-8;');
    });

    $('dlSettings').addEventListener('click', function () {
        const copy = JSON.parse(JSON.stringify(saved));
        copy.profile.avatar = copy.profile.avatar ? '(photo not included)' : null;
        download('bibliodesk-my-settings.json',
            JSON.stringify({ exportedAt: new Date().toISOString(), profile: copy.profile, notifications: copy.notify, preferences: copy.prefs }, null, 2),
            'application/json');
    });

    /* ---------- close account ---------- */
    const closeInput = $('closeConfirmInput');

    $('closeAccountBtn').addEventListener('click', function () {
        closeInput.value = '';
        $('closeReason').value = '';
        $('closeConfirmBtn').disabled = true;
        bootstrap.Modal.getOrCreateInstance($('closeModal')).show();
    });

    closeInput.addEventListener('input', function () {
        $('closeConfirmBtn').disabled = closeInput.value.trim() !== 'CLOSE';
    });

    $('closeConfirmBtn').addEventListener('click', function () {
        if (closeInput.value.trim() !== 'CLOSE') return;
        const reason = $('closeReason').value.trim();
        bootstrap.Modal.getOrCreateInstance($('closeModal')).hide();
        persist({ type: 'close_account', reason: reason }).then(function () {
            showToast(API_URL
                ? 'Your request was sent. A librarian will review it.'
                : 'Demo mode: no request was sent because the server is not connected yet.');
        }).catch(function () {
            showToast('Could not send your request. Please try again.');
        });
    });

    /* ---------- start ---------- */
    $('demoNote').hidden = !!API_URL;
    saved = merge(DEFAULTS, P.load());
    fillForm(saved);
    saved = readForm();            // normalise so "unsaved" compares like with like
    evalPassword();
    showPanel(location.hash.slice(1), false);
    refreshDirty();
})();