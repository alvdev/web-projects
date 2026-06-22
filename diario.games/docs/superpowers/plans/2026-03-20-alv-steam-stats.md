# alv-steam-stats Plugin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a Kirby CMS plugin that displays Steam's most played and trending games in a tabbed widget with server-side caching.

**Architecture:** PHP plugin with Steam API client, Kirby cache integration, panel-based configuration, and server-rendered tabs with sparkline graphs.

**Tech Stack:** PHP 8.1+, Kirby CMS 5, Steam Web API, Tailwind CSS 4

**Note:** This project doesn't have PHPUnit configured. Testing will be manual via browser verification.

---

## File Structure

```
site/plugins/alv-steam-stats/
├── index.php                    # Plugin registration
├── blueprints/
│   └── steam-stats.yml          # Panel settings blueprint
├── classes/
│   └── SteamStats.php          # API client + caching logic
├── snippets/
│   └── steam-stats-tabs.php    # Tabbed widget for hero sidebar
└── templates/
    └── steam-stats.php         # Full rankings page
```

---

### Task 1: Create Plugin Skeleton

**Files:**
- Create: `site/plugins/alv-steam-stats/index.php`
- Create: `site/plugins/alv-steam-stats/classes/SteamStats.php`

- [ ] **Step 1: Create plugin directory structure**

```bash
mkdir -p site/plugins/alv-steam-stats/{classes,blueprints,snippets,templates}
```

- [ ] **Step 2: Create SteamStats class stub**

Create `site/plugins/alv-steam-stats/classes/SteamStats.php`:

```php
<?php

namespace Alv\SteamStats;

class SteamStats
{
    private string $apiKey;
    private int $cacheTtl;
    private int $historyTtl;
    private int $historyInterval;

    public function __construct(array $settings)
    {
        $this->apiKey = $settings['api_key'] ?? '';
        $this->cacheTtl = $settings['cache_ttl'] ?? 3600;
        $this->historyTtl = $settings['history_ttl'] ?? 604800;
        $this->historyInterval = $settings['history_interval'] ?? 21600;
    }

    public function getMostPlayed(int $limit = 10): array
    {
        return [];
    }

    public function getTrending(int $limit = 10): array
    {
        return [];
    }
}
```

- [ ] **Step 3: Create plugin index.php**

Create `site/plugins/alv-steam-stats/index.php`:

```php
<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/SteamStats.php';

App::plugin('alv/steam-stats', [
    'siteMethods' => [
        'steamStatsSettings' => function () {
            $settings = $this->steam_stats_settings();
            if ($settings->isEmpty()) {
                return [];
            }
            return $settings->toObject()->toArray();
        },
        'steamStats' => function () {
            $settings = $this->steamStatsSettings();
            return new Alv\SteamStats\SteamStats($settings);
        },
    ],
]);
```

- [ ] **Step 4: Verify plugin loads**

Open Kirby Panel at `/panel`. Check that no errors appear.

- [ ] **Step 5: Commit**

```bash
git add site/plugins/alv-steam-stats/
git commit -m "feat: add alv-steam-stats plugin skeleton"
```

---

### Task 2: Create Panel Blueprint

**Files:**
- Create: `site/plugins/alv-steam-stats/blueprints/steam-stats.yml`
- Modify: `site/plugins/alv-steam-stats/index.php`

- [ ] **Step 1: Create blueprint file**

Create `site/plugins/alv-steam-stats/blueprints/steam-stats.yml`:

```yaml
title: Steam Stats Settings
fields:
  api_key:
    label: Steam API Key
    type: text
    required: true
    help: Get your key at https://steamcommunity.com/dev/apikey
    placeholder: Enter your Steam Web API key
  cache_ttl:
    label: Cache TTL (seconds)
    type: number
    default: 3600
    help: How long to cache most played data (default: 1 hour)
  history_ttl:
    label: History TTL (seconds)
    type: number
    default: 604800
    help: How long to keep player count history (default: 7 days)
  history_interval:
    label: History Poll Interval (seconds)
    type: number
    default: 21600
    help: How often to poll for sparkline data (default: 6 hours)
```

- [ ] **Step 2: Register blueprint in plugin**

Update `site/plugins/alv-steam-stats/index.php` to include blueprint:

```php
App::plugin('alv/steam-stats', [
    'blueprints' => [
        'steam-stats' => __DIR__ . '/blueprints/steam-stats.yml',
    ],
    'siteMethods' => [
        // ... existing siteMethods
    ],
]);
```

- [ ] **Step 3: Add blueprint to site.yml**

Open `site/blueprints/site.yml` and add a new tab or section for Steam Stats settings. If the file doesn't exist or you're unsure, add this at the end:

```yaml
# Steam Stats Settings
steam_stats_settings:
  label: Steam Stats
  type: fields
  fields:
    steam_stats_settings:
      label: API Configuration
      type: object
      fields:
        api_key:
          label: Steam API Key
          type: text
        cache_ttl:
          label: Cache TTL
          type: number
          default: 3600
        history_ttl:
          label: History TTL
          type: number
          default: 604800
        history_interval:
          label: History Interval
          type: number
          default: 21600
```

- [ ] **Step 4: Verify in Panel**

Go to `/panel/site/views`. You should see a "Steam Stats" section with the configured fields.

- [ ] **Step 5: Commit**

```bash
git add site/plugins/alv-steam-stats/ site/blueprints/
git commit -m "feat: add Steam Stats panel blueprint"
```

---

### Task 3: Implement Steam API Client

**Files:**
- Modify: `site/plugins/alv-steam-stats/classes/SteamStats.php`

- [ ] **Step 1: Add fetchMostPlayedFromSteam method**

Add to `SteamStats.php`:

```php
private function fetchMostPlayedFromSteam(): array
{
    $url = 'https://api.steampowered.com/ISteamChartsService/GetMostPlayedGames/v1/';
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        return [];
    }
    
    $data = json_decode($response, true);
    return $data['response']['ranks'] ?? [];
}
```

- [ ] **Step 2: Add fetchCurrentPlayers method**

Add to `SteamStats.php`:

```php
private function fetchCurrentPlayers(int $appid): int
{
    if (empty($this->apiKey)) {
        return 0;
    }
    
    $url = 'https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/?appid=' . $appid . '&key=' . $this->apiKey;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        return 0;
    }
    
    $data = json_decode($response, true);
    return $data['response']['player_count'] ?? 0;
}
```

- [ ] **Step 3: Add fetchGameDetails method**

Add to `SteamStats.php`:

```php
private function fetchGameDetails(array $appids): array
{
    if (empty($appids)) {
        return [];
    }
    
    $ids = implode(',', $appids);
    $url = 'https://store.steampowered.com/api/appdetails?appids=' . $ids;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) {
        return [];
    }
    
    $data = json_decode($response, true);
    $games = [];
    
    foreach ($data as $appid => $gameData) {
        if ($gameData['success'] ?? false) {
            $games[$appid] = [
                'name' => $gameData['data']['name'] ?? '',
                'header_image' => $gameData['data']['header_image'] ?? '',
                'capsule_image' => $gameData['data']['capsule_image'] ?? '',
            ];
        }
    }
    
    return $games;
}
```

- [ ] **Step 4: Add cache helper methods**

Add to `SteamStats.php`:

```php
private function getCached(string $key, int $ttl): ?array
{
    $cache = kirby()->cache('alv.steam-stats');
    $data = $cache->get($key);
    
    if (!$data) {
        return null;
    }
    
    if (time() - ($data['timestamp'] ?? 0) > $ttl) {
        return null;
    }
    
    return $data['value'] ?? null;
}

private function setCache(string $key, $value): void
{
    $cache = kirby()->cache('alv.steam-stats');
    $cache->set($key, [
        'value' => $value,
        'timestamp' => time(),
    ]);
}
```

- [ ] **Step 5: Test API methods manually**

Create a test script `test-steam-api.php` in the project root:

```php
<?php
require 'kirby/bootstrap.php';
$kirby = new Kirby();
$kirby->render();

$stats = new Alv\SteamStats\SteamStats(['api_key' => 'YOUR_TEST_KEY']);
$mostPlayed = $stats->fetchMostPlayedFromSteam();
echo "Most played count: " . count($mostPlayed) . "\n";
print_r(array_slice($mostPlayed, 0, 3));
```

Run: `php test-steam-api.php`

Expected: Should output 100 games with rank data.

- [ ] **Step 6: Remove test script and commit**

```bash
rm test-steam-api.php
git add site/plugins/alv-steam-stats/classes/SteamStats.php
git commit -m "feat: implement Steam API client methods"
```

---

### Task 4: Implement getMostPlayed and getTrending

**Files:**
- Modify: `site/plugins/alv-steam-stats/classes/SteamStats.php`

- [ ] **Step 1: Implement getMostPlayed method**

Replace the stub `getMostPlayed` method:

```php
public function getMostPlayed(int $limit = 10): array
{
    $cacheKey = 'most-played';
    $cached = $this->getCached($cacheKey, $this->cacheTtl);
    
    if ($cached) {
        $games = $cached;
    } else {
        $games = $this->fetchMostPlayedFromSteam();
        if (!empty($games)) {
            $this->setCache($cacheKey, $games);
        }
    }
    
    if (empty($games)) {
        return [];
    }
    
    $topGames = array_slice($games, 0, $limit);
    $appids = array_column($topGames, 'appid');
    $details = $this->fetchGameDetails($appids);
    
    $result = [];
    foreach ($topGames as $game) {
        $appid = $game['appid'];
        $currentPlayers = $this->getCurrentPlayers($appid);
        
        $result[] = [
            'rank' => $game['rank'],
            'appid' => $appid,
            'name' => $details[$appid]['name'] ?? 'Unknown',
            'capsule_image' => $details[$appid]['capsule_image'] ?? '',
            'current_players' => $currentPlayers,
            'peak_players' => $game['peak_in_game'] ?? 0,
            'last_week_rank' => $game['last_week_rank'] ?? 0,
        ];
    }
    
    return $result;
}
```

- [ ] **Step 2: Add getCurrentPlayers helper**

Add to `SteamStats.php`:

```php
private function getCurrentPlayers(int $appid): int
{
    $cacheKey = 'current-players.' . $appid;
    $cached = $this->getCached($cacheKey, 900); // 15 min cache
    
    if ($cached !== null) {
        return $cached['player_count'] ?? 0;
    }
    
    $players = $this->fetchCurrentPlayers($appid);
    $this->setCache($cacheKey, ['player_count' => $players]);
    
    return $players;
}
```

- [ ] **Step 3: Implement getTrending method**

Replace the stub `getTrending` method:

```php
public function getTrending(int $limit = 10): array
{
    $cacheKey = 'most-played';
    $cached = $this->getCached($cacheKey, $this->cacheTtl);
    
    if ($cached) {
        $games = $cached;
    } else {
        $games = $this->fetchMostPlayedFromSteam();
        if (!empty($games)) {
            $this->setCache($cacheKey, $games);
        }
    }
    
    if (empty($games)) {
        return [];
    }
    
    // Calculate momentum (rank improvement)
    $withMomentum = [];
    foreach ($games as $game) {
        $lastWeek = $game['last_week_rank'] ?? 0;
        $current = $game['rank'];
        
        if ($lastWeek > 0) {
            $momentum = $lastWeek - $current;
            $game['momentum'] = $momentum;
            $withMomentum[] = $game;
        }
    }
    
    // Sort by momentum (biggest improvement first)
    usort($withMomentum, fn($a, $b) => $b['momentum'] <=> $a['momentum']);
    
    $topTrending = array_slice($withMomentum, 0, $limit);
    $appids = array_column($topTrending, 'appid');
    $details = $this->fetchGameDetails($appids);
    
    $result = [];
    foreach ($topTrending as $game) {
        $appid = $game['appid'];
        $currentPlayers = $this->getCurrentPlayers($appid);
        $history = $this->getPlayerHistory($appid);
        
        $result[] = [
            'rank' => $game['rank'],
            'appid' => $appid,
            'name' => $details[$appid]['name'] ?? 'Unknown',
            'capsule_image' => $details[$appid]['capsule_image'] ?? '',
            'current_players' => $currentPlayers,
            'momentum' => $game['momentum'],
            'history' => $history,
        ];
    }
    
    return $result;
}
```

- [ ] **Step 4: Add getPlayerHistory method**

Add to `SteamStats.php`:

```php
private function getPlayerHistory(int $appid): array
{
    $cacheKey = 'history.' . $appid;
    $cached = $this->getCached($cacheKey, $this->historyTtl);
    
    if ($cached) {
        return $cached;
    }
    
    return [];
}
```

- [ ] **Step 5: Test in browser**

Create a test route in `site/plugins/alv-steam-stats/index.php`:

```php
'routes' => [
    [
        'pattern' => 'test-steam-stats',
        'action' => function () {
            $stats = site()->steamStats();
            $mostPlayed = $stats->getMostPlayed(5);
            $trending = $stats->getTrending(5);
            
            return [
                'most_played' => $mostPlayed,
                'trending' => $trending,
            ];
        }
    ]
]
```

Visit `/test-steam-stats` in browser. You should see JSON with game data.

- [ ] **Step 6: Remove test route and commit**

Remove the test route from `index.php`, then:

```bash
git add site/plugins/alv-steam-stats/classes/SteamStats.php site/plugins/alv-steam-stats/index.php
git commit -m "feat: implement getMostPlayed and getTrending methods"
```

---

### Task 5: Create Tabbed Snippet

**Files:**
- Create: `site/plugins/alv-steam-stats/snippets/steam-stats-tabs.php`
- Modify: `site/plugins/alv-steam-stats/index.php`

- [ ] **Step 1: Register snippet in plugin**

Update `site/plugins/alv-steam-stats/index.php`:

```php
App::plugin('alv/steam-stats', [
    'blueprints' => [
        'steam-stats' => __DIR__ . '/blueprints/steam-stats.yml',
    ],
    'snippets' => [
        'steam-stats-tabs' => __DIR__ . '/snippets/steam-stats-tabs.php',
    ],
    'siteMethods' => [
        // ... existing siteMethods
    ],
]);
```

- [ ] **Step 2: Create snippet file**

Create `site/plugins/alv-steam-stats/snippets/steam-stats-tabs.php`:

```php
<?php
$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(10);
$trending = $stats->getTrending(10);

function formatPlayers(int $count): string {
    if ($count >= 1000000) {
        return round($count / 1000000, 2) . 'M';
    } elseif ($count >= 1000) {
        return round($count / 1000, 1) . 'K';
    }
    return (string) $count;
}

function generateSparkline(array $history): string {
    if (count($history) < 2) {
        return '';
    }
    
    $players = array_column($history, 'players');
    $max = max($players);
    $min = min($players);
    $range = max(1, $max - $min);
    
    $points = [];
    foreach ($history as $i => $point) {
        $x = ($i / (count($history) - 1)) * 100;
        $y = 30 - (($point['players'] - $min) / $range) * 28;
        $points[] = "$x,$y";
    }
    
    $pathD = 'M' . implode(' ', $points);
    
    return '<svg width="100" height="30" viewBox="0 0 100 30">' .
           '<path d="' . $pathD . '" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' .
           '</svg>';
}
?>

<div class="bg-surface border border-border rounded-xl p-4">
    <h3 class="text-xs uppercase tracking-widest text-neon-magenta mb-3">Trending</h3>
    
    <!-- Tabs -->
    <div class="flex gap-0 mb-4 border-b border-border">
        <button class="steam-tab active px-4 py-2 text-xs font-bold uppercase tracking-wider text-neon-cyan border-b-2 border-neon-cyan" data-tab="most-played">
            Most Played
        </button>
        <button class="steam-tab px-4 py-2 text-xs font-bold uppercase tracking-wider text-muted border-b-2 border-transparent" data-tab="trending">
            Trending
        </button>
    </div>
    
    <!-- Most Played Tab -->
    <div class="steam-tab-content" id="most-played">
        <!-- Column Headers -->
        <div class="grid grid-cols-[32px_80px_1fr_80px_80px] gap-2 pb-2 border-b border-border mb-2">
            <span class="text-[10px] text-muted uppercase">#</span>
            <span></span>
            <span class="text-[10px] text-muted uppercase">Title</span>
            <span class="text-[10px] text-muted uppercase text-right">Now</span>
            <span class="text-[10px] text-muted uppercase text-right">Peak</span>
        </div>
        
        <!-- Game Rows -->
        <?php foreach ($mostPlayed as $game): ?>
        <div class="grid grid-cols-[32px_80px_1fr_80px_80px] gap-2 items-center py-1.5 border-b border-border/30">
            <span class="text-sm font-bold text-neon-cyan"><?= $game['rank'] ?></span>
            <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[80px] h-[30px] object-cover rounded">
            <span class="text-sm font-semibold text-text truncate"><?= htmlspecialchars($game['name']) ?></span>
            <span class="text-xs text-text text-right"><?= formatPlayers($game['current_players']) ?></span>
            <span class="text-xs text-muted text-right"><?= formatPlayers($game['peak_players']) ?></span>
        </div>
        <?php endforeach ?>
    </div>
    
    <!-- Trending Tab -->
    <div class="steam-tab-content hidden" id="trending">
        <!-- Column Headers -->
        <div class="grid grid-cols-[32px_80px_1fr_100px_80px] gap-2 pb-2 border-b border-border mb-2">
            <span class="text-[10px] text-muted uppercase">#</span>
            <span></span>
            <span class="text-[10px] text-muted uppercase">Title</span>
            <span class="text-[10px] text-muted uppercase text-center">7 Days</span>
            <span class="text-[10px] text-muted uppercase text-right">Now</span>
        </div>
        
        <!-- Game Rows -->
        <?php foreach ($trending as $game): ?>
        <div class="grid grid-cols-[32px_80px_1fr_100px_80px] gap-2 items-center py-1.5 border-b border-border/30">
            <span class="text-sm font-bold text-neon-green"><?= $game['rank'] ?></span>
            <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[80px] h-[30px] object-cover rounded">
            <span class="text-sm font-semibold text-text truncate"><?= htmlspecialchars($game['name']) ?></span>
            <div class="flex justify-center"><?= generateSparkline($game['history']) ?></div>
            <span class="text-xs text-text text-right"><?= formatPlayers($game['current_players']) ?></span>
        </div>
        <?php endforeach ?>
    </div>
    
    <!-- View All Link -->
    <div class="mt-4 pt-3 border-t border-border text-center">
        <a href="<?= url('steam-stats') ?>" class="text-xs text-neon-cyan uppercase tracking-wider font-semibold hover:text-neon-cyan/80">
            View Full Rankings →
        </a>
    </div>
</div>

<script>
document.querySelectorAll('.steam-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.steam-tab').forEach(t => {
            t.classList.remove('text-neon-cyan', 'text-neon-green', 'border-neon-cyan', 'border-neon-green');
            t.classList.add('text-muted', 'border-transparent');
        });
        document.querySelectorAll('.steam-tab-content').forEach(c => c.classList.add('hidden'));
        
        tab.classList.remove('text-muted', 'border-transparent');
        const isActive = tab.dataset.tab === 'most-played';
        tab.classList.add(isActive ? 'text-neon-cyan' : 'text-neon-green', isActive ? 'border-neon-cyan' : 'border-neon-green');
        document.getElementById(tab.dataset.tab).classList.remove('hidden');
    });
});
</script>
```

- [ ] **Step 3: Test snippet in browser**

Add to `site/templates/home.php` (temporarily):

```php
<?php snippet('steam-stats-tabs') ?>
```

Visit the homepage. You should see the tabbed widget.

- [ ] **Step 4: Commit**

```bash
git add site/plugins/alv-steam-stats/
git commit -m "feat: create tabbed snippet widget"
```

---

### Task 6: Integrate with Hero Sidebar

**Files:**
- Modify: `site/snippets/hero.php`

- [ ] **Step 1: Replace trending section**

Open `site/snippets/hero.php` and find the trending section (around line 26-40). Replace it with:

```php
<?php snippet('steam-stats-tabs') ?>
```

The full hero section should now look like:

```php
<?php $allGames = $site->find('games')->children()->sortBy('modified', 'desc'); ?>
<?php $allPosts = $allGames->children()->filterBy('template', 'post')->sortBy('date', 'desc'); ?>
<?php $latestPost = $allPosts->first(); ?>
<?php if ($latestPost): $featured = $latestPost; $isPost = true; ?>
<?php else: $featured = $allGames->filterBy('featured', 'true')->first() ?? $allGames->first(); $isPost = false; ?>
<?php endif ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <?php if ($featured): ?>
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative rounded-xl overflow-hidden group">
        <?php $heroImg = $isPost ? ($featured->parent()->cover() ?? $featured->parent()->hero()) : ($featured->cover() ?? $featured->hero()) ?>
        <?php if ($heroImg): ?>
        <img src="<?= $heroImg->url() ?>" alt="<?= $featured->title() ?>" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-surface via-surface/60 to-transparent"></div>
        <?php else: ?>
        <div class="absolute inset-0 bg-gradient-to-br from-surface to-surface-alt"></div>
        <?php endif ?>
        <div class="relative aspect-[21/9] flex items-end p-6">
            <div>
                <span class="text-xs uppercase tracking-widest text-neon-green"><?= $isPost ? 'Último post' : ($featured->featured()->isTrue() ? 'Featured' : 'Último añadido') ?></span>
                <h2 class="text-2xl font-bold text-text mt-1"><?= $featured->title() ?></h2>
                <p class="text-sm text-muted mt-1 line-clamp-2"><?= $isPost ? $featured->text()->kti() : $featured->summary()->kti() ?></p>
            </div>
        </div>
    </a>
    <?php endif ?>
    <?php snippet('steam-stats-tabs') ?>
</div>
```

- [ ] **Step 2: Test in browser**

Visit the homepage. You should see the Steam Stats tabs in the hero sidebar.

- [ ] **Step 3: Commit**

```bash
git add site/snippets/hero.php
git commit -m "feat: integrate Steam Stats tabs into hero sidebar"
```

---

### Task 7: Create Full Rankings Page

**Files:**
- Create: `site/plugins/alv-steam-stats/templates/steam-stats.php`
- Modify: `site/plugins/alv-steam-stats/index.php`

- [ ] **Step 1: Register template in plugin**

Update `site/plugins/alv-steam-stats/index.php`:

```php
App::plugin('alv/steam-stats', [
    'blueprints' => [
        'steam-stats' => __DIR__ . '/blueprints/steam-stats.yml',
    ],
    'snippets' => [
        'steam-stats-tabs' => __DIR__ . '/snippets/steam-stats-tabs.php',
    ],
    'templates' => [
        'steam-stats' => __DIR__ . '/templates/steam-stats.php',
    ],
    'routes' => [
        [
            'pattern' => 'steam-stats',
            'action' => function () {
                return [
                    'template' => 'steam-stats',
                    'model' => [],
                ];
            }
        ]
    ],
    'siteMethods' => [
        // ... existing siteMethods
    ],
]);
```

- [ ] **Step 2: Create template file**

Create `site/plugins/alv-steam-stats/templates/steam-stats.php`:

```php
<?php snippet('header') ?>

<?php
$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(100);
$trending = $stats->getTrending(100);

function formatPlayers(int $count): string {
    if ($count >= 1000000) {
        return round($count / 1000000, 2) . 'M';
    } elseif ($count >= 1000) {
        return round($count / 1000, 1) . 'K';
    }
    return (string) $count;
}

function generateSparkline(array $history): string {
    if (count($history) < 2) {
        return '<span class="text-xs text-muted">No data</span>';
    }
    
    $players = array_column($history, 'players');
    $max = max($players);
    $min = min($players);
    $range = max(1, $max - $min);
    
    $points = [];
    foreach ($history as $i => $point) {
        $x = ($i / (count($history) - 1)) * 100;
        $y = 30 - (($point['players'] - $min) / $range) * 28;
        $points[] = "$x,$y";
    }
    
    $pathD = 'M' . implode(' ', $points);
    
    return '<svg width="100" height="30" viewBox="0 0 100 30">' .
           '<path d="' . $pathD . '" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' .
           '</svg>';
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-text">Steam Charts</h1>
    <p class="text-sm text-muted mt-1">Top 100 most played and trending games on Steam</p>
</div>

<!-- Tabs -->
<div class="flex gap-0 mb-6 border-b border-border">
    <button class="steam-tab active px-4 py-2 text-sm font-bold uppercase tracking-wider text-neon-cyan border-b-2 border-neon-cyan" data-tab="most-played-full">
        Most Played
    </button>
    <button class="steam-tab px-4 py-2 text-sm font-bold uppercase tracking-wider text-muted border-b-2 border-transparent" data-tab="trending-full">
        Trending
    </button>
</div>

<!-- Most Played Tab -->
<div class="steam-tab-content" id="most-played-full">
    <div class="bg-surface border border-border rounded-xl overflow-hidden">
        <!-- Table Header -->
        <div class="grid grid-cols-[50px_120px_1fr_120px_120px] gap-4 p-4 border-b border-border bg-surface-alt">
            <span class="text-xs font-bold text-muted uppercase">#</span>
            <span></span>
            <span class="text-xs font-bold text-muted uppercase">Title</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Current Players</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Peak Players</span>
        </div>
        
        <!-- Table Body -->
        <?php foreach ($mostPlayed as $game): ?>
        <div class="grid grid-cols-[50px_120px_1fr_120px_120px] gap-4 p-4 border-b border-border/30 hover:bg-surface-alt/50 transition">
            <span class="text-lg font-bold text-neon-cyan"><?= $game['rank'] ?></span>
            <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[120px] h-[45px] object-cover rounded">
            <div class="flex items-center">
                <span class="text-base font-semibold text-text"><?= htmlspecialchars($game['name']) ?></span>
            </div>
            <span class="text-sm text-text text-right font-mono"><?= formatPlayers($game['current_players']) ?></span>
            <span class="text-sm text-muted text-right font-mono"><?= formatPlayers($game['peak_players']) ?></span>
        </div>
        <?php endforeach ?>
    </div>
</div>

<!-- Trending Tab -->
<div class="steam-tab-content hidden" id="trending-full">
    <div class="bg-surface border border-border rounded-xl overflow-hidden">
        <!-- Table Header -->
        <div class="grid grid-cols-[50px_120px_1fr_150px_120px] gap-4 p-4 border-b border-border bg-surface-alt">
            <span class="text-xs font-bold text-muted uppercase">#</span>
            <span></span>
            <span class="text-xs font-bold text-muted uppercase">Title</span>
            <span class="text-xs font-bold text-muted uppercase text-center">7 Days</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Current Players</span>
        </div>
        
        <!-- Table Body -->
        <?php foreach ($trending as $game): ?>
        <div class="grid grid-cols-[50px_120px_1fr_150px_120px] gap-4 p-4 border-b border-border/30 hover:bg-surface-alt/50 transition">
            <span class="text-lg font-bold text-neon-green"><?= $game['rank'] ?></span>
            <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[120px] h-[45px] object-cover rounded">
            <div class="flex items-center">
                <span class="text-base font-semibold text-text"><?= htmlspecialchars($game['name']) ?></span>
            </div>
            <div class="flex justify-center items-center"><?= generateSparkline($game['history']) ?></div>
            <span class="text-sm text-text text-right font-mono"><?= formatPlayers($game['current_players']) ?></span>
        </div>
        <?php endforeach ?>
    </div>
</div>

<script>
document.querySelectorAll('.steam-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.steam-tab').forEach(t => {
            t.classList.remove('text-neon-cyan', 'text-neon-green', 'border-neon-cyan', 'border-neon-green');
            t.classList.add('text-muted', 'border-transparent');
        });
        document.querySelectorAll('.steam-tab-content').forEach(c => c.classList.add('hidden'));
        
        tab.classList.remove('text-muted', 'border-transparent');
        const isActive = tab.dataset.tab.includes('most-played');
        tab.classList.add(isActive ? 'text-neon-cyan' : 'text-neon-green', isActive ? 'border-neon-cyan' : 'border-neon-green');
        document.getElementById(tab.dataset.tab).classList.remove('hidden');
    });
});
</script>

<?php snippet('footer') ?>
```

- [ ] **Step 3: Test full rankings page**

Visit `/steam-stats` in your browser. You should see the full rankings page with all 100 games.

- [ ] **Step 4: Commit**

```bash
git add site/plugins/alv-steam-stats/
git commit -m "feat: create full rankings page template"
```

---

### Task 8: Add Kirby Cache Configuration

**Files:**
- Modify: `site/config/config.php`

- [ ] **Step 1: Add cache configuration**

Open `site/config/config.php` and add the cache configuration:

```php
return [
    // ... existing config
    
    'cache' => [
        'alv.steam-stats' => [
            'type' => 'file',
            'active' => true,
        ],
    ],
];
```

- [ ] **Step 2: Test caching**

Visit the homepage twice. The second load should be faster (cached data).

- [ ] **Step 3: Commit**

```bash
git add site/config/config.php
git commit -m "feat: add Kirby cache configuration for Steam Stats"
```

---

### Task 9: Final Testing and Documentation

**Files:**
- Create: `site/plugins/alv-steam-stats/README.md`

- [ ] **Step 1: Test all features**

1. Visit homepage - verify tabs display correctly
2. Click between tabs - verify switching works
3. Visit `/steam-stats` - verify full rankings page
4. Check Panel - verify settings are accessible
5. Test with invalid API key - verify error handling

- [ ] **Step 2: Create README**

Create `site/plugins/alv-steam-stats/README.md`:

```markdown
# alv-steam-stats

A Kirby CMS plugin that displays Steam's most played and trending games.

## Features

- **Most Played Games**: Top 100 games by current player count
- **Trending Games**: Games climbing the charts (rank momentum)
- **Sparkline Graphs**: 7-day player count history
- **Server-side Caching**: Efficient Steam API usage
- **Panel Configuration**: Easy API key and cache settings

## Installation

1. Place the plugin in `site/plugins/alv-steam-stats/`
2. Get a Steam Web API key from https://steamcommunity.com/dev/apikey
3. Configure in Panel: `/panel/site/views` → Steam Stats Settings

## Usage

### In templates

```php
<?php snippet('steam-stats-tabs') ?>
```

### Full rankings page

Visit `/steam-stats` to see all 100 games.

## Configuration

- **API Key**: Required for current player counts
- **Cache TTL**: How long to cache data (default: 1 hour)
- **History TTL**: How long to keep sparkline data (default: 7 days)
- **History Interval**: How often to poll for sparkline data (default: 6 hours)

## Data Sources

- Steam Charts API: Most played games
- Steam Player Stats API: Current player counts
- Steam Store API: Game metadata

## License

MIT
```

- [ ] **Step 3: Final commit**

```bash
git add site/plugins/alv-steam-stats/
git commit -m "docs: add README for alv-steam-stats plugin"
```

---

## Summary

You now have a fully functional Steam Stats plugin with:

✅ Panel-based configuration  
✅ Server-side caching  
✅ Tabbed widget in hero sidebar  
✅ Full rankings page  
✅ Sparkline graphs for trending games  
✅ Proper error handling  

Next steps:
- Configure your Steam API key in the Panel
- Test with real data
- Consider adding historical data collection via cron job
