/* Pantau Balita — grafik pertumbuhan (Chart.js) */
(function () {
    'use strict';
    if (window.Chart) Chart.defaults.font.family = "'Nunito', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif";
    const C = { weight: '#2a9d8f', height: '#4c8bd6', head: '#e76f81' };
    const SD = { '3': '#d64545', '2': '#e9a23b', '0': '#3a9d5d', '-2': '#e9a23b', '-3': '#d64545' };

    function base(yTitle, xTitle, linearX) {
        return {
            responsive: true, maintainAspectRatio: false, animation: { duration: 400 },
            interaction: { mode: 'nearest', intersect: false },
            plugins: {
                legend: { labels: { boxWidth: 12, usePointStyle: true, font: { family: "'Nunito', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif" } } },
                tooltip: { callbacks: {} }
            },
            scales: {
                x: Object.assign({ title: { display: !!xTitle, text: xTitle } }, linearX ? { type: 'linear' } : {}),
                y: { title: { display: true, text: yTitle }, beginAtZero: false }
            }
        };
    }

    const ready = (canvas) => { if (canvas && canvas.parentElement) canvas.parentElement.classList.add('is-ready'); };

    function byDate(canvas, labels, data, label, unit, color) {
        if (!canvas) return;
        const chart = new Chart(canvas, {
            type: 'line',
            data: { labels, datasets: [{ label: label + ' (' + unit + ')', data, borderColor: color, backgroundColor: color + '33', fill: true, tension: .3, spanGaps: true, pointRadius: 4 }] },
            options: base(unit, 'Tanggal pengukuran', false)
        });
        ready(canvas);
        return chart;
    }

    function curveSets(curve, suffix) {
        if (!curve || !curve.x.length) return [];
        return ['3', '2', '0', '-2', '-3'].map(z => ({
            label: (z === '0' ? 'Median' : (Number(z) > 0 ? '+' : '') + z + ' SD') + (suffix || ''),
            data: curve.x.map((x, i) => ({ x, y: curve[z][i] })),
            borderColor: SD[z], borderWidth: z === '0' ? 2 : 1.2, borderDash: z === '0' ? [] : [5, 4],
            pointRadius: 0, fill: false, tension: .3, order: 2
        }));
    }

    function byAge(canvas, points, curves, curves2, label, unit, color, range) {
        if (!canvas) return;
        let sets = curveSets(curves, '');
        if (curves2) {
            const extra = curveSets(curves2, ' ');
            extra.forEach(s => { s.label = s.label.trim(); });
            // gabungkan agar legenda tidak ganda
            sets = sets.map((s, i) => Object.assign(s, { data: s.data.concat([{ x: 24, y: null }], extra[i].data), spanGaps: false }));
        }
        sets.push({ label: label + ' anak', data: points, borderColor: color, backgroundColor: color, pointRadius: 5, borderWidth: 2.5, showLine: true, fill: false, order: 1, tension: .2 });
        const opt = base(unit, 'Usia (bulan)', true);
        opt.scales.x.min = 0; opt.scales.x.max = range; opt.scales.x.ticks = { stepSize: range > 24 ? 6 : 3 };
        const chart = new Chart(canvas, { type: 'line', data: { datasets: sets }, options: opt });
        ready(canvas);
        return chart;
    }

    // Grafik di tab tersembunyi baru digambar saat tab ditampilkan
    const pending = [];
    function whenVisible(canvas, draw) {
        if (!canvas) return;
        if (canvas.offsetParent !== null) { draw(); return; }
        pending.push({ canvas, draw });
    }
    document.addEventListener('shown.bs.tab', function () {
        for (let i = pending.length - 1; i >= 0; i--) {
            if (pending[i].canvas.offsetParent !== null) { pending[i].draw(); pending.splice(i, 1); }
        }
    });

    window.renderGrowthCharts = function (p, prefix) {
        prefix = prefix || '';
        if (!window.Chart) {
            document.querySelectorAll('.chart-box').forEach((b) => b.classList.add('is-error'));
            return;
        }
        const $ = id => document.getElementById(prefix + id);
        byDate($('cWeight'), p.byDate.labels, p.byDate.weight, 'Berat badan', 'kg', C.weight);
        byDate($('cHeight'), p.byDate.labels, p.byDate.height, 'Panjang/tinggi', 'cm', C.height);
        if (p.byDate.head.some(v => v !== null)) {
            byDate($('cHead'), p.byDate.labels, p.byDate.head, 'Lingkar kepala', 'cm', C.head);
        }
        if (p.who) {
            whenVisible($('wWeight'), () => byAge($('wWeight'), p.byAge.weight, p.who.weight, null, 'BB', 'kg', C.weight, p.who.range));
            whenVisible($('wHeight'), () => byAge($('wHeight'), p.byAge.height, p.who.height, p.who.height2, 'PB/TB', 'cm', C.height, p.who.range));
            if (p.byAge.head.length) whenVisible($('wHead'), () => byAge($('wHead'), p.byAge.head, p.who.head, null, 'LK', 'cm', C.head, p.who.range));
        }
    };
})();
