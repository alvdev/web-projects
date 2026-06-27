# Steam Player Charts Implementation Plan

> **For agentic workers:** Use superpowers:subagent-driven-development to implement step-by-step.

**Goal:** Add interactive Steam player count charts to individual game pages and a site-wide autocomplete search for Steam games.

**Architecture:** SQLite stores hourly player count snapshots and game-to-appid mapping. A PHP collector runs via cron to populate data. Chart.js renders interactive line charts on game pages. A JSON autocomplete endpoint powers the header search.

**Tech Stack:** SQLite (PHP PDO), Chart.js + date-fns adapter, Kirby CMS plugin system, Vite

---

### Task 1: Install npm dependencies

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Add Chart.js and date-fns adapter**

Run:
```bash
npm install chart.js chartjs-adapter-date-fns
```

Verify with:
```bash
node -e "require('chart.js'); require('chartjs-adapter-date-fns'); console.log('ok')"
```

---

### Task 2: Update Vite config

**Files:**
- Modify: `vite.config.js`

- [ ] **Step 1: Add steam-chart entry point**

Edit `vite.config.js` to add the second entry point:

```js
export default defineConfig({
    plugins: [tailwindcss()],
    publicDir: false,
    build: {
        outDir: 'public/assets',
        assetsDir: '',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: path.resolve(__dirname, 'assets/src/js/app.js'),
                'steam-chart': path.resolve(__dirname, 'assets/src/js/steam-chart.js')
            }
        }
    },
    server: {
        port: 5173,
        strictPort: true
    }
})
```

- [ ] **Step 2: Verify build works**

Run:
```bash
npm run build
```
Expected: builds without errors, generates `public/assets/steam-chart.js` in the manifest.

---

### Task 3: Create SteamStatsDB class

**Files:**
- Create: `site/plugins/alv-steam-stats/classes/SteamStatsDB.php`

- [ ] **Step 1: Create the database manager class**

This class handles all SQLite interactions: schema creation, game lookup, search, data insertion, and range queries.

```php
<?php

namespace Alv\SteamStats;

class SteamStatsDB
{
    private ?\PDO $pdo = null;

    private function db(): \PDO
    {
        if ($this->pdo === null) {
            $dir = dirname(__DIR__, 5) . '/sqlite';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->pdo = new \PDO('sqlite:' . $dir . '/steam_stats.db');
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec('PRAGMA journal_mode=WAL');
            $this->pdo->exec('PRAGMA synchronous=NORMAL');
            $this->initSchema();
        }
        return $this->pdo;
    }

    private function initSchema(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS steam_games (
                appid   INTEGER PRIMARY KEY,
                slug    TEXT UNIQUE NOT NULL,
                name    TEXT NOT NULL
            );
        ');
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS player_counts (
                appid        INTEGER NOT NULL,
                timestamp    INTEGER NOT NULL,
                player_count INTEGER NOT NULL,
                PRIMARY KEY (appid, timestamp)
            );
        ');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_pc_appid ON player_counts(appid)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_pc_ts ON player_counts(timestamp)');
    }

    public function upsertGame(int $appid, string $slug, string $name): void
    {
        $db = $this->db();
        $stmt = $db->prepare('INSERT OR REPLACE INTO steam_games (appid, slug, name) VALUES (?, ?, ?)');
        $stmt->execute([$appid, $slug, $name]);
    }

    public function getGameBySlug(string $slug): ?array
    {
        $db = $this->db();
        $stmt = $db->prepare('SELECT appid, slug, name FROM steam_games WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function searchGames(string $query): array
    {
        $db = $this->db();
        $stmt = $db->prepare('SELECT slug, name, appid FROM steam_games WHERE name LIKE ? LIMIT 15');
        $stmt->execute(['%' . $query . '%']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insertPlayerCount(int $appid, int $timestamp, int $playerCount): void
    {
        $db = $this->db();
        $stmt = $db->prepare('INSERT OR IGNORE INTO player_counts (appid, timestamp, player_count) VALUES (?, ?, ?)');
        $stmt->execute([$appid, $timestamp, $playerCount]);
    }

    public function getPlayerCounts(int $appid, int $since): array
    {
        $db = $this->db();
        $stmt = $db->prepare('SELECT timestamp, player_count AS p FROM player_counts WHERE appid = ? AND timestamp >= ? ORDER BY timestamp ASC');
        $stmt->execute([$appid, $since]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCurrentPlayers(int $appid): ?int
    {
        $db = $this->db();
        $stmt = $db->prepare('SELECT player_count FROM player_counts WHERE appid = ? ORDER BY timestamp DESC LIMIT 1');
        $stmt->execute([$appid]);
        $row = $stmt->fetch(\PDO::FETCH_COLUMN);
        return $row !== false ? (int) $row : null;
    }

    public function getPeakPlayers(int $appid, int $since): ?int
    {
        $db = $this->db();
        $stmt = $db->prepare('SELECT MAX(player_count) FROM player_counts WHERE appid = ? AND timestamp >= ?');
        $stmt->execute([$appid, $since]);
        $row = $stmt->fetch(\PDO::FETCH_COLUMN);
        return $row !== false ? (int) $row : null;
    }

    public function getAllAppids(): array
    {
        $db = $this->db();
        return $db->query('SELECT appid FROM steam_games')->fetchAll(\PDO::FETCH_COLUMN);
    }
}
```

- [ ] **Step 2: Add Roman numeral normalization helper**

In the SteamStatsDB class, add a static helper used by the collector:

```php
public static function normalizeSlug(string $slug): string
{
    $map = [
        '/\biii\b/i' => '3',
        '/\bii\b/i'  => '2',
        '/\biv\b/i'  => '4',
        '/\bvi\b/i'  => '6',
    ];
    // Only replace when followed by word boundary or end of string
    foreach ($map as $pattern => $replacement) {
        $slug = preg_replace($pattern, $replacement, $slug);
    }
    return $slug;
}
```

---

### Task 4: Create SteamStatsCollector class

**Files:**
- Create: `site/plugins/alv-steam-stats/classes/SteamStatsCollector.php`

- [ ] **Step 1: Create the collector class**

```php
<?php

namespace Alv\SteamStats;

class SteamStatsCollector
{
    private string $apiKey;
    private SteamStatsDB $db;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->db = new SteamStatsDB();
    }

    public function collect(): array
    {
        $gamesDir = dirname(__DIR__, 4) . '/content/games';
        $stats = ['scanned' => 0, 'updated' => 0, 'errors' => []];

        $dirs = new \DirectoryIterator($gamesDir);
        foreach ($dirs as $dir) {
            if (!$dir->isDir() || $dir->isDot()) continue;

            $slug = $dir->getFilename();
            $gameFile = $dir->getPathname() . '/game.txt';
            if (!file_exists($gameFile)) continue;

            $stats['scanned']++;
            $content = file_get_contents($gameFile);

            // Extract Steam app ID from Websites field
            if (preg_match('/store\.steampowered\.com\/app\/(\d+)/i', $content, $m)) {
                $appid = (int) $m[1];

                // Extract title
                preg_match('/^Title:\s*(.+)/m', $content, $tm);
                $name = trim($tm[1] ?? $slug);

                $normalizedSlug = SteamStatsDB::normalizeSlug($slug);
                $this->db->upsertGame($appid, $normalizedSlug, $name);
            }
        }

        // Fetch current players for all known appids
        $appids = $this->db->getAllAppids();
        $now = time();
        $hourSlot = $now - ($now % 3600); // round to current hour

        foreach ($appids as $appid) {
            $count = $this->fetchCurrentPlayers($appid);
            if ($count !== null) {
                $this->db->insertPlayerCount($appid, $hourSlot, $count);
                $stats['updated']++;
            } else {
                $stats['errors'][] = $appid;
            }
        }

        return $stats;
    }

    private function fetchCurrentPlayers(int $appid): ?int
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $url = 'https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/?' . http_build_query([
            'appid' => $appid,
            'key' => $this->apiKey,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['response']['player_count'] ?? null;
    }
}
```

---

### Task 5: Register API routes in plugin

**Files:**
- Modify: `site/plugins/alv-steam-stats/index.php`

- [ ] **Step 1: Add routes for search, game data, and collect**

Edit `site/plugins/alv-steam-stats/index.php`:

```php
<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/SteamStats.php';
@include_once __DIR__ . '/classes/SteamStatsDB.php';
@include_once __DIR__ . '/classes/SteamStatsCollector.php';

App::plugin('alv/steam-stats', [
    'snippets' => [
        'steam-stats-tabs' => __DIR__ . '/snippets/steam-stats-tabs.php',
        'steam-chart' => __DIR__ . '/snippets/steam-chart.php',
    ],
    'options' => [
        'cache' => [
            'type' => 'file',
            'active' => true,
        ],
    ],
    'routes' => [
        [
            'pattern' => 'steam-stats',
            'action' => function () {
                return \Kirby\Cms\Page::factory([
                    'slug' => 'steam-stats',
                    'template' => 'steam-stats',
                    'content' => [
                        'title' => 'Steam Charts',
                    ],
                ])->render();
            }
        ],
        [
            'pattern' => 'steam-stats-update-history',
            'method' => 'POST',
            'action' => function () {
                $stats = site()->steamStats();
                $stats->updatePlayerHistory();
                return ['status' => 'ok'];
            }
        ],
        [
            'pattern' => 'steam-stats-warm',
            'method' => 'POST',
            'action' => function () {
                $key = get('key');
                $expectedKey = option('alv.steam-stats.warm-key');
                if ($expectedKey && $key !== $expectedKey) {
                    return ['error' => 'unauthorized'];
                }

                $stats = site()->steamStats();
                $stats->getMostPlayed(100);
                $stats->getTrending(100);
                $stats->updatePlayerHistory();

                kirby()->cache('alv/steam-stats.cache')
                    ->set('warm-last-run', ['value' => time(), 'timestamp' => time()]);

                return ['status' => 'ok'];
            }
        ],
        // --- New API routes ---
        [
            'pattern' => 'steam-stats-api/search',
            'method' => 'GET',
            'action' => function () {
                $q = get('q', '');
                if (strlen($q) < 1) {
                    return ['results' => []];
                }
                $db = new \Alv\SteamStats\SteamStatsDB();
                return ['results' => $db->searchGames($q)];
            }
        ],
        [
            'pattern' => 'steam-stats-api/game/(:any)/data',
            'method' => 'GET',
            'action' => function (string $slug) {
                $db = new \Alv\SteamStats\SteamStatsDB();
                $game = $db->getGameBySlug($slug);
                if (!$game) {
                    return ['error' => 'not found'];
                }

                $appid = $game['appid'];
                $now = time();
                $day = 86400;
                $hour = 3600;

                $ranges = ['48h' => 2 * $day, '1w' => 7 * $day, '1m' => 30 * $day, '3m' => 90 * $day, '6m' => 180 * $day, '1y' => 365 * $day, 'max' => 0];

                $data = [
                    'game' => $game,
                    'current' => $db->getCurrentPlayers($appid),
                    'peak_24h' => $db->getPeakPlayers($appid, $now - $day),
                    'peak_3m' => $db->getPeakPlayers($appid, $now - 90 * $day),
                    'ranges' => [],
                ];

                foreach ($ranges as $key => $duration) {
                    $since = $duration > 0 ? $now - $duration : 0;
                    $points = $db->getPlayerCounts($appid, $since);
                    if (in_array($key, ['max', '1y', '6m', '3m'], true)) {
                        $step = max(1, intdiv(count($points), 1000));
                        $filtered = [];
                        foreach ($points as $i => $pt) {
                            if ($i % $step === 0) $filtered[] = $pt;
                        }
                        $points = $filtered;
                    }
                    $data['ranges'][$key] = $points;
                }

                return $data;
            }
        ],
        [
            'pattern' => 'steam-stats-api/collect',
            'method' => 'POST',
            'action' => function () {
                $key = get('key');
                $expectedKey = option('alv.steam-stats.warm-key');
                if ($expectedKey && $key !== $expectedKey) {
                    return ['error' => 'unauthorized'];
                }

                $collector = new \Alv\SteamStats\SteamStatsCollector(option('alv.steam-stats.api-key', ''));
                $stats = $collector->collect();

                kirby()->cache('alv/steam-stats.cache')
                    ->set('warm-last-run', ['value' => time(), 'timestamp' => time()]);

                return ['status' => 'ok', 'stats' => $stats];
            }
        ],
    ],
    'templates' => [
        'steam-stats' => __DIR__ . '/templates/steam-stats.php',
    ],
    'siteMethods' => [
        'steamStatsSettings' => function () {
            return [
                'api_key'          => option('alv.steam-stats.api-key', ''),
                'cache_ttl'        => (int) ($this->steam_stats_cache_ttl()->value() ?: 3600),
                'history_ttl'      => (int) ($this->steam_stats_history_ttl()->value() ?: 604800),
                'history_interval' => (int) ($this->steam_stats_history_interval()->value() ?: 21600),
            ];
        },
        'steamStats' => function () {
            $settings = $this->steamStatsSettings();
            return new Alv\SteamStats\SteamStats($settings);
        },
        'steamChartData' => function (string $slug) {
            $db = new \Alv\SteamStats\SteamStatsDB();
            $game = $db->getGameBySlug($slug);
            if (!$game) return null;

            $appid = $game['appid'];
            $now = time();
            $day = 86400;

            $ranges = ['48h' => 2 * $day, '1w' => 7 * $day, '1m' => 30 * $day, '3m' => 90 * $day, '6m' => 180 * $day, '1y' => 365 * $day, 'max' => 0];

            $data = [
                'game' => $game,
                'current' => $db->getCurrentPlayers($appid),
                'peak_24h' => $db->getPeakPlayers($appid, $now - $day),
                'peak_3m' => $db->getPeakPlayers($appid, $now - 90 * $day),
                'ranges' => [],
            ];

            foreach ($ranges as $key => $duration) {
                $since = $duration > 0 ? $now - $duration : 0;
                $points = $db->getPlayerCounts($appid, $since);
                if ($key === 'max' || $key === '1y' || $key === '6m' || $key === '3m') {
                    $limit = 1000;
                    $step = max(1, intdiv(count($points), $limit));
                    $filtered = [];
                    foreach ($points as $i => $pt) {
                        if ($i % $step === 0) {
                            $filtered[] = $pt;
                        }
                    }
                    $points = $filtered;
                }
                $data['ranges'][$key] = $points;
            }

            return $data;
        },
    ],
]);
```

- [ ] **Step 2: Add CLI Kirby command for collection**

Add a `'commands'` key inside the main `App::plugin()` config array (at the same level as `'routes'`, `'siteMethods'`, etc.):

```php
    'commands' => [
        'steam-stats:collect' => [
            'description' => 'Collect current Steam player counts for all tracked games',
            'args' => [],
            'command' => function () {
                $collector = new \Alv\SteamStats\SteamStatsCollector(option('alv.steam-stats.api-key', ''));
                $stats = $collector->collect();
                echo "Scanned: {$stats['scanned']}, Updated: {$stats['updated']}, Errors: " . count($stats['errors']) . "\n";
                if (!empty($stats['errors'])) {
                    echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
                }
            }
        ],
    ],
```

This allows running `php kirby steam-stats:collect` from CLI.

---

### Task 6: Create chart snippet

**Files:**
- Create: `site/plugins/alv-steam-stats/snippets/steam-chart.php`

- [ ] **Step 1: Create the chart HTML snippet**

```php
<?php if (!isset($data) || !$data): return; endif; ?>
<div class="mt-8 pt-8 border-t border-border" id="steam-chart-section">
    <h2 class="text-lg font-bold text-neon-green mb-6">Jugadores en Steam</h2>

    <div class="bg-surface border border-border rounded-xl p-6">
        <!-- Stats summary -->
        <div class="grid grid-cols-3 gap-4 mb-6 text-center">
            <div>
                <div class="text-xs uppercase tracking-wider text-muted">Ahora</div>
                <div class="text-xl font-bold text-neon-cyan" id="steam-current">-</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-muted">Pico 24h</div>
                <div class="text-xl font-bold text-neon-magenta" id="steam-peak-24h">-</div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-muted">Pico 3 meses</div>
                <div class="text-xl font-bold text-neon-green" id="steam-peak-3m">-</div>
            </div>
        </div>

        <!-- Time range tabs -->
        <div class="flex flex-wrap gap-1 mb-4 border-b border-border pb-2">
            <?php $tabs = ['48h', '1w', '1m', '3m', '6m', '1y', 'max']; ?>
            <?php foreach ($tabs as $i => $tab): ?>
                <button type="button"
                    class="steam-range-tab px-3 py-1 text-xs font-semibold rounded transition
                    <?= $i === 0 ? 'bg-neon-cyan/20 text-neon-cyan' : 'text-muted hover:text-text' ?>"
                    data-range="<?= $tab ?>">
                    <?= strtoupper($tab) ?>
                </button>
            <?php endforeach ?>
        </div>

        <!-- Chart canvas -->
        <div class="relative" style="height: 300px;">
            <canvas id="steam-chart-canvas"></canvas>
        </div>
    </div>
</div>

<script>
window.__STEAM_CHART_DATA = <?= json_encode($data) ?>;
</script>
```

---

### Task 7: Create steam-chart.js

**Files:**
- Create: `assets/src/js/steam-chart.js`

- [ ] **Step 1: Write Chart.js initialization and search autocomplete**

```js
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

    // Populate stats
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

    // Tab switching
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

    // Close on click outside
    document.addEventListener('click', function (e) {
        if (!container.contains(e.target)) {
            results.classList.add('hidden');
        }
    });

    // Keyboard navigation
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
```

- [ ] **Step 2: Build and verify**

Run:
```bash
npm run build
```
Expected: builds without errors, `steam-chart.js` appears in the Vite manifest.

---

### Task 8: Modify game template to include chart

**Files:**
- Modify: `site/templates/game.php`

- [ ] **Step 1: Add chart after screenshots section**

After the screenshots section's closing `</div>` for `#lightbox` (line 243), add:

```php
<?php $steamData = $site->steamChartData($page->slug()); ?>
<?php if ($steamData): ?>
    <?php snippet('steam-chart', ['data' => $steamData]) ?>
<?php endif ?>
```

- [ ] **Step 2: Add chart to game template**

Edit `site/templates/game.php`. After the screenshots section's closing `</div>` for `#lightbox` and before the posts section, add:

```php
<?php $steamData = $site->steamChartData($page->slug()); ?>
<?php if ($steamData): ?>
    <?php snippet('steam-chart', ['data' => $steamData]) ?>
<?php endif ?>
```

---

### Task 9: Add autocomplete search to header

**Files:**
- Modify: `site/snippets/header.php`

- [ ] **Step 1: Add steam-chart JS to global head**

Add the steam-chart entry point alongside the existing app.js entry. The search autocomplete code runs on all pages; the chart code only activates when the canvas element is present on game pages.

```php
<?= vite()->js('assets/src/js/app.js') ?>
<?= vite()->js('assets/src/js/steam-chart.js') ?>
<?= vite()->css('assets/src/js/app.js') ?>
<?= vite()->css('assets/src/js/steam-chart.js') ?>
```

- [ ] **Step 2: Add search HTML**

Replace the existing search link with the autocomplete search component. Remove this:
```html
<a href="<?= url('search') ?>" class="text-neon-magenta hover:text-neon-cyan transition shrink-0">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
</a>
```

Add this in its place:
```html
<div id="steam-header-search" class="relative shrink-0">
    <input type="text"
        placeholder="Buscar juegos..."
        class="w-48 lg:w-64 px-3 py-1.5 text-sm bg-surface border border-border rounded-lg text-text placeholder-muted focus:outline-none focus:border-neon-cyan transition">
    <div class="steam-search-results hidden absolute top-full right-0 mt-1 w-72 bg-surface border border-border rounded-lg shadow-xl max-h-96 overflow-y-auto z-50"></div>
</div>
```

---

### Task 10: Verify and test

- [ ] **Step 1: Run the collector**

```bash
php kirby steam-stats:collect
```
Expected: outputs "Scanned: X, Updated: Y, Errors: 0"

- [ ] **Step 2: Check SQLite data**

```bash
sqlite3 sqlite/steam_stats.db "SELECT COUNT(*) FROM steam_games; SELECT COUNT(*) FROM player_counts;"
```
Expected: shows some games and player count entries.

- [ ] **Step 3: Build frontend**

```bash
npm run build
```
Expected: builds cleanly.

- [ ] **Step 4: Test search endpoint**

Visit `/steam-stats-api/search?q=baldur`
Expected: JSON with matching games.

- [ ] **Step 5: Test game page with Steam data**

Visit `/games/baldurs-gate-3` (or whatever the slug resolves to)
Expected: Chart section visible after screenshots, showing player counts.

- [ ] **Step 6: Test non-Steam game page**

Visit `/games/chrono-trigger`
Expected: No chart section (game has no Steam data).

- [ ] **Step 7: Test header search**

Click on the header search input, type "baldur"
Expected: Dropdown appears with matching games, click navigates to game page.

- [ ] **Step 8: Test range tabs on chart**

Click each range tab
Expected: Chart updates to show data for that range. Tooltips work on hover/touch.

---

### Task 11: Add cron setup documentation

- [ ] **Step 1: Update plugin README**

Add cron collector instructions to `site/plugins/alv-steam-stats/README.md`:

```markdown
## Cron Job (Required for Chart Data)

The player count charts require hourly data collection. Set up a cron job:

```
0 * * * * curl -s -X POST https://yoursite.com/steam-stats-api/collect -d "key=YOUR_WARM_KEY"
```

Or via Kirby CLI:

```
0 * * * * cd /path/to/project && php kirby steam-stats:collect
```
```
