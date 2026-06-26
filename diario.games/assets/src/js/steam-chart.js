import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';

Chart.register(...registerables);

document.addEventListener('DOMContentLoaded', function () {
    initSearch();
    initChart();
});

function formatNumber(n) {
    if (n === null || n === undefined) return '-';
    if (n >= 1000000) return (n / 1000000).toFixed(2) + 'M';
    if (n >= 1000) return Math.round(n / 1000) + 'K';
    return n.toLocaleString();
}

function initChart() {
    var canvas = document.getElementById('steam-chart-canvas');
    if (!canvas) return;

    var data = window.__STEAM_CHART_DATA;
    if (!data) return;

    document.getElementById('steam-current').textContent = formatNumber(data.current);
    document.getElementById('steam-peak-24h').textContent = formatNumber(data.peak_24h);
    document.getElementById('steam-peak-3m').textContent = formatNumber(data.peak_3m);

    var activeRange = '48h';
    var chart = new Chart(canvas, {
        type: 'line',
        data: {
            datasets: [{
                data: data.ranges[activeRange].map(function (p) { return { x: p.timestamp * 1000, y: p.p }; }),
                borderColor: '#00ffff',
                backgroundColor: function (ctx) {
                    if (!ctx.chart || !ctx.chart.ctx) return 'rgba(0, 255, 255, 0.05)';
                    var gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(0, 255, 255, 0.15)');
                    gradient.addColorStop(1, 'rgba(0, 255, 255, 0)');
                    return gradient;
                },
                fill: true,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#00ffff',
                pointHoverBorderColor: '#ffffff',
                pointHoverBorderWidth: 2,
                tension: 0.1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                    titleColor: '#ffffff',
                    bodyColor: '#00ffff',
                    borderColor: 'rgba(0, 255, 255, 0.3)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        title: function (items) {
                            if (!items.length) return '';
                            var d = new Date(items[0].parsed.x);
                            return d.toLocaleDateString('es-ES', { month: 'short', day: 'numeric', year: 'numeric' })
                                + ', ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
                        },
                        label: function (item) {
                            return item.parsed.y.toLocaleString() + ' jugadores';
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        tooltipFormat: 'MMM dd, HH:mm',
                        displayFormats: {
                            hour: 'MMM dd HH:mm',
                            day: 'MMM dd',
                            month: 'MMM yyyy',
                        }
                    },
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#888888', maxTicksLimit: 10 }
                },
                y: {
                    beginAtZero: false,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: {
                        color: '#888888',
                        callback: function (value) { return formatNumber(value); }
                    }
                }
            }
        }
    });

    document.querySelectorAll('.steam-range-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var range = btn.getAttribute('data-range');
            if (range === activeRange) return;
            activeRange = range;

            document.querySelectorAll('.steam-range-tab').forEach(function (t) {
                t.classList.remove('bg-neon-cyan/20', 'text-neon-cyan');
                t.classList.add('text-muted', 'hover:text-text');
            });
            btn.classList.add('bg-neon-cyan/20', 'text-neon-cyan');
            btn.classList.remove('text-muted', 'hover:text-text');

            chart.data.datasets[0].data = data.ranges[range].map(function (p) {
                return { x: p.timestamp * 1000, y: p.p };
            });
            chart.update('none');
        });
    });
}

function initSearch() {
    var container = document.getElementById('steam-header-search');
    if (!container) return;

    var input = container.querySelector('input');
    var results = container.querySelector('.steam-search-results');
    var debounceTimer;

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = input.value.trim();
        if (q.length < 1) {
            results.classList.add('hidden');
            results.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(function () {
            fetch('/steam-stats-api/search?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    results.innerHTML = '';
                    if (!data.results || data.results.length === 0) {
                        results.classList.add('hidden');
                        return;
                    }
                    data.results.forEach(function (game) {
                        var a = document.createElement('a');
                        a.href = '/games/' + game.slug;
                        a.className = 'block px-4 py-2 text-sm text-text hover:bg-surface-alt transition border-b border-border/30 last:border-0';
                        a.innerHTML = '<span class="font-medium">' + escapeHtml(game.name) + '</span> <span class="text-xs text-neon-cyan">Steam</span>';
                        results.appendChild(a);
                    });
                    results.classList.remove('hidden');
                })
                .catch(function () {
                    results.classList.add('hidden');
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!container.contains(e.target)) {
            results.classList.add('hidden');
        }
    });

    input.addEventListener('keydown', function (e) {
        var items = results.querySelectorAll('a');
        if (items.length === 0) return;
        var active = results.querySelector('a:hover') || results.querySelector('a:focus');
        var idx = Array.from(items).indexOf(active);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            var next = (idx + 1) % items.length;
            items[next].focus();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            var prev = (idx - 1 + items.length) % items.length;
            items[prev].focus();
        } else if (e.key === 'Escape') {
            results.classList.add('hidden');
            input.blur();
        }
    });
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
