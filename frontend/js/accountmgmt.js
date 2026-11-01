(function () {
    'use strict';

    // Set this to your PHP endpoint once it exists, e.g. 'update_account_status.php'.
    // While it is null the page works in demo mode: changes show on screen only and
    // are lost on reload. Each save is POSTed as JSON: { ids, status, reason, at, undo? }
    const API_URL = null;

    // Active Status is always derived from Status.
    const STATUS = {
        approved: { label: 'Approved', active: 'Active' },
        pending:  { label: 'Pending',  active: 'Pending' },
        denied:   { label: 'Denied',   active: '—' },
        banned:   { label: 'Banned',   active: 'Closed' }
    };
    const TAB_NAMES = { users: 'User Accounts', librarians: 'Librarian Accounts', pending: 'Pending' };
    const NEEDS_REASON = ['denied', 'banned'];

    const $ = function (id) { return document.getElementById(id); };
    const rows      = Array.from(document.querySelectorAll('#accountRows tr[data-id]'));
    const tbody     = $('accountRows');
    const table     = $('amTable');
    const emptyRow  = $('emptyRow');
    const tabs      = Array.from(document.querySelectorAll('[data-tab]'));
    const search    = $('accountSearch');
    const statusSel = $('filterStatus');
    const activeSel = $('filterActive');
    const filterBtn = $('filterBtn');
    const exportBtn = $('exportCsv');
    const counter   = $('resultCount');
    const noHeader  = $('noHeader');
    const lastHeader = $('lastHeader');
    const bulkBar   = $('bulkBar');
    const selectAll = $('selectAll');

    let currentTab = 'users';
    const selected = new Set();
    const auditLog = [];       // in-memory record of every change this session
    window.accountAuditLog = auditLog;

    /* ---------- helpers ---------- */
    function pillClass(label) { return label === '—' ? 'none' : label.toLowerCase(); }

    function setRowState(row, status) {
        const d = row.dataset;
        const s = STATUS[status];
        d.status = status;
        d.active = s.active.toLowerCase();
        d.group  = status === 'pending' ? 'pending' : d.role;
        d.search = [d.name, d.email, d.joined, d.no, d.program, s.label, s.active].join(' ').toLowerCase();

        const sp = row.querySelector('.pillStatus');
        sp.textContent = s.label;
        sp.className = 'pill pillStatus ' + status;
        const ap = row.querySelector('.pillActive');
        ap.textContent = s.active;
        ap.className = 'pill pillActive ' + pillClass(s.active);
    }

    function visibleRows() { return rows.filter(function (r) { return !r.hidden; }); }

    /* ---------- filtering, counts, selection ---------- */
    function applyFilters() {
        const q = search.value.trim().toLowerCase();
        let shown = 0, inTab = 0;

        rows.forEach(function (row) {
            const d = row.dataset;
            const inThisTab = d.group === currentTab;
            const match = inThisTab
                && (!q || d.search.includes(q))
                && (!statusSel.value || d.status === statusSel.value)
                && (!activeSel.value || d.active === activeSel.value);
            row.hidden = !match;
            if (inThisTab) inTab++;
            if (match) shown++;
        });

        emptyRow.hidden = shown !== 0;
        exportBtn.disabled = shown === 0;
        counter.textContent = 'Showing ' + shown + ' of ' + inTab + ' account' + (inTab === 1 ? '' : 's');
        filterBtn.classList.toggle('hasFilter', !!(statusSel.value || activeSel.value));
    }

    function updateCounts() {
        tabs.forEach(function (t) {
            const n = rows.filter(function (r) { return r.dataset.group === t.dataset.tab; }).length;
            t.querySelector('.tabBadge').textContent = n;
        });
    }

    function updateSelectionUI() {
        // keep only rows that are still visible and pending
        const eligible = rows.filter(function (r) { return !r.hidden && r.dataset.group === 'pending'; });
        Array.from(selected).forEach(function (id) {
            if (!eligible.some(function (r) { return r.dataset.id === id; })) selected.delete(id);
        });
        rows.forEach(function (r) {
            r.querySelector('.rowCheck').checked = selected.has(r.dataset.id);
        });

        const n = selected.size;
        bulkBar.hidden = n === 0;
        $('bulkCount').textContent = n + ' selected';
        selectAll.checked = eligible.length > 0 && n === eligible.length;
        selectAll.indeterminate = n > 0 && n < eligible.length;
    }

    function refreshAll() {
        updateCounts();
        applyFilters();
        updateSelectionUI();
    }

    function setTab(id) {
        currentTab = id;
        selected.clear();
        tabs.forEach(function (t) {
            const on = t.dataset.tab === id;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        table.classList.toggle('isPending', id === 'pending');
        noHeader.innerHTML = id === 'librarians' ? 'Employee<br>No.' : 'Student<br>No.';
        lastHeader.innerHTML = id === 'pending' ? 'Actions' : 'Active<br>Status';
        refreshAll();
    }

    /* ---------- saving ---------- */
    function persist(entry) {
        if (!API_URL) return Promise.resolve();
        return fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(entry)
        }).then(function (res) { if (!res.ok) throw new Error('Save failed'); });
    }

    // Applies a status change to one or more rows. Returns a snapshot for Undo.
    function changeStatus(targetRows, status, reason) {
        const snapshot = targetRows.map(function (r) { return { row: r, status: r.dataset.status }; });
        snapshot.forEach(function (s) { setRowState(s.row, status); });
        refreshAll();

        const entry = {
            ids: targetRows.map(function (r) { return r.dataset.id; }),
            status: status,
            reason: reason || '',
            at: new Date().toISOString()
        };
        auditLog.push(entry);
        persist(entry).catch(function () {
            snapshot.forEach(function (s) { setRowState(s.row, s.status); });
            refreshAll();
            showToast('Could not save the change, so it was reverted.');
        });
        return snapshot;
    }

    function undoChange(snapshot) {
        snapshot.forEach(function (s) {
            setRowState(s.row, s.status);
            const entry = { ids: [s.row.dataset.id], status: s.status, reason: '', at: new Date().toISOString(), undo: true };
            auditLog.push(entry);
            persist(entry).catch(function () {});
        });
        refreshAll();
    }

    function toastText(targetRows, status) {
        const n = targetRows.length;
        const who = n === 1 ? targetRows[0].dataset.name : n + ' accounts';
        if (status === 'approved') {
            return who + ' approved' + (n === 1 ? ' and moved to ' + TAB_NAMES[targetRows[0].dataset.role] : '') + '.';
        }
        if (status === 'denied')  return who + ' denied.';
        if (status === 'banned')  return who + ' banned.';
        return who + ' moved back to Pending.';
    }

    function commit(targetRows, status, reason) {
        const snapshot = changeStatus(targetRows, status, reason);
        showToast(toastText(targetRows, status), function () { undoChange(snapshot); });
    }

    /* ---------- toast ---------- */
    let undoFn = null;
    function showToast(text, undo) {
        $('toastText').textContent = text;
        undoFn = undo || null;
        $('toastUndo').classList.toggle('d-none', !undo);
        bootstrap.Toast.getOrCreateInstance($('actionToast'), { delay: 7000 }).show();
    }
    $('toastUndo').addEventListener('click', function () {
        if (undoFn) undoFn();
        undoFn = null;
        bootstrap.Toast.getOrCreateInstance($('actionToast')).hide();
    });

    /* ---------- deny dialog ---------- */
    let denyRows = [];
    function askDeny(targetRows) {
        denyRows = targetRows;
        const inst = bootstrap.Toast.getInstance($('actionToast'));
        if (inst) inst.hide();
        $('denyText').textContent = targetRows.length === 1
            ? 'You are about to deny ' + targetRows[0].dataset.name + '.'
            : 'You are about to deny ' + targetRows.length + ' accounts.';
        $('denyReason').value = '';
        bootstrap.Modal.getOrCreateInstance($('denyModal')).show();
    }
    $('denyConfirm').addEventListener('click', function () {
        bootstrap.Modal.getOrCreateInstance($('denyModal')).hide();
        commit(denyRows, 'denied', $('denyReason').value.trim());
        denyRows = [];
    });

    /* ---------- side panel ---------- */
    let panelRow = null;

    function summaryFor(name, next, changed) {
        if (!changed) return 'Pick a new status to make a change.';
        switch (next) {
            case 'approved': return name + ' will be approved and set to Active.';
            case 'denied':   return name + ' will be denied. Active Status will show —.';
            case 'banned':   return 'This will ban ' + name + ' and close their account.';
            default:         return name + ' will be moved back to Pending.';
        }
    }

    function updatePanel() {
        const d = panelRow.dataset;
        const next = $('panelStatus').value;
        const changed = next !== d.status;
        const needsReason = changed && NEEDS_REASON.indexOf(next) !== -1;

        $('modalBar').className = 'modal-header modalBar ' + next;
        $('reasonWrap').classList.toggle('d-none', !needsReason);
        $('panelActive').textContent = STATUS[next].active;
        $('panelSummary').textContent = summaryFor(d.name, next, changed);
        $('panelSave').disabled = !(changed && (!needsReason || $('panelReason').value.trim() !== ''));
    }

    function hideToast() {
        const inst = bootstrap.Toast.getInstance($('actionToast'));
        if (inst) inst.hide();
    }

    function openPanel(row) {
        hideToast();
        panelRow = row;
        const d = row.dataset;
        const librarian = d.role === 'librarians';
        $('panelName').textContent = d.name;
        $('panelEmail').textContent = d.email;
        $('panelRole').textContent = librarian ? 'Librarian' : 'Student';
        $('panelJoined').textContent = d.joined;
        $('panelNoLabel').textContent = librarian ? 'Employee No.' : 'Student No.';
        $('panelNo').textContent = d.no;
        $('panelProgram').textContent = d.program;
        const cur = $('panelCurrent');
        cur.textContent = STATUS[d.status].label;
        cur.className = 'pill ' + d.status;
        $('panelStatus').value = d.status;
        $('panelReason').value = '';
        updatePanel();
        bootstrap.Modal.getOrCreateInstance($('accountModal')).show();
    }

    $('panelStatus').addEventListener('change', updatePanel);
    $('panelReason').addEventListener('input', updatePanel);
    $('panelSave').addEventListener('click', function () {
        const next = $('panelStatus').value;
        const reason = $('panelReason').value.trim();
        bootstrap.Modal.getOrCreateInstance($('accountModal')).hide();
        commit([panelRow], next, reason);
    });

    /* ---------- table interactions ---------- */
    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.actBtn');
        if (btn) {
            const row = btn.closest('tr');
            if (btn.dataset.act === 'approve') commit([row], 'approved', '');
            else askDeny([row]);
            return;
        }
        if (e.target.closest('input, label, button, a')) return;
        const row = e.target.closest('tr[data-id]');
        if (row) openPanel(row);
    });

    tbody.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('tr[data-id]')) {
            e.preventDefault();
            openPanel(e.target);
        }
    });

    tbody.addEventListener('change', function (e) {
        if (!e.target.classList.contains('rowCheck')) return;
        const id = e.target.closest('tr').dataset.id;
        if (e.target.checked) selected.add(id); else selected.delete(id);
        updateSelectionUI();
    });

    selectAll.addEventListener('change', function () {
        rows.forEach(function (r) {
            if (r.hidden || r.dataset.group !== 'pending') return;
            if (selectAll.checked) selected.add(r.dataset.id); else selected.delete(r.dataset.id);
        });
        updateSelectionUI();
    });

    function selectedRows() {
        return rows.filter(function (r) { return selected.has(r.dataset.id) && !r.hidden; });
    }
    $('bulkApprove').addEventListener('click', function () {
        const picked = selectedRows();
        if (picked.length) commit(picked, 'approved', '');
    });
    $('bulkDeny').addEventListener('click', function () {
        const picked = selectedRows();
        if (picked.length) askDeny(picked);
    });
    $('bulkClear').addEventListener('click', function () {
        selected.clear();
        updateSelectionUI();
    });

    /* ---------- tabs, search, filters ---------- */
    tabs.forEach(function (t) {
        t.addEventListener('click', function () { setTab(t.dataset.tab); });
    });
    search.addEventListener('input', function () { applyFilters(); updateSelectionUI(); });
    statusSel.addEventListener('change', function () { applyFilters(); updateSelectionUI(); });
    activeSel.addEventListener('change', function () { applyFilters(); updateSelectionUI(); });
    $('resetFilters').addEventListener('click', function () {
        statusSel.value = '';
        activeSel.value = '';
        applyFilters();
        updateSelectionUI();
    });

    /* ---------- CSV export ---------- */
    function csvCell(value) {
        let s = String(value == null ? '' : value);
        if (/^[=+\-@]/.test(s)) s = "'" + s;   // stop spreadsheets reading text as a formula
        return '"' + s.replace(/"/g, '""') + '"';
    }

    function exportCsv() {
        const header = ['Name', 'Email', 'Date Joined', noHeader.innerText.replace(/\s+/g, ' ').trim(), 'Program', 'Status', 'Active Status'];
        const lines = [header.map(csvCell).join(',')];

        visibleRows().forEach(function (r) {
            const d = r.dataset;
            lines.push([
                d.name, d.email, d.joined, d.no, d.program,
                r.querySelector('.pillStatus').textContent.trim(),
                r.querySelector('.pillActive').textContent.trim()
            ].map(csvCell).join(','));
        });

        // BOM so Excel reads UTF-8 (the "—" character) correctly
        const blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'accounts-' + currentTab + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    }
    exportBtn.addEventListener('click', exportCsv);

    setTab('users');
})();