# SteamDB Lazy Chunked Ranking — Design

Date: 2026-09-12
Status: approved

## Goal

Replace the top-100-only "Más jugados" tab on `/steam-stats` with a full ranked
list of up to ~1,000 Steam games (100-rank chunks), sourced from SteamDB's
`/charts/` page. SteamDB data is fetched lazily, only when the tab or infinite
scroll requests a range. The home hero, trending tab, and favorites tab are not
changed.

## Background

Steam only publishes a ranked top 100, but `ISteamUserStats/GetNumberOfCurrentPlayers`
returns live counts for any appid. SteamDB polls every app and builds its own
ranking from its own database. diario.games already polls Steam for its 221
tracked games every 15 minutes (`player-update` cron) and scrapes SteamDB per
game for history.

Current freshness on `/steam-stats` today:

- Displayed player numbers: <= 15 min old (15-min `player-update` cron writes
  `player_counts`; `getMostPlayed()` overlays those values).
- Ranking membership/order: <= 1 h old (`stats-most-played` scraped cache,
  `steam_stats_cache_ttl`, default 3600).

The new ranking takes both rank and numbers from SteamDB in one scrape, so a
single per-chunk TTL governs everything.

## Decisions

- Source: SteamDB `/charts/` only. No SteamSpy, no steamcharts.com catalog.
- Fetch trigger: on demand only; no new cron jobs.
- Chunking: 100-rank chunks (0 = ranks 1–100, 1 = 101–200, ...).
- TTL: 900 s per chunk, configurable via `alv.steam-stats.charts-ttl`.
- One scrape extracts the whole table client-side in a single page load, so a
  single run refreshes every chunk. The per-chunk TTL still decides when a
  scrape is triggered: a chunk is only re-scraped when someone requests it
  after expiry.
- Refresh is asynchronous and never blocks a request. Cached rows are served
  immediately; the response carries `refresh_pending`; the client polls.
- History: snapshot into `player_counts` only for chunks 0–2 (ranks 1–300),
  using the same hour-slot upsert as `SteamStatsCollector::collect()` so
  history stays hourly and grows at most 300 rows/hour.
- UI: stale numbers stay visible (dimmed) with a small inline spinner beside
  them; skeleton rows with spinner placeholders when a chunk has no data.
- Untouched: `site/snippets/hero.php`, `steam-stats-tabs.php`, trending and
  favorites tabs, existing cron entries, per-game history scripts.

## Architecture

```
Browser (/steam-stats)
  |  initial render: chunk 0 from SQLite (fallback: getMostPlayed)
  |  infinite scroll: GET steam-stats-api/rankings?chunk=N
  v
Kirby route (plugin index.php)
  |  fresh chunk (<= TTL): return rows, refresh_pending=false
  |  stale/missing: return rows + refresh_pending=true
  |                 spawn `nohup php collect-steam-stats.php charts N &` (locked)
  v
CLI worker (collect-steam-stats.php charts N)
  |  per-chunk lock + global browser lock
  v
scripts/scrape-steamdb-charts.mjs (Camoufox + proxy + CF handling)
  |  JSON rows: rank, appid, name, current, peak_24h, peak_all_time
  v
SQLite: chart_entries, chart_chunks, game_peaks, player_counts (chunks 0-2)
```

Client polling: while `refresh_pending`, the page re-requests the same chunk
every ~3–5 s (up to ~60 s). Responses are cached ~60 s.

## Data model (SQLite `steam_stats.db`)

```sql
CREATE TABLE chart_chunks (
    chunk       INTEGER PRIMARY KEY,
    fetched_at  INTEGER NOT NULL DEFAULT 0,
    status      TEXT NOT NULL DEFAULT 'missing', -- missing|loading|fresh|error
    entry_count INTEGER NOT NULL DEFAULT 0,
    last_error  TEXT
);

CREATE TABLE chart_entries (
    appid         INTEGER PRIMARY KEY,
    chunk         INTEGER NOT NULL,
    rank          INTEGER NOT NULL,
    name          TEXT NOT NULL,
    current_players INTEGER NOT NULL DEFAULT 0,
    peak_24h      INTEGER NOT NULL DEFAULT 0,
    peak_all_time INTEGER NOT NULL DEFAULT 0,
    peak_all_time_ts INTEGER NOT NULL DEFAULT 0,
    scraped_at    INTEGER NOT NULL
);
CREATE INDEX ... ON chart_entries(chunk, rank);
```

`chart_entries` is keyed by appid: a later chunk fetch updates the row's
chunk/rank if a game drifts across a boundary, so each app appears once and the
list is served `ORDER BY rank`.

## Components

### 1. Phase 0 discovery — `scripts/inspect-steamdb-charts.mjs`

Read-only tool modeled on `inspect-steamdb.mjs` / `fetch-steamdb-peak.mjs`
(Camoufox, proxy from `.env`, CF challenge detection). Findings from the
discovery runs:

- The charts table (`#table-apps`) is DataTables 2.3.8 running **client-side**
  (`serverSide: false`, no AJAX endpoint). jQuery is exposed as `window.$`.
- The entire dataset (1,848 global entries at discovery time, more on category
  pages) is already in JS memory; `?page=N` is ignored and clicking "next" or
  changing page length performs no network request.
- Anonymous users see a filtered table ("games with enough players"); the global
  chart is enough for the top 1,000.
- Extraction uses `$('#table-apps').DataTable().rows().data()`, which returns
  every row regardless of the rendered page.

If the page ever moves to server-side pagination, the scraper falls back to
fetching the full list and slicing locally; chunks still cache per TTL.

### 2. Scraper — `scripts/scrape-steamdb-charts.mjs [limit]`

Same stack and structure as `scrape-steamdb-history.mjs`: Camoufox via
`camoufox-js` + `playwright-core`, `.env` proxy (`PROXY_HOST/PORT/USER/PASS`),
humanize, CF detection (`cf-mitigated`, title, DOM), retries with fresh proxy
IP, images/CSS/fonts blocked. It loads `https://steamdb.info/charts/`, waits for
DataTables, and reads the first `limit` rows (default 1000) through the
DataTables API. Prints JSON `{ total, rows }` to stdout; logs to stderr.
CF challenge headers on the initial document are tolerated as long as the table
eventually renders (challenge solving reloads the page).

### 3. CLI worker — `charts <chunk>` mode in `collect-steam-stats.php`

1. Exit if the global browser lock (`/tmp/steamdb-charts-browser.lock`) exists
   and is younger than 5 minutes; stale locks are removed.
2. Create the lock and run the node scraper through `findNodeBinary()` with
   `limit = max(1000, (chunk + 1) * 100)`.
3. `replaceChartEntries()` the full snapshot in one transaction (the scrape is
   a complete snapshot, so old rows are replaced), then
   `markChartChunksFresh()` marks every chunk covered by the snapshot.
4. Upsert `game_peaks` with `peak_all_time` for each row.
5. For chunks 0–2 only (rank <= 300): `insertPlayerCount(appid, hourSlot, current)`
   using the same hour-slot key as `collect()`, so repeated refreshes within an
   hour overwrite one row per app.
6. Clear the `player-data-summary` cache. On failure: `markChartChunkError()`
   on the requested chunk, keep previous rows, remove the lock.

### 4. Trigger route — `steam-stats-api/rankings`

`GET steam-stats-api/rankings?chunk=N` in
`site/plugins/alv-steam-stats/index.php`:

- Returns `{ rows, chunk, status, fresh, fetched_at, ttl, refresh_pending }`.
- Fresh chunk (age <= `charts-ttl`, default 900): `refresh_pending: false`.
- Stale/missing, in range, no lock, spawn cooldown (60 s cache) elapsed: spawn
  `nohup php scripts/collect-steam-stats.php charts N > /dev/null 2>&1 &` and
  return `refresh_pending: true` with whatever rows exist.
- Out-of-range chunks (beyond the highest stored rank) never spawn and return
  empty rows.
- While a scrape is running (lock present), requests return
  `refresh_pending: true` without spawning duplicates.

### 5. Template — `site/plugins/alv-steam-stats/templates/steam-stats.php`

- Chunk 0 server-rendered from `chart_entries`; if empty, fall back to the
  existing `getMostPlayed()` top-100 while the first scrape runs.
- Infinite scroll requests chunks via the API instead of slicing an embedded
  top-100 JSON; remove the `renderedCount[tabId] >= 100` cap.
- Spinner behavior:
  - chunk refresh in flight: cached numbers stay, `opacity-60`, small inline
    `animate-spin` ring next to each count cell; numbers swap in place,
  - chunk with no data: skeleton rows with spinner placeholders in count cells,
  - fetch in flight: sentinel shows a spinner until rows append,
  - failure/timeout: spinner removed, last known numbers (or "—") remain,
    retry on next scroll.
- Capsules from the Steam CDN URL pattern (`.../apps/{appid}/capsule_231x87.jpg`),
  no appdetails calls. Slugs joined from `steam_games`; unknown apps link to
  `/games/by-appid/{appid}` as today.
- Trending/favorites logic and the `steam-slug-map` flow are preserved.

## Error handling

- Cloudflare challenge or scrape failure: previous `chart_entries` stay served;
  chunk marked `error`; next request after TTL retries (locked to one process).
- Stale lock files older than ~5 minutes are considered dead and removed.
- `refresh_pending` responses never block; the client stops polling after ~60 s
  and re-tries on the next scroll/tab interaction.

## Crawler / IGDB quota protection

Ranking rows link to `/games/by-appid/{appid}` (or a slug without a content
page), and those routes perform on-the-fly IGDB lookups/imports on first hit.
To keep crawlers from burning the IGDB quota:

- Links that trigger an import carry `rel="nofollow"` alongside
  `data-importing`, in the `/steam-stats` template (server-rendered and
  JS-rendered rows) and in the hero snippet. Imported games keep plain
  crawlable `/slug` links.
- `robots.txt` disallows `/games/by-appid/` via
  `tearoom1.meta-kit.robots.rules` in `site/config/config.php`. The meta-kit
  generator duplicates `customDirectives` when set from config, so the `rules`
  mechanism is used instead.

## Verification

- Phase 0 inspect output reviewed before wiring.
- `node --check` on scripts; `php -l` on changed PHP files.
- `php collect-steam-stats.php charts 0` and `charts 1` -> 100 rows each,
  `chart_chunks` fresh, `game_peaks` updated, `player_counts` written only for
  chunks 0–2.
- `curl` route: fresh -> no spawn; stale -> exactly one spawn, second call
  within cooldown -> no new process.
- Homepage HTML/requests unchanged (no ranking/SteamDB calls).
- Browser test: scroll past rank 100, spinner placeholders appear, numbers swap
  in; skeleton rows on cold chunk; no blocking.

## Risks

- SteamDB pagination shape unknown until Phase 0; fallback defined.
- Cloudflare behavior is handled by the existing Camoufox/proxy stack but can
  still fail; failure degrades to stale data only.
- Cold chunk load shows skeleton rows for ~15–45 s (browser launch + CF solve).
