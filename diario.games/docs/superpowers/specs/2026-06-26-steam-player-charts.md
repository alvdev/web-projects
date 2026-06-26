# Steam Player Count Charts

## Overview

Add interactive player count charts to individual game pages (after screenshots) and a site-wide header autocomplete search for Steam games, similar to steamdb.info/charts. Data is persisted in SQLite and collected hourly via cron.

## Architecture

```
┌─────────────────────────────────────────┐
│           Cron (hourly)                  │
│  SteamStatsCollector                     │
│  ┌──────────┐   ┌──────────────┐        │
│  │ Scan all  │──>│ Extract App  │        │
│  │ game.txt  │   │ IDs from     │        │
│  │ files     │   │ Websites     │        │
│  └──────────┘   └──────┬───────┘        │
│                        │                 │
│                        ▼                 │
│             ┌──────────────────┐         │
│             │ Fetch from Steam │         │
│             │ API (current     │         │
│             │ players)         │         │
│             └──────┬───────────┘         │
│                    │                     │
│                    ▼                     │
│             ┌──────────────┐            │
│             │  SQLite DB    │            │
│             │  steam_stats  │            │
│             └──────────────┘            │
└─────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────┐
│          Page Request (PHP)              │
│                                          │
│  Header: HTML search + JS autocomplete   │
│  └─► GET /steam-stats-api/search?q=...   │
│      └─► queries SQLite steam_games      │
│                                          │
│  Game page: after screenshots            │
│  └─► Embed JSON data inline              │
│  └─► Chart.js renders chart              │
│      with hover tooltips + time tabs     │
└─────────────────────────────────────────┘
```

## Data Flow

1. **Collection** — A PHP CLI/Kirby command runs hourly via cron, scans `content/games/*/game.txt` for Steam URLs, extracts app IDs, calls Steam API for current player count, stores in SQLite
2. **Serving** — Game page PHP reads from SQLite, passes JSON to the template, Chart.js renders interactively
3. **Search** — Header autocomplete queries SQLite `steam_games` table via a JSON endpoint

## SQLite Schema

File: `sqlite/steam_stats.db`

```sql
CREATE TABLE steam_games (
    appid   INTEGER PRIMARY KEY,
    slug    TEXT UNIQUE NOT NULL,
    name    TEXT NOT NULL
);

CREATE TABLE player_counts (
    appid        INTEGER NOT NULL,
    timestamp    INTEGER NOT NULL,
    player_count INTEGER NOT NULL,
    PRIMARY KEY (appid, timestamp)
);

CREATE INDEX idx_pc_appid ON player_counts(appid);
CREATE INDEX idx_pc_ts ON player_counts(timestamp);
```

## API Endpoints

All under `/steam-stats-api` prefix. Defined in plugin's `index.php`.

### GET /steam-stats-api/search?q=...

Returns matching Steam games for header autocomplete.

**Response:**
```json
{
  "results": [
    { "slug": "baldurs-gate-3", "name": "Baldur's Gate III", "appid": 1086940 }
  ]
}
```
When extracting slugs from directory names, Roman numerals are normalized to regular numbers (e.g. `baldurs-gate-iii` → `baldurs-gate-3`). The normalized slug is stored in `steam_games` and used in URLs returned by search.

**Implementation:** `SELECT slug, name, appid FROM steam_games WHERE name LIKE '%query%' LIMIT 15`

### GET /steam-stats-api/game/:slug

Returns all chart data for a single game. Called server-side (not from JS) to embed JSON in the page.

**Response:**
```json
{
  "game":      { "slug": "baldurs-gate-3", "name": "Baldur's Gate III", "appid": 1086940 },
  "current":   45231,
  "peak_24h":  58300,
  "peak_3m":   87200,
  "ranges": {
    "48h":  [ { "t": 1719350000, "p": 45100 }, ... ],
    "1w":   [ ... ],
    "1m":   [ ... ],
    "3m":   [ ... ],
    "6m":   [ ... ],
    "1y":   [ ... ],
    "max":  [ ... ]
  }
}
```

Data points are sorted by timestamp ascending (numerical order). Returns 404 JSON if slug has no Steam data.

**Range bucketing:** 48h/1w = hourly points, 1m/3m = 6-hourly, 6m/1y/max = daily.

### POST /steam-stats-api/collect

Protected cron endpoint (`key` param required). Scans all games, fetches current players, stores in SQLite.

## Frontend

### Chart.js Integration

- Chart.js + chartjs-adapter-date-fns installed via npm
- Separate Vite entry point: `assets/src/js/steam-chart.js`
- Loaded on all pages (search) and selectively on game pages (chart)

### Game Page Chart Widget

Appears after the screenshots section, only for games with Steam data. Displays:
- Stats bar: Players now, 24h peak, 3m peak
- Time range tabs: [48h] [1w] [1m] [3m] [6m] [1y] [MAX]
- Line chart with area fill using neon-cyan theme colors
- Hover/click tooltip: "Jun 25, 14:00 — 45,231 players"

Chart options:
- Time scale X axis (date-fns adapter)
- Player count Y axis with abbreviation (1.5K, 45K, 1.2M)
- Point radius 0, hover radius 4
- Crosshair on hover
- Touch-friendly (tap to show tooltip)

### Header Search Autocomplete

Input in header nav with dropdown. Shows on all pages.

- Debounced input (300ms) → fetches search endpoint
- Dropdown shows up to 15 matches with game name
- Click navigates to `/games/{slug}`
- Arrow keys + enter navigation
- Click outside closes
- If no Steam data, search returns empty results (no "Steam graphs" shown)

## New Files

| Path | Purpose |
|------|---------|
| `site/plugins/alv-steam-stats/classes/SteamStatsDB.php` | SQLite database manager (schema, insert, query) |
| `site/plugins/alv-steam-stats/classes/SteamStatsCollector.php` | Cron collector: scan games, fetch API, store |
| `site/plugins/alv-steam-stats/snippets/steam-chart.php` | Chart HTML snippet for game pages |
| `assets/src/js/steam-chart.js` | Chart.js init + search autocomplete |

## Modified Files

| File | Change |
|------|--------|
| `site/plugins/alv-steam-stats/index.php` | Add routes (`/steam-stats-api/*`), site methods for DB, collector |
| `site/templates/game.php` | Include `steam-chart` snippet after screenshots if Steam data exists |
| `site/snippets/header.php` | Add autocomplete search input |
| `vite.config.js` | Add `steam-chart` entry point |
| `package.json` | Add `chart.js`, `chartjs-adapter-date-fns` dependencies |

## Error Handling

- **No Steam API key:** Collector silently skips fetch, existing data in SQLite remains accessible
- **API rate limit:** Collector logs warning, continues next cycle
- **No Steam data for game:** Chart section not rendered; search endpoint returns empty
- **SQLite errors:** Logged, page renders without chart (graceful degradation)
- **Empty player_counts:** Chart shows "No data yet" message

## Edge Cases

- **New game added to content:** Discovered by collector on next cron run, starts getting tracked
- **Game removed from content:** Data remains in SQLite (no auto-cleanup, negligible size)
- **Duplicate hourly entries:** `INSERT OR IGNORE` prevents duplicates (only one entry per appid+hour)
- **Backfill:** No initial backfill — data accumulates naturally from cron start
- **Non-Steam games:** No field in steam_games, no chart rendered, absent from search
