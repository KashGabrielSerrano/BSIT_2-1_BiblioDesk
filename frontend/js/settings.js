(function () {
    'use strict';

    const CONTROLLER_URL = 'settings_controller.php';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const serverProfile = window.bdProfile || {};
    const DEFAULTS = {
        profile: Object.assign(
            { name: '', email: '', phone: '', program: '', year: '1st Year', avatar: null },
            serverProfile
        ),
        notify: { due: true, dueDays: '1', reservations: true, announcements: true, activity: true, email: false },
        prefs:  { theme: 'light', textSize: 'default', reduceMotion: false, libraryView: 'grid', genres: [], trackReading: true }
    };

    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const PHONE_RE = /^(09|\+639|639)\d{9}$/;
    const DEFAULT_AVATAR = serverProfile.defaultAvatar || '../frontend/assets/icons/pfp-icon.png';

    const MAX_AVATAR_BYTES = 100 * 1024; // 100 KB
    const ALLOWED_MIMES    = ['image/jpeg', 'image/png'];
    const ALLOWED_EXTS     = /\.(jpe?g|png)$/i;

    const $ = function (id) { return document.getElementById(id); };
    const P = window.BDPrefs;

    const navItems = Array.from(document.querySelectorAll('.navItem'));
    const panels   = Array.from(document.querySelectorAll('.settingsPanel'));
    const saveBar  = $('saveBar');

    let avatar = serverProfile.avatar || null; // Supabase public URL, or null
    let saved  = null;                         // last saved settings

    /* ---------- storage & backend helper ---------- */
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
        try {
            if (P && typeof P.save === 'function') P.save(data);
            else if (P && P.KEY) localStorage.setItem(P.KEY, JSON.stringify(data));
        } catch (e) { /* storage may be blocked; page still works */ }
    }

    async function postController(formData, endpoint = CONTROLLER_URL) {
        formData.append('csrf_token', CSRF_TOKEN);
        const res = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });
        let data = {};
        try {
            data = await res.json();
        } catch (e) {
            throw new Error('Unexpected server response.');
        }
        if (!res.ok || !data.success) {
            const err = new Error(data.message || 'Request failed.');
            err.errors = data.errors || {};
            throw err;
        }
        return data;
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
        $('pfName').value = s.profile.name || '';
        $('pfEmail').value = s.profile.email || '';
        $('pfPhone').value = (s.profile && s.profile.phone) ? s.profile.phone : '';
        $('pfProgram').value = s.profile.program || 'Other';
        $('pfYear').value = s.profile.year || '1st Year';
        avatar = s.profile.avatar || null;
        showAvatar();

        $('ntDue').checked = !!s.notify.due;
        $('ntDueDays').value = s.notify.dueDays || '1';
        $('ntRes').checked = !!s.notify.reservations;
        $('ntAnn').checked = !!s.notify.announcements;
        $('ntAct').checked = !!s.notify.activity;
        $('ntEmail').checked = !!s.notify.email;
        syncDueDays();

        setRadio('theme', s.prefs.theme || 'light');
        setRadio('textSize', s.prefs.textSize || 'default');
        $('prMotion').checked = !!s.prefs.reduceMotion;
        setRadio('libraryView', s.prefs.libraryView || 'grid');
        $('prTrack').checked = s.prefs.trackReading !== false;
        document.querySelectorAll('input[name="genre"]').forEach(function (c) {
            c.checked = (s.prefs.genres || []).indexOf(c.value) !== -1;
        });

        if (P && typeof P.apply === 'function') P.apply(s.prefs);
        updateReadingPreview();
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
        bootstrap.Toast.getOrCreateInstance($('stToast'), { delay: 4500 }).show();
    }

    /* ---------- section list ---------- */
    function showPanel(id, updateHash) {
        if (!document.getElementById('panel-' + id)) id = 'profile';
        panels.forEach(function (p) { p.classList.toggle('active', p.id === 'panel-' + id); });
        navItems.forEach(function (n) {
            const on = n.dataset.panel === id;
            n.classList.toggle('active', on);
            n.setAttribute('aria-selected', on ? 'true' : 'false');
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
        const errEl = $(errId);
        if (!input || !errEl) return;
        errEl.textContent = msg;
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

        check(EMAIL_RE.test(v.email), 'pfEmail', 'pfEmailErr', 'Enter a valid email address.');
        check(v.phone === '' || PHONE_RE.test(v.phone.replace(/[\s\-()]/g, '')), 'pfPhone', 'pfPhoneErr', 'Use a PH mobile number like 09123456789 or +639123456789.');

        if (first && focusFirst) $(first).focus();
        return ok;
    }

    [['pfEmail', 'pfEmailErr'], ['pfPhone', 'pfPhoneErr']].forEach(function (pair) {
        const input = $(pair[0]);
        if (!input) return;
        input.addEventListener('blur', function () { validateProfile(true, false); });
        input.addEventListener('input', function () {
            if (input.classList.contains('is-invalid')) validateProfile(true, false);
        });
    });

    /* ---------- profile photo (Supabase Storage: JPG/JPEG/PNG, max 100 KB) ---------- */
    function showAvatar() {
        const displayUrl = avatar || DEFAULT_AVATAR;
        $('avatarImg').src = displayUrl;
        $('avatarRemove').disabled = !avatar;
        document.querySelectorAll('img[data-bd-avatar]').forEach(function (img) {
            img.src = displayUrl;
        });
        if (P && typeof P.applyAvatar === 'function') {
            P.applyAvatar(avatar);
        }
    }

    $('avatarFile').addEventListener('change', async function (e) {
        const file = e.target.files[0];
        const err = $('avatarError');
        const btnLabel = $('avatarUploadBtn');
        err.textContent = '';
        if (!file) return;

        if (!ALLOWED_MIMES.includes(file.type) || !ALLOWED_EXTS.test(file.name)) {
            err.textContent = 'Only JPG, JPEG, and PNG images are allowed.';
            e.target.value = '';
            return;
        }
        if (file.size > MAX_AVATAR_BYTES) {
            err.textContent = 'The image must be 100 KB or smaller.';
            e.target.value = '';
            return;
        }

        if (btnLabel) btnLabel.textContent = 'Uploading...';
        $('avatarFile').disabled = true;

        try {
            const fd = new FormData();
            fd.append('action', 'upload_avatar');
            fd.append('avatar', file);
            const res = await postController(fd);

            avatar = res.url || null;
            if (saved && saved.profile) {
                saved.profile.avatar = avatar;
                store(saved);
            }
            showAvatar();
            showToast(res.message || 'Profile photo updated.');
        } catch (uploadErr) {
            err.textContent = uploadErr.message || 'Could not upload profile photo.';
        } finally {
            if (btnLabel) btnLabel.textContent = 'Change photo';
            $('avatarFile').disabled = false;
            e.target.value = '';
        }
    });

    $('avatarRemove').addEventListener('click', async function () {
        const err = $('avatarError');
        err.textContent = '';
        $('avatarRemove').disabled = true;

        try {
            const fd = new FormData();
            fd.append('action', 'remove_avatar');
            const res = await postController(fd);

            avatar = null;
            if (saved && saved.profile) {
                saved.profile.avatar = null;
                store(saved);
            }
            showAvatar();
            showToast(res.message || 'Profile photo removed.');
        } catch (removeErr) {
            err.textContent = removeErr.message || 'Could not remove profile photo.';
            $('avatarRemove').disabled = !avatar;
        }
    });

    /* ---------- live preview + dirty tracking ---------- */
    function updateReadingPreview() {
        const form = readForm();
        const p = form.prefs;
        const themeBadge = $('previewThemeBadge');
        const layoutBadge = $('previewLayoutBadge');
        const genreTags = $('previewGenreTags');
        const motionStatus = $('previewMotionStatus');
        const trackStatus = $('previewTrackStatus');

        if (themeBadge) {
            themeBadge.textContent = p.theme === 'dark' ? 'Dark theme' : (p.theme === 'system' ? 'Match device' : 'Light theme');
        }
        if (layoutBadge) {
            layoutBadge.textContent = p.libraryView === 'list' ? 'List View' : 'Grid View';
        }
        if (genreTags) {
            if (p.genres && p.genres.length) {
                genreTags.innerHTML = p.genres.map(function (g) {
                    return '<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">' + g + '</span>';
                }).join(' ');
            } else {
                genreTags.innerHTML = '<span class="badge bg-secondary-subtle text-secondary-emphasis">No genres selected</span>';
            }
        }
        if (motionStatus) {
            motionStatus.innerHTML = p.reduceMotion
                ? '<i class="bi bi-dash-circle me-1"></i>Animations: Reduced'
                : '<i class="bi bi-activity me-1"></i>Animations: Enabled';
        }
        if (trackStatus) {
            trackStatus.innerHTML = p.trackReading
                ? '<i class="bi bi-stopwatch text-success me-1"></i>Reading tracking: Active'
                : '<i class="bi bi-pause-circle text-muted me-1"></i>Reading tracking: Paused';
        }
    }

    document.querySelectorAll('input[name="theme"], input[name="textSize"], #prMotion, input[name="libraryView"], #prTrack, input[name="genre"]').forEach(function (el) {
        el.addEventListener('change', function () {
            if (P && typeof P.apply === 'function') P.apply(readForm().prefs);
            updateReadingPreview();
        });
    });

    $('ntDue').addEventListener('change', syncDueDays);

    ['panel-profile', 'panel-notifications', 'panel-reading'].forEach(function (id) {
        const panel = $(id);
        panel.addEventListener('input', refreshDirty);
        panel.addEventListener('change', refreshDirty);
    });

    $('saveBtn').addEventListener('click', async function () {
        if (!validateProfile(true, true)) {
            showPanel('profile', true);
            validateProfile(true, true);
            return;
        }
        const data = readForm();
        $('saveBtn').disabled = true;

        try {
            const fd = new FormData();
            fd.append('action', 'save_preferences');
            fd.append('email', data.profile.email);
            fd.append('phone', data.profile.phone);
            fd.append('notify', JSON.stringify(data.notify));
            fd.append('prefs', JSON.stringify(data.prefs));

            const res = await postController(fd);
            if (res.email) {
                data.profile.email = res.email;
                $('pfEmail').value = res.email;
            }

            saved = data;
            store(data);
            window.bdUserPreferences = {
                profile: { phone: data.profile.phone },
                notify: Object.assign({}, data.notify),
                prefs: Object.assign({}, data.prefs)
            };
            if (P && typeof P.apply === 'function') P.apply(data.prefs);
            updateReadingPreview();
            refreshDirty();
            showToast('Settings saved to your account.');
        } catch (saveErr) {
            if (saveErr.errors && saveErr.errors.email) {
                setErr('pfEmail', 'pfEmailErr', saveErr.errors.email);
                showPanel('profile', true);
            } else if (saveErr.errors && saveErr.errors.phone) {
                setErr('pfPhone', 'pfPhoneErr', saveErr.errors.phone);
                showPanel('profile', true);
            } else {
                showToast(saveErr.message || 'Could not save your settings. Please try again.');
            }
        } finally {
            $('saveBtn').disabled = false;
        }
    });

    $('discardBtn').addEventListener('click', function () {
        fillForm(saved);
        ['pfEmail', 'pfPhone'].forEach(function (id) {
            const el = $(id);
            if (el) { el.classList.remove('is-invalid'); el.removeAttribute('aria-invalid'); }
        });
        ['pfEmailErr', 'pfPhoneErr', 'avatarError'].forEach(function (id) {
            const el = $(id);
            if (el) el.textContent = '';
        });
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

    $('pwForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!evalPassword()) return;
        $('pwSave').disabled = true;

        try {
            const fd = new FormData();
            fd.append('action', 'change_password');
            fd.append('current_password', pwCur.value);
            fd.append('new_password', pwNew.value);
            fd.append('confirm_password', pwCon.value);
            const res = await postController(fd);

            pwCur.value = pwNew.value = pwCon.value = '';
            evalPassword();
            showToast(res.message || 'Password updated.');
        } catch (pwErr) {
            showToast(pwErr.message || 'Could not update your password. Check your current password and try again.');
            evalPassword();
        }
    });

    /* ---------- downloads ---------- */
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
        window.location.href = 'circulation_controller.php?action=export_csv';
    });

    $('dlSettings').addEventListener('click', function () {
        const currentData = readForm();
        const copy = JSON.parse(JSON.stringify(currentData));
        copy.profile.avatar = copy.profile.avatar ? '(photo not included)' : null;
        download('bibliodesk-my-settings.json',
            JSON.stringify({
                exportedAt: new Date().toISOString(),
                profile: copy.profile,
                notifications: copy.notify,
                preferences: copy.prefs
            }, null, 2),
            'application/json');
        showToast('Settings snapshot downloaded.');
    });

    /* ---------- close account ---------- */
    const closeInput = $('closeConfirmInput');

    $('closeAccountBtn').addEventListener('click', function () {
        closeInput.value = '';
        $('closeReason').value = '';
        $('closeConfirmBtn').disabled = true;
        $('closeConfirmBtn').textContent = 'Send request';
        bootstrap.Modal.getOrCreateInstance($('closeModal')).show();
    });

    closeInput.addEventListener('input', function () {
        $('closeConfirmBtn').disabled = closeInput.value.trim() !== 'CLOSE';
    });

    closeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && closeInput.value.trim() === 'CLOSE') {
            e.preventDefault();
            $('closeConfirmBtn').click();
        }
    });

    $('closeConfirmBtn').addEventListener('click', async function () {
        if (closeInput.value.trim() !== 'CLOSE') return;
        const reason = $('closeReason').value.trim();
        $('closeConfirmBtn').disabled = true;
        $('closeConfirmBtn').textContent = 'Sending...';

        try {
            const fd = new FormData();
            fd.append('action', 'submit');
            fd.append('subject', 'Account / Login');
            fd.append('message', 'Account closure request.' + (reason ? ' Reason: ' + reason : ''));
            const res = await postController(fd, 'helpdesk_controller.php');
            bootstrap.Modal.getOrCreateInstance($('closeModal')).hide();
            showToast(res.message || 'Your request was sent. A librarian will review it.');
        } catch (closeErr) {
            showToast(closeErr.message || 'Could not send your request. Please try again.');
        } finally {
            $('closeConfirmBtn').disabled = closeInput.value.trim() !== 'CLOSE';
            $('closeConfirmBtn').textContent = 'Send request';
        }
    });

    /* ---------- start ---------- */
    if ($('demoNote')) $('demoNote').hidden = true;
    const dbData = window.bdDatabasePreferences || null;
    const localData = (P && typeof P.load === 'function') ? P.load() : {};
    saved = merge(DEFAULTS, localData);
    if (dbData) {
        saved = merge(saved, dbData);
    }

    // Server DB values always override cached localStorage for locked/synced profile fields
    if (serverProfile.name) saved.profile.name = serverProfile.name;
    if (serverProfile.email) saved.profile.email = serverProfile.email;
    if (serverProfile.phone !== undefined) saved.profile.phone = serverProfile.phone;
    if (serverProfile.program) saved.profile.program = serverProfile.program;
    saved.profile.avatar = serverProfile.avatar || null;

    fillForm(saved);
    store(saved);
    saved = readForm(); // normalise so "unsaved" compares like with like
    evalPassword();
    showPanel(location.hash.slice(1), false);
    refreshDirty();
})();