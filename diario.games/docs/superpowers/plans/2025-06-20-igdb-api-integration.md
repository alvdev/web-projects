# IGDB API Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all dummy game content with real data from the IGDB API, saving each fetched game as a local Kirby flat-file page.

**Architecture:** Service layer (`site/igdb/`) with IGDBClient (auth + rate limiting + HTTP), GameImporter (converts IGDB JSON → Kirby content files + images), and AutoFetcher (bulk pagination). CLI entry point at `scripts/igdb.php`. On-the-fly route catches missing game URLs, fetches from IGDB, caches locally, and redirects.

**Tech Stack:** PHP 8.1+, Kirby CMS 5, IGDB v4 API, native PHP cURL.

**Files Created:**
- `site/igdb/IGDBClient.php`
- `site/igdb/GameImporter.php`
- `site/igdb/AutoFetcher.php`
- `site/igdb/helpers.php`
- `scripts/igdb.php`

**Files Modified:**
- `site/config/config.php` (add IGDB config + on-the-fly route)
- `site/models/game.php` (add `hero()` method, dynamic `genres()`)
- `site/controllers/home.php` (dynamic genre detection)
- `site/snippets/genre-nav.php` (dynamic genre list)
- `site/snippets/game-card.php` (use hero image)
- `site/snippets/hero.php` (use hero image)
- `site/templates/home.php` (remove hardcoded genre labels)
- `site/templates/genre.php` (remove hardcoded genre labels)
- `site/blueprints/pages/game.yml` (add hero, dynamic genres)

**Files Cleaned Up:**
- `content/games/` (all seeded dummy games)
- `content/.seed-generated`
- `scripts/seed.php` (can be deleted after cleanup — replaced by `scripts/igdb.php`)

---

### Task 1: Clean up dummy content

**Files:**
- Delete: `content/games/` (whole directory tree)
- Delete: `content/.seed-generated`
- Delete: `scripts/seed.php`

- [ ] **Step 1: Remove all seed content and the seed script**

```bash
cd /home/alvdev/dev/www/web-projects/diario.games
php scripts/seed.php --clean
rm scripts/seed.php
```

This removes all 12 dummy games, their cover SVGs, posts, and the `.seed-generated` marker. Then deletes the seed script since it's replaced by `scripts/igdb.php`.

- [ ] **Step 2: Ensure the games parent page still exists**

```bash
mkdir -p content/games
```

Kirby needs the parent `content/games/` directory (with its `games.txt`) to list game children. Verify it survives.

- [ ] **Step 3: Commit cleanup**

```bash
git add content/ scripts/
git commit -m "chore: remove dummy seed content and seed.php"
```

---

### Task 2: Create IGDB helpers (helpers.php)

**Files:**
- Create: `site/igdb/helpers.php`

Small utility functions used across the codebase.

- [ ] **Step 1: Write helpers.php**

```php
<?php

namespace DiarioGames\IGDB;

function slugify(string $text): string
{
    return strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($text)), '-'));
}

function igdbImageUrl(string $imageId, string $size = 't_cover_big'): string
{
    return "https://images.igdb.com/igdb/image/upload/t_{$size}/{$imageId}.jpg";
}

function downloadImage(string $url, string $destPath): bool
{
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ch = curl_init($url);
    $fp = fopen($destPath, 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'diario.games/1.0',
    ]);
    $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    fclose($fp);
    if (!$success) {
        unlink($destPath);
        return false;
    }
    return true;
}
```

---

### Task 3: Create IGDBClient (auth, rate limiting, API calls)

**Files:**
- Create: `site/igdb/IGDBClient.php`

- [ ] **Step 1: Write IGDBClient.php**

```php
<?php

namespace DiarioGames\IGDB;

class IGDBClient
{
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;
    private array $requestTimestamps = [];
    private string $tokenPath;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenPath = dirname(__DIR__, 2) . '/storage/igdb_token.json';
    }

    public function authenticate(): void
    {
        if ($this->accessToken && $this->tokenExpiresAt > time()) return;

        $cached = $this->loadCachedToken();
        if ($cached && $cached['expires_at'] > time()) {
            $this->accessToken = $cached['token'];
            $this->tokenExpiresAt = $cached['expires_at'];
            return;
        }

        $url = 'https://id.twitch.tv/oauth2/token?' . http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("IGDB auth failed: HTTP $httpCode");
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + $data['expires_in'] - 300; // 5min buffer
        $this->cacheToken($this->accessToken, $this->tokenExpiresAt);
    }

    public function post(string $endpoint, string $body): array
    {
        $this->authenticate();
        $this->throttle();

        $ch = curl_init("https://api.igdb.com/v4/{$endpoint}");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Client-ID: ' . $this->clientId,
                'Authorization: Bearer ' . $this->accessToken,
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 429) {
            sleep(1);
            return $this->post($endpoint, $body);
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException("IGDB API error (HTTP $httpCode): " . substr($response, 0, 500));
        }

        return json_decode($response, true) ?? [];
    }

    public function fetchGames(array $fields, int $limit = 500, int $offset = 0, string $where = '', string $sort = ''): array
    {
        $body = 'fields ' . implode(',', $fields) . ';';
        $body .= " limit {$limit}; offset {$offset};";
        if ($where) $body .= " where {$where};";
        if ($sort) $body .= " sort {$sort};";
        return $this->post('games', $body);
    }

    public function fetchGameById(int $id): ?array
    {
        $result = $this->post('games', "fields *; where id = {$id};");
        return $result[0] ?? null;
    }

    public function fetchGameBySlug(string $slug): ?array
    {
        $result = $this->post('games', 'fields *; where slug = "' . $slug . '";');
        return $result[0] ?? null;
    }

    public function searchGames(string $query): array
    {
        $safe = addslashes($query);
        return $this->post('games', "search \"{$safe}\"; fields name,slug,first_release_date,cover,genres.name,involved_companies.company.name,platforms.name,rating,summary; where version_parent = null; limit 20;");
    }

    public function fetchCovers(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->post('covers', 'fields image_id,game; where game = (' . implode(',', $ids) . '); limit 500;');
    }

    public function fetchScreenshots(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->post('screenshots', 'fields image_id,game; where game = (' . implode(',', $ids) . '); limit 500;');
    }

    public function fetchGenres(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->post('genres', 'fields name; where id = (' . implode(',', $ids) . '); limit 500;');
    }

    private function throttle(): void
    {
        $now = microtime(true);
        $this->requestTimestamps = array_filter($this->requestTimestamps, fn($t) => $t > $now - 1);
        if (count($this->requestTimestamps) >= 4) {
            usleep(250000); // 250ms
        }
        $this->requestTimestamps[] = $now;
    }

    private function loadCachedToken(): ?array
    {
        if (!file_exists($this->tokenPath)) return null;
        $data = json_decode(file_get_contents($this->tokenPath), true);
        return $data ?? null;
    }

    private function cacheToken(string $token, int $expiresAt): void
    {
        $dir = dirname($this->tokenPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($this->tokenPath, json_encode(['token' => $token, 'expires_at' => $expiresAt]));
    }
}
```

---

### Task 4: Create GameImporter (IGDB → Kirby content files)

**Files:**
- Create: `site/igdb/GameImporter.php`

- [ ] **Step 1: Write GameImporter.php**

```php
<?php

namespace DiarioGames\IGDB;

class GameImporter
{
    private IGDBClient $client;
    private string $gamesDir;

    public function __construct(IGDBClient $client)
    {
        $this->client = $client;
        $this->gamesDir = dirname(__DIR__, 2) . '/content/games';
    }

    public function import(array $gameData): ?string
    {
        if (empty($gameData['slug'])) return null;
        $slug = $gameData['slug'];
        $dir = "{$this->gamesDir}/{$slug}";

        if (is_dir($dir)) return $slug; // already imported

        mkdir($dir, 0755, true);

        // Fetch related data
        $coverId = $gameData['cover'] ?? null;
        $screenshotIds = array_column($gameData['screenshots'] ?? [], 'id');
        $genreIds = array_column($gameData['genres'] ?? [], 'id');
        $platformIds = array_column($gameData['platforms'] ?? [], 'id');

        // Resolve names for genres and platforms
        $genreNames = $this->resolveGenreNames($genreIds);
        $platformNames = $this->resolvePlatformNames($platformIds);
        $developer = $this->findDeveloper($gameData);
        $publisher = $this->findPublisher($gameData);

        // Parse release date
        $releaseDate = '';
        if (!empty($gameData['first_release_date'])) {
            $releaseDate = date('Y-m-d', $gameData['first_release_date']);
        }

        // Write game.txt
        $content = "Title: {$gameData['name']}\n\n----\n\nSummary: {$gameData['summary']}\n\n----\n\nReleaseDate: {$releaseDate}\n\n----\n\nDeveloper: {$developer}\n\n----\n\nPublisher: {$publisher}\n\n----\n\nGenres: {$genreNames}\n\n----\n\nPlatforms: {$platformNames}\n\n----\n\nIgdbId: {$gameData['id']}\n\n----\n\nRating: " . ($gameData['rating'] ?? '') . "\n\n----\n\nAggregatedRating: " . ($gameData['aggregated_rating'] ?? '') . "\n\n----\n\nFeatured:\n\n----\n";
        file_put_contents("{$dir}/game.txt", $content);

        // Download cover image (2:3 portrait)
        if ($coverId) {
            $coverData = $this->client->fetchCovers([$gameData['id']]);
            $firstCover = $coverData[0] ?? null;
            if ($firstCover && !empty($firstCover['image_id'])) {
                $this->downloadCover($slug, $dir, $firstCover['image_id']);
            }
        }

        // Download hero screenshot (16:9)
        if (!empty($screenshotIds)) {
            $screenshots = $this->client->fetchScreenshots([$gameData['id']]);
            $firstShot = $screenshots[0] ?? null;
            if ($firstShot && !empty($firstShot['image_id'])) {
                $this->downloadHero($slug, $dir, $firstShot['image_id']);
            }
        }

        return $slug;
    }

    public function importBySlug(string $slug): ?string
    {
        $data = $this->client->fetchGameBySlug($slug);
        if (!$data) return null;

        // Fetch all expandable fields
        $fields = 'name,slug,summary,first_release_date,cover,screenshots,genres,involved_companies,platforms,rating,aggregated_rating';
        $data = $this->client->fetchGameBySlug($slug);
        if (!$data) return null;

        return $this->import($data);
    }

    private function resolveGenreNames(array $ids): string
    {
        if (empty($ids)) return '';
        $genres = $this->client->fetchGenres($ids);
        return implode(', ', array_column($genres, 'name'));
    }

    private function resolvePlatformNames(array $ids): string
    {
        if (empty($ids)) return '';
        $platforms = $this->client->post('platforms', 'fields name; where id = (' . implode(',', $ids) . '); limit 500;');
        return implode(', ', array_column($platforms, 'name'));
    }

    private function findDeveloper(array $gameData): string
    {
        $companies = $gameData['involved_companies'] ?? [];
        foreach ($companies as $ic) {
            if (!empty($ic['developer'])) {
                return $ic['company']['name'] ?? '';
            }
        }
        return '';
    }

    private function findPublisher(array $gameData): string
    {
        $companies = $gameData['involved_companies'] ?? [];
        foreach ($companies as $ic) {
            if (!empty($ic['publisher'])) {
                return $ic['company']['name'] ?? '';
            }
        }
        return '';
    }

    private function downloadCover(string $slug, string $dir, string $imageId): void
    {
        $url = igdbImageUrl($imageId, 't_cover_big');
        $path = "{$dir}/{$slug}.jpg";
        if (downloadImage($url, $path)) {
            file_put_contents(
                "{$dir}/{$slug}.jpg.txt",
                "Title: Cover\n\n----\n\nTemplate: cover\n\n----\n"
            );
        }
    }

    private function downloadHero(string $slug, string $dir, string $imageId): void
    {
        $url = igdbImageUrl($imageId, 't_screenshot_huge');
        $path = "{$dir}/{$slug}-hero.jpg";
        if (downloadImage($url, $path)) {
            file_put_contents(
                "{$dir}/{$slug}-hero.jpg.txt",
                "Title: Hero\n\n----\n\nTemplate: hero\n\n----\n"
            );
        }
    }
}
```

---

### Task 5: Create AutoFetcher (bulk paginated import)

**Files:**
- Create: `site/igdb/AutoFetcher.php`

- [ ] **Step 1: Write AutoFetcher.php**

```php
<?php

namespace DiarioGames\IGDB;

class AutoFetcher
{
    private IGDBClient $client;
    private GameImporter $importer;
    private int $imported = 0;
    private int $skipped = 0;

    public function __construct(IGDBClient $client, GameImporter $importer)
    {
        $this->client = $client;
        $this->importer = $importer;
    }

    public function run(int $maxGames = 0): array
    {
        $offset = 0;
        $limit = 500;

        while (true) {
            if ($maxGames > 0 && $this->imported >= $maxGames) break;

            $games = $this->client->fetchGames(
                ['name', 'slug', 'summary', 'first_release_date', 'cover', 'screenshots',
                 'genres', 'involved_companies', 'platforms', 'rating', 'aggregated_rating'],
                $limit,
                $offset,
                'category = 0 & version_parent = null',
                'rating desc'
            );

            if (empty($games)) break;

            foreach ($games as $game) {
                if ($maxGames > 0 && $this->imported >= $maxGames) break 2;

                $result = $this->importer->import($game);
                if ($result) {
                    $this->imported++;
                    echo "  [{$this->imported}] imported: {$game['name']}\n";
                } else {
                    $this->skipped++;
                }
            }

            $offset += $limit;
            echo "  offset {$offset}, imported {$this->imported}, skipped {$this->skipped}\n";
        }

        return ['imported' => $this->imported, 'skipped' => $this->skipped];
    }
}
```

---

### Task 6: Create CLI script (scripts/igdb.php)

**Files:**
- Create: `scripts/igdb.php`

- [ ] **Step 1: Write igdb.php**

```php
<?php
/**
 * IGDB CLI — Seed, search, import, and auto-fetch games.
 *
 * Usage:
 *   php scripts/igdb.php seed [--limit N]
 *   php scripts/igdb.php search <query>
 *   php scripts/igdb.php import <igdb-id>
 *   php scripts/igdb.php auto-fetch [--max N]
 *   php scripts/igdb.php clean
 */

require_once dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/igdb/helpers.php';
require_once dirname(__DIR__) . '/site/igdb/IGDBClient.php';
require_once dirname(__DIR__) . '/site/igdb/GameImporter.php';
require_once dirname(__DIR__) . '/site/igdb/AutoFetcher.php';

use DiarioGames\IGDB\IGDBClient;
use DiarioGames\IGDB\GameImporter;
use DiarioGames\IGDB\AutoFetcher;

$root = dirname(__DIR__);
$config = require $root . '/site/config/config.php';

$clientId = $config['igdb']['client_id'] ?? getenv('IGDB_CLIENT_ID');
$clientSecret = $config['igdb']['client_secret'] ?? getenv('IGDB_CLIENT_SECRET');

if (!$clientId || !$clientSecret) {
    echo "Error: IGDB_CLIENT_ID and IGDB_CLIENT_SECRET must be set.\n";
    echo "Set them in site/config/config.php or as environment variables.\n";
    exit(1);
}

$command = $argv[1] ?? 'help';

try {
    $client = new IGDBClient($clientId, $clientSecret);

    switch ($command) {
        case 'seed':
            $limit = 0;
            foreach ($argv as $arg) {
                if (str_starts_with($arg, '--limit=')) {
                    $limit = (int) substr($arg, 7);
                }
            }
            echo "Seeding top " . ($limit ?: 'all') . " games...\n";
            $importer = new GameImporter($client);
            $fetcher = new AutoFetcher($client, $importer);
            $result = $fetcher->run($limit);
            echo "Done. Imported: {$result['imported']}, Skipped: {$result['skipped']}\n";
            break;

        case 'search':
            $query = implode(' ', array_slice($argv, 2));
            if (!$query) {
                echo "Usage: php scripts/igdb.php search <query>\n";
                exit(1);
            }
            echo "Searching for \"{$query}\"...\n";
            $results = $client->searchGames($query);
            if (empty($results)) {
                echo "No results found.\n";
                exit;
            }
            echo "\nResults:\n";
            foreach ($results as $i => $game) {
                $date = !empty($game['first_release_date']) ? date('Y-m-d', $game['first_release_date']) : 'N/A';
                echo "  [{$i}] {$game['name']} ({$date}) — ID: {$game['id']}\n";
            }
            echo "\nEnter number to import, or 'q' to quit: ";
            $input = trim(fgets(STDIN));
            if ($input === 'q') exit;
            $index = (int) $input;
            if (isset($results[$index])) {
                $importer = new GameImporter($client);
                $slug = $importer->import($results[$index]);
                if ($slug) {
                    echo "Imported: {$results[$index]['name']} → /games/{$slug}\n";
                } else {
                    echo "Failed to import.\n";
                }
            }
            break;

        case 'import':
            $id = (int) ($argv[2] ?? 0);
            if (!$id) {
                echo "Usage: php scripts/igdb.php import <igdb-id>\n";
                exit(1);
            }
            echo "Fetching game ID {$id}...\n";
            $gameData = $client->fetchGameById($id);
            if (!$gameData) {
                echo "Game not found.\n";
                exit(1);
            }
            $importer = new GameImporter($client);
            $slug = $importer->import($gameData);
            echo "Imported: {$gameData['name']} → /games/{$slug}\n";
            break;

        case 'auto-fetch':
            $max = 0;
            foreach ($argv as $arg) {
                if (str_starts_with($arg, '--max=')) {
                    $max = (int) substr($arg, 6);
                }
            }
            echo "Auto-fetching " . ($max ? "up to {$max}" : "all") . " games...\n";
            $importer = new GameImporter($client);
            $fetcher = new AutoFetcher($client, $importer);
            $result = $fetcher->run($max);
            echo "Done. Imported: {$result['imported']}, Skipped: {$result['skipped']}\n";
            break;

        case 'clean':
            $gamesDir = $root . '/content/games';
            if (!is_dir($gamesDir)) {
                echo "Nothing to clean.\n";
                exit;
            }
            $dirs = glob($gamesDir . '/*', GLOB_ONLYDIR);
            $count = 0;
            foreach ($dirs as $dir) {
                // Only clean game directories (skip games.txt parent)
                $basename = basename($dir);
                if ($basename === 'games') continue;
                array_map('unlink', glob("{$dir}/*"));
                rmdir($dir);
                $count++;
                echo "  removed: {$basename}\n";
            }
            if (file_exists($root . '/content/.seed-generated')) {
                unlink($root . '/content/.seed-generated');
            }
            echo "Done. Removed {$count} games.\n";
            break;

        default:
            echo "Usage: php scripts/igdb.php <command> [options]\n\n";
            echo "Commands:\n";
            echo "  seed [--limit=N]      Import top-rated games (default: all)\n";
            echo "  search <query>         Search IGDB and import interactively\n";
            echo "  import <id>            Import a game by IGDB ID\n";
            echo "  auto-fetch [--max=N]   Bulk import all main games\n";
            echo "  clean                  Remove all imported local games\n";
            break;
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

---

### Task 7: Update Kirby config (IGDB config + on-the-fly route)

**Files:**
- Modify: `site/config/config.php`

- [ ] **Step 1: Add IGDB credentials config and on-the-fly route**

Edit `site/config/config.php`:

```php
<?php

return [
    'debug' => true,
    'url' => $_SERVER['HTTP_HOST'] ?? 'http://localhost:5173',

    'igdb' => [
        'client_id' => env('IGDB_CLIENT_ID', ''),
        'client_secret' => env('IGDB_CLIENT_SECRET', ''),
    ],

    'arnoson.kirby-vite' => [
        'outDir' => 'public/assets',
    ],

    'arnoson.kirby-loupe' => [
        'pages' => fn($page) => $page->intendedTemplate()->name() === 'game' || $page->intendedTemplate()->name() === 'post',
        'fields' => [
            'title',
            'summary',
            'text' => fn($page) => strip_tags($page->text()),
        ],
        'searchable' => ['title', 'summary', 'text'],
    ],

    'tearoom1.meta-kit' => [
        'ai.enabled' => false,
        'review.enabled' => false,
        'sitemap.exclude' => ['error'],
        'excludeTemplates' => ['default'],
        'excludeStatus' => ['draft'],
    ],

    'routes' => [
        [
            'pattern' => 'genre/(:any)',
            'action'  => function ($genre) {
                return page('genre')->render(['genreSlug' => $genre]);
            },
        ],
        [
            'pattern' => 'games/(:any)',
            'method' => 'GET',
            'action' => function (string $slug) {
                $page = page('games/' . $slug);
                if ($page) return $page;

                $config = kirby()->option('igdb');
                if (empty($config['client_id']) || empty($config['client_secret'])) {
                    return page('error');
                }

                try {
                    require_once kirby()->root('base') . '/site/igdb/helpers.php';
                    require_once kirby()->root('base') . '/site/igdb/IGDBClient.php';
                    require_once kirby()->root('base') . '/site/igdb/GameImporter.php';

                    $client = new \DiarioGames\IGDB\IGDBClient($config['client_id'], $config['client_secret']);
                    $importer = new \DiarioGames\IGDB\GameImporter($client);
                    $result = $importer->importBySlug($slug);

                    if ($result) {
                        go('/games/' . $slug);
                    }
                } catch (\Throwable $e) {
                    // fall through to 404
                }

                return page('error');
            }
        ],
    ],
];
```

---

### Task 8: Update GamePage model (hero(), dynamic genres())

**Files:**
- Modify: `site/models/game.php`

- [ ] **Step 1: Add `hero()` method and update `genres()` to be dynamic**

```php
<?php

class GamePage extends \Kirby\Cms\Page
{
    public function genres(): string
    {
        return $this->content()->get('genres')->value() ?? '';
    }

    public function genreList(): array
    {
        return array_map('trim', explode(',', $this->genres()));
    }

    public function posts(): \Kirby\Cms\Pages
    {
        return $this->children()->listed()->sortBy('date', 'desc');
    }

    public function cover(): ?\Kirby\Cms\File
    {
        return $this->files()->template('cover')->first();
    }

    public function hero(): ?\Kirby\Cms\File
    {
        return $this->files()->template('hero')->first();
    }

    public function releaseDate(): string
    {
        return $this->content()->get('release_date')->value();
    }

    public function releaseYear(): string
    {
        $date = $this->releaseDate();
        if (preg_match('/^\d{4}/', $date, $m)) {
            return $m[0];
        }
        return $date;
    }
}
```

The `genres()` method now returns the raw comma-separated string from IGDB (e.g. "Role-playing (RPG), Adventure"). Frontend templates use this directly.

---

### Task 9: Update home controller (dynamic genre detection)

**Files:**
- Modify: `site/controllers/home.php`

- [ ] **Step 1: Replace hardcoded genre list with dynamic detection**

```php
<?php

return function ($site) {
    $games = $site->find('games')->children()->listed()->sortBy('title', 'asc');

    // Build genre list dynamically from game data
    $allGenres = [];
    foreach ($games as $game) {
        foreach ($game->genreList() as $genre) {
            $genre = trim($genre);
            if ($genre) $allGenres[$genre] = true;
        }
    }

    $genreGames = [];
    foreach (array_keys($allGenres) as $genre) {
        $filtered = $games->filter(function ($g) use ($genre) {
            return in_array($genre, $g->genreList());
        })->limit(2);

        if ($filtered->count() > 0) {
            $genreGames[$genre] = $filtered;
        }
    }

    return [
        'genreGames' => $genreGames,
    ];
};
```

---

### Task 10: Update genre nav (dynamic genre list)

**Files:**
- Modify: `site/snippets/genre-nav.php`

- [ ] **Step 1: Build nav dynamically from game genres**

```php
<?php

$games = $site->find('games')->children()->listed();
$allGenres = [];
foreach ($games as $game) {
    foreach ($game->genreList() as $genre) {
        $genre = trim($genre);
        if ($genre) $allGenres[$genre] = true;
    }
}
ksort($allGenres);
?>
<nav class="flex gap-4 overflow-x-auto text-sm">
    <a href="<?= url('games') ?>" class="text-muted hover:text-neon-cyan transition whitespace-nowrap">All</a>
    <?php foreach (array_keys($allGenres) as $genre): ?>
    <a href="<?= url('genre/' . urlencode($genre)) ?>" class="text-muted hover:text-neon-cyan transition whitespace-nowrap">
        <?= htmlspecialchars($genre) ?>
    </a>
    <?php endforeach ?>
</nav>
```

---

### Task 11: Update home template (dynamic genre labels)

**Files:**
- Modify: `site/templates/home.php`

- [ ] **Step 1: Remove hardcoded label map, use genre name directly**

```php
<?php snippet('header') ?>

<?php snippet('hero') ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($genreGames as $genre => $games): ?>
    <div class="bg-surface/50 border border-border rounded-xl p-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-neon-cyan mb-4 pb-2 border-b border-border">
            <?= htmlspecialchars($genre) ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($games as $game): ?>
                <?php snippet('game-card', ['game' => $game]) ?>
            <?php endforeach ?>
        </div>
    </div>
    <?php endforeach ?>
</div>

<?php snippet('footer') ?>
```

---

### Task 12: Update genre template (dynamic labels)

**Files:**
- Modify: `site/templates/genre.php`

- [ ] **Step 1: Remove hardcoded labels, use genre name from URL**

```php
<?php snippet('header') ?>

<?php
$genreSlug = $genreSlug ?? param('genre');
$allGames = $site->find('games')->children()->listed();
$games = $allGames->filter(function ($game) use ($genreSlug) {
    return in_array($genreSlug, $game->genreList());
})->sortBy('title', 'asc');
?>

<h1 class="text-2xl font-bold text-neon-cyan mb-6"><?= htmlspecialchars(urldecode($genreSlug)) ?></h1>

<?php if ($games->count() > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($games as $game): ?>
        <?php snippet('game-card', ['game' => $game]) ?>
    <?php endforeach ?>
</div>
<?php else: ?>
<p class="text-muted">No games found in this category yet.</p>
<?php endif ?>

<?php snippet('footer') ?>
```

---

### Task 13: Update game-card snippet (use hero image)

**Files:**
- Modify: `site/snippets/game-card.php`

- [ ] **Step 1: Use hero image (16:9) instead of cover (2:3) for card displays**

```php
<?php $game = $game ?? $page; ?>
<a href="<?= $game->url() ?>" class="block bg-surface border border-border rounded-lg overflow-hidden hover:border-neon-cyan/50 transition group">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($hero = $game->hero()): ?>
        <img src="<?= $hero->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php elseif ($cover = $game->cover()): ?>
        <img src="<?= $cover->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-muted text-sm">No image</div>
        <?php endif ?>
    </div>
    <div class="p-3">
        <h3 class="font-semibold text-text text-sm"><?= $game->title() ?></h3>
        <div class="flex items-center gap-2 mt-1 text-xs text-muted">
            <span class="text-neon-cyan"><?= implode(', ', array_slice($game->genreList(), 0, 2)) ?></span>
            <?php if ($game->releaseYear()): ?>
            <span>• <?= $game->releaseYear() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
```

---

### Task 14: Update hero snippet (use hero image)

**Files:**
- Modify: `site/snippets/hero.php`

- [ ] **Step 1: Add hero image background to featured game**

```php
<?php $featured = $site->find('games')->children()->listed()->filterBy('featured', 'true')->first(); ?>
<?php if ($featured): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative rounded-xl overflow-hidden group">
        <?php if ($hero = $featured->hero()): ?>
        <img src="<?= $hero->url() ?>" alt="<?= $featured->title() ?>" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-surface via-surface/60 to-transparent"></div>
        <?php endif ?>
        <div class="relative aspect-[21/9] flex items-end p-6">
            <div>
                <span class="text-xs uppercase tracking-widest text-neon-green">Featured</span>
                <h2 class="text-2xl font-bold text-text mt-1"><?= $featured->title() ?></h2>
                <p class="text-sm text-muted mt-1 line-clamp-2"><?= $featured->summary()->kti() ?></p>
            </div>
        </div>
    </a>
    <div class="bg-surface border border-border rounded-xl p-4">
        <h3 class="text-xs uppercase tracking-widest text-neon-magenta mb-3">Trending</h3>
        <div class="space-y-3">
            <?php $trending = $site->find('games')->children()->listed()->sortBy('release_date', 'desc')->limit(4); ?>
            <?php foreach ($trending as $game): ?>
            <a href="<?= $game->url() ?>" class="block text-sm text-muted hover:text-text transition pb-2 border-b border-border last:border-0">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>
```

---

### Task 15: Update game blueprint (hero field, dynamic genres)

**Files:**
- Modify: `site/blueprints/pages/game.yml`

- [ ] **Step 1: Add hero files section and replace hardcoded genres with tags**

```yaml
title: Game

columns:
  main:
    width: 2/3
    sections:
      images:
        type: fields
        fields:
          cover:
            label: Cover Image
            type: files
            query: model.files.template('cover')
            uploads:
              template: cover
          hero:
            label: Hero Image
            type: files
            query: model.files.template('hero')
            uploads:
              template: hero
      content:
        type: fields
        fields:
          title:
            label: Title
            type: text
            required: true
          summary:
            label: Summary
            type: textarea
            size: small
          release_date:
            label: Release Date
            type: date
          developer:
            label: Developer
            type: text
          publisher:
            label: Publisher
            type: text
          genres:
            label: Genres
            type: text
            placeholder: Comma-separated genre names
          platforms:
            label: Platforms
            type: text
            placeholder: Comma-separated platform names

  sidebar:
    width: 1/3
    sections:
      posts:
        label: Posts
        type: pages
        template: post
        layout: cards
        size: small
        info: "{{ page.date }}"
      meta:
        type: fields
        fields:
          rating:
            label: Rating
            type: text
            placeholder: IGDB rating score
          igdb_id:
            label: IGDB ID
            type: text
            disabled: true
          featured:
            label: Featured game
            type: toggle
            text: Show on homepage hero
```

---

### Task 16: Test the integration end-to-end

**Files:**
- No files changed

- [ ] **Step 1: Run a search to verify API connectivity**

```bash
cd /home/alvdev/dev/www/web-projects/diario.games
php scripts/igdb.php search "Elden Ring"
```

Expected: Shows search results from IGDB with game names and IDs.

- [ ] **Step 2: Import a single game**

```bash
php scripts/igdb.php import 28540  # Elden Ring IGDB ID (or pick from search results)
```

Expected: Game files created under `content/games/elden-ring/` with `game.txt`, `elden-ring.jpg`, `elden-ring-hero.jpg`, and their `.txt` metadata files.

- [ ] **Step 3: Verify the game appears in Kirby**

Start the dev server and visit `/games/elden-ring`. Expected: The game page renders with cover art and hero image.

- [ ] **Step 4: Seed a few top-rated games**

```bash
php scripts/igdb.php seed --limit=50
```

Expected: 50 top-rated games imported, each with cover + hero images.

- [ ] **Step 5: Visit the homepage**

Visit `/`. Expected: Dynamic genre sections appear based on the 50 imported games. Featured game shows hero image in the background.

- [ ] **Step 6: Clean up test data**

```bash
php scripts/igdb.php clean
```

Expected: All imported games removed.
