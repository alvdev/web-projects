# Twitch Charts Design

## Overview

A new `alv-twitch-stats` Kirby plugin that adds a Twitch charts section to the homepage, in the reserved right-hand column of the charts row (`data-home-reserved` in `site/templates/home.php`), plus a full `/twitch-stats` rankings page. It mirrors the structure and UX of `alv-steam-stats`.

Live rankings come from the official Twitch Helix API (reusing the existing Twitch/IGDB credentials). 30-day aggregates come from TwitchTracker's documented basic API. Viewer-count history is collected by our own cron snapshots into SQLite, which powers sparklines and 7-day trends.

## Requirements

- **Homepage widget**, three neon tabs styled like the Steam widget:
  - **Juegos**: top 10 live Twitch categories by current viewers. Columns: rank, box art, name, viewers now, 30-day average viewers.
  - **Streamers**: top 10 live channels by current viewers. Columns: rank, avatar, streamer name, category, viewers now, 30-day average viewers.
  - **En español**: top 10 live Spanish-language channels. Same columns as Streamers.
  - Rows link to internal game pages when the Twitch category matches an imported game (`igdb_id`), using the existing `data-importing` overlay for on-the-fly IGDB import. Unmatched links stay on-site via `/games/by-igdb-id/{id}`.
  - Footer link to the full rankings page + Twitch attribution.
- **Full page `/twitch-stats`**: 100 rows per tab, same three tabs, with additional 30-day metrics (hours watched for games; max viewers/followers for streamers) and a 7-day inline SVG sparkline plus % change from self-collected history.
- **Caching**: live data 5 minutes; TwitchTracker summaries 6 hours; avatars 24 hours.
- **Warm endpoint + cron** every 5 minutes: refreshes caches and writes an hourly history snapshot.
- **No scraping.** Only documented APIs: Helix (`api.twitch.tv`) and TwitchTracker's `/api/...` endpoints. No Camoufox or proxy usage for this feature.
- Graceful degradation: a failing source only blanks its columns; it never blocks the page.
- Attribution: "Datos de Twitch" (Twitch API terms) and TwitchTracker for 30-day data.

## Data Sources

### 1. Twitch Helix (live)

Auth: OAuth client-credentials app access token from `https://id.twitch.tv/oauth2/token`, using the existing `IGDB_CLIENT_ID` / `IGDB_CLIENT_SECRET` (same Twitch application). Token cached in `storage/twitch_token.json`. Requests need `Client-Id` and `Authorization: Bearer` headers.

| Endpoint | Fields used | Notes |
|---|---|---|
| `GET /helix/games/top?first=100` | `id`, `name`, `box_art_url`, `igdb_id` | Sorted by live viewers desc |
| `GET /helix/streams?first=100` | `user_id`, `user_login`, `user_name`, `game_id`, `game_name`, `viewer_count`, `title`, `thumbnail_url` | Top live channels |
| `GET /helix/streams?first=100&language=es` | same | Spanish tab |
| `GET /helix/users?id=…` (up to 100 ids) | `profile_image_url`, `display_name` | Avatars; cached 24 h |

Rate limit is 800 points/min and a warm run costs ~4–6 points, so cadence is a non-issue. `box_art_url` uses `{width}x{height}` placeholders; we replace them.

### 2. TwitchTracker basic API (30-day aggregates)

Documented endpoints only (their site says scraping is prohibited):

| Endpoint | Response (30 days) |
|---|---|
| `GET /api/games/summary/{twitch_id}` | `avg_viewers`, `avg_channels`, `rank`, `hours_watched` |
| `GET /api/channels/summary/{channel_login}` | `rank`, `minutes_streamed`, `avg_viewers`, `max_viewers`, `hours_watched`, `followers`, `followers_total` |

Fetched only for entities actually displayed (top ~10–20 per tab per TTL), with `curl`, a descriptive User-Agent, and `null` on any non-200/malformed response. Columns render `–` when enrichment is missing.

### 3. Self-collected history

The warm cron snapshots current viewers/ranks into SQLite (`sqlite/twitch_stats.db`):
- top 100 games, top 100 streamers, top 100 Spanish streamers,
- written once per hour (while the cache refresh runs every 5 minutes),
- pruned after a configurable retention (default 90 days),
- aggregated for sparklines via hourly buckets (last 7 days) and daily buckets for longer ranges.

TwitchTracker exposes no history API, so this is the only legitimate source of time-series data.

## Plugin Structure

```
site/plugins/alv-twitch-stats/
├── index.php                        # Registration: snippets, templates, routes, siteMethods, options
├── README.md                        # Setup, cron, data sources
├── blueprints/
│   └── twitch-stats.yml             # Panel settings (TTLs, retention)
├── classes/
│   ├── TwitchClient.php             # Helix transport + OAuth token cache
│   ├── TwitchTrackerClient.php      # TwitchTracker summary transport + parse
│   ├── TwitchStatsDB.php            # SQLite: metadata + viewer snapshots
│   ├── TwitchStatsCollector.php     # Snapshot + prune
│   └── TwitchStats.php              # Ranking assembly, TTL caches, history merge
├── snippets/
│   └── twitch-stats-tabs.php        # Homepage widget
└── templates/
    └── twitch-stats.php             # Full rankings page
```

Class boundaries:
- `TwitchClient` / `TwitchTrackerClient` deal with transport and parsing only; both accept an injectable HTTP layer or are stubbed in tests.
- `TwitchStats` orchestrates: live ranking → enrichment → history merge → cache.
- `TwitchStatsDB` owns persistence and aggregation queries.
- `TwitchStatsCollector` owns the scheduled snapshot workflow.

## Database Schema

```sql
CREATE TABLE IF NOT EXISTS twitch_games (
    twitch_id   TEXT PRIMARY KEY,
    name        TEXT NOT NULL,
    igdb_id     INTEGER,
    box_art_url TEXT,
    updated_at  INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS twitch_streamers (
    user_id      TEXT PRIMARY KEY,
    login        TEXT NOT NULL,
    display_name TEXT NOT NULL,
    avatar_url   TEXT,
    language     TEXT,
    updated_at   INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS viewer_snapshots (
    entity_type TEXT NOT NULL,   -- 'game' | 'streamer' | 'streamer_es'
    entity_id   TEXT NOT NULL,
    timestamp   INTEGER NOT NULL,
    viewers     INTEGER NOT NULL,
    rank        INTEGER NOT NULL,
    game_name   TEXT,
    PRIMARY KEY (entity_type, entity_id, timestamp)
);
CREATE INDEX IF NOT EXISTS idx_vs_type_ts ON viewer_snapshots(entity_type, timestamp);
CREATE INDEX IF NOT EXISTS idx_vs_entity ON viewer_snapshots(entity_type, entity_id, timestamp);
```

## Caching Strategy

Cache namespace `alv/twitch-stats.cache` (file), configured in `site/config/config.php`.

| Key | TTL | Content |
|---|---|---|
| `top-games` | 300 s | assembled game list (live + 30-day + history) |
| `top-streamers` | 300 s | assembled streamer list |
| `top-spanish` | 300 s | assembled Spanish streamer list |
| `tracker.game.{id}` | 21 600 s | TwitchTracker game summary |
| `tracker.channel.{login}` | 21 600 s | TwitchTracker channel summary |
| `avatars` | 86 400 s | user_id → profile_image_url |
| `warm-last-run` | — | timestamp for "updated X min ago" |

TTLs come from panel/plugin options with sensible defaults; tests inject smaller values.

## Routes and Configuration

New routes registered by the plugin:

| Route | Method | Purpose |
|---|---|---|
| `twitch-stats` | GET | Renders the full rankings page |
| `twitch-stats-warm` | POST | Refreshes caches, writes hourly snapshot, prunes; requires warm key |
| `twitch-stats-api/refresh` | GET | Optional JSON refresh endpoint used by the page's "actualizado" indicator (cache-only, never triggers upstream calls) |

`site/config/config.php` additions:
- `'cache.alv/twitch-stats.cache'` file cache,
- `'alv.twitch-stats.warm-key' => env('TWITCH_STATS_WARM_KEY', env('STEAM_STATS_WARM_KEY', ''))`,
- TTL options: `cache-ttl` (300), `tracker-ttl` (21600), `history-ttl` (7776000),
- `'twitch-stats'` added to both reserved-slug lists in the catch-all routes,
- new route `games/by-igdb-id/(:num)` → fetch by IGDB id, run `GameImporter::import()`, redirect to the created slug (mirrors `games/by-appid`).

`.env.example` additions: `TWITCH_STATS_WARM_KEY=`, optional `TWITCH_STATS_DB_PATH=`.

`scripts/collect-twitch-stats.php` CLI with `snapshot` (default) and `prune` modes, mirroring `scripts/collect-steam-stats.php`.

## UI

### Homepage widget

Replaces the `data-home-reserved` div in `site/templates/home.php` with `<?php snippet('twitch-stats-tabs') ?>` and drops the `hidden lg:block` so it stacks under the Steam widget on mobile. Same card classes as `steam-stats-tabs` (`bg-surface border border-border rounded-xl p-4 flex flex-col`) and the same tab affordances/active colors (neon-cyan / neon-green / neon-magenta). Empty state: "No hay datos".

### Full page

Mirrors `templates/steam-stats.php` conventions: header with title/subtitle and "Last updated X min ago", neon tab bar, server-rendered rows (100 per tab; no infinite scroll needed since data is local), image lazy-loading, `data-importing` overlay behavior inherited from the global `initImportOverlay` script on game pages only when rendered on `/twitch-stats` (overlay JS is currently loaded by `steam-chart.js` on game pages; the widget must keep the link behavior working on the homepage — verified via e2e).

Columns:
- **Juegos**: rank, box art, name, Ahora, Media 30d, Horas vistas 30d, Twitch rank, 7d sparkline + %.
- **Streamers / En español**: rank, avatar, streamer, category, Ahora, Media 30d, Máx. 30d, Seguidores, 7d sparkline + %.

## Error Handling

- Helix failure: serve the cached ranking; if no cache, render the empty state. Log via `error_log`.
- TwitchTracker failure: enrichment columns show `–`; the list still renders.
- Token failure: same as Helix failure; token refresh happens on next warm.
- Missing credentials: plugin renders nothing on the homepage (no broken card) and logs once; `/twitch-stats` shows a configuration notice.
- All upstream calls have timeouts (10 s) and never run during a page render (only warm/CLI).

## Testing

- **PHPUnit unit** (`tests/phpunit/Unit/TwitchStats/`): parser fixtures for Helix games/streams/users and TwitchTracker summaries; DB upsert/snapshot/prune/aggregation; ranking assembly with fake clients; collector snapshot behavior.
- **PHPUnit integration** (`tests/phpunit/Integration/`): homepage right column renders the three tabs; `twitch-stats` route renders 100 rows and the tabs; reserved-slug route does not break existing pages.
- **Playwright e2e** (`tests/e2e/specs/`): homepage Twitch tabs switch content; `/twitch-stats` loads and switches tabs. External APIs are replaced through an injectable seam (site option pointing at fixture data) so e2e never calls live services.
- Verified with `composer test`, `bun run test:unit`, `bun run test:e2e`.

## Attribution and Retention

- Visible "Datos de Twitch" note linking to twitch.tv in the widget footer and page header; TwitchTracker credited for 30-day aggregates.
- History retention is bounded (default 90 days) and configurable.

## Out of Scope

- Favorites for streamers/games in the Twitch section.
- Historical backfill before the feature ships (no legitimate source exists).
- Scraping TwitchTracker or any other site.
- Twitch chat/embeds or live player.
