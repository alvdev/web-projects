# Steam Stats: Cron Pre-Warming Design

## Problem

The `/steam-stats` page makes multiple synchronous external API calls (Steam Stats HTML scrape, Steam Charts API, per-game player counts, per-game details) on every page load. Kirby's file cache helps on repeat visits, but cache misses and TTL expiry cause 2-5 second load times. The page is slow because data fetching and rendering are coupled into the same request.

## Solution

Decouple data fetching from page rendering with a cron-driven pre-warming approach. A cron job calls a dedicated warm endpoint every 30 minutes that proactively fetches all Steam data and populates the cache. The page then loads instantly from cache on virtually every request.

## Architecture

```
Cron (every 30 min)
  │
  └── POST /steam-stats-warm ──► SteamStats::getMostPlayed(100)   ──► warms: stats-most-played, game-details.*, current-players.*
                                 ├── SteamStats::getTrending(100)     ──► warms: peak-ranks, game-details.*, current-players.*, history.*
                                 ├── SteamStats::updatePlayerHistory() ──► warms: history.* for top 40 games
                                 └── setCache('warm-last-run')        ──► for optional "last updated" badge

Browser
  │
  └── GET /steam-stats ──► Kirby cache HIT (~50ms) ──► instant render
                            (cache miss: fallback to sync fetch, current behavior)
```

## Files to Change

### 1. `site/plugins/alv-steam-stats/index.php`

**Add new route** for cron warming:
```
POST /steam-stats-warm
- Optional secret key check via `?key=` or POST body
- Calls getMostPlayed(100), getTrending(100), updatePlayerHistory()
- Sets 'warm-last-run' cache timestamp
- Returns JSON { status: "ok" }
```

**Remove passive `route:before` hook** (optional). The hook that updates history every 6 hours on any page load is redundant once cron runs every 30 minutes. Removing it saves an unnecessary check on every request across the entire site.

### 2. `site/plugins/alv-steam-stats/templates/steam-stats.php`

**Add "last updated" badge** after the page subtitle. Reads `warm-last-run` cache key and displays minutes since last cron run. Uses a small inline JS timer to update the "X min ago" text every 60 seconds.

### 3. `.env.example`

Add `STEAM_STATS_WARM_KEY` for optional authentication of the warm endpoint.

### 4. Crontab (infrastructure, outside codebase)

```
*/30 * * * * curl -s -X POST https://diario.games/steam-stats-warm -d "key=YOUR_WARM_KEY" >/dev/null 2>&1
```

## Cache Strategy

| Cache Key | Current TTL | New TTL | Rationale |
|-----------|-------------|---------|-----------|
| `stats-most-played` | 3600s | 7200s (2h) | Cron runs every 30 min; double interval for safety |
| `peak-ranks` | 3600s | 7200s (2h) | Same as above |
| `current-players.*` | 900s | 3600s (1h) | Fine for display purposes; cron keeps it fresh |
| `game-details.*` | 86400s (24h) | 86400s (24h) | Unchanged — game metadata rarely changes |
| `history.*` | 604800s (7d) | 604800s (7d) | Unchanged — historical data |

## Error Handling

- If Steam APIs fail during warmup, the warm endpoint simply skips `setCache()` for the failed data. Existing cached data remains valid until its TTL expires.
- The warm endpoint never clears existing cache on failure — stale data is better than no data.
- On the rare cold start (deploy, manual cache clear), the page falls back to synchronous fetching — same behavior as today.

## What Changes for the User

- **Before:** 2-5 second page load, with occasional timeouts
- **After:** ~50ms page load on every visit. Data is never more than 30 minutes stale.

## Out of Scope

- HTMX/Turbo/streaming rendering — unnecessary complexity given cron keeps cache warm
- Loading skeletons — page loads too fast to need them with warm cache
- Infinite scroll — the page already shows top 100, which is the full Steam top list
- Frontend framework migration — vanilla JS tabs work fine
