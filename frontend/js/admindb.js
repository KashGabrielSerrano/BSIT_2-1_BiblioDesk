document.addEventListener('DOMContentLoaded', function () {

    // ---------- DATA (placeholders: replace with your real data) ----------

    // Months (x-axis)
    const months = ['Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];

    // Categories (left side). Remove 'True Crime' here and in `activity` if not needed.
    const categories = [
        'Educational',
        'Research Articles',
        'General Fiction (Horror, Sci-Fi, Romance, etc.)',
        'Memoirs, Autobiographies & Biographies',
        'True Crime',
        'Travel Literatures & Travelogues',
        'Magazines',
        'Art Section'
    ];

    // Per category, per month: [borrowed, returned] for Jan..Dec
    const activity = {
        'Educational': [[28,47],[72,36],[15,42],[41,63],[10,22],[8,8],[15,1],[10,63],[38,80],[12,57],[35,68],[23,16]],
        'Research Articles': [[12,20],[30,25],[8,18],[22,30],[6,10],[4,5],[7,3],[9,28],[20,35],[8,25],[18,30],[11,9]],
        'General Fiction (Horror, Sci-Fi, Romance, etc.)': [[40,52],[65,48],[30,45],[55,60],[25,30],[18,20],[22,15],[28,50],[60,72],[35,55],[48,66],[30,28]],
        'Memoirs, Autobiographies & Biographies': [[10,15],[18,12],[9,14],[16,20],[5,8],[3,4],[6,2],[7,18],[14,24],[8,16],[13,20],[9,8]],
        'True Crime': [[14,18],[22,16],[10,15],[19,24],[7,9],[5,6],[8,4],[9,20],[17,28],[9,19],[15,24],[10,9]],
        'Travel Literatures & Travelogues': [[6,9],[11,8],[5,9],[10,12],[3,5],[2,3],[4,1],[4,10],[9,14],[5,10],[8,12],[5,4]],
        'Magazines': [[20,26],[35,22],[14,21],[26,32],[12,15],[8,9],[10,6],[12,30],[24,38],[14,28],[22,34],[15,12]],
        'Art Section': [[7,10],[12,9],[6,10],[11,14],[4,6],[3,4],[5,2],[5,12],[10,16],[6,11],[9,14],[6,5]]
    };

    // ---------- BUILD THE CHART (plain JS, no library) ----------
    const chart = document.getElementById('catChart');
    if (!chart) {
        console.error('admindb.js: #catChart not found in the HTML.');
        return;
    }

    // Tallest bar across everything, so all bars share one scale
    let maxVal = 1;
    categories.forEach(function (cat) {
        (activity[cat] || []).forEach(function (pair) {
            maxVal = Math.max(maxVal, pair[0], pair[1]);
        });
    });

    // One row per category (left side), 12 month cells each
    categories.forEach(function (cat) {
        const row = document.createElement('div');
        row.className = 'catRow';

        const label = document.createElement('div');
        label.className = 'catLabel';
        label.textContent = cat;
        label.title = cat;
        row.appendChild(label);

        const data = activity[cat] || [];
        months.forEach(function (m, i) {
            const pair = data[i] || [0, 0];
            const cell = document.createElement('div');
            cell.className = 'catCell';
            cell.title = cat + ' - ' + m + '\nBorrowed: ' + pair[0] + '\nReturned: ' + pair[1];

            const b = document.createElement('span');
            b.className = 'catBar barBorrowed';
            b.style.height = (pair[0] / maxVal * 100) + '%';

            const r = document.createElement('span');
            r.className = 'catBar barReturned';
            r.style.height = (pair[1] / maxVal * 100) + '%';

            cell.appendChild(b);
            cell.appendChild(r);
            row.appendChild(cell);
        });

        chart.appendChild(row);
    });

    // Month labels at the bottom (like the old x-axis)
    const header = document.createElement('div');
    header.className = 'catRow catHeader';
    header.appendChild(document.createElement('div')).className = 'catLabel';
    months.forEach(function (m) {
        const cell = document.createElement('div');
        cell.className = 'catMonth';
        cell.textContent = m;
        header.appendChild(cell);
    });
    chart.appendChild(header);
});