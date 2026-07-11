import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';

Chart.register(...registerables);

document.addEventListener('DOMContentLoaded', function () {
    initSearch();
    initChart();
});

function formatNumber(n) {
    if (n === null || n === undefined) return '-';
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
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
                tension: 0,
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
        if (q.length < 2) {
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
                        var wrapper = document.createElement('div');
                        wrapper.className = 'relative flex items-center border-b border-border/30 last:border-0 text-balance';
                        var a = document.createElement('a');
                        a.href = '/games/' + game.slug;
                        a.className = 'flex items-center justify-between gap-1 flex-1 px-2 py-2 text-sm text-text hover:bg-surface-alt transition';
                        var info = '';
                        if (game.platforms) info += '<span class="text-xs text-neon-cyan">' + escapeHtml(game.platforms) + (game.year ? ' <span class="text-xs text-muted">- ' + escapeHtml(game.year) + '</span>' : '') + '</span>';
                        else if (game.year) info += '<span class="text-xs text-muted">' + escapeHtml(game.year) + '</span>';
                        var titleHtml = escapeHtml(game.name);
                        if (game.hasSteam) {
                            titleHtml += ' <button type="button" class="site-fav inline align-text-top text-sm text-muted hover:text-yellow-400 transition" data-slug="' + game.slug + '" data-title="' + escapeHtml(game.name) + '" data-cover="' + (game.cover || '') + '">\u2606</button>';
                        }
                        var badges = '';
                        if (game.hasSteam) badges += ' <span class="text-xs text-neon-cyan"><svg class="inline w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V9"/><path d="M10 19V5"/><path d="M16 19V12"/><path d="M22 19V7"/></svg></span>';
                        if (!game.exists) badges += ' <span class="text-xs text-neon-magenta"><svg class="inline w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg></span>';
                        a.innerHTML = '<div><div class="font-medium">' + titleHtml + '</div><div class="flex items-center gap-1">' + info + '</div></div>' + badges;
                        wrapper.appendChild(a);
                        results.appendChild(wrapper);
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
