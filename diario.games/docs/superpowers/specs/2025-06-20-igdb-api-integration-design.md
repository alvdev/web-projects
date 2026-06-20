# Diario.Games — IGDB API Integration Design

## Overview

Replace all dummy/placeholder game content with real data from the IGDB API. Game data is fetched from IGDB, saved as local Kirby flat-file content, and served from disk on subsequent requests. Supports bulk seeding, per-game search/import, and on-the-fly fetch when a visitor hits a game URL that doesn't exist locally.

## Tech Changes

| Component | Change |
|-----------|--------|
| PHP | No new dependencies — uses native `curl` for HTTP |
| Content | Replaces 12 seeded dummy games with real IGDB data |
| Architecture | Adds service layer under `site/igdb/` |
| Images | Downloads cover art (2:3) + hero screenshots (16:9) from IGDB CDN |
| Routing | Adds Kirby route for on-the-fly fetch |

## Architecture

```
site/igdb/
├── IGDBClient.php       # OAuth2 auth, HTTP requests, rate limiter
├── GameImporter.php     # IGDB JSON → Kirby content files + images
├── AutoFetcher.php      # Paginated bulk import loop
└── helpers.php          # Slug generation, image URL builder

scripts/
└── igdb.php             # CLI entry point for all commands
```

## Data Flow

### Bulk Import (Auto-Fetch)
```
IGDB API ──(paginated, 500 games/req)──→ AutoFetcher
                                              │
                                              ▼
                                        GameImporter
                                         ↙           ↘
                              content/games/{slug}/    media/
                              ├── game.txt             └── cover.jpg
                              ├── cover.jpg                 hero.jpg
                              └── hero.jpg
```

### On-the-Fly Fetch
```
Visitor: GET /games/{slug}
         │
         ▼
    Kirby route handler
         │
         ├── page exists? ──→ serve from disk
         │
         └── missing ──→ IGDBClient::fetchBySlug(slug)
                              │
                              ▼
                         GameImporter::import()
                              │
                              ▼
                         save to content/games/{slug}/
                              │
                              ▼
                         redirect to /games/{slug}
```

## Components

### 1. IGDBClient

Responsible for:
- **OAuth2 token management**: POST to `https://id.twitch.tv/oauth2/token`, cache token + `expires_in`, auto-refresh on expiry. Store credentials in `site/config/config.php` as Kirby options.
- **Rate limiting**: Track request timestamps, enforce 4 req/s max. Sleep if approaching limit.
- **API calls**: `POST` to `https://api.igdb.com/v4/{endpoint}` with Apicalypse query body. Headers: `Client-ID`, `Authorization: Bearer <token>`, `Accept: application/json`.

Methods:
```
fetchGames(array $fields, string $where = '', int $limit = 500, int $offset = 0): array
fetchGameById(int $id): array|null
fetchGameBySlug(string $slug): array|null
searchGames(string $query): array
fetchCovers(array $gameIds): array
fetchScreenshots(array $gameIds): array
fetchGenres(array $genreIds): array
fetchCompanies(array $companyIds): array
fetchReleaseDates(array $gameIds): array
```

### 2. GameImporter

Takes raw IGDB game data and creates:

**`content/games/{slug}/game.txt`**
```
Title: {name}
----
Summary: {summary}
----
ReleaseDate: {first_release_date} (unix timestamp)
----
Developer: {developer company name}
----
Publisher: {publisher company name}
----
Genres: {comma-separated genre names}
----
Platforms: {comma-separated platform names}
----
IgdbId: {id}
----
Rating: {rating}
----
AggregatedRating: {aggregated_rating}
----
Featured:
----
```

**`content/games/{slug}/{slug}.jpg`** — cover downloaded from IGDB CDN using `t_cover_big` size (264×374, 2:3)

**`content/games/{slug}/{slug}-hero.jpg`** — hero screenshot from IGDB CDN using `t_screenshot_huge` size (1280×720, 16:9)

**`content/games/{slug}/{slug}.jpg.txt`** — Kirby file metadata:
```
Title: Cover
----
Template: cover
----
```

**`content/games/{slug}/{slug}-hero.jpg.txt`**:
```
Title: Hero
----
Template: hero
----
```

Cover is saved as `{slug}.jpg` (e.g., `elden-ring.jpg`). Hero as `{slug}-hero.jpg` (e.g., `elden-ring-hero.jpg`). The `{slug}` is the IGDB slug, which already uses hyphens.

### 3. AutoFetcher

Loop logic:
```
offset = 0
while true:
    games = IGDBClient::fetchGames(
        fields: ['name', 'slug', 'summary', 'first_release_date', 'cover', 'screenshots',
                 'genres', 'involved_companies', 'platforms', 'rating', 'aggregated_rating'],
        limit: 500,
        offset: offset,
        where: 'category = 0 & version_parent = null',  // main games only, no DLC
        sort: 'rating desc'
    )
    if empty: break

    foreach (games as game):
        GameImporter::import(game)

    offset += 500
```

Respects rate limiter (250ms minimum between requests). Fetches covers + screenshots + genres + companies + platforms in batch multi-queries to minimize requests.

### 4. CLI Commands (`scripts/igdb.php`)

| Command | Description |
|---|---|
| `php scripts/igdb.php seed [--limit N]` | Fetch top N games by rating (default 500). First fetches "the next" batch from where last auto-fetch stopped. |
| `php scripts/igdb.php search <query>` | Search IGDB, print table of results, prompt user to pick one to import |
| `php scripts/igdb.php import <id>` | Import a single game by IGDB ID |
| `php scripts/igdb.php auto-fetch [--max N]` | Bulk paginate through all main games, 500 at a time, until done (or N reached) |
| `php scripts/igdb.php clean` | Remove all imported games from `content/games/` |

Arguments are read from environment variables if available (for CI/automation):
- `IGDB_CLIENT_ID`
- `IGDB_CLIENT_SECRET`

### 5. On-the-Fly Route

In `site/config/config.php`:
```php
'routes' => [
  [
    'pattern' => 'games/(:any)',
    'method' => 'GET',
    'action' => function (string $slug) {
      $page = page('games/' . $slug);
      if ($page) return $page; // already cached

      try {
        $importer = new \DiarioGames\IGDB\GameImporter();
        $imported = $importer->importBySlug($slug);
        if ($imported) go('/games/' . $slug);
      } catch (\Exception $e) {
        // fall through to 404
      }

      return page('error');
    }
  ]
]
```

### 6. Panel Integration (Future — Webhooks pattern)

Post-MVP addition: Kirby panel blueprint section with a "Search IGDB" text field + results dropdown. Admin can search, preview, and import games without touching the CLI.

## Dummy Content Cleanup

`php scripts/igdb.php clean` deletes all `content/games/*` directories and removes `.seed-generated`.

## Image Storage Convention

| Image | Source | IGDB Size | Local Filename | Dimensions |
|-------|--------|-----------|----------------|------------|
| Cover | IGDB `/covers` endpoint | `t_cover_big` | `{igdb-slug}.jpg` | 264×374 (2:3) |
| Hero | IGDB `/screenshots` endpoint | `t_screenshot_huge` | `{igdb-slug}-hero.jpg` | 1280×720 (16:9) |

## Configuration

Stored in Kirby options (e.g., via `.env` or `site/config/config.php`):

```php
'igdb' => [
  'client_id' => env('IGDB_CLIENT_ID', ''),
  'client_secret' => env('IGDB_CLIENT_SECRET', ''),
]
```

## Webhooks (Post-MVP)

IGDB supports webhooks for create/update/delete events per record type. Register webhook URLs pointing to a Kirby route handler. When a game is updated in IGDB, the webhook triggers a re-import of that game's local content. Implementation scheduled for after initial integration is stable.
