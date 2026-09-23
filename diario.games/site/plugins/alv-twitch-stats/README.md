# alv-twitch-stats

A Kirby CMS plugin that adds Twitch charts: a tabbed homepage widget (Juegos / Streamers / En español) and a full `/twitch-stats` rankings page.

## Features

- **Juegos**: top live Twitch categories with box art, live viewers (aggregated from the top 100 live streams) and 30-day average viewers
- **Streamers**: top live channels with avatars, category, live viewers and 30-day averages
- **En español**: top live Spanish-language channels
- **Full page** `/twitch-stats`: 100 rows per tab with 30-day hours/max viewers/followers, Twitch rank, 7-day sparkline and % change
- Games link to internal pages via `igdb_id`; unmatched categories route through `/games/by-igdb-id/{id}` for on-the-fly IGDB import
- Server-side caching and self-collected history in SQLite

## Requirements

- PHP 8.1+
- Existing Twitch application credentials, already configured for IGDB:
  - `IGDB_CLIENT_ID`
  - `IGDB_CLIENT_SECRET`
- [kirby3-dotenv](https://github.com/bnomei/kirby3-dotenv) plugin (for `.env` support)

No separate Twitch API key is needed: the IGDB application credentials are Twitch credentials.

## Data sources

- **Twitch Helix API** (`api.twitch.tv`): live top games (`/helix/games/top`), live streams (`/helix/streams`, including `language=es`) and avatars (`/helix/users`). App access token is cached in `storage/twitch_token.json`.
- **TwitchTracker basic API** (documented, no scraping): 30-day summaries per game (`/api/games/summary/{id}`) and per channel (`/api/channels/summary/{login}`).
- **Self-collected history**: the warm cron snapshots viewer counts into `sqlite/twitch_stats.db` once per hour, which powers sparklines and 7-day change. TwitchTracker offers no history API, so this is the only time-series source.

## Cron (required for fresh data)

Live caches expire after 5 minutes; history snapshots are written once per hour. Set up a cron every 5 minutes:

```
*/5 * * * * curl -s -X POST https://yoursite.com/twitch-stats-warm -d "key=YOUR_WARM_KEY" >/dev/null 2>&1
```

The warm key is `TWITCH_STATS_WARM_KEY`, falling back to `STEAM_STATS_WARM_KEY`.

Alternatively use the CLI:

```
*/5 * * * * cd /path/to/project && php scripts/collect-twitch-stats.php >/dev/null 2>&1
```

## CLI

```bash
php scripts/collect-twitch-stats.php            # snapshot (hourly guard)
php scripts/collect-twitch-stats.php snapshot   # explicit snapshot
php scripts/collect-twitch-stats.php prune      # remove snapshots older than retention
```

## Configuration

Options (config/config.php or `.env`):

| Option | Env | Default | Description |
|-------|-----|---------|-------------|
| `alv.twitch-stats.cache-ttl` | `TWITCH_STATS_CACHE_TTL` | 300 | Live ranking cache (seconds) |
| `alv.twitch-stats.tracker-ttl` | `TWITCH_STATS_TRACKER_TTL` | 21600 | TwitchTracker summary cache (seconds) |
| `alv.twitch-stats.history-ttl` | `TWITCH_STATS_HISTORY_TTL` | 7776000 | Snapshot retention (seconds, 90 days) |
| `alv.twitch-stats.warm-key` | `TWITCH_STATS_WARM_KEY` | `STEAM_STATS_WARM_KEY` | Warm endpoint key |
| `alv.twitch-stats.fixture-file` | `TWITCH_STATS_FIXTURE` | — | JSON fixture for tests/offline development |

Panel fields (`twitch_stats_cache_ttl`, `twitch_stats_tracker_ttl`, `twitch_stats_history_ttl`) override the option values when set on the site content.

## Testing

The plugin is covered by PHPUnit unit/integration tests and Playwright e2e tests. E2E uses `TWITCH_STATS_FIXTURE` so it never calls live APIs. For local development without credentials, point `TWITCH_STATS_FIXTURE` at a JSON file with the shape:

```json
{
  "games": [{"id": "509658", "name": "Just Chatting", "box_art_url": "https://.../{width}x{height}.jpg", "igdb_id": 509658}],
  "streamers": [{"user_id": "u1", "user_login": "ibai", "user_name": "Ibai", "game_id": "509658", "game_name": "Just Chatting", "viewer_count": 100}],
  "spanish": [],
  "tracker_games": {"509658": {"avg_viewers": 120, "avg_channels": 10, "rank": 2, "hours_watched": 5000}},
  "tracker_channels": {"ibai": {"avg_viewers": 90, "max_viewers": 200, "followers_total": 17000000, "rank": 25}},
  "avatars": {"u1": "https://.../avatar.jpg"}
}
```

## Attribution

Twitch data is shown with a "Datos de Twitch" credit; 30-day aggregates are credited to TwitchTracker. History retention is bounded (default 90 days).
