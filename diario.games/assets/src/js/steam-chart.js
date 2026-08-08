import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';
import { es } from 'date-fns/locale/es';
import { getDisplayLabel, findCityMatch, findCountryMatch, isValidTimezone, getUtcOffset } from './timezones.js';
import { initShare } from './share-chart.js';

Chart.register(...registerables);

var activeTimezone = 'UTC';
var activeDisplayLabel = 'UTC';
var chart;
var activeRange = '48h';

document.addEventListener('DOMContentLoaded', function () {
    initSearch();
    initTimezone();
    initChart();
    initImportOverlay();
    initShare({
        buttonSelector: '#steam-share-btn',
        get chart() { return chart; },
        get range() { return activeRange; },
        get timezone() { return activeTimezone; },
        get data() { return window.__STEAM_CHART_DATA; },
        get appid() { return (window.__STEAM_CHART_DATA && window.__STEAM_CHART_DATA.game) ? window.__STEAM_CHART_DATA.game.appid : null; },
        get title() { return (window.__STEAM_CHART_DATA && window.__STEAM_CHART_DATA.game) ? window.__STEAM_CHART_DATA.game.name : document.title; },
        get slug() { return (window.__STEAM_CHART_DATA && window.__STEAM_CHART_DATA.game) ? window.__STEAM_CHART_DATA.game.slug : ''; },
        get capsuleUrl() {
            if (!window.__STEAM_CHART_DATA || !window.__STEAM_CHART_DATA.game) return '';
            return '/media/steam-capsule/' + window.__STEAM_CHART_DATA.game.slug + '.jpg';
        }
    });
});

function formatNumber(n) {
    if (n === null || n === undefined) return '-';
    if (n >= 1000000) return (n / 1000000).toFixed(2) + 'M';
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
    document.getElementById('steam-peak-alltime').textContent = formatNumber(data.peak_all_time);

    activeRange = '48h';
    chart = new Chart(canvas, {
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
                tension: 0.4,
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
                            var tz = activeTimezone;
                            if (tz === 'UTC') {
                                return d.toLocaleDateString('es-ES', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' })
                                    + ', ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' }) + ' UTC';
                            }
                            return d.toLocaleDateString('es-ES', { month: 'short', day: 'numeric', year: 'numeric', timeZone: tz })
                                + ', ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', timeZone: tz });
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
                    adapters: {
                        date: { zone: getUtcOffset(activeTimezone), locale: es }
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
                    },
                    afterFit: function (scale) {
                        scale.width = 60;
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

            var newData = data.ranges[range].map(function (p) {
                return { x: p.timestamp * 1000, y: p.p };
            });

            chart.data.datasets[0].data = newData;
            chart.data.datasets[0].pointRadius = 0;

            // Force Chart.js to recalculate scale range from the new data
            chart.options.scales.x.min = undefined;
            chart.options.scales.x.max = undefined;

            // Animate only vertically — skip X interpolation to avoid ghost sparklines
            chart.options.animation = { x: { duration: 0 } };
            chart.update();
            chart.options.animation = {};
        });
    });
}

function initTimezone() {
    var input = document.getElementById('steam-timezone-input');
    var suggestions = document.getElementById('steam-tz-suggestions');
    if (!input || !suggestions) return;

    var browserTz;
    try {
        browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch (e) {
        browserTz = 'UTC';
    }

    if (!isValidTimezone(browserTz)) browserTz = 'UTC';
    activeTimezone = browserTz;
    activeDisplayLabel = getDisplayLabel(browserTz);
    input.value = activeDisplayLabel;

    var blurTimeout;

    function renderSuggestions(timezones) {
        suggestions.innerHTML = '';
        if (!timezones || timezones.length === 0) {
            suggestions.classList.add('hidden');
            return;
        }
        timezones.forEach(function (tz) {
            var item = document.createElement('div');
            var name = getDisplayLabel(tz);
            var offset = getUtcOffset(tz);
            item.className = 'steam-tz-option px-3 py-1.5 text-xs text-text hover:bg-surface-alt cursor-pointer flex justify-between items-center transition-colors';
            item.setAttribute('data-tz', tz);
            item.innerHTML = '<span>' + name + '</span><span class="text-muted ml-4">UTC' + offset + '</span>';
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectTimezone(tz);
            });
            suggestions.appendChild(item);
        });
        suggestions.classList.remove('hidden');
    }

    function selectTimezone(tz) {
        activeTimezone = tz;
        activeDisplayLabel = getDisplayLabel(tz);
        input.value = activeDisplayLabel;
        suggestions.classList.add('hidden');
        applyTimezone(tz);
    }

    function applySearch(query) {
        var cityTz = findCityMatch(query);
        if (cityTz) {
            renderSuggestions([cityTz]);
            return;
        }

        var countryTzs = findCountryMatch(query);
        if (countryTzs) {
            renderSuggestions(countryTzs);
            return;
        }

        if (query.toUpperCase() === 'UTC') {
            renderSuggestions(['UTC']);
            return;
        }

        var cleaned = query.replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_\/\-]/g, '');
        if (isValidTimezone(cleaned)) {
            renderSuggestions([cleaned]);
            return;
        }

        suggestions.classList.add('hidden');
    }

    input.addEventListener('input', function () {
        clearTimeout(blurTimeout);
        var q = input.value.trim();
        if (!q || q === activeDisplayLabel) {
            suggestions.classList.add('hidden');
            return;
        }
        applySearch(q);
    });

    input.addEventListener('focus', function () {
        if (input.value === activeDisplayLabel) {
            input.select();
        }
    });

    input.addEventListener('blur', function () {
        blurTimeout = setTimeout(function () {
            suggestions.classList.add('hidden');
            input.value = activeDisplayLabel;
        }, 200);
    });

    input.addEventListener('keydown', function (e) {
        var items = suggestions.querySelectorAll('.steam-tz-option');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length === 0) return;
            var active = suggestions.querySelector('.steam-tz-option.bg-surface-alt');
            var idx = Array.from(items).indexOf(active);
            var next = (idx + 1) % items.length;
            if (active) active.classList.remove('bg-surface-alt');
            items[next].classList.add('bg-surface-alt');
            items[next].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length === 0) return;
            var active = suggestions.querySelector('.steam-tz-option.bg-surface-alt');
            var idx = Array.from(items).indexOf(active);
            var prev = (idx - 1 + items.length) % items.length;
            if (active) active.classList.remove('bg-surface-alt');
            items[prev].classList.add('bg-surface-alt');
            items[prev].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (items.length > 0) {
                var highlighted = suggestions.querySelector('.steam-tz-option.bg-surface-alt');
                if (highlighted) {
                    selectTimezone(highlighted.getAttribute('data-tz'));
                } else {
                    selectTimezone(items[0].getAttribute('data-tz'));
                }
            } else {
                var q = input.value.trim();
                if (!q) return;
                var cleaned = q.replace(/\s+/g, '_').replace(/[^a-zA-Z0-9_\/\-]/g, '');
                if (isValidTimezone(cleaned)) {
                    selectTimezone(cleaned);
                }
            }
        } else if (e.key === 'Escape') {
            suggestions.classList.add('hidden');
            input.value = activeDisplayLabel;
            input.blur();
        }
    });

    document.addEventListener('click', function (e) {
        if (!input.parentElement.contains(e.target)) {
            suggestions.classList.add('hidden');
        }
    });
}

function applyTimezone(tz) {
    if (!chart) return;
    var offset = getUtcOffset(tz);
    var utcZone = tz === 'UTC' ? 'UTC' : offset;
    chart.options.scales.x.adapters.date.zone = utcZone;
    chart.options.plugins.tooltip.callbacks.title = function (items) {
        if (!items.length) return '';
        var d = new Date(items[0].parsed.x);
        if (tz === 'UTC') {
            return d.toLocaleDateString('es-ES', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' })
                + ', ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' }) + ' UTC';
        }
        return d.toLocaleDateString('es-ES', { month: 'short', day: 'numeric', year: 'numeric', timeZone: tz })
            + ', ' + d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', timeZone: tz });
    };
    chart.options.scales.x.ticks.callback = function (val, index, ticks) {
        var d = new Date(val);
        var label = d.toLocaleDateString('es-ES', { month: 'short', day: 'numeric', timeZone: tz });
        if (tz === 'UTC') label += ' UTC';
        return label;
    };
    chart.update();
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
                        a.href = '/' + game.slug;
                        a.className = 'flex items-center justify-between gap-2 flex-1 px-2 py-2 text-sm text-text hover:bg-surface-alt transition';
                        if (!game.exists) a.setAttribute('data-importing', '');
                        var coverHtml = game.cover
                            ? '<img src="' + escapeHtml(game.cover) + '" alt="' + escapeHtml(game.name) + '" class="w-8 h-12 object-cover rounded shrink-0 bg-surface-alt">'
                            : '<div class="w-8 h-12 rounded shrink-0 bg-surface-alt flex items-center justify-center text-muted text-[8px] text-center leading-tight">Sin imagen</div>';
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
                        a.innerHTML = coverHtml + '<div class="flex-1 min-w-0"><div class="font-medium truncate">' + titleHtml + '</div><div class="flex items-center gap-1">' + info + '</div></div>' + badges;
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

function initImportOverlay() {
    var overlay = null;
    var pollTimer = null;
    var timeoutId = null;

    function hideOverlay() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
        if (!overlay) return;
        overlay.remove();
        overlay = null;
    }

    function updateOverlayText(text) {
        if (!overlay) return;
        var p = overlay.querySelector('.import-progress-text');
        if (p) p.textContent = text;
    }

    function buildOverlay(text) {
        overlay = document.createElement('div');
        overlay.setAttribute('role', 'alertdialog');
        overlay.setAttribute('aria-label', 'Importando juego');
        overlay.className = 'fixed inset-0 z-[100] bg-black/80 backdrop-blur-sm flex items-center justify-center pointer-events-none';
        overlay.innerHTML =
            '<div class="text-center pointer-events-auto">' +
            '<div class="inline-block w-12 h-12 border-4 border-neon-cyan border-t-transparent rounded-full animate-spin"></div>' +
            '<p class="text-text mt-4 text-sm font-medium">Importando juego…</p>' +
            '<p class="import-progress-text text-muted mt-1 text-xs">' + (text || 'Esto puede tardar unos segundos') + '</p>' +
            '<button class="mt-4 text-xs text-muted/60 hover:text-neon-cyan underline transition-colors" data-overlay-close>Cerrar</button>' +
            '</div>';
        document.body.appendChild(overlay);

        timeoutId = setTimeout(function () {
            updateOverlayText('Parece estar tardando más de lo esperado. Puedes cerrar e intentar de nuevo.');
        }, 60000);
    }

    function pollProgress(importId) {
        pollTimer = setInterval(function () {
            fetch('/steam-stats-api/import-progress/' + importId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.text) updateOverlayText(data.text);
                    if (data.ready) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                        updateOverlayText('¡Listo! Redirigiendo…');
                        setTimeout(function () {
                            window.location = '/' + data.slug;
                        }, 500);
                    }
                    if (data.error) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                        updateOverlayText('Error: ' + (data.text || 'No se pudo importar'));
                        if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                    }
                })
                .catch(function () {});
        }, 500);
    }

    function startImport(slug) {
        if (overlay) return;
        buildOverlay('Conectando con IGDB…');

        fetch('/steam-stats-api/import-game?slug=' + encodeURIComponent(slug))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.id) {
                    pollProgress(data.id);
                } else {
                    updateOverlayText('Error al iniciar la importación: ' + (data.error || ''));
                    if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
                }
            })
            .catch(function (err) {
                updateOverlayText('Error: ' + (err.message || 'conexión fallida'));
                if (timeoutId) { clearTimeout(timeoutId); timeoutId = null; }
            });
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-importing]');
        if (!a) return;
        var slug = a.getAttribute('href').replace(/^\/+/, '').replace(/\/+$/, '');
        if (!slug) return;
        e.preventDefault();
        startImport(slug);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay) {
            hideOverlay();
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.hasAttribute('data-overlay-close') && overlay) {
            hideOverlay();
        }
    });
}
