# Plugin Seams & Integration Tests (Part 1) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lock in the IGDB import pipeline with fake-client integration tests, extract testable AI parser methods, and add Kirby page-render smoke tests — all hermetic (no live APIs, no production data writes).

**Architecture:** `GameImporter` gets additive seams: an optional `$gamesDir` constructor arg, `private` → `protected` visibility on its I/O steps, and small protected wrappers around global functions (`translate`, `downloadImage`, `fetchThesvgIcon`, `option`). Tests use `Tests\Support\FakeIGDBClient` (canned `post()` responses) and `Tests\Support\TestableGameImporter` (stubs for network/media). `AIClient` gets `buildMessages()`/`parseCompletion()` extraction with unit tests. The page smoke test boots a real Kirby `App` with temp cache/media/accounts/sessions roots and a temp SQLite DB, renders real content read-only, and asserts pages render.

**Tech Stack:** PHP 8.4, PHPUnit 11, Kirby 5, PDO SQLite.

**Spec:** `docs/superpowers/specs/2026-09-17-regression-test-suite-design.md` (Phase 2, IGDB + AI + smoke portion; prices/banners, steam collectors, routes, CLI and E2E move to later plans).

**Phase mapping:** This plan covers spec Phase 2's IGDB import, AIClient, and page-smoke items. Spec Phase 2's price adapters/banners are Plan 3; steam collector seams are Plan 4; Kirby routes/site methods are Plan 5; CLI/scrapers Plan 6; E2E Plan 7.

**Prerequisites:** Plan 1 merged (PHPUnit infra + `TempDatabase`/`PluginClasses` + DB seams). Run tests from `diario.games/`.

**Safety rules:**
- Production code changes are additive; defaults identical.
- Tests never write `content/`, `sqlite/`, `storage/`, `media/`, `site/cache`.
- The page smoke test assumes no price-provider keys in `.env` (see `.env.example`; only IGDB/Steam/AI keys are documented there). If price keys exist, `priceComparison` may attempt network calls during game-page render; check before running Task 6 and clear any `PRICE_*`/`ITAD_*`/`G2A_*`/`INSTANT_GAMING_*` keys if present.
- The game page smoke test requires `public/assets/.vite/manifest.json` (run `bun run build` if missing and no `.dev` dev-server file exists).

---

### Task 1: GameImporter testability seams

**Files:**
- Create: `tests/phpunit/Unit/Igdb/GameImporterSeamTest.php`
- Create: `tests/Support/FakeIGDBClient.php`
- Create: `tests/Support/Files.php`
- Modify: `site/plugins/alv-igdb/classes/GameImporter.php`

- [ ] **Step 1: Create the support classes**

`tests/Support/Files.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

final class Files
{
    public static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path) && !is_link($path)) {
                self::removeDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
```

`tests/Support/FakeIGDBClient.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use DiarioGames\IGDB\IGDBClient;

class FakeIGDBClient extends IGDBClient
{
    public array $gamesBySlug = [];
    public array $searchResults = [];
    public array $gamesResponse = [];
    public array $platforms = [['id' => 6, 'name' => 'PC (Microsoft Windows)']];
    public array $genres = [['id' => 12, 'name' => 'Role-playing (RPG)']];
    public array $themes = [['id' => 1, 'name' => 'Action']];
    public array $covers = [['id' => 1, 'image_id' => 'cover_abc']];
    public array $screenshots = [
        ['id' => 1, 'image_id' => 'shot_1'],
        ['id' => 2, 'image_id' => 'shot_2'],
    ];
    public array $videos = [['id' => 1, 'video_id' => 'yt123']];

    public function __construct()
    {
        parent::__construct('test-client-id', 'test-client-secret');
    }

    public function post(string $endpoint, string $body): array
    {
        return match ($endpoint) {
            'platforms' => $this->platforms,
            'genres' => $this->genres,
            'themes' => $this->themes,
            'covers' => $this->covers,
            'screenshots' => $this->screenshots,
            'videos' => $this->videos,
            default => [],
        };
    }

    public function fetchGameBySlug(string $slug): ?array
    {
        return $this->gamesBySlug[$slug] ?? null;
    }

    public function searchGames(string $query): array
    {
        return $this->searchResults;
    }

    public function fetchGames(array $fields, int $limit = 500, int $offset = 0, string $where = '', string $sort = ''): array
    {
        return $offset === 0 ? $this->gamesResponse : [];
    }
}
```

- [ ] **Step 2: Write the seam test**

`tests/phpunit/Unit/Igdb/GameImporterSeamTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use DiarioGames\IGDB\GameImporter;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class GameImporterSeamTest extends TestCase
{
    use TempDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    public function testConstructorUsesInjectedGamesDir(): void
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        $importer = new GameImporter(new FakeIGDBClient(), $gamesDir);

        $property = new \ReflectionProperty(GameImporter::class, 'gamesDir');
        $property->setAccessible(true);

        $this->assertSame($gamesDir, $property->getValue($importer));
    }
}
```

- [ ] **Step 3: Run it to verify it fails safely**

Run: `vendor/bin/phpunit --filter GameImporterSeamTest`
Expected: FAIL — `assertSame` sees the real `content/games` path because the second constructor arg is ignored. This test performs no import, so nothing is written anywhere.

- [ ] **Step 4: Implement the seams in `site/plugins/alv-igdb/classes/GameImporter.php`**

Replace the constructor (lines 78-85):

```php
    private IGDBClient $client;
    private string $gamesDir;

    public function __construct(IGDBClient $client, ?string $gamesDir = null)
    {
        $this->client = $client;
        $this->gamesDir = $gamesDir ?? dirname(__DIR__, 4) . '/content/games';
    }

    protected function translateText(string $text, string $backend = 'opencode'): string
    {
        return \DiarioGames\IGDB\translate($text, $backend);
    }

    protected function downloadImageTo(string $url, string $destPath): bool
    {
        return \DiarioGames\IGDB\downloadImage($url, $destPath);
    }

    protected function fetchThesvgIconFor(string $url): ?array
    {
        return \DiarioGames\IGDB\fetchThesvgIcon($url);
    }

    protected function steamApiKey(): string
    {
        if (function_exists('option')) {
            return (string) option('alv.steam-stats.api-key', '');
        }
        return (string) (getenv('STEAM_STATS_API_KEY') ?: '');
    }

    protected function makeSteamCollector(string $apiKey): \Alv\SteamStats\SteamStatsCollector
    {
        return new \Alv\SteamStats\SteamStatsCollector($apiKey);
    }
```

Widen visibility (`private function` → `protected function`) for exactly these methods:
`registerSteamGame`, `verifySteamAppId`, `fetchSteamCurrentPlayers`, `resolveGenresAndTags`, `resolvePlatformNames`, `resolveInvolvedCompanies`, `importMissingMedia`, `downloadCover`, `downloadHero`.

Apply these exact call-site replacements:

1. Summary translation:
   - old: `$summary = \DiarioGames\IGDB\translate($this->stringVal($gameData['summary'] ?? ''));`
   - new: `$summary = $this->translateText($this->stringVal($gameData['summary'] ?? ''));`
2. Thesvg icons:
   - old: `if ($siteUrl) \DiarioGames\IGDB\fetchThesvgIcon($siteUrl);`
   - new: `if ($siteUrl) $this->fetchThesvgIconFor($siteUrl);`
3. Steam API key:
   - old: `$apiKey = option('alv.steam-stats.api-key', '');`
   - new: `$apiKey = $this->steamApiKey();`
4. Collector creation:
   - old: `$collector = new \Alv\SteamStats\SteamStatsCollector($apiKey);`
   - new: `$collector = $this->makeSteamCollector($apiKey);`
5. Image downloads — replace all four occurrences of `downloadImage($url, $path)` with `$this->downloadImageTo($url, $path)` (inside `downloadCover`, `downloadHero`, `downloadScreenshots`, `downloadVideoThumbs`).

Do not change any other logic.

- [ ] **Step 5: Run the seam test**

Run: `vendor/bin/phpunit --filter GameImporterSeamTest`
Expected: `OK (1 test, 1 assertion)`.

- [ ] **Step 6: Run the full suite and lint**

Run: `composer test:unit` (expect 53 tests) and `php -l site/plugins/alv-igdb/classes/GameImporter.php`.
Expected: all pass; no syntax errors.

- [ ] **Step 7: Commit**

```bash
git add site/plugins/alv-igdb/classes/GameImporter.php tests/Support/Files.php tests/Support/FakeIGDBClient.php tests/phpunit/Unit/Igdb/GameImporterSeamTest.php
git commit -m "refactor(igdb): add importer seams for hermetic integration tests"
```

---

### Task 2: GameImporter import pipeline tests

**Files:**
- Create: `tests/Support/TestableGameImporter.php`
- Create: `tests/phpunit/Unit/Igdb/GameImporterImportTest.php`

- [ ] **Step 1: Create the test double**

`tests/Support/TestableGameImporter.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use DiarioGames\IGDB\GameImporter;

class TestableGameImporter extends GameImporter
{
    public array $iconsFetched = [];
    public array $translated = [];
    public bool $steamAppIdValid = false;
    public ?int $liveSteamPlayers = null;

    protected function translateText(string $text, string $backend = 'opencode'): string
    {
        $this->translated[] = $text;
        return $text === '' ? '' : '[es] ' . $text;
    }

    protected function downloadImageTo(string $url, string $destPath): bool
    {
        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($destPath, 'fake-bytes:' . $url) !== false;
    }

    protected function fetchThesvgIconFor(string $url): ?array
    {
        $this->iconsFetched[] = $url;
        return null;
    }

    protected function verifySteamAppId(int $appid): bool
    {
        return $this->steamAppIdValid;
    }

    protected function fetchSteamCurrentPlayers(string $apiKey, int $appid): ?int
    {
        return $this->liveSteamPlayers;
    }

    protected function steamApiKey(): string
    {
        return 'test-api-key';
    }

    protected function makeSteamCollector(string $apiKey): \Alv\SteamStats\SteamStatsCollector
    {
        return new class($apiKey) extends \Alv\SteamStats\SteamStatsCollector {
            public function downloadCapsule(int $appid, ?string $slug = null): ?string
            {
                return null;
            }
        };
    }
}
```

- [ ] **Step 2: Write the import pipeline tests**

`tests/phpunit/Unit/Igdb/GameImporterImportTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableGameImporter;
use PHPUnit\Framework\TestCase;

final class GameImporterImportTest extends TestCase
{
    use TempDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    private function gamesDir(): string
    {
        return $this->tempDatabaseDir . '/games';
    }

    private function importer(?FakeIGDBClient $client = null): TestableGameImporter
    {
        return new TestableGameImporter($client ?? new FakeIGDBClient(), $this->gamesDir());
    }

    private function gameData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1234,
            'slug' => 'test-game',
            'name' => 'Test Game',
            'first_release_date' => mktime(0, 0, 0, 3, 15, 2024),
            'summary' => 'A test game summary.',
            'platforms' => [6],
            'genres' => [12],
            'themes' => [1],
            'involved_companies' => [
                ['developer' => true, 'publisher' => false, 'company' => ['name' => 'Dev Studio']],
                ['developer' => false, 'publisher' => true, 'company' => ['name' => 'Pub Studio']],
            ],
            'screenshots' => [['id' => 1]],
            'videos' => [],
            'cover' => 1,
            'rating' => 88.5,
            'aggregated_rating' => 90.0,
            'websites' => [],
        ], $overrides);
    }

    public function testImportWritesShardedContentAndMedia(): void
    {
        $importer = $this->importer();

        $this->assertSame('test-game', $importer->import($this->gameData()));

        $dir = $this->gamesDir() . '/2024/03/test-game';
        $this->assertDirectoryExists($dir);

        $content = file_get_contents($dir . '/game.txt');
        $this->assertStringContainsString('Title: Test Game', $content);
        $this->assertStringContainsString('Summary: [es] A test game summary.', $content);
        $this->assertStringContainsString('ReleaseDate: 2024-03-15', $content);
        $this->assertStringContainsString('Developer: Dev Studio', $content);
        $this->assertStringContainsString('Publisher: Pub Studio', $content);
        $this->assertStringContainsString('Genres: RPG', $content);
        $this->assertStringContainsString('Tags: Acción', $content);
        $this->assertStringContainsString('Platforms: PC', $content);
        $this->assertStringContainsString('IgdbId: 1234', $content);
        $this->assertStringContainsString('Screenshots: shot_1, shot_2', $content);

        $this->assertFileExists($dir . '/test-game.jpg');
        $this->assertFileExists($dir . '/test-game.jpg.txt');
        $this->assertFileExists($dir . '/test-game-hero.jpg');
        $this->assertFileExists($dir . '/test-game-hero.jpg.txt');
        $this->assertFileExists($dir . '/screenshot-0.jpg');
        $this->assertFileExists($dir . '/screenshot-1.jpg');
        $this->assertFileExists($dir . '/screenshot-0.jpg.txt');

        $this->assertSame('2024/03', \DiarioGames\IGDB\resolveGamePath('test-game'));
        $this->assertSame(['A test game summary.'], $importer->translated);
    }

    public function testImportReturnsNullWhenPlatformsAreNotAllowed(): void
    {
        $client = new FakeIGDBClient();
        $client->platforms = [['id' => 99, 'name' => 'Commodore 64']];

        $this->assertNull($this->importer($client)->import($this->gameData()));
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/test-game');
    }

    public function testImportReturnsNullWithoutScreenshotsOrVideos(): void
    {
        $data = $this->gameData(['screenshots' => [], 'videos' => []]);

        $this->assertNull($this->importer()->import($data));
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/test-game');
    }

    public function testReimportingExistingGameDoesNotDuplicateContent(): void
    {
        $importer = $this->importer();

        $this->assertSame('test-game', $importer->import($this->gameData()));
        $this->assertSame('test-game', $importer->import($this->gameData()));

        $content = file_get_contents($this->gamesDir() . '/2024/03/test-game/game.txt');
        $this->assertSame(1, substr_count($content, 'Title: Test Game'));
        $this->assertSame('2024/03', \DiarioGames\IGDB\resolveGamePath('test-game'));
    }
}
```

- [ ] **Step 3: Run the tests**

Run: `vendor/bin/phpunit --filter GameImporterImportTest`
Expected: `OK (4 tests, 20+ assertions)`. If any assertion fails, STOP and report the exact mismatch; do not modify production code.

- [ ] **Step 4: Run the full suite**

Run: `composer test:unit`
Expected: all pass (57 tests).

- [ ] **Step 5: Commit**

```bash
git add tests/Support/TestableGameImporter.php tests/phpunit/Unit/Igdb/GameImporterImportTest.php
git commit -m "test: cover GameImporter import pipeline"
```

---

### Task 3: Import fallback + Steam registration tests

**Files:**
- Create: `tests/phpunit/Unit/Igdb/GameImporterFallbackTest.php`

- [ ] **Step 1: Write the tests**

`tests/phpunit/Unit/Igdb/GameImporterFallbackTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableGameImporter;

final class GameImporterFallbackTest extends TestCase
{
    use TempDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    private function gamesDir(): string
    {
        return $this->tempDatabaseDir . '/games';
    }

    private function gameData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1234,
            'slug' => 'test-game',
            'name' => 'Test Game',
            'first_release_date' => mktime(0, 0, 0, 3, 15, 2024),
            'summary' => 'A test game summary.',
            'platforms' => [6],
            'genres' => [12],
            'themes' => [1],
            'involved_companies' => [
                ['developer' => true, 'publisher' => false, 'company' => ['name' => 'Dev Studio']],
            ],
            'screenshots' => [['id' => 1]],
            'videos' => [],
            'cover' => 1,
            'rating' => 88.5,
            'aggregated_rating' => 90.0,
            'websites' => [],
        ], $overrides);
    }

    public function testImportBySlugUsesDirectLookup(): void
    {
        $client = new FakeIGDBClient();
        $client->gamesBySlug['direct-game'] = $this->gameData(['slug' => 'direct-game']);

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertSame('direct-game', $importer->importBySlug('direct-game'));
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/direct-game');
    }

    public function testImportBySlugWithFallbackUsesExactSearchMatch(): void
    {
        $client = new FakeIGDBClient();
        $client->searchResults = [$this->gameData(['slug' => 'search-game'])];

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertSame('search-game', $importer->importBySlugWithFallback('search-game'));
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/search-game');
    }

    public function testImportBySlugWithFallbackImportsUnderRequestedSlug(): void
    {
        $client = new FakeIGDBClient();
        $client->searchResults = [$this->gameData(['slug' => 'different-igdb-slug'])];

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertSame('requested-game', $importer->importBySlugWithFallback('requested-game'));
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/requested-game');
    }

    public function testImportBySlugWithFallbackReturnsNullWhenNothingFound(): void
    {
        $client = new FakeIGDBClient();

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertNull($importer->importBySlugWithFallback('unknown-game'));
    }

    public function testImportRegistersSteamGameWithLivePlayerCount(): void
    {
        $client = new FakeIGDBClient();
        $importer = new TestableGameImporter($client, $this->gamesDir());
        $importer->steamAppIdValid = true;
        $importer->liveSteamPlayers = 12345;

        $data = $this->gameData([
            'websites' => [
                ['category' => 1, 'url' => 'https://store.steampowered.com/app/570/'],
            ],
        ]);

        $this->assertSame('test-game', $importer->import($data));

        $db = new SteamStatsDB($this->tempDatabasePath);
        $game = $db->getGameByAppId(570);

        $this->assertNotNull($game);
        $this->assertSame('test-game', $game['slug']);
        $this->assertSame(12345, $db->getCurrentPlayers(570));
        $this->assertSame(['https://store.steampowered.com/app/570/'], $importer->iconsFetched);
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter GameImporterFallbackTest`
Expected: `OK (5 tests, 12 assertions)`.

- [ ] **Step 3: Run the full suite**

Run: `composer test:unit`
Expected: all pass (62 tests).

- [ ] **Step 4: Commit**

```bash
git add tests/phpunit/Unit/Igdb/GameImporterFallbackTest.php
git commit -m "test: cover IGDB import fallback and steam registration"
```

---

### Task 4: AutoFetcher test

**Files:**
- Create: `tests/phpunit/Unit/Igdb/AutoFetcherTest.php`

- [ ] **Step 1: Write the tests**

`tests/phpunit/Unit/Igdb/AutoFetcherTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use DiarioGames\IGDB\AutoFetcher;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableGameImporter;

final class AutoFetcherTest extends TestCase
{
    use TempDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    private function gamesDir(): string
    {
        return $this->tempDatabaseDir . '/games';
    }

    private function gameData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1234,
            'slug' => 'auto-game',
            'name' => 'Auto Game',
            'first_release_date' => mktime(0, 0, 0, 3, 15, 2024),
            'summary' => 'Summary.',
            'platforms' => [6],
            'genres' => [12],
            'themes' => [1],
            'involved_companies' => [],
            'screenshots' => [['id' => 1]],
            'videos' => [],
            'cover' => 1,
            'rating' => 80,
            'aggregated_rating' => 81,
            'websites' => [],
        ], $overrides);
    }

    public function testRunImportsUntilMaxGames(): void
    {
        $client = new FakeIGDBClient();
        $client->gamesResponse = [
            $this->gameData(['slug' => 'auto-1']),
            $this->gameData(['slug' => 'auto-2']),
        ];
        $importer = new TestableGameImporter($client, $this->gamesDir());
        $fetcher = new AutoFetcher($client, $importer);

        $result = $fetcher->run(1);

        $this->assertSame(['imported' => 1, 'skipped' => 0], $result);
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/auto-1');
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/auto-2');
    }

    public function testRunCountsSkippedGames(): void
    {
        $client = new FakeIGDBClient();
        $client->gamesResponse = [
            $this->gameData(['slug' => 'no-media', 'screenshots' => [], 'videos' => []]),
        ];
        $importer = new TestableGameImporter($client, $this->gamesDir());
        $fetcher = new AutoFetcher($client, $importer);

        $result = $fetcher->run();

        $this->assertSame(['imported' => 0, 'skipped' => 1], $result);
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/no-media');
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter AutoFetcherTest`
Expected: `OK (2 tests, 5 assertions)`.

- [ ] **Step 3: Run the full suite**

Run: `composer test:unit`
Expected: all pass (64 tests).

- [ ] **Step 4: Commit**

```bash
git add tests/phpunit/Unit/Igdb/AutoFetcherTest.php
git commit -m "test: cover AutoFetcher run loop"
```

---

### Task 5: AIClient parser extraction + tests

**Files:**
- Create: `tests/phpunit/Unit/Ai/AIClientTest.php`
- Modify: `site/plugins/alv-ai/classes/AIClient.php`

- [ ] **Step 1: Write the failing tests**

`tests/phpunit/Unit/Ai/AIClientTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use DiarioGames\AI\AIClient;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class AIClientTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testTranslateReturnsInputUnchangedForBlankText(): void
    {
        $this->assertSame('', AIClient::translate(''));
        $this->assertSame('   ', AIClient::translate('   '));
    }

    public function testRewriteReturnsInputUnchangedForBlankText(): void
    {
        $this->assertSame('', AIClient::rewrite(''));
    }

    public function testGenerateReturnsEmptyForBlankPrompt(): void
    {
        $this->assertSame('', AIClient::generate(''));
    }

    public function testBuildMessagesShape(): void
    {
        $this->assertSame([
            ['role' => 'system', 'content' => 'system prompt'],
            ['role' => 'user', 'content' => 'user text'],
        ], AIClient::buildMessages('system prompt', 'user text'));
    }

    public function testParseCompletionExtractsTrimmedContent(): void
    {
        $data = ['choices' => [['message' => ['content' => "  hola mundo \n"]]]];

        $this->assertSame('hola mundo', AIClient::parseCompletion($data));
    }

    public function testParseCompletionReturnsNullForMissingOrBlankContent(): void
    {
        $this->assertNull(AIClient::parseCompletion([]));
        $this->assertNull(AIClient::parseCompletion(['choices' => [['message' => ['content' => '   ']]]]));
        $this->assertNull(AIClient::parseCompletion(['choices' => [['message' => []]]]));
    }
}
```

- [ ] **Step 2: Run to verify the new methods fail**

Run: `vendor/bin/phpunit --filter AIClientTest`
Expected: FAIL — `Call to undefined method DiarioGames\AI\AIClient::buildMessages()`.

- [ ] **Step 3: Implement the extraction in `site/plugins/alv-ai/classes/AIClient.php`**

Add these methods after `generate()`:

```php
    public static function buildMessages(string $systemPrompt, string $userMsg): array
    {
        return [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userMsg],
        ];
    }

    public static function parseCompletion(array $data): ?string
    {
        $content = $data['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            return null;
        }
        return trim($content);
    }
```

In `callOpenCode()`, replace the inline messages array:

```php
        $body = json_encode([
            'model' => 'deepseek-v4-flash',
            'messages' => self::buildMessages($systemPrompt, $userMsg),
        ]);
```

and replace the inline response parsing:

```php
        if ($response && $httpCode === 200) {
            $content = self::parseCompletion(json_decode($response, true) ?? []);
            if ($content !== null) return $content;
        }

        return null;
```

Apply the same two replacements in `callOpenRouter()` (model `openrouter/auto` stays).

- [ ] **Step 4: Run the tests**

Run: `vendor/bin/phpunit --filter AIClientTest`
Expected: `OK (6 tests, 9 assertions)`.

- [ ] **Step 5: Full suite + lint**

Run: `composer test:unit` (expect 70 tests) and `php -l site/plugins/alv-ai/classes/AIClient.php`.

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-ai/classes/AIClient.php tests/phpunit/Unit/Ai/AIClientTest.php
git commit -m "refactor(ai): extract message building and completion parsing"
```

---

### Task 6: Kirby page-render smoke tests

**Files:**
- Create: `tests/phpunit/Integration/PageSmokeTest.php`

- [ ] **Step 1: Preconditions check**

Run: `ls public/assets/.vite/manifest.json || bun run build`
Expected: manifest present (or build completes).

Note (execution reality, 2026-09-17): this project's `.env` contains price-provider keys, so `priceComparison()` would hit live ITAD/G2A/InstantGaming APIs during game-page render. The smoke test therefore stubs the `priceComparison` site method (`Site::$methods`, public static) for the test process only and restores it after — no production change, no network.

- [ ] **Step 2: Write the smoke test**

`tests/phpunit/Integration/PageSmokeTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kirby\Cms\App;
use Kirby\Cms\Site;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;

final class PageSmokeTest extends TestCase
{
    private static App $kirby;
    private static string $tmp;
    private static mixed $originalPriceComparison = false;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 3);

        self::$tmp = sys_get_temp_dir() . '/diario-smoke-' . bin2hex(random_bytes(6));
        foreach (['cache', 'media', 'sessions', 'accounts'] as $dir) {
            mkdir(self::$tmp . '/' . $dir, 0775, true);
        }

        putenv('STEAM_STATS_DB_PATH=' . self::$tmp . '/steam_stats.db');

        self::$kirby = new App([
            'roots' => [
                'index' => $root,
                'content' => $root . '/content',
                'cache' => self::$tmp . '/cache',
                'media' => self::$tmp . '/media',
                'sessions' => self::$tmp . '/sessions',
                'accounts' => self::$tmp . '/accounts',
            ],
            'options' => [
                'debug' => false,
            ],
        ]);

        self::$originalPriceComparison = Site::$methods['priceComparison'] ?? false;
        Site::$methods['priceComparison'] = fn (...$args): array => [];
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$originalPriceComparison === false) {
            unset(Site::$methods['priceComparison']);
        } else {
            Site::$methods['priceComparison'] = self::$originalPriceComparison;
        }

        putenv('STEAM_STATS_DB_PATH');
        Files::removeDir(self::$tmp);
    }

    public function testSearchPageRenders(): void
    {
        $html = self::$kirby->page('search')->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Diario.Games', $html);
    }

    public function testGenrePageRenders(): void
    {
        $games = self::$kirby->site()->find('games')->children()->children()->children()
            ->filterBy('intendedTemplate', 'game');
        $genre = $games->first()->genreList()[0] ?? 'Acción';

        $html = self::$kirby->page('genre')->render(['genreSlug' => $genre]);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString($genre, $html);
    }

    public function testGamePageRenders(): void
    {
        $game = self::$kirby->site()->index()
            ->filterBy('intendedTemplate', 'game')->first();

        $this->assertNotNull($game);

        $html = $game->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString($game->title()->value(), $html);
    }

    public function testArticlePagesRender(): void
    {
        $articles = self::$kirby->site()->index()->filter(function ($page) {
            return in_array($page->intendedTemplate()->name(), ['post', 'guide', 'news'], true);
        });

        if ($articles->count() === 0) {
            $this->markTestSkipped('No article pages in content');
        }

        $html = $articles->first()->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
    }
}
```

- [ ] **Step 3: Run the smoke test**

Run: `vendor/bin/phpunit --filter PageSmokeTest`

Expected: `OK (4 tests, ...)`. If rendering fails, read the exception; typical fixes:
- `manifest.json not found` → run `bun run build`.
- Network attempt from `priceComparison` → price env keys present (see Step 1).
- Missing temp root → ensure all four dirs are created.

Do not weaken assertions to hide a real render error; fix the environment or report NEEDS_CONTEXT.

- [ ] **Step 4: Verify production isolation**

Run:

```bash
php -r '$p=new PDO("sqlite:sqlite/steam_stats.db"); echo "fixtures: ".$p->query("SELECT COUNT(*) FROM store_prices WHERE slug=\"game\"")->fetchColumn()."\n";'
git status --short | head
```

Expected: `fixtures: 0`; no new untracked files under `content/` or `site/cache/`.

- [ ] **Step 5: Full suite**

Run: `composer test`
Expected: all pass — 74 tests, 212 assertions, 1 skipped (no article content locally). Note: `composer test:unit` only runs the `unit` suite; the smoke test lives in the `integration` suite.

- [ ] **Step 6: Commit**

```bash
git add tests/phpunit/Integration/PageSmokeTest.php
git commit -m "test: add kirby page render smoke tests"
```

---

## Plan 2 done when

- `composer test` passes: 74 tests, 212 assertions, 1 skipped (article pages absent locally).
- Production diff contains only `GameImporter.php` and `AIClient.php`, both additive.
- No test writes to `content/`, `sqlite/steam_stats.db`, `storage/`, `media/`, or `site/cache`.

## Next plans

3. Prices & affiliate banners (adapter parser extraction, fixture tests, banner config/placement extraction).
4. Steam stats seams (collector parse extraction, HTTP fakes, `SteamStats` cache wrapper).
5. Kirby route/site-method integration (rankings/search/chart-data routes, fake `exec`).
6. CLI + `.mjs` scraper parser tests.
7. Playwright E2E.
