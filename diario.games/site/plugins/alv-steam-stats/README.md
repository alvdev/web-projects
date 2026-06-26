# alv-steam-stats

A Kirby CMS plugin that displays Steam's most played and trending games in a tabbed widget.

## Features

- **Most Played Games**: Top ~100 games by real-time current player count
- **Trending Games**: Games climbing the Steam charts (rank momentum) with 7-day sparkline graphs
- **Server-side Caching**: Efficient Steam API usage via Kirby's cache system
- **Panel Configuration**: Cache and history settings managed from the Kirby Panel

## Requirements

- PHP 8.0+
- [kirby3-dotenv](https://github.com/bnomei/kirby3-dotenv) plugin (for `.env` support)
- Steam Web API key ([get one here](https://steamcommunity.com/dev/apikey))

## Installation

1. Place the plugin in `site/plugins/alv-steam-stats/`
2. Get a Steam Web API key from https://steamcommunity.com/dev/apikey
3. Add to your `.env` file:

```
STEAM_STATS_API_KEY=your_key_here
```

4. Verify it's loaded in `site/config/config.php`:

```php
'alv.steam-stats.api-key' => env('STEAM_STATS_API_KEY', ''),
```

5. (Optional) Add a warm key for the cron endpoint in your `.env`:

```
STEAM_STATS_WARM_KEY=choose_a_secret_key
```

## Cron Job (Required for Performance)

The `/steam-stats` page loads instantly when caches are pre-warmed. Set up a cron job every 30 minutes:

```
*/30 * * * * curl -s -X POST https://yoursite.com/steam-stats-warm -d "key=YOUR_WARM_KEY" >/dev/null 2>&1
```

This calls the warm endpoint which pre-fetches all Steam data (most played, trending, player counts, game details, history) and populates the cache. The page then renders from cache in ~50ms instead of 2-5 seconds.

Without this cron, the page falls back to synchronous fetching on cache miss (same as before caching).

## Usage

### In templates

```php
<?php snippet('steam-stats-tabs') ?>
```

### Full rankings page

Visit `/steam-stats` to see all ~100 games.

## Configuration

Panel fields (Site → Steam Stats):

| Field | Default | Description |
|-------|---------|-------------|
| Cache TTL | 7200s | How long to cache most played data (2 hours) |
| History TTL | 604800s | How long to keep sparkline data (7 days) |
| History Interval | 21600s | How often to poll for sparkline data (6 hours) |

## Data Sources

- **store.steampowered.com/stats/stats**: Game list sorted by current players (scraped HTML)
- **ISteamChartsService/GetMostPlayedGames**: Rank momentum data for trending tab
- **ISteamUserStats/GetNumberOfCurrentPlayers**: Live player counts (uses your API key)
- **store.steampowered.com/api/appdetails**: Game metadata (name, images)

## Architecture

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
