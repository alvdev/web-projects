# alv-steam-stats Plugin Design

## Overview

A Kirby CMS plugin that displays Steam's "Most Played Games" and "Trending Games" in a tabbed widget, replacing the current simple trending list in the hero sidebar. Data comes from official Steam APIs with server-side caching.

## Requirements

- **Most Played Games tab**: Top 10 games with columns: Rank / Thumbnail / Title / Current Players / Peak Players (weekly)
- **Trending Games tab**: Top 10 games by rank momentum with columns: Rank / Thumbnail / Title / 7-Day Sparkline / Current Players
- **Full rankings page**: Internal page showing all 100 games from Steam's charts
- **Data source**: Official Steam APIs only (no third-party services)
- **Configuration**: Panel-based settings (API key, cache TTLs)
- **Caching**: Server-side with Kirby cache system

## Data Sources

### Steam APIs Used

1. **ISteamChartsService/GetMostPlayedGames/v1/**
   - Returns: Top 100 games with rank, appid, peak_in_game (weekly peak), last_week_rank
   - No API key required
   - Used for: Most Played tab ranking, 24h peak column, trending calculation

2. **ISteamUserStats/GetNumberOfCurrentPlayers/v1/?appid={appid}**
   - Returns: Current live player count for a specific game
   - API key required
   - Used for: Current players column, sparkline data points

3. **store.steampowered.com/api/appdetails?appids={appid}**
   - Returns: Game name, header image, capsule image, genres
   - No API key required
   - Used for: Game metadata (title, thumbnail)

### Trending Calculation

Trending is calculated by **rank momentum**: the difference between `last_week_rank` and current `rank`. Games climbing the chart fastest (biggest rank improvement) appear first.

Example: A game at rank 7 that was rank 29 last week has momentum of +22.

## Plugin Structure

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

## Configuration

### Panel Blueprint

Settings managed via Kirby Panel at `site/plugins/alv-steam-stats/blueprints/steam-stats.yml`:

```yaml
title: Steam Stats Settings
fields:
  api_key:
    label: Steam API Key
    type: text
    required: true
    help: Get your key at https://steamcommunity.com/dev/apikey
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

### Access Pattern

```php
// In SteamStats class
$settings = site()->steamStatsSettings();
$apiKey = $settings['api_key'];
$cacheTtl = $settings['cache_ttl'] ?? 3600;
```

## Caching Strategy

### Three Cache Layers

1. **Most Played Games**
   - Cache key: `alv.steam-stats.most-played`
   - TTL: Configurable (default 3600s = 1 hour)
   - Stores: Full API response (top 100 games)

2. **Current Players**
   - Cache key: `alv.steam-stats.current-players.{appid}`
   - TTL: 900s (15 minutes, hardcoded)
   - Stores: `{player_count: int, timestamp: int}`
   - Called for: Top 10 games on page load

3. **Historical Data** (for sparkline)
   - Cache key: `alv.steam-stats.history.{appid}`
   - TTL: Configurable (default 604800s = 7 days)
   - Stores: Array of `{timestamp: int, players: int}` snapshots
   - Max entries: 28 (7 days × 4 polls/day at 6h intervals)

### Sparkline Data Collection

- On page load, check if `history_interval` has passed since last poll
- If yes, fetch current players for top 20 games from Steam API
- Append `{timestamp, players}` to each game's history array
- Trim history to max 28 entries
- Save back to cache

### Rate Limiting

- Steam API allows ~200k calls/day with API key
- Aggressive caching minimizes API calls
- Current players fetched only for top 10 (not all 100)
- Historical polling limited to top 20 games

## Rendering

### Hero Sidebar Integration

Replace current trending list in `site/snippets/hero.php`:

```php
<?php snippet('steam-stats-tabs') ?>
```

### Snippet Structure

`site/plugins/alv-steam-stats/snippets/steam-stats-tabs.php`:

```php
<?php
$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(10);
$trending = $stats->getTrending(10);
?>

<div class="bg-surface border border-border rounded-xl p-4">
  <h3 class="text-xs uppercase tracking-widest text-neon-magenta mb-3">Trending</h3>
  
  <!-- Tabs -->
  <div class="flex gap-0 mb-4 border-b border-border">
    <button class="steam-tab active" data-tab="most-played">Most Played</button>
    <button class="steam-tab" data-tab="trending">Trending</button>
  </div>
  
  <!-- Most Played Tab -->
  <div class="steam-tab-content" id="most-played">
    <!-- Column headers -->
    <div class="grid grid-cols-[32px_80px_1fr_80px_80px] gap-2 pb-2 border-b border-border mb-2">
      <span class="text-[10px] text-muted uppercase">#</span>
      <span></span>
      <span class="text-[10px] text-muted uppercase">Title</span>
      <span class="text-[10px] text-muted uppercase text-right">Now</span>
      <span class="text-[10px] text-muted uppercase text-right">Peak</span>
    </div>
    
    <!-- Game rows -->
    <?php foreach ($mostPlayed as $game): ?>
      <!-- Rank, thumbnail, title, current players, 24h peak -->
    <?php endforeach ?>
  </div>
  
  <!-- Trending Tab -->
  <div class="steam-tab-content hidden" id="trending">
    <!-- Column headers -->
    <div class="grid grid-cols-[32px_80px_1fr_100px_80px] gap-2 pb-2 border-b border-border mb-2">
      <span class="text-[10px] text-muted uppercase">#</span>
      <span></span>
      <span class="text-[10px] text-muted uppercase">Title</span>
      <span class="text-[10px] text-muted uppercase text-center">7 Days</span>
      <span class="text-[10px] text-muted uppercase text-right">Now</span>
    </div>
    
    <!-- Game rows with sparkline -->
    <?php foreach ($trending as $game): ?>
      <!-- Rank, thumbnail, title, sparkline SVG, current players -->
    <?php endforeach ?>
  </div>
  
  <!-- View All Link -->
  <div class="mt-4 pt-3 border-t border-border text-center">
    <a href="<?= url('steam-stats') ?>" class="text-xs text-neon-cyan uppercase tracking-wider font-semibold">
      View Full Rankings →
    </a>
  </div>
</div>
```

### Tab Switching

Pure CSS/JS (no framework):

```html
<script>
document.querySelectorAll('.steam-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.steam-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.steam-tab-content').forEach(c => c.classList.add('hidden'));
    tab.classList.add('active');
    document.getElementById(tab.dataset.tab).classList.remove('hidden');
  });
});
</script>
```

### Sparkline SVG

Inline SVG generated from historical data:

```php
<?php
$history = $game['history']; // Array of {timestamp, players}
$max = max(array_column($history, 'players'));
$min = min(array_column($history, 'players'));
$range = max(1, $max - $min);

$points = [];
foreach ($history as $i => $point) {
  $x = ($i / (count($history) - 1)) * 100;
  $y = 30 - (($point['players'] - $min) / $range) * 28;
  $points[] = "$x,$y";
}
$pathD = 'M' . implode(' ', $points);
?>

<svg width="100" height="30" viewBox="0 0 100 30">
  <path d="<?= $pathD ?>" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

### Full Rankings Page

Template: `site/plugins/alv-steam-stats/templates/steam-stats.php`

- Route: `/steam-stats`
- Shows all 100 games in table layout
- Same 5-column structure as tabs
- Responsive: collapses to card layout on mobile

## Implementation Notes

### SteamStats Class

```php
class SteamStats {
  private $apiKey;
  private $cacheTtl;
  private $historyTtl;
  private $historyInterval;
  
  public function __construct($settings) {
    $this->apiKey = $settings['api_key'];
    $this->cacheTtl = $settings['cache_ttl'] ?? 3600;
    $this->historyTtl = $settings['history_ttl'] ?? 604800;
    $this->historyInterval = $settings['history_interval'] ?? 21600;
  }
  
  public function getMostPlayed($limit = 10) {
    // Fetch from cache or Steam API
    // Return top $limit games with current players
  }
  
  public function getTrending($limit = 10) {
    // Calculate rank momentum from most played data
    // Return top $limit games with sparkline data
  }
  
  private function fetchMostPlayedFromSteam() {
    // Call ISteamChartsService/GetMostPlayedGames
  }
  
  private function fetchCurrentPlayers($appid) {
    // Call ISteamUserStats/GetNumberOfCurrentPlayers
  }
  
  private function fetchGameDetails($appids) {
    // Call store.steampowered.com/api/appdetails
  }
  
  private function updateHistory($appids) {
    // Poll current players and append to history
  }
}
```

### Plugin Registration

```php
// In index.php
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
  'siteMethods' => [
    'steamStatsSettings' => function () {
      return $this->steam_stats_settings()->toObject()->toArray();
    },
    'steamStats' => function () {
      $settings = $this->steamStatsSettings();
      return new SteamStats($settings);
    },
  ],
]);
```

## Error Handling

- If Steam API fails, show cached data (even if expired)
- If no cache exists, show empty state with message
- If API key missing, show admin-only warning in Panel
- Log errors to Kirby's error log

## Performance

- Total API calls per page load: 11 (1 for most played + 10 for current players)
- With cache hit: 0 API calls
- Historical polling: 20 API calls every 6 hours = ~80 calls/day
- Estimated daily API usage: < 1000 calls (well within 200k limit)

## Future Enhancements (Out of Scope)

- Filter by genre
- Search games
- Compare with other games
- Export data
- Admin dashboard with analytics
