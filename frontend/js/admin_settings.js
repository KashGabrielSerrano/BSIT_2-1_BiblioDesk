/* Admin "System" tab on settings.php (A2) -> backend/settings_controller.php
   Saves the library-wide settings (borrowing limits, loan days, fines...). Server-side validation
   errors are shown under each field; nothing is saved unless every value is valid.
*/
(function () {
    'use strict';

    var form = document.getElementById('systemSettingsForm');
    if (!form) return;

    var meta = document.querySelector('meta[name="csrf-token"]');
    var CSRF = (meta && meta.content) || '';
    var result = document.getElementById('systemResult');
    var saveBtn = document.getElementById('systemSave');

    function clearErrors() {
        form.querySelectorAll('.fieldError').forEach(function (p) { p.textContent = ''; });
        form.querySelectorAll('.is-invalid').forEach(function (i) { i.classList.remove('is-invalid'); });
    }

    function showResult(ok, text) {
        result.textContent = text;
        result.className = 'small ' + (ok ? 'text-success' : 'text-danger');
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();

        var fd = new FormData();
        fd.set('action', 'update');
        fd.set('csrf_token', CSRF);
        form.querySelectorAll('input[name]').forEach(function (input) {
            fd.set(input.name, input.type === 'checkbox' ? (input.checked ? '1' : '0') : input.value.trim());
        });

        saveBtn.disabled = true;
        fetch('settings_controller.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                showResult(!!data.success, data.message || 'Could not save the settings.');
                Object.keys(data.errors || {}).forEach(function (key) {
                    var input = document.getElementById('sys_' + key);
                    var err = document.getElementById('sys_' + key + '_err');
                    if (input) input.classList.add('is-invalid');
                    if (err) err.textContent = data.errors[key];
                });
                // show the saved (normalised) values, e.g. 5 -> 5.00
                (data.settings || []).forEach(function (s) {
                    var input = document.getElementById('sys_' + s.key);
                    if (!input) return;
                    if (input.type === 'checkbox') input.checked = !!s.value;
                    else input.value = s.type === 'money' ? Number(s.value).toFixed(2) : s.value;
                });
            })
            .catch(function () { showResult(false, 'Server connection error. Please try again.'); })
            .then(function () { saveBtn.disabled = false; });
    });
})();
