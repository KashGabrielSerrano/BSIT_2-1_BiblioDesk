/* "Report an issue" forms (U4) -> backend/user_librarydb_controller.php?action=report_issue
   Markup: a toggle button [data-report-toggle="<form id>"] and a form [data-report-panel] that is
   hidden by default. The book comes from data-book-id, or from the element in data-book-source
   (the book details modal keeps the open book's id in #modalBookId).
   Used in the book details modal (user_librarydb.php) and the reader toolbar (read_book.php).
*/
(function () {
    'use strict';

    var API = 'user_librarydb_controller.php';

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return (meta && meta.content) || window.csrfToken || (typeof csrfToken !== 'undefined' ? csrfToken : '');
    }

    function result(form, ok, text) {
        var box = form.querySelector('[data-report-result]');
        box.hidden = false;
        box.textContent = text;
        box.className = ok ? 'report-result ok text-success' : 'report-result error text-danger';
        box.setAttribute('data-report-result', '');
    }

    function reset(form) {
        form.reset();
        form.hidden = true;
        var box = form.querySelector('[data-report-result]');
        if (box) box.hidden = true;
        var send = form.querySelector('button[type="submit"]');
        if (send) send.disabled = false;
    }

    document.querySelectorAll('[data-report-toggle]').forEach(function (btn) {
        var form = document.getElementById(btn.getAttribute('data-report-toggle'));
        if (!form) return;
        btn.addEventListener('click', function () {
            var open = form.hidden;
            if (open) reset(form);
            form.hidden = !open;
            btn.setAttribute('aria-expanded', String(open));
            if (open) {
                var sel = form.querySelector('select');
                if (sel) sel.focus();
            }
        });
    });

    document.querySelectorAll('form[data-report-panel]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var src = form.getAttribute('data-book-source');
            var bookId = form.getAttribute('data-book-id') || (src && document.querySelector(src) ? document.querySelector(src).value : '');
            var issue = form.querySelector('[name="issue_type"]').value;
            if (!issue) { result(form, false, 'Please choose what is wrong with the book.'); return; }

            var send = form.querySelector('button[type="submit"]');
            send.disabled = true;
            var fd = new FormData();
            fd.set('action', 'report_issue');
            fd.set('csrf_token', csrf());
            fd.set('book_id', bookId);
            fd.set('issue_type', issue);
            fd.set('description', form.querySelector('[name="description"]').value.trim());

            fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    result(form, !!data.success, data.message || 'Could not send the report.');
                    send.disabled = !!data.success;   // one report per open; reopen the form to send another
                })
                .catch(function () { result(form, false, 'Server connection error. Please try again.'); send.disabled = false; });
        });
    });

    // the book details modal is reused for every book: start each book with the form closed
    var modal = document.getElementById('bookDetailsModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function () {
            modal.querySelectorAll('form[data-report-panel]').forEach(reset);
        });
    }
})();
