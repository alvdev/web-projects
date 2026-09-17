# Regression Test Suite — Design

Date: 2026-09-17
Status: proposed

## Goal

Add a regression test suite that locks in the currently working behavior of
diario.games before further changes. Coverage targets, in priority order:

1. IGDB import pipeline
2. Prices & affiliate banners
3. Steam stats & charts
4. JS interactions
5. CLI scripts & SteamDB scrapers

The suite must be fast, deterministic and hermetic: no live external APIs, no
writes to production data (`content/`, `sqlite/`, `storage/`, `site/cache`,
`media/`), no required API keys. It runs locally only; scripts are structured so
CI can be added later without rework.

## Background

- Kirby 5 CMS (PHP 8.4.25) + Vite/Tailwind. ~122 games stored sharded under
  `content/games/<year>/<month>/<slug>/`.
- Custom plugins: `alv-igdb` (import pipeline), `alv-prices` (price adapters +
  store DB), `alv-steam-stats` (collector, DB, charts, routes), `alv-ai`
  (OpenRouter/OpenCode client), `alv-aff-banners` (placement/render).
- One SQLite file, `sqlite/steam_stats.db` (~13 MB, untracked), is shared by
  `Alv\SteamStats\SteamStatsDB` and `Alv\Prices\StorePriceDB`.
- No test framework, no `tests/`, no CI. `playwright`/`puppeteer` exist only as
  scraper dependencies.
- External dependencies: IGDB + Twitch OAuth, Steam store/web APIs,
  steamcharts.com, SteamDB (browser scrapers via Camoufox), ITAD, G2A,
  InstantGaming (Algolia), OpenRouter/OpenCode, jsDelivr/thesvg.org.
- Testability blockers found during the audit:
  - DB path hard-coded in `SteamStatsDB.php:9-22` and `StorePriceDB.php:9-24`.
  - Content/storage paths hard-coded via `dirname(__DIR__, 4)` in
    `GameImporter.php:84`, `IGDBClient.php:18`, `SteamStatsCollector.php:18`.
  - Raw `curl`/`file_get_contents` throughout; network not injectable.
  - Parsing logic embedded inside I/O methods and mostly `private`.
  - CLI scripts and `.mjs` scrapers are top-level scripts with no exports or
    path/env overrides.
  - Banner placement state lives in `$GLOBALS` (`affiliate-banner.php:14-17`).
  - `AIClient` is fully static with hard-coded URLs.

## Decisions (approved)

- **Tooling**: PHPUnit 11 (composer dev dependency), Vitest + jsdom (JS units),
  `@playwright/test` pinned to the existing `playwright` version (1.63.0) for
  E2E.
- **External services**: recorded fixtures only. No live calls in the suite.
- **Isolation**: every DB access resolves `STEAM_STATS_DB_PATH` first; Kirby
  cache/media roots redirected to `tests/.tmp`; fixture content for integration
  tests; E2E uses a local upstream mock server.
- **Seams**: production changes are additive with identical defaults. No
  behavior changes. `private` may widen to `protected` where test doubles need
  overriding.
- **Local only**: no CI, no coverage gates. `composer test`, `npm run test:unit`,
  `npm run test:e2e` are the entry points; CI-ready by construction.
- **No browser automation for scrapers**: SteamDB `.mjs` scripts are covered by
  extracted pure parsers + fixtures, not by launching Camoufox.

## Hard guarantees

- Tests must never open or write `sqlite/steam_stats.db`, real `content/`,
  `storage/`, `media/` or `site/cache`.
- The `_db()` lazy singleton in `helpers.php:175` must be resettable between
  tests.
- Tests must pass with no `.env` and no API keys; the bootstrap clears/sets the
  relevant env vars explicitly.
- E2E aborts external image/CDN requests so runs are hermetic.

## Suite layout

```
phpunit.xml
vitest.config.js
playwright.config.js
tests/
  bootstrap.php              # composer autoload, TZ=UTC, env defaults, class requires
  Support/                   # temp dir/DB helpers, fake IGDB client, fake HTTP traits
  fixtures/
    igdb/  prices/  steam/  steamdb/     # recorded JSON/HTML responses
    content/                 # minimal Kirby content tree (site, home, games, posts…)
  phpunit/
    Unit/                    # pure logic (helpers, parsers, slug/normalize)
    Integration/             # temp-DB + booted-Kirby tests
    Cli/                     # subprocess tests for scripts/*.php
  js/                        # Vitest specs (timezones, parsers, share/steam utils)
  e2e/
    specs/                   # Playwright specs
    upstream.php             # local fixture server for mocked upstream APIs
    seed-db.php              # builds fixture SQLite via production createTables()
    app-server.sh            # temp DB copy + env + php -S 127.0.0.1:8899
```

`.gitignore` gains `tests/.tmp/`.

## Production seams (additive, defaults unchanged)

1. **DB path injection + env**
   - `SteamStatsDB::__construct(?string $dbPath = null)` and
     `StorePriceDB::__construct(?string $dbPath = null)`, resolving
     `getenv('STEAM_STATS_DB_PATH') ?: <current hard-coded path>`.
   - Because both classes share one file, both must honor the same override.
   - Add a static reset for `DiarioGames\IGDB\_db()` (e.g.
     `_db_reset()` / `set_db()`), used by tests only.
2. **Path injection**
   - `GameImporter::__construct(IGDBClient $client, ?string $gamesDir = null)`.
   - `IGDBClient::__construct(string $clientId, string $clientSecret, ?string $tokenPath = null)`.
   - `SteamStatsCollector::collect(?string $gamesDir = null)`, plus
     `STEAM_STATS_CONTENT_DIR` env fallback for CLI/E2E.
3. **Fake-ability (visibility `private` → `protected`, no logic change)**
   - `GameImporter`: `registerSteamGame`, `verifySteamAppId`,
     `fetchSteamCurrentPlayers`, `resolveGenresAndTags`, `resolvePlatformNames`,
     `resolveInvolvedCompanies`, `importMissingMedia`, `downloadCover`,
     `downloadHero`; add protected wrappers around global calls
     (`translate()`, `downloadImage()`, `fetchThesvgIcon()`, `_db()`) so
     subclasses can stub them.
   - `IGDBClient`: `throttle`, `loadCachedToken`, `cacheToken`.
   - `SteamStats`: `fetchGameListFromStats`, `fetchMostPlayedFromSteam`,
     `fetchCurrentPlayers`, `fetchGameDetails`, `getCached`/`setCache`.
   - `SteamStatsCollector`: `batchFetchPeaks`, `fetchCurrentPlayers`, node
     runner (`collectSteamDBPeak`/`History`/`Charts` via one `runNodeScript`
     seam).
   - `PriceFetcher`: `formatResults`, `anyExpired`, `adapterStoreAllCached`.
4. **Pure parser extraction (fixture-testable, behavior identical)**
   - `ItadAdapter::parseDeals(array $data): array` (from `fetchPrices`),
     `appendAffiliate`, `buildFallbackUrl`, `resolveStoreUrl` promoted for tests.
   - `G2AAdapter::parseOffers(array $data): ?array`, `slugify` promoted.
   - `InstantGamingAdapter::pickBestHit` promoted to public.
   - `SteamStats::parseStatsHtml(string $html): array` (comma/entity handling),
     `parseMostPlayedJson(array $data): array`.
   - `SteamStatsCollector::parseGameTxt(string $txt): array`,
     `mapSteamchartsPoints(array $json): array`,
     `mapSteamDbHistory(array $payload, ?int $domPeak): array`.
   - `AIClient::buildMessages(string $kind, string $text): array`,
     `parseCompletion(array $json): ?string`, transport behind
     `protected static function httpPost()`.
   - `StorePriceDB::isExpired(int $scrapedAt, int $ttl = 86400, ?int $now = null)`
     for deterministic TTL tests (default `null` keeps `time()`).
5. **Endpoint env overrides** (defaults unchanged)
   - `STORE_STEAM_BASE`, `STEAM_API_BASE`, `STEAMCHARTS_BASE_URL` used by
     `SteamStats`/`SteamStatsCollector`/`GameImporter`; consumed only when set.
6. **JS**
   - New `scripts/lib/steamdb-parsers.mjs` exporting `loadEnv`, `buildProxyUrl`,
     `mergeGraphPoints`, `computePeak`, `parseDataTableRows`, `parseDomPeak`;
     the six `.mjs` scripts import them (removes 4x duplication).
   - `assets/src/js/steam-chart.js`: export pure helpers (`formatNumber`, and
     favorites merge/removal if extracted); DOM bootstrap unchanged.
   - `timezones.js` unchanged (already exports pure functions).
7. **Affiliate banners**
   - Extract config parsing (`frequency` pipe/numeric/`sm:N md:N xl:N` forms)
     and placement matching into a pure `AffiliateBanners` class used by
     `alv-aff-banners/index.php:14-67` and `affiliate-banner.php:37-58`.
   - Snippet keeps rendering and `$GLOBALS` registry, with an explicit reset for
     tests.
8. **CLI scripts**
   - Honor `STEAM_STATS_DB_PATH` and `STEAM_STATS_CONTENT_DIR`.
   - Add a `STEAM_STATS_SKIP_EXTRAS` env / `--no-extras` flag to skip the
     fall-through warm/capsule/peaks blocks in `collect-steam-stats.php`
     (defaults preserved).
9. **E2E cache isolation**
   - `site/config/config.127.0.0.1.php` test-host config redirects the Kirby
     cache root to `tests/.tmp/cache`. If host matching proves unreliable, fall
     back to a `KIRBY_CACHE_ROOT` env check in `site/config/config.php`.

## Coverage map

### IGDB import pipeline

- Pure: `slugify`, `romanToDigits`, `deriveYearMonth`, `normalizePlatformNames`,
  `platformCategory`, `igdbImageUrl`, `GameImporter::isExcluded`.
- Integration: `GameImporter::import()` with a fake `IGDBClient` overriding
  `post()`, temp `gamesDir`, temp DB via env — asserts `game.txt` fields,
  sharded path, media downloads (against a local file:// or mock), Steam
  registration, index row; `importBySlugWithFallback` search fallback paths.
- `AutoFetcher::run` with fake client/importer.
- Page smoke (PHPUnit integration): boot Kirby with real content (read-only),
  temp DB via `STEAM_STATS_DB_PATH` and temp cache root; render `/`, one game
  URL, `/genre/<x>`, `/search?q=…`, `/steam-stats` and assert 200 + a marker.
  Run before/after each seam phase as the regression net for the refactors.

### Prices & affiliate banners

- Adapter parsers vs recorded fixtures: ITAD lookup + deals, G2A offers,
  InstantGaming Algolia hits/`pickBestHit`, affiliate URL building/tracking
  stripping.
- `StorePriceDB` CRUD, `isExpired` (with injected `$now`), `findG2aProductId`
  matching on temp DB.
- `PriceFetcher::fetch` orchestration with fake adapters + temp DB (appid
  resolution reads the temp DB via `STEAM_STATS_DB_PATH`; no injection needed).
- Banner config parse (all three frequency forms, enabled handling, position
  clamping) and placement matching (sm→md→xl precedence, dedupe); Kirby
  integration render with fixture content.

### Steam stats & charts

- `SteamStatsDB` on temp DB: `normalizeSlug`; all aggregations
  (`getDaily/Weekly/MonthlyPeakCounts`, `getWeeklyAverages`, `getAllPlayerData`);
  `upsertGame` duplicate-slug branches; `upsertGamePeak` monotonicity;
  `replaceChartEntries` chunk math/invalid-row skipping; chart chunk round-trips.
- Collector: `collect()` with fixture `game.txt` + fake HTTP + temp DB;
  `fetchCurrentPlayers` 404-with-JSON-body semantics; steamcharts point mapping;
  SteamDB history payload variants + DOM peak priority.
- `SteamStats`: HTML/JSON fixture parsing; `getTrending` growth math incl.
  `is_new`/`minPlayers`; `updatePlayerHistory` trim/cache/DB writes;
  `getCached` TTL wrapper.
- Route integration with booted Kirby + temp DB + faked `exec`:
  `steam-stats-api/rankings` freshness matrix (fresh/stale/lock/cooldown),
  search merge/dedupe/limit ordering, `game/(:any)/data` and `steamChartData`
  range builder (adaptive max, drop-last-incomplete).
- E2E: game-page chart + `/steam-stats` interactions (below).

### JS interactions

- Vitest: `timezones.js` (`findCityMatch`/`findCountryMatch` precedence,
  `normalize` accents, `isValidTimezone`, `getUtcOffset`); extracted mjs parsers;
  share formatting; steam-chart response-shape handling with mocked `fetch`
  (search debounce/AbortController, IGDB follow-up, import overlay state
  machine).
- Playwright: game-page chart (range tabs, timezone picker, share button),
  favorite add/reload persistence via localStorage, header search fast + IGDB
  results, import overlay flow (mock `/steam-stats-api/import-game` and
  `import-progress`), `/steam-stats` tab switching + infinite scroll with
  `/steam-stats-api/rankings` mocked.

### CLI scripts & scrapers

- PHP subprocess tests: argv validation, exit codes, lock handling, and
  `collect`/`backfill`/`charts` modes against temp DB/content + local upstream
  mock (`STEAMCHARTS_BASE_URL`, `STEAM_API_BASE`, `STEAM_STATS_SKIP_EXTRAS`).
- `.mjs`: extracted parser functions unit-tested with saved fixtures
  (DataTables row arrays, graph merge/highstock override, peak computation).
  Scripts themselves are not launched in tests.

## E2E environment

- `tests/e2e/seed-db.php` builds `tests/.tmp/e2e/steam_stats.db` by
  instantiating `SteamStatsDB` against the fixture path (schema comes from the
  production `createTables()`), seeding games/chart chunks/player history with
  timestamps relative to now; chart chunks marked fresh.
- `tests/e2e/app-server.sh`: copies fixture DB, exports `STEAM_STATS_DB_PATH`,
  `STORE_STEAM_BASE`/`STEAM_API_BASE`/`STEAMCHARTS_BASE_URL` pointing at the
  upstream mock, empty IGDB credentials, then
  `php -S 127.0.0.1:8899 kirby/router.php`.
- `tests/e2e/upstream.php` serves recorded fixtures for Steam store/web and
  steamcharts paths on `127.0.0.1:8898`.
- `playwright.config.js` starts both servers (`webServer` array), testDir
  `tests/e2e/specs`, context `timezoneId` pinned. `npm run test:e2e` runs
  `npm run build` first so Vite manifest assets are used.
- One-time setup: `npx playwright install chromium`.

## Commands

| Command | Runs |
|---|---|
| `composer test` | PHPUnit (all suites) |
| `composer test:unit` | PHPUnit unit suite only |
| `npm run test:unit` | Vitest |
| `npm run test:e2e` | build + Playwright |
| `npm test` | fast suite (PHPUnit + Vitest) |
| `npm run test:all` | fast suite + E2E |

## Phasing

1. **Infra + pure tests (only seam 1)**: tooling, configs, bootstrap, fixture
   helpers, `tests/js` timezones, `SteamStatsDB`/`StorePriceDB` temp-DB tests
   (seam 1, the DB-path override, lands here), IGDB pure helpers.
2. **Seams + tests**: IGDB import integration, prices adapters/DB/fetcher,
   steam collectors/aggregations, AIClient parsers — each seam with tests and a
   page smoke check.
3. **Kirby integration**: routes, site methods, snippets, banners.
4. **CLI + `.mjs` parser extraction/tests.**
5. **E2E infra + specs.**

Each phase ends green and independently useful. Phases 1 and 2 deliver the
highest regression value per effort.

## Verification

- `php -l` on every changed PHP file; `node --check` on changed `.mjs`.
- Full fast suite green; E2E green.
- After each seam phase, manual smoke against the dev server (`php -S`):
  `/`, `/steam-stats`, one game page, `/genre/<genre>`, `/search?q=zelda`
  return 200 with expected markers.
- `git diff` review confirming every seam default is byte-identical to the
  previous behavior.
- Confirm no test run modifies `sqlite/steam_stats.db` (mtime/hash check in
  verification).

## Risks

- **Seam refactors touch working code.** Mitigated by additive defaults,
  `protected` only where needed, page smoke assertions, and manual smoke.
- **Env override leaking into production.** Only honored when the env var is
  set; defaults remain hard-coded paths.
- **E2E upstream mocking complexity.** Phased; specs may start with
  browser-level `page.route` mocks and add the upstream server incrementally.
- **Fixture schema drift.** Fixture DB is created via production
  `createTables()`, so drift is impossible by construction.
- **Time-dependent stats.** Fixtures use timestamps relative to now, tests pin
  `date_default_timezone_set('UTC')` and assert ranges rather than absolute
  values.
- **Playwright browser download (~150 MB).** One-time; documented in the plan.
- **`$GLOBALS` banner state across tests.** Explicit reset in bootstrap/teardown.

## Non-goals

- No CI workflow, no coverage thresholds.
- No golden-master HTML snapshots (only the small page smoke assertions).
- No live API "contract" tests (decided against; can be added later behind env
  flags).
- No browser-driven tests for SteamDB scrapers.
- No refactoring beyond the seams listed above.
