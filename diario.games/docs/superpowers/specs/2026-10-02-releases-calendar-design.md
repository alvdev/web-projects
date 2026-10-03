# Releases Calendar Design

## Overview

A new `alv-releases` Kirby plugin that replaces the homepage's dummy genre grid with a three-column releases module and adds a full **`/lanzamientos`** page. Data comes from the IGDB API (same Twitch/IGDB credentials already in use), cached for 6 hours and warmed by cron. No new game pages are created for unreleased titles.

The three lists mirror IGDB's own sections:

| Column | Definition |
|---|---|
| **Recién lanzados** | Released in the last 30 days, sorted by `hypes desc` |
| **Próximos lanzamientos** | Future releases sorted by date asc, only notable titles (see below) |
| **Más esperados** | Future releases with `hypes > 0`, sorted by `hypes desc` |

Genre browsing lives on **`/games`** as client-side filter chips over the A–Z grid. `/lanzamientos` uses the same chip pattern to filter the three lists by genre.

Audience: Spanish-speaking PC/console players. All UI copy follows the project's Spanish game-writing tone.

## Requirements

- **Homepage module** (three columns, one per list):
  - Up to 8 games per column: cover, title, display date, platform abbreviations; "Más esperados" also shows the follower count.
  - CTA "Ver calendario completo →" linking to `/lanzamientos`.
  - Renders nothing when all three datasets are empty; never breaks the homepage.
  - One affiliate banner slot after the module.
- **`/lanzamientos` page**:
  - H1 "Próximos lanzamientos de videojuegos {year}" plus a short intro paragraph targeting "lanzamientos {year}".
  - Three sections (**Recién lanzados** 30 / **Próximos lanzamientos** 60 / **Más esperados** 30), same definitions as the homepage.
  - Genre filter chips (like `/games`: counts, single-select, "Todos" first, client-side) filtering rows across all sections; sections with zero visible rows auto-hide and an empty-state message appears when nothing matches.
  - Released games link to their internal page or `/games/by-igdb-id/{id}`; upcoming/anticipated rows are unlinked.
  - Affiliate banners inserted after each section.
  - JSON-LD `ItemList` of `VideoGame` entries with a precise release date.
  - Meta title/description via existing meta-kit.
  - Required attribution "Datos de IGDB".
- **`/games`**: Spanish H1/meta and genre filter chips (already implemented; unchanged by this feature).
- **Header**: "Lanzamientos" link near the existing Steam/Twitch links.
- **Caching**: one dataset cached 6 h; warm route + CLI script; documented cron entry.
- **Link policy**: unreleased games are not linked anywhere. Released games link internally.
- **Graceful degradation**: IGDB failure or empty cache hides the module / shows an empty state; errors are logged, never fatal.

## Data Sources

Existing `DiarioGames\IGDB\IGDBClient` (token caching + throttling already implemented).

### Queries

Three cached queries against `games` with `fields name,slug,first_release_date,hypes,cover.image_id,platforms.abbreviation,genres.name,category,version_parent,release_dates.human,release_dates.date,release_dates.platform`.

**Critical IGDB detail:** IGDB stores `category` as `null` for main games, so filtering `category = 0` matches nothing (verified against the live API). All queries therefore use `(category = 0 | category = null)`.

| Dataset | `where` | `sort` | limit |
|---|---|---|---|
| recentlyReleased | `first_release_date > {now - 30d} & first_release_date <= {now} & version_parent = null & (category = 0 \| category = null) & platforms = ({allowedIds})` | `hypes desc` | 100 |
| upcoming | `first_release_date > {now} & hypes >= {notableHypes} & version_parent = null & (category = 0 \| category = null) & platforms = ({allowedIds})` | `first_release_date asc` | 200 |
| anticipated | `first_release_date > {now} & hypes > 0 & version_parent = null & (category = 0 \| category = null) & platforms = ({allowedIds})` | `hypes desc` | 50 |

- **Notable upcoming rule**: the `hypes >= {notableHypes}` threshold (default 20) is applied in the IGDB query so that date ordering surfaces known titles instead of obscure same-day releases; `getNotableUpcoming()` re-applies the threshold (fixtures bypass queries) and removes any `igdb_id` present in the `anticipated` dataset so the columns never duplicate.
- `allowedIds`: same platform keyword whitelist as `AutoFetcher` (`pc`, `xbox`, `playstation`, `nintendo`, `android`), shared via the `DiarioGames\IGDB\allowedPlatformIds(IGDBClient $client): array` helper in `site/plugins/alv-igdb/classes/helpers.php`.
- Exclude results where `GameImporter::isExcluded($game)` is true.
- Cover URL via existing `igdbImageUrl($imageId, 'cover_big')`.

### Date precision

`first_release_date` is used for sorting and windows. The displayed date prefers the first `release_dates.human` entry:

- Exact date known → `15 de octubre de 2026`.
- Only human estimate (`Q1 2026`, `2026`) → show the `human` string as-is.
- No usable date → entry excluded.

## Plugin Structure

```
site/plugins/alv-releases/
├── index.php                 # plugin registration
├── classes/Releases.php      # fetch + normalize + cache + warm
├── templates/lanzamientos.php
├── snippets/home-upcoming-releases.php
└── README.md                 # cron / options docs
```

### `index.php`

- `snippets`: `home-upcoming-releases`
- `templates`: `lanzamientos`
- `options`:
  - `alv.releases.cache-ttl` (default `21600`)
  - `alv.releases.warm-key` (default `env('RELEASES_WARM_KEY', env('STEAM_STATS_WARM_KEY', ''))`)
  - `alv.releases.notable-hypes` (default `20`)
  - `alv.releases.fixture-file` (default `env('RELEASES_FIXTURE', '')`)
- `routes`:
  - `GET lanzamientos` → virtual page render with title + meta description
  - `POST lanzamientos-warm` → key check identical to `steam-stats-warm`, calls `site()->releases()->warm()`
- `siteMethods`: `releases()` returns `new \Alv\Releases\Releases([...])`

### `classes/Releases.php`

Public API:

- `getRecentlyReleased(int $limit = 8): array`
- `getNotableUpcoming(int $limit = 8): array`
- `getAnticipated(int $limit = 8): array`
- `warm(): array` — forces a refresh, returns counts for the three datasets

Normalized entry shape:

```php
[
  'igdb_id'      => 1234,
  'slug'         => 'game-slug',
  'name'         => 'Game Name',
  'release_date' => '2026-10-15',   // null when only human estimate
  'display_date' => '15 de octubre de 2026', // or 'Q1 2026' / '2026'
  'month_key'    => '2026-10',
  'month_label'  => 'Octubre 2026',
  'hypes'        => 812,
  'cover_url'    => 'https://images.igdb.com/.../cover_big/abc.jpg', // null fallback
  'platforms'    => ['PC', 'PS5'],
  'genres'       => ['RPG', 'Aventura'],
  'local_url'    => '/game-slug', // only when the game is already imported, else null
]
```

- `local_url` resolved via existing `resolveGameByIgdbId()` (`'/' . $slug`, or `null`).
- The service never returns `/games/by-igdb-id/...`; templates build that fallback for released games.

### Cache

- Kirby file cache `alv/releases.cache`, single key `dataset-v3`, TTL `alv.releases.cache-ttl`.
- On cache miss, fetch synchronously once and store; on failure return empty datasets and log via `error_log`.
- Fixture mode: when `alv.releases.fixture-file` is set, load a JSON file with the normalized dataset keys (`recentlyReleased`, `upcoming`, `anticipated`) and skip IGDB entirely.

### Warm

- `scripts/collect-releases.php` — instantiates the service and calls `warm()`, printing counts.
- `POST /lanzamientos-warm?key=...` for cron parity with other plugins.
- Cron cadence documented in `README.md` (every 6 h).

## `/lanzamientos` Page

- `snippet('header')` / `snippet('footer')`.
- H1 + intro paragraph in Spanish with dynamic `{year}`.
- Genre chips (`data-releases-filter`) derived from the genres of all rendered games; rows carry `data-genre-item data-genres='[...]'`.
- Three sections (`data-releases-section="recent|upcoming|anticipated"`), two-column row grid on desktop.
- Inline vanilla JS filters rows and hides empty sections; "no lanzamientos para este filtro" empty state.
- Affiliate banner after each section via `snippet('affiliate-banner', ['grid' => true, 'itemCount' => $i])`.
- JSON-LD `ItemList` with `VideoGame` (`name`, `image`, `releaseDate`, `gamePlatform`).
- Attribution footer: "Datos de IGDB" linking to `https://www.igdb.com`.
- Meta title/description set on the virtual page content.

## Homepage

`site/templates/home.php`:

- Genre grid removed; `snippet('home-upcoming-releases')` followed by a standalone `snippet('affiliate-banner', ['itemCount' => 1])`.
- `home-upcoming-releases.php` renders a responsive three-column grid (`sm:grid-cols-2 lg:grid-cols-3`), returns early when all datasets are empty.
- Column accents: green (recent), cyan (upcoming), magenta (anticipated).
- `site/controllers/home.php`: keeps only `$latestPosts`.
- `site/snippets/header.php`: "Lanzamientos" link next to the Steam/Twitch links.

## `/games` Genre Filter

Already implemented: Spanish H1/meta and genre chips filtering the A–Z grid client-side. `/genre/{name}` remains the canonical genre URL.

## Config Changes

`site/config/config.php`:

- `cache.alv/releases.cache` (type `file`, active).
- `alv.releases.cache-ttl`, `alv.releases.warm-key`, `alv.releases.notable-hypes`, `alv.releases.fixture-file`.
- `lanzamientos` in the reserved slug arrays of both catch-all routes.

## Error Handling

- IGDB request failure → log, serve empty datasets; homepage module hidden; `/lanzamientos` shows an empty state with the intro intact.
- Missing/invalid fixture file → same as failure.
- Malformed release dates → excluded; never throw during render.
- Affiliate banners unchanged (their plugin handles enablement).

## Testing

- **Unit** (`tests/phpunit/Unit/Releases/ReleasesTest.php`) using `Tests\Support\RecordingReleasesClient`:
  - NULL-aware `(category = 0 | category = null)` query regression guard
  - notable-upcoming hypes threshold and anticipation de-duplication
  - fuzzy vs exact date display, platform/genre normalization
  - `isExcluded` filtering, limits, fixture loading path
- **Integration** (`tests/phpunit/Integration/`):
  - `LanzamientosPageTest` — sections, genre chips, internal/absent links, JSON-LD, meta, attribution
  - `HomePageReleasesTest` — three columns rendered; hidden when all datasets empty
  - `LanzamientosWarmRouteTest` — auth + counts
  - `GamesPageFilterTest` — chips and `data-genres` attributes
- **E2E** (`tests/e2e/specs/releases.spec.js`) with `RELEASES_FIXTURE` wired in `tests/e2e/app-server.sh`:
  - homepage three columns with 9 fixture entries
  - `/lanzamientos` sections + genre filtering
  - `/games` chip filtering
- Verification: `composer test`, `bun run test:unit`, `bun run test:e2e`, `bun run build` all green.

## Out of Scope

- Importing unreleased games as content pages.
- External links (Steam store) for unreleased games.
- Steam wishlist / SteamDB anticipated ranking (possible future enhancement).
- i18n; the site remains Spanish-only for this feature.
