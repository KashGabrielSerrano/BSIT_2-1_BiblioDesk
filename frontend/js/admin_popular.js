
document.addEventListener('DOMContentLoaded', function () {

    var chart = document.getElementById('popChart');
    if (!chart) return;   // this page has no chart


    var data = [
        { name: 'Educational',                                     count: 48 },
        { name: 'Research Articles',                               count: 22 },
        { name: 'General Fiction (Horror, Sci-Fi, Romance, etc.)', count: 61 },
        { name: 'Memoirs, Autobiographies & Biographies',          count: 14 },
        { name: 'True Crime',                                      count: 27 },
        { name: 'Travel Literatures & Travelogues',                count: 9 },
        { name: 'Magazines',                                       count: 35 },
        { name: 'Art Section',                                     count: 12 }
    ];

    
    var monthEl = document.getElementById('popMonth');
    if (monthEl) {
        monthEl.textContent = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    
    data.sort(function (a, b) { return b.count - a.count; });

   
    var max = data.reduce(function (m, d) { return Math.max(m, d.count); }, 0);

    function niceStep(value) {
        if (value <= 0) return 1;
        var target = value / 5;                                   // about 5 intervals
        var pow = Math.pow(10, Math.floor(Math.log(target) / Math.LN10));
        var f = target / pow;
        var nf = f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10;
        return nf * pow;
    }

    var step = niceStep(max);
    var steps = Math.max(1, Math.ceil(max / step));
    var axisMax = steps * step;

    chart.style.setProperty('--steps', steps);

    // ---------- rows ----------
    data.forEach(function (d) {
        var pct = axisMax ? (d.count / axisMax * 100) : 0;

        var row = document.createElement('div');
        row.className = 'popRow';

        var label = document.createElement('div');
        label.className = 'popLabel';
        label.textContent = d.name;
        label.title = d.name;

        var track = document.createElement('div');
        track.className = 'popTrack';
        track.title = d.name + ': ' + d.count;

        var bar = document.createElement('span');
        bar.className = 'popBar';
        bar.style.width = pct + '%';

        var value = document.createElement('span');
        value.className = 'popValue';
        value.textContent = d.count;
        value.style.left = 'calc(' + pct + '% + 6px)';

        track.appendChild(bar);
        track.appendChild(value);
        row.appendChild(label);
        row.appendChild(track);
        chart.appendChild(row);
    });

    
    var axis = document.createElement('div');
    axis.className = 'popAxis';
    axis.appendChild(document.createElement('div'));

    var scale = document.createElement('div');
    scale.className = 'popScale';

    for (var i = 0; i <= steps; i++) {
        var tick = document.createElement('span');
        tick.className = 'popTick';
        tick.textContent = i * step;
        tick.style.left = (i / steps * 100) + '%';
        scale.appendChild(tick);
    }

    axis.appendChild(scale);
    chart.appendChild(axis);

    var caption = document.createElement('p');
    caption.className = 'popCaption';
    caption.textContent = 'Books borrowed';
    chart.appendChild(caption);
});