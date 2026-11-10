document.addEventListener('DOMContentLoaded', function () {
    var table      = document.querySelector('.logTable');
    var filterForm = document.getElementById('filterForm');
    var searchForm = document.querySelector('.searchWrap');
    var searchBox  = document.querySelector('.searchInput');
    var perPageSel = document.getElementById('perPage');
    var pageInfo   = document.querySelector('.pageInfo');
    var pagerUl    = document.querySelector('.logPager .pagination');
    var perPageForm = document.querySelector('.perPage');
    if (!table) return;

    var rows = Array.prototype.slice.call(table.querySelectorAll('.reportRow'));
    rows.forEach(function (r, i) { r.dataset.index = i; }); // remember original order

    
    var state = {
        page: 1,
        perPage: perPageSel ? parseInt(perPageSel.value, 10) : 8,
        query: '',
        status: '',
        sort: 'default'
    };

   
    var emptyEl = document.createElement('div');
    emptyEl.className = 'emptyState';
    emptyEl.textContent = table.dataset.emptyText || 'No reports found.';
    emptyEl.hidden = true;
    table.appendChild(emptyEl);

    function getFiltered() {
        var q = state.query.trim().toLowerCase();

        var list = rows.filter(function (r) {
            var okStatus = !state.status || r.dataset.status === state.status;
            var okQuery  = !q || r.textContent.replace(/\s+/g, ' ').toLowerCase().indexOf(q) !== -1;
            return okStatus && okQuery;
        });

        list.sort(function (a, b) {
            switch (state.sort) {
                case 'name-asc':    return a.dataset.name.localeCompare(b.dataset.name);
                case 'name-desc':   return b.dataset.name.localeCompare(a.dataset.name);
                case 'date-newest': return new Date(b.dataset.date) - new Date(a.dataset.date);
                case 'date-oldest': return new Date(a.dataset.date) - new Date(b.dataset.date);
                default:            return a.dataset.index - b.dataset.index;
            }
        });
        return list;
    }

    
    function pageNumbers(current, total) {
        var pages = [];
        for (var i = 1; i <= total; i++) {
            if (i === 1 || i === total || Math.abs(i - current) <= 1) {
                pages.push(i);
            } else if (pages[pages.length - 1] !== '...') {
                pages.push('...');
            }
        }
        return pages;
    }

    function makeItem(label, page, opts) {
        opts = opts || {};
        var li = document.createElement('li');
        li.className = 'page-item' + (opts.active ? ' active' : '') + (opts.disabled ? ' disabled' : '');
        var a = document.createElement(opts.dots ? 'span' : 'a');
        a.className = 'page-link';
        a.textContent = label;
        if (!opts.dots) {
            a.href = '#';
            a.addEventListener('click', function (e) {
                e.preventDefault();
                if (opts.disabled || opts.active) return;
                state.page = page;
                render();
            });
        }
        li.appendChild(a);
        return li;
    }

    function renderPager(totalPages) {
        if (!pagerUl) return;
        pagerUl.innerHTML = '';

        pagerUl.appendChild(makeItem('Prev', state.page - 1, { disabled: state.page <= 1 }));

        pageNumbers(state.page, totalPages).forEach(function (p) {
            if (p === '...') {
                pagerUl.appendChild(makeItem('...', 0, { dots: true, disabled: true }));
            } else {
                pagerUl.appendChild(makeItem(String(p), p, { active: p === state.page }));
            }
        });

        pagerUl.appendChild(makeItem('Next', state.page + 1, { disabled: state.page >= totalPages }));
    }

    function render() {
        var list = getFiltered();
        var total = list.length;
        var totalPages = Math.max(1, Math.ceil(total / state.perPage));

        if (state.page > totalPages) state.page = totalPages;
        if (state.page < 1) state.page = 1;

        var start = (state.page - 1) * state.perPage;
        var end   = Math.min(start + state.perPage, total);

        // hide everything, then re-append in sorted order and show only this page
        rows.forEach(function (r) { r.style.display = 'none'; });
        list.forEach(function (r, i) {
            table.insertBefore(r, emptyEl); // keeps sorted order in the DOM
            if (i >= start && i < end) r.style.display = '';
        });

        emptyEl.hidden = total !== 0;

        if (pageInfo) {
            pageInfo.textContent = total === 0
                ? 'Showing 0 of 0'
                : 'Showing ' + (start + 1) + '-' + end + ' of ' + total;
        }

        renderPager(totalPages);
    }

  
    if (searchForm && searchBox) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            state.query = searchBox.value;
            state.page = 1;
            render();
        });

        
        searchBox.addEventListener('input', function () {
            if (searchBox.value === '' && state.query !== '') {
                state.query = '';
                state.page = 1;
                render();
            }
        });
    }

    
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            state.sort   = document.getElementById('sortBy').value;
            state.status = document.getElementById('status').value;
            state.page = 1;
            render();
            var toggle = document.getElementById('filterDropdown');
            if (toggle && window.bootstrap) {
                bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            }
        });

        
        var resetBtn = document.querySelector('.btnReset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                filterForm.reset();
                state.sort = 'default';
                state.status = '';
                state.query = '';
                if (searchBox) searchBox.value = '';
                state.page = 1;
                render();
            });
        }
    }

    // ---- Rows per page ----
    if (perPageSel) {
        perPageSel.addEventListener('change', function () {
            state.perPage = parseInt(perPageSel.value, 10) || 8;
            state.page = 1;
            render();
        });
    }
    if (perPageForm) {
        perPageForm.addEventListener('submit', function (e) { e.preventDefault(); });
    }

    render();
});