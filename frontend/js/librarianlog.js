document.addEventListener('DOMContentLoaded', function () {
    var form  = document.getElementById('filterForm');
    var table = document.querySelector('.logTable');
    if (!form || !table) return;
 
    var rows = Array.prototype.slice.call(table.querySelectorAll('.reportRow'));
    rows.forEach(function (r, i) { r.dataset.index = i; }); // remember original order
 
    function applyFilters() {
        var sort   = document.getElementById('sortBy').value;
        var status = document.getElementById('status').value;
 
        // filter
        rows.forEach(function (r) {
            r.style.display = (!status || r.dataset.status === status) ? '' : 'none';
        });
 
        // sort
        var sorted = rows.slice().sort(function (a, b) {
            switch (sort) {
                case 'name-asc':    return a.dataset.name.localeCompare(b.dataset.name);
                case 'name-desc':   return b.dataset.name.localeCompare(a.dataset.name);
                case 'date-newest': return new Date(b.dataset.date) - new Date(a.dataset.date);
                case 'date-oldest': return new Date(a.dataset.date) - new Date(b.dataset.date);
                default:            return a.dataset.index - b.dataset.index;
            }
        });
        sorted.forEach(function (r) { table.appendChild(r); });
    }
 
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        applyFilters();
        bootstrap.Dropdown.getOrCreateInstance(document.getElementById('filterDropdown')).hide();
    });
 
    // Reset: restore everything without reloading
    var resetBtn = document.querySelector('.btnReset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            form.reset();
            applyFilters();
        });
    }
});