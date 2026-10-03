# Releases Calendar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the homepage dummy genre grid with an IGDB-powered "Próximos lanzamientos" module plus a `/lanzamientos` calendar page, move genre browsing to `/games` as filter chips, and place affiliate banners on both the homepage and the calendar page.

**Architecture:** A new `alv-releases` Kirby plugin (mirroring `alv-steam-stats` / `alv-twitch-stats`) registers a `Releases` service, a virtual `/lanzamientos` page route, a key-guarded warm route, and a homepage snippet. The service fetches three IGDB queries (upcoming, anticipated by `hypes`, released this week), normalizes them, and caches the dataset for 6 hours in Kirby's file cache. No content pages are created for unreleased games.

**Tech Stack:** PHP 8.1 / Kirby 5, IGDB API v4 (existing `IGDBClient`), Tailwind CSS (markup only), vanilla JS tabs/filters, PHPUnit 11, Playwright, Vitest.

**Spec:** `docs/superpowers/specs/2026-10-02-releases-calendar-design.md`

> **Commit policy:** This workspace requires explicit user approval before any `git commit`. Several tasks end with a commit step; if the user has not asked for commits, skip those steps and keep the working tree dirty.

---

## File Structure

**Create:**
- `site/plugins/alv-releases/index.php` — plugin registration (template, snippet, options, routes, site method)
- `site/plugins/alv-releases/classes/Releases.php` — IGDB fetch, normalization, cache, fixture, warm
- `site/plugins/alv-releases/templates/lanzamientos.php` — calendar page
- `site/plugins/alv-releases/snippets/home-upcoming-releases.php` — homepage module
- `site/plugins/alv-releases/README.md` — options + cron docs
- `scripts/collect-releases.php` — CLI cache warmer
- `tests/phpunit/Unit/Igdb/AllowedPlatformIdsTest.php`
- `tests/phpunit/Unit/Releases/ReleasesTest.php`
- `tests/phpunit/Integration/LanzamientosPageTest.php`
- `tests/phpunit/Integration/LanzamientosWarmRouteTest.php`
- `tests/phpunit/Integration/HomePageReleasesTest.php` (includes empty-fixture case)
- `tests/phpunit/Integration/GamesPageFilterTest.php`
- `tests/e2e/fixtures/releases.json`
- `tests/e2e/specs/releases.spec.js`

**Modify:**
- `site/plugins/alv-igdb/classes/helpers.php` — add `allowedPlatformIds()`
- `site/plugins/alv-igdb/classes/AutoFetcher.php:20-35` — use the shared helper
- `site/config/config.php` — cache entry, warm key, reserved slugs
- `tests/Support/PluginClasses.php` — load `Releases.php`
- `site/templates/home.php` — remove genre grid, add releases module + banner
- `site/controllers/home.php` — drop genre computation
- `site/snippets/header.php` — add "Lanzamientos" link
- `site/templates/games.php` — Spanish H1 + genre filter chips
- `content/games/games.txt` — title + summary for meta
- `tests/e2e/app-server.sh` — export `RELEASES_FIXTURE`
- `tests/e2e/seed-content.php` — give the second e2e game a genre

---

### Task 1: Shared platform whitelist helper

**Files:**
- Modify: `site/plugins/alv-igdb/classes/helpers.php` (append near `deriveYearMonth`)
- Modify: `site/plugins/alv-igdb/classes/AutoFetcher.php:20-35`
- Test: `tests/phpunit/Unit/Igdb/AllowedPlatformIdsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/phpunit/Unit/Igdb/AllowedPlatformIdsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\PluginClasses;

final class AllowedPlatformIdsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testFiltersPlatformsByKeyword(): void
    {
        $client = new FakeIGDBClient();
        $client->platforms = [
            ['id' => 6, 'name' => 'PC (Microsoft Windows)'],
            ['id' => 167, 'name' => 'PlayStation 5'],
            ['id' => 169, 'name' => 'Xbox Series X|S'],
            ['id' => 130, 'name' => 'Nintendo Switch'],
            ['id' => 34, 'name' => 'Android'],
            ['id' => 39, 'name' => 'iOS'],
            ['id' => 82, 'name' => 'Google Stadia'],
        ];

        $this->assertSame([6, 167, 169, 130, 34], \DiarioGames\IGDB\allowedPlatformIds($client));
    }

    public function testReturnsEmptyArrayWhenNothingMatches(): void
    {
        $client = new FakeIGDBClient();
        $client->platforms = [['id' => 82, 'name' => 'Google Stadia']];

        $this->assertSame([], \DiarioGames\IGDB\allowedPlatformIds($client));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite unit --filter AllowedPlatformIdsTest`
Expected: FAIL — `Call to undefined function DiarioGames\IGDB\allowedPlatformIds()`

- [ ] **Step 3: Add the helper**

In `site/plugins/alv-igdb/classes/helpers.php`, append after `deriveYearMonth()`:

```php
function allowedPlatformIds(IGDBClient $client): array
{
    $allowedKeywords = ['pc', 'xbox', 'playstation', 'nintendo', 'android'];
    $ids = [];

    foreach ($client->fetchAllPlatforms() as $platform) {
        $lower = mb_strtolower((string) ($platform['name'] ?? ''));
        foreach ($allowedKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                $ids[] = (int) $platform['id'];
                break;
            }
        }
    }

    return $ids;
}
```

- [ ] **Step 4: Refactor AutoFetcher to use it**

In `site/plugins/alv-igdb/classes/AutoFetcher.php`, replace lines 20–35:

```php
        $allowedKeywords = ['pc', 'xbox', 'playstation', 'nintendo', 'android'];
        $allPlatforms = $this->client->fetchAllPlatforms();
        $allowedPlatformIds = [];
        foreach ($allPlatforms as $p) {
            $lower = mb_strtolower($p['name']);
            foreach ($allowedKeywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $allowedPlatformIds[] = $p['id'];
                    break;
                }
            }
        }

        if (empty($allowedPlatformIds)) {
            throw new \RuntimeException('No allowed platform IDs found from IGDB');
        }
```

with:

```php
        $allowedPlatformIds = allowedPlatformIds($this->client);

        if (empty($allowedPlatformIds)) {
            throw new \RuntimeException('No allowed platform IDs found from IGDB');
        }
```

(`AutoFetcher` is in namespace `DiarioGames\IGDB`, so the unqualified call resolves to the new helper.)

- [ ] **Step 5: Run the unit suite**

Run: `composer test`
Expected: all tests pass (the new test plus the existing `AutoFetcherTest`).

- [ ] **Step 6: Commit (only with user approval)**

```bash
git add site/plugins/alv-igdb/classes/helpers.php site/plugins/alv-igdb/classes/AutoFetcher.php tests/phpunit/Unit/Igdb/AllowedPlatformIdsTest.php
git commit -m "feat(igdb): extract shared allowedPlatformIds helper"
```

---

### Task 2: `Releases` service

**Files:**
- Create: `site/plugins/alv-releases/classes/Releases.php`
- Modify: `tests/Support/PluginClasses.php:36-39`
- Test: `tests/phpunit/Unit/Releases/ReleasesTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/phpunit/Unit/Releases/ReleasesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Releases;

use Alv\Releases\Releases;
use Alv\SteamStats\SteamStatsDB;
use DiarioGames\IGDB\IGDBClient;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class ReleasesTest extends TestCase
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

    private function game(array $overrides = []): array
    {
        return array_merge([
            'id' => 100,
            'name' => 'Upcoming One',
            'slug' => 'upcoming-one',
            'first_release_date' => gmmktime(0, 0, 0, 10, 15, 2026),
            'hypes' => 120,
            'cover' => ['id' => 5, 'image_id' => 'cover_abc'],
            'platforms' => [['abbreviation' => 'PC'], ['abbreviation' => 'PS5']],
            'genres' => [['name' => 'RPG']],
            'release_dates' => [['human' => 'Oct 15, 2026']],
            'category' => 0,
        ], $overrides);
    }

    private function client(array $upcoming = [], array $anticipated = [], array $released = []): IGDBClient
    {
        return new class($upcoming, $anticipated, $released) extends IGDBClient {
            public function __construct(
                private array $upcoming,
                private array $anticipated,
                private array $released
            ) {
                parent::__construct('test-id', 'test-secret');
            }

            public function post(string $endpoint, string $body): array
            {
                return $endpoint === 'platforms'
                    ? [['id' => 6, 'name' => 'PC (Microsoft Windows)']]
                    : [];
            }

            public function fetchGames(array $fields, int $limit = 500, int $offset = 0, string $where = '', string $sort = ''): array
            {
                if ($offset > 0) {
                    return [];
                }
                if ($sort === 'hypes desc') {
                    return array_slice($this->anticipated, 0, $limit);
                }
                if (str_contains($where, 'first_release_date <=')) {
                    return array_slice($this->released, 0, $limit);
                }

                return array_slice($this->upcoming, 0, $limit);
            }
        };
    }

    public function testUpcomingGamesAreNormalizedAndInOrder(): void
    {
        $service = new Releases([], $this->client([
            $this->game(['id' => 1, 'name' => 'First', 'slug' => 'first']),
            $this->game([
                'id' => 2,
                'name' => 'Second',
                'slug' => 'second',
                'first_release_date' => gmmktime(0, 0, 0, 11, 2, 2026),
            ]),
        ]));

        $games = $service->getUpcoming(6);

        $this->assertCount(2, $games);
        $this->assertSame('First', $games[0]['name']);
        $this->assertSame('15 de octubre de 2026', $games[0]['display_date']);
        $this->assertSame('2026-10-15', $games[0]['release_date']);
        $this->assertSame('Octubre 2026', $games[0]['month_label']);
        $this->assertSame('https://images.igdb.com/igdb/image/upload/t_cover_big/cover_abc.jpg', $games[0]['cover_url']);
        $this->assertSame(['PC', 'PS5'], $games[0]['platforms']);
        $this->assertSame(['RPG'], $games[0]['genres']);
        $this->assertNull($games[0]['local_url']);
    }

    public function testUpcomingRespectsLimitAndGroupsByMonth(): void
    {
        $service = new Releases([], $this->client([
            $this->game(['id' => 1, 'name' => 'October', 'slug' => 'october']),
            $this->game([
                'id' => 2,
                'name' => 'November',
                'slug' => 'november',
                'first_release_date' => gmmktime(0, 0, 0, 11, 2, 2026),
            ]),
        ]));

        $this->assertCount(1, $service->getUpcoming(1));

        $months = $service->getMonths();
        $this->assertCount(2, $months);
        $this->assertSame('Octubre 2026', $months[0]['label']);
        $this->assertSame('Noviembre 2026', $months[1]['label']);
        $this->assertSame('November', $months[1]['games'][0]['name']);
    }

    public function testFuzzyQuarterDateUsesHumanLabel(): void
    {
        $service = new Releases([], $this->client([
            $this->game([
                'id' => 3,
                'name' => 'Fuzzy',
                'slug' => 'fuzzy',
                'first_release_date' => gmmktime(0, 0, 0, 1, 1, 2027),
                'release_dates' => [['human' => 'Q1 2027']],
            ]),
        ]));

        $game = $service->getUpcoming(1)[0];

        $this->assertSame('Q1 2027', $game['display_date']);
        $this->assertNull($game['release_date']);
        $this->assertSame('2027-q1', $game['month_key']);
        $this->assertSame('Q1 2027', $game['month_label']);
    }

    public function testYearOnlyDateUsesHumanLabel(): void
    {
        $service = new Releases([], $this->client([
            $this->game([
                'id' => 4,
                'name' => 'Yearly',
                'slug' => 'yearly',
                'first_release_date' => gmmktime(0, 0, 0, 1, 1, 2027),
                'release_dates' => [['human' => '2027']],
            ]),
        ]));

        $game = $service->getUpcoming(1)[0];

        $this->assertSame('2027', $game['display_date']);
        $this->assertSame('2027', $game['month_key']);
        $this->assertSame('2027', $game['month_label']);
    }

    public function testExcludedGamesAreDropped(): void
    {
        $service = new Releases([], $this->client([
            $this->game(['id' => 5, 'name' => 'Battle Pass Pack', 'slug' => 'battle-pass']),
            $this->game(['id' => 6, 'name' => 'Keep Me', 'slug' => 'keep-me']),
        ]));

        $games = $service->getUpcoming(6);

        $this->assertCount(1, $games);
        $this->assertSame('Keep Me', $games[0]['name']);
    }

    public function testLocalUrlResolvedForImportedGames(): void
    {
        $db = new SteamStatsDB();
        $db->upsertGame(990001, 'alpha-quest', 'Alpha Quest', 100);

        $service = new Releases([], $this->client([$this->game()]));

        $this->assertSame('/alpha-quest', $service->getUpcoming(1)[0]['local_url']);
    }

    public function testAnticipatedAndReleasedAreSeparateDatasets(): void
    {
        $service = new Releases([], $this->client(
            [$this->game()],
            [$this->game(['id' => 7, 'name' => 'Hyped', 'slug' => 'hyped', 'hypes' => 999])],
            [$this->game(['id' => 8, 'name' => 'Just Out', 'slug' => 'just-out'])]
        ));

        $this->assertSame('Hyped', $service->getAnticipated(3)[0]['name']);
        $this->assertSame('Just Out', $service->getReleasedThisWeek()[0]['name']);
    }

    public function testFixtureFileBypassesClient(): void
    {
        $fixtureFile = $this->tempDatabaseDir . '/releases-fixture.json';
        file_put_contents($fixtureFile, json_encode([
            'upcoming' => [[
                'igdb_id' => 42,
                'slug' => 'fixture-game',
                'name' => 'Fixture Game',
                'release_date' => '2026-12-01',
                'display_date' => '1 de diciembre de 2026',
                'month_key' => '2026-12',
                'month_label' => 'Diciembre 2026',
                'hypes' => 7,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'anticipated' => [],
            'releasedThisWeek' => [],
        ]));

        $service = new Releases(['fixture_file' => $fixtureFile]);

        $this->assertSame('Fixture Game', $service->getUpcoming(1)[0]['name']);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit --testsuite unit --filter ReleasesTest`
Expected: FAIL — class `Alv\Releases\Releases` not found.

- [ ] **Step 3: Implement the service**

Create `site/plugins/alv-releases/classes/Releases.php`:

```php
<?php

namespace Alv\Releases;

use DiarioGames\IGDB\GameImporter;
use DiarioGames\IGDB\IGDBClient;

class Releases
{
    private const MONTHS = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    private const FIELDS = [
        'name', 'slug', 'first_release_date', 'hypes', 'cover.image_id',
        'platforms.abbreviation', 'genres.name', 'category', 'version_parent',
        'release_dates.human', 'release_dates.date', 'release_dates.platform',
    ];

    private array $settings;
    private ?IGDBClient $client;
    private bool $fixtureLoaded = false;
    private ?array $fixtureData = null;

    public function __construct(array $settings = [], ?IGDBClient $client = null)
    {
        $this->settings = array_merge([
            'client_id' => '',
            'client_secret' => '',
            'cache_ttl' => 21600,
            'fixture_file' => '',
        ], $settings);

        if ($client !== null) {
            $this->client = $client;
            return;
        }

        if ($this->fixture() !== null) {
            $this->client = null;
            return;
        }

        $id = (string) $this->settings['client_id'];
        $secret = (string) $this->settings['client_secret'];
        $this->client = ($id !== '' && $secret !== '') ? new IGDBClient($id, $secret) : null;
    }

    public function getUpcoming(int $limit = 6): array
    {
        return array_slice($this->dataset()['upcoming'], 0, max(0, $limit));
    }

    public function getAnticipated(int $limit = 3): array
    {
        return array_slice($this->dataset()['anticipated'], 0, max(0, $limit));
    }

    public function getReleasedThisWeek(): array
    {
        return $this->dataset()['releasedThisWeek'];
    }

    public function getMonths(): array
    {
        $months = [];

        foreach ($this->dataset()['upcoming'] as $game) {
            $key = $game['month_key'] ?: 'sin-fecha';
            if (!isset($months[$key])) {
                $months[$key] = ['label' => $game['month_label'], 'games' => []];
            }
            $months[$key]['games'][] = $game;
        }

        return array_values($months);
    }

    public function warm(): array
    {
        $dataset = $this->fixture() !== null ? $this->dataset() : $this->fetch();
        $this->cacheSet('dataset', $dataset, (int) $this->settings['cache_ttl']);

        return [
            'upcoming' => count($dataset['upcoming']),
            'anticipated' => count($dataset['anticipated']),
            'releasedThisWeek' => count($dataset['releasedThisWeek']),
        ];
    }

    private function dataset(): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $this->normalizeDatasetShape($fixture);
        }

        $cached = $this->cacheGet('dataset');
        if (is_array($cached)) {
            return $this->normalizeDatasetShape($cached);
        }

        $dataset = $this->fetch();
        if (!empty($dataset['upcoming']) || !empty($dataset['anticipated']) || !empty($dataset['releasedThisWeek'])) {
            $this->cacheSet('dataset', $dataset, (int) $this->settings['cache_ttl']);
        }

        return $dataset;
    }

    private function normalizeDatasetShape(array $dataset): array
    {
        return [
            'upcoming' => is_array($dataset['upcoming'] ?? null) ? $dataset['upcoming'] : [],
            'anticipated' => is_array($dataset['anticipated'] ?? null) ? $dataset['anticipated'] : [],
            'releasedThisWeek' => is_array($dataset['releasedThisWeek'] ?? null) ? $dataset['releasedThisWeek'] : [],
        ];
    }

    private function fetch(): array
    {
        $empty = ['upcoming' => [], 'anticipated' => [], 'releasedThisWeek' => []];

        if ($this->client === null) {
            return $empty;
        }

        try {
            $allowed = \DiarioGames\IGDB\allowedPlatformIds($this->client);
            if (empty($allowed)) {
                return $empty;
            }

            $now = time();
            $platforms = 'platforms = (' . implode(',', $allowed) . ')';
            $base = "version_parent = null & category = 0 & {$platforms}";

            $upcoming = $this->client->fetchGames(
                self::FIELDS,
                200,
                0,
                "first_release_date > {$now} & {$base}",
                'first_release_date asc'
            );

            $anticipated = $this->client->fetchGames(
                self::FIELDS,
                12,
                0,
                "first_release_date > {$now} & hypes > 0 & {$base}",
                'hypes desc'
            );

            $releasedFrom = $now - 7 * 86400;
            $released = $this->client->fetchGames(
                self::FIELDS,
                12,
                0,
                "first_release_date > {$releasedFrom} & first_release_date <= {$now} & {$base}",
                'first_release_date desc'
            );

            return [
                'upcoming' => $this->normalizeMany($upcoming),
                'anticipated' => $this->normalizeMany($anticipated),
                'releasedThisWeek' => $this->normalizeMany($released),
            ];
        } catch (\Throwable $e) {
            error_log('Releases fetch failed: ' . $e->getMessage());
            return $empty;
        }
    }

    private function normalizeMany(array $games): array
    {
        $result = [];

        foreach ($games as $game) {
            if (!is_array($game) || empty($game['name'])) {
                continue;
            }
            if (GameImporter::isExcluded($game)) {
                continue;
            }
            $entry = $this->normalize($game);
            if ($entry !== null) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    private function normalize(array $game): ?array
    {
        $display = $this->displayDate($game);
        if ($display === null) {
            return null;
        }

        $slug = (string) ($game['slug'] ?? '');
        $localSlug = !empty($game['id']) ? \DiarioGames\IGDB\resolveGameByIgdbId((int) $game['id']) : null;

        return [
            'igdb_id' => (int) ($game['id'] ?? 0),
            'slug' => $slug,
            'name' => (string) $game['name'],
            'release_date' => $display['date'],
            'display_date' => $display['label'],
            'month_key' => $display['month_key'],
            'month_label' => $display['month_label'],
            'hypes' => (int) ($game['hypes'] ?? 0),
            'cover_url' => $this->coverUrl($game),
            'platforms' => $this->platforms($game),
            'genres' => $this->genres($game),
            'local_url' => $localSlug ? '/' . $localSlug : null,
        ];
    }

    private function displayDate(array $game): ?array
    {
        $human = '';
        foreach (($game['release_dates'] ?? []) as $releaseDate) {
            if (!is_array($releaseDate)) {
                continue;
            }
            $candidate = trim((string) ($releaseDate['human'] ?? ''));
            if ($candidate !== '') {
                $human = $candidate;
                break;
            }
        }

        if (preg_match('/^(TBA|TBD)$/i', $human)) {
            return ['date' => null, 'label' => 'Fecha por confirmar', 'month_key' => 'sin-fecha', 'month_label' => 'Fecha por confirmar'];
        }

        if (preg_match('/^Q([1-4])\s*(\d{4})?$/i', $human, $m)) {
            $year = ($m[2] ?? '') !== '' ? $m[2] : gmdate('Y');
            $label = 'Q' . $m[1] . ' ' . $year;
            return ['date' => null, 'label' => $label, 'month_key' => $year . '-q' . $m[1], 'month_label' => $label];
        }

        if (preg_match('/^\d{4}$/', $human)) {
            return ['date' => null, 'label' => $human, 'month_key' => $human, 'month_label' => $human];
        }

        $timestamp = isset($game['first_release_date']) ? (int) $game['first_release_date'] : 0;
        if ($timestamp <= 0) {
            if ($human !== '') {
                return ['date' => null, 'label' => $human, 'month_key' => 'sin-fecha', 'month_label' => 'Fecha por confirmar'];
            }
            return null;
        }

        $month = (int) gmdate('n', $timestamp);
        $year = gmdate('Y', $timestamp);

        return [
            'date' => gmdate('Y-m-d', $timestamp),
            'label' => (int) gmdate('j', $timestamp) . ' de ' . mb_strtolower(self::MONTHS[$month]) . ' de ' . $year,
            'month_key' => gmdate('Y-m', $timestamp),
            'month_label' => self::MONTHS[$month] . ' ' . $year,
        ];
    }

    private function coverUrl(array $game): ?string
    {
        $cover = $game['cover'] ?? null;

        if (is_array($cover) && !empty($cover['image_id'])) {
            return \DiarioGames\IGDB\igdbImageUrl((string) $cover['image_id'], 'cover_big');
        }

        if (is_string($cover) && $cover !== '' && !ctype_digit($cover)) {
            return \DiarioGames\IGDB\igdbImageUrl($cover, 'cover_big');
        }

        return null;
    }

    private function platforms(array $game): array
    {
        $names = [];
        foreach (($game['platforms'] ?? []) as $platform) {
            if (!is_array($platform)) {
                continue;
            }
            $abbr = trim((string) ($platform['abbreviation'] ?? ''));
            if ($abbr !== '') {
                $names[$abbr] = true;
            }
        }

        return array_slice(array_keys($names), 0, 4);
    }

    private function genres(array $game): array
    {
        $names = [];
        foreach (($game['genres'] ?? []) as $genre) {
            if (!is_array($genre)) {
                continue;
            }
            $name = trim((string) ($genre['name'] ?? ''));
            if ($name !== '') {
                $names[$name] = true;
            }
        }

        return array_slice(array_keys($names), 0, 2);
    }

    private function fixture(): ?array
    {
        if ($this->fixtureLoaded) {
            return $this->fixtureData;
        }

        $this->fixtureLoaded = true;
        $file = (string) ($this->settings['fixture_file'] ?? '');
        if ($file !== '' && is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            $this->fixtureData = is_array($data) ? $data : null;
        }

        return $this->fixtureData;
    }

    private function cacheGet(string $key)
    {
        $cache = $this->cache();
        if ($cache === null) {
            return null;
        }

        try {
            return $cache->get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cacheSet(string $key, $value, int $ttlSeconds): void
    {
        $cache = $this->cache();
        if ($cache === null) {
            return;
        }

        try {
            $cache->set($key, $value, max(1, (int) ceil($ttlSeconds / 60)));
        } catch (\Throwable $e) {
        }
    }

    private function cache(): ?\Kirby\Cache\Cache
    {
        try {
            if (!function_exists('kirby') || \Kirby\Cms\App::instance(null, true) === null) {
                return null;
            }

            return kirby()->cache('alv/releases.cache');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
```

- [ ] **Step 4: Register the class for tests**

In `tests/Support/PluginClasses.php`, add to the `$files` array after the `alv-igdb` entries (before the closing `];`):

```php
            '/site/plugins/alv-releases/classes/Releases.php',
```

- [ ] **Step 5: Run the unit suite**

Run: `composer test`
Expected: all tests pass, including the eight new `ReleasesTest` cases.

- [ ] **Step 6: Commit (only with user approval)**

```bash
git add site/plugins/alv-releases/classes/Releases.php tests/Support/PluginClasses.php tests/phpunit/Unit/Releases/ReleasesTest.php
git commit -m "feat(releases): add IGDB releases service with cache and fixture support"
```

---

### Task 3: Plugin registration, config, CLI and warm route

**Files:**
- Create: `site/plugins/alv-releases/index.php`
- Create: `scripts/collect-releases.php`
- Modify: `site/config/config.php` (options, cache, reserved slugs)
- Test: `tests/phpunit/Integration/LanzamientosWarmRouteTest.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/phpunit/Integration/LanzamientosWarmRouteTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class LanzamientosWarmRouteTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/releases-warm-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'upcoming' => [[
                'igdb_id' => 1,
                'slug' => 'warm-upcoming',
                'name' => 'Warm Upcoming',
                'release_date' => '2026-11-01',
                'display_date' => '1 de noviembre de 2026',
                'month_key' => '2026-11',
                'month_label' => 'Noviembre 2026',
                'hypes' => 5,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'anticipated' => [],
            'releasedThisWeek' => [],
        ]));

        RouteTestApp::boot([
            'alv.releases.warm-key' => 'releases-test-key',
            'alv.releases.fixture-file' => self::$fixtureFile,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        RouteTestApp::shutdown();
    }

    public function testWarmRejectsWrongKey(): void
    {
        $result = RouteTestApp::call('lanzamientos-warm', ['key' => 'nope'], 'POST');

        $this->assertSame(['error' => 'unauthorized'], $result);
    }

    public function testWarmReturnsCountsWithCorrectKey(): void
    {
        $result = RouteTestApp::call('lanzamientos-warm', ['key' => 'releases-test-key'], 'POST');

        $this->assertSame('ok', $result['status']);
        $this->assertSame(1, $result['counts']['upcoming']);
    }

    public function testReleasesSiteMethodUsesFixture(): void
    {
        $games = RouteTestApp::app()->site()->releases()->getUpcoming(10);

        $this->assertCount(1, $games);
        $this->assertSame('Warm Upcoming', $games[0]['name']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite integration --filter LanzamientosWarmRouteTest`
Expected: FAIL — `releases()` site method missing.

- [ ] **Step 3: Create the plugin registration**

Create `site/plugins/alv-releases/index.php`:

```php
<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/Releases.php';

App::plugin('alv/releases', [
    'snippets' => [
        'home-upcoming-releases' => __DIR__ . '/snippets/home-upcoming-releases.php',
    ],
    'templates' => [
        'lanzamientos' => __DIR__ . '/templates/lanzamientos.php',
    ],
    'routes' => [
        [
            'pattern' => 'lanzamientos',
            'method' => 'GET',
            'action' => function () {
                return \Kirby\Cms\Page::factory([
                    'slug' => 'lanzamientos',
                    'template' => 'lanzamientos',
                    'content' => [
                        'title' => 'Lanzamientos',
                    ],
                ])->render();
            },
        ],
        [
            'pattern' => 'lanzamientos-warm',
            'method' => 'POST',
            'action' => function () {
                $key = get('key');
                $expectedKey = option('alv.releases.warm-key');
                if ($expectedKey && $key !== $expectedKey) {
                    return ['error' => 'unauthorized'];
                }

                $counts = site()->releases()->warm();

                return ['status' => 'ok', 'counts' => $counts];
            },
        ],
    ],
    'siteMethods' => [
        'releases' => function () {
            $igdb = option('igdb') ?? [];

            return new \Alv\Releases\Releases([
                'client_id' => $igdb['client_id'] ?? '',
                'client_secret' => $igdb['client_secret'] ?? '',
                'cache_ttl' => (int) option('alv.releases.cache-ttl', 21600),
                'fixture_file' => (string) option('alv.releases.fixture-file', ''),
            ]);
        },
    ],
]);
```

- [ ] **Step 4: Create the CLI warmer**

Create `scripts/collect-releases.php`:

```php
<?php
/**
 * Warm the upcoming-releases cache.
 *
 * Usage:
 *   php scripts/collect-releases.php
 *
 * Cron: run every 6 hours.
 */

require __DIR__ . '/../kirby/bootstrap.php';

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('RELEASES_WEB_HOST') ?: getenv('STEAM_STATS_WEB_HOST') ?: 'localhost:8888';
}

$kirby = new \Kirby\Cms\App([
    'cli' => true,
]);

$igdb = option('igdb') ?? [];
$releases = new \Alv\Releases\Releases([
    'client_id' => $igdb['client_id'] ?? '',
    'client_secret' => $igdb['client_secret'] ?? '',
    'cache_ttl' => (int) option('alv.releases.cache-ttl', 21600),
    'fixture_file' => (string) option('alv.releases.fixture-file', ''),
]);

$counts = $releases->warm();
echo "Releases warmed: {$counts['upcoming']} upcoming, {$counts['anticipated']} anticipated, {$counts['releasedThisWeek']} released this week.\n";
```

- [ ] **Step 5: Add config options, cache and reserved slugs**

In `site/config/config.php`, add after the `alv.twitch-stats.fixture-file` line:

```php
    'alv.releases.cache-ttl' => (int) env('RELEASES_CACHE_TTL', 21600),
    'alv.releases.warm-key' => env('RELEASES_WARM_KEY', env('STEAM_STATS_WARM_KEY', '')),
    'alv.releases.fixture-file' => env('RELEASES_FIXTURE', ''),
```

Add after the `cache.alv/twitch-stats.cache` block:

```php
    'cache.alv/releases.cache' => [
        'type' => 'file',
        'active' => true,
    ],
```

In the `(:any)` route's reserved list, change:

```php
                $reserved = ['search', 'genre', 'steam-stats', 'twitch-stats', 'error', 'home'];
```

to:

```php
                $reserved = ['search', 'genre', 'steam-stats', 'twitch-stats', 'lanzamientos', 'error', 'home'];
```

In the `(:any)/(:all)` route's reserved list, change:

```php
                $reserved = ['search', 'genre', 'steam-stats', 'twitch-stats', 'error', 'home', 'games'];
```

to:

```php
                $reserved = ['search', 'genre', 'steam-stats', 'twitch-stats', 'lanzamientos', 'error', 'home', 'games'];
```

- [ ] **Step 6: Create placeholder template and snippet**

The plugin references two files that must exist before the route renders. Create minimal versions now; they are fleshed out in Tasks 4–5.

`site/plugins/alv-releases/templates/lanzamientos.php`:

```php
<?php snippet('header') ?>
<h1>Lanzamientos</h1>
<?php snippet('footer') ?>
```

`site/plugins/alv-releases/snippets/home-upcoming-releases.php`:

```php
<?php return; ?>
```

- [ ] **Step 7: Run the integration suite**

Run: `vendor/bin/phpunit --testsuite integration --filter LanzamientosWarmRouteTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Run the whole fast PHP suite**

Run: `composer test`
Expected: all pass.

- [ ] **Step 9: Commit (only with user approval)**

```bash
git add site/plugins/alv-releases site/config/config.php scripts/collect-releases.php tests/phpunit/Integration/LanzamientosWarmRouteTest.php
git commit -m "feat(releases): register plugin, warm route, CLI and config"
```

---

### Task 4: `/lanzamientos` page

**Files:**
- Modify: `site/plugins/alv-releases/templates/lanzamientos.php` (replace placeholder)
- Test: `tests/phpunit/Integration/LanzamientosPageTest.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/phpunit/Integration/LanzamientosPageTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class LanzamientosPageTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/releases-page-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'upcoming' => [
                [
                    'igdb_id' => 1,
                    'slug' => 'october-game',
                    'name' => 'October Game',
                    'release_date' => '2026-10-20',
                    'display_date' => '20 de octubre de 2026',
                    'month_key' => '2026-10',
                    'month_label' => 'Octubre 2026',
                    'hypes' => 40,
                    'cover_url' => null,
                    'platforms' => ['PC', 'PS5'],
                    'genres' => ['RPG'],
                    'local_url' => null,
                ],
                [
                    'igdb_id' => 2,
                    'slug' => 'november-game',
                    'name' => 'November Game',
                    'release_date' => '2026-11-05',
                    'display_date' => '5 de noviembre de 2026',
                    'month_key' => '2026-11',
                    'month_label' => 'Noviembre 2026',
                    'hypes' => 10,
                    'cover_url' => null,
                    'platforms' => ['PC'],
                    'genres' => ['Aventura'],
                    'local_url' => null,
                ],
            ],
            'anticipated' => [[
                'igdb_id' => 3,
                'slug' => 'hyped-game',
                'name' => 'Hyped Game',
                'release_date' => null,
                'display_date' => 'Q1 2027',
                'month_key' => '2027-q1',
                'month_label' => 'Q1 2027',
                'hypes' => 900,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'releasedThisWeek' => [[
                'igdb_id' => 4,
                'slug' => 'just-out',
                'name' => 'Just Out',
                'release_date' => '2026-09-29',
                'display_date' => '29 de septiembre de 2026',
                'month_key' => '2026-09',
                'month_label' => 'Septiembre 2026',
                'hypes' => 3,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['Acción'],
                'local_url' => null,
            ]],
        ]));

        RouteTestApp::boot([
            'alv.releases.fixture-file' => self::$fixtureFile,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        RouteTestApp::shutdown();
    }

    private function renderPage(): string
    {
        return (string) RouteTestApp::call('lanzamientos');
    }

    public function testPageRendersHeadingTabsAndMonthGroups(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('Próximos lanzamientos de videojuegos', $html);
        $this->assertStringContainsString('data-releases-tab="date"', $html);
        $this->assertStringContainsString('data-releases-tab="hype"', $html);
        $this->assertStringContainsString('Octubre 2026', $html);
        $this->assertStringContainsString('Noviembre 2026', $html);
        $this->assertSame(3, substr_count($html, 'data-release-row'));
    }

    public function testAnticipatedTabAndAttribution(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('data-releases-content="hype"', $html);
        $this->assertStringContainsString('Hyped Game', $html);
        $this->assertStringContainsString('Datos de IGDB', $html);
    }

    public function testReleasedStripLinksInternallyAndUpcomingStaysUnlinked(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('/games/by-igdb-id/4', $html);
        $this->assertSame(1, substr_count($html, '/games/by-igdb-id/'));
        $this->assertStringNotContainsString('href="/october-game"', $html);
    }

    public function testJsonLdItemListIsPresent(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"ItemList"', $html);
        $this->assertStringContainsString('"releaseDate":"2026-10-20"', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite integration --filter LanzamientosPageTest`
Expected: FAIL — placeholder page misses tabs/months/JSON-LD.

- [ ] **Step 3: Implement the template**

Replace `site/plugins/alv-releases/templates/lanzamientos.php` with:

```php
<?php snippet('header') ?>

<?php
$releases = site()->releases();
$months = $releases->getMonths();
$anticipated = $releases->getAnticipated(12);
$releasedThisWeek = $releases->getReleasedThisWeek();
$year = date('Y');
$hasEnabledPrograms = site()->alvAffBanners()['enabled'] && !empty(array_filter(site()->alvAffBanners()['programs'], fn($p) => $p['enabled']));
$hasContent = !empty($months) || !empty($anticipated);
?>

<section class="mb-8">
    <h1 class="text-2xl font-bold text-neon-cyan mb-3">Próximos lanzamientos de videojuegos <?= $year ?></h1>
    <p class="text-muted max-w-3xl">
        Calendario de lanzamientos de videojuegos de <?= $year ?> en PC y consolas, actualizado con las fechas
        confirmadas y las ventanas de lanzamiento de los títulos más esperados.
    </p>
</section>

<?php if (!empty($releasedThisWeek)): ?>
<section class="mb-8">
    <h2 class="text-xs uppercase tracking-wider text-neon-green mb-3">Ya disponibles esta semana</h2>
    <div class="flex gap-3 overflow-x-auto pb-2">
        <?php foreach ($releasedThisWeek as $game): ?>
            <a href="<?= htmlspecialchars($game['local_url'] ?? '/games/by-igdb-id/' . $game['igdb_id']) ?>"
               class="flex min-w-56 items-center gap-3 rounded-lg border border-border bg-surface p-2 hover:border-neon-green/50 transition">
                <?php if ($game['cover_url']): ?>
                    <img src="<?= htmlspecialchars($game['cover_url']) ?>" alt="" class="h-14 w-11 shrink-0 rounded object-cover" loading="lazy">
                <?php endif ?>
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-text"><?= htmlspecialchars($game['name']) ?></span>
                    <span class="block text-xs text-neon-green"><?= htmlspecialchars($game['display_date']) ?></span>
                </span>
            </a>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>

<?php if (!$hasContent): ?>
    <p class="text-muted">No hay lanzamientos disponibles en este momento. Vuelve pronto.</p>
<?php else: ?>
<div data-releases-tabs>
    <div class="mb-6 flex gap-2" role="tablist">
        <button type="button" role="tab" aria-selected="true" data-releases-tab="date"
                class="cursor-pointer rounded-lg border border-neon-cyan px-3 py-1.5 text-sm text-neon-cyan transition">
            Por fecha
        </button>
        <button type="button" role="tab" aria-selected="false" data-releases-tab="hype"
                class="cursor-pointer rounded-lg border border-border px-3 py-1.5 text-sm text-muted transition">
            Más esperados
        </button>
    </div>

    <div data-releases-content="date">
        <?php if (empty($months)): ?>
            <p class="text-muted">No hay fechas confirmadas todavía.</p>
        <?php else: ?>
            <?php $i = 0; ?>
            <?php foreach ($months as $month): $i++; ?>
                <section class="mb-8">
                    <h2 class="mb-3 uppercase tracking-wider text-shadow-neon-cyan"><?= htmlspecialchars($month['label']) ?></h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($month['games'] as $game): ?>
                            <article data-release-row class="flex gap-3 rounded-lg border border-border bg-surface p-3">
                                <div class="h-24 w-18 shrink-0 overflow-hidden rounded bg-surface-alt">
                                    <?php if ($game['cover_url']): ?>
                                        <img src="<?= htmlspecialchars($game['cover_url']) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
                                    <?php endif ?>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold leading-tight text-text"><?= htmlspecialchars($game['name']) ?></h3>
                                    <p class="mt-1 text-xs text-neon-cyan"><?= htmlspecialchars($game['display_date']) ?></p>
                                    <?php if (!empty($game['platforms'])): ?>
                                        <p class="mt-1 text-xs text-muted"><?= htmlspecialchars(implode(' · ', $game['platforms'])) ?></p>
                                    <?php endif ?>
                                    <?php if (!empty($game['genres'])): ?>
                                        <p class="mt-1 text-xs text-muted"><?= htmlspecialchars(implode(', ', $game['genres'])) ?></p>
                                    <?php endif ?>
                                </div>
                            </article>
                        <?php endforeach ?>
                    </div>
                </section>
                <?php if ($hasEnabledPrograms): ?>
                    <?php snippet('affiliate-banner', ['grid' => true, 'itemCount' => $i]) ?>
                <?php endif ?>
            <?php endforeach ?>
        <?php endif ?>
    </div>

    <div data-releases-content="hype" class="hidden">
        <?php if (empty($anticipated)): ?>
            <p class="text-muted">No hay datos de juegos más esperados todavía.</p>
        <?php else: ?>
            <ol class="space-y-3">
                <?php foreach ($anticipated as $index => $game): ?>
                    <li data-release-row class="flex items-center gap-3 rounded-lg border border-border bg-surface p-3">
                        <span class="w-6 shrink-0 text-center text-sm font-bold text-neon-magenta"><?= $index + 1 ?></span>
                        <div class="h-16 w-12 shrink-0 overflow-hidden rounded bg-surface-alt">
                            <?php if ($game['cover_url']): ?>
                                <img src="<?= htmlspecialchars($game['cover_url']) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
                            <?php endif ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-text"><?= htmlspecialchars($game['name']) ?></h3>
                            <p class="text-xs text-muted"><?= htmlspecialchars($game['display_date']) ?></p>
                        </div>
                        <span class="shrink-0 text-xs text-neon-magenta"><?= number_format($game['hypes']) ?> seguidores</span>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>
    </div>
</div>

<?php
$listItems = [];
$position = 1;
foreach ($months as $month) {
    foreach ($month['games'] as $game) {
        if (!$game['release_date']) {
            continue;
        }
        $item = [
            '@type' => 'VideoGame',
            'name' => $game['name'],
            'releaseDate' => $game['release_date'],
        ];
        if ($game['cover_url']) {
            $item['image'] = $game['cover_url'];
        }
        if (!empty($game['platforms'])) {
            $item['gamePlatform'] = $game['platforms'];
        }
        $listItems[] = ['@type' => 'ListItem', 'position' => $position++, 'item' => $item];
    }
}
?>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Próximos lanzamientos ' . $year,
    'itemListElement' => $listItems,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif ?>

<p class="mt-8 text-xs text-muted">
    Datos de IGDB — <a href="https://www.igdb.com" target="_blank" rel="noopener" class="text-neon-cyan hover:text-white">igdb.com</a>
</p>

<script>
    (function() {
        var root = document.querySelector('[data-releases-tabs]');
        if (!root || root.hasAttribute('data-releases-ready')) return;
        root.setAttribute('data-releases-ready', '1');

        var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-releases-tab]'));
        var panels = Array.prototype.slice.call(root.querySelectorAll('[data-releases-content]'));

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                var target = button.getAttribute('data-releases-tab');

                buttons.forEach(function(candidate) {
                    var active = candidate === button;
                    candidate.classList.toggle('border-neon-cyan', active);
                    candidate.classList.toggle('text-neon-cyan', active);
                    candidate.classList.toggle('border-border', !active);
                    candidate.classList.toggle('text-muted', !active);
                    candidate.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                panels.forEach(function(panel) {
                    panel.classList.toggle('hidden', panel.getAttribute('data-releases-content') !== target);
                });
            });
        });
    })();
</script>

<?php snippet('footer') ?>
```

- [ ] **Step 4: Run the integration test**

Run: `vendor/bin/phpunit --testsuite integration --filter LanzamientosPageTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit (only with user approval)**

```bash
git add site/plugins/alv-releases/templates/lanzamientos.php tests/phpunit/Integration/LanzamientosPageTest.php
git commit -m "feat(releases): add lanzamientos calendar page"
```

---

### Task 5: Homepage module, controller cleanup and header link

**Files:**
- Modify: `site/plugins/alv-releases/snippets/home-upcoming-releases.php` (replace placeholder)
- Modify: `site/templates/home.php:22-44`
- Modify: `site/controllers/home.php`
- Modify: `site/snippets/header.php` (after the `/steam-stats` anchor)
- Test: `tests/phpunit/Integration/HomePageReleasesTest.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/phpunit/Integration/HomePageReleasesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class HomePageReleasesTest extends TestCase
{
    private static string $fixtureFile;
    private static string $emptyFixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/releases-home-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'upcoming' => [
                [
                    'igdb_id' => 1,
                    'slug' => 'first-upcoming',
                    'name' => 'First Upcoming',
                    'release_date' => '2026-11-01',
                    'display_date' => '1 de noviembre de 2026',
                    'month_key' => '2026-11',
                    'month_label' => 'Noviembre 2026',
                    'hypes' => 10,
                    'cover_url' => null,
                    'platforms' => ['PC'],
                    'genres' => ['RPG'],
                    'local_url' => null,
                ],
                [
                    'igdb_id' => 2,
                    'slug' => 'second-upcoming',
                    'name' => 'Second Upcoming',
                    'release_date' => '2026-12-01',
                    'display_date' => '1 de diciembre de 2026',
                    'month_key' => '2026-12',
                    'month_label' => 'Diciembre 2026',
                    'hypes' => 5,
                    'cover_url' => null,
                    'platforms' => ['PS5'],
                    'genres' => ['Aventura'],
                    'local_url' => null,
                ],
            ],
            'anticipated' => [[
                'igdb_id' => 3,
                'slug' => 'most-hyped',
                'name' => 'Most Hyped',
                'release_date' => null,
                'display_date' => 'Q1 2027',
                'month_key' => '2027-q1',
                'month_label' => 'Q1 2027',
                'hypes' => 900,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'releasedThisWeek' => [],
        ]));

        self::$emptyFixtureFile = sys_get_temp_dir() . '/releases-home-empty-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$emptyFixtureFile, json_encode([
            'upcoming' => [],
            'anticipated' => [],
            'releasedThisWeek' => [],
        ]));

        RouteTestApp::boot([
            'alv.releases.fixture-file' => self::$fixtureFile,
        ]);

        mkdir(RouteTestApp::content('home'), 0775, true);
        file_put_contents(
            RouteTestApp::content('home/home.txt'),
            "Title: Inicio\n\n----\n\nTemplate: home\n"
        );
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        @unlink(self::$emptyFixtureFile);
        RouteTestApp::shutdown();
    }

    public function testHomepageRendersUpcomingModuleAndCalendarLink(): void
    {
        $html = RouteTestApp::app()->page('home')->render();

        $this->assertStringContainsString('data-home-releases', $html);
        $this->assertSame(2, substr_count($html, 'data-home-release '));
        $this->assertStringContainsString('First Upcoming', $html);
        $this->assertStringContainsString('1 de noviembre de 2026', $html);
        $this->assertStringContainsString('Más esperados', $html);
        $this->assertStringContainsString('Most Hyped', $html);
        $this->assertStringContainsString('href="/lanzamientos"', $html);
    }

    public function testModuleHiddenWhenFixtureIsEmpty(): void
    {
        RouteTestApp::app()->cache('alv/releases.cache')->remove('dataset');

        $original = file_get_contents(self::$fixtureFile);
        file_put_contents(self::$fixtureFile, file_get_contents(self::$emptyFixtureFile));

        try {
            $html = RouteTestApp::app()->page('home')->render();
            $this->assertStringNotContainsString('data-home-releases', $html);
        } finally {
            file_put_contents(self::$fixtureFile, $original);
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite integration --filter HomePageReleasesTest`
Expected: FAIL — `data-home-releases` missing (snippet still returns early).

- [ ] **Step 3: Implement the homepage snippet**

Replace `site/plugins/alv-releases/snippets/home-upcoming-releases.php` with:

```php
<?php
$releases = site()->releases();
$upcoming = $releases->getUpcoming(6);
$anticipated = $releases->getAnticipated(3);

if (empty($upcoming) && empty($anticipated)) return;
?>
<div data-home-releases class="bg-surface/50 backdrop-blur-sm border-4 border-border rounded-xl p-4">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="uppercase tracking-wider text-shadow-neon-cyan">Próximos lanzamientos</h2>
        <a href="/lanzamientos" class="shrink-0 text-xs text-neon-cyan transition hover:text-neon-magenta">Ver calendario completo →</a>
    </div>

    <?php if (!empty($upcoming)): ?>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <?php foreach ($upcoming as $game): ?>
            <div data-home-release class="flex items-center gap-3 rounded-lg border border-border bg-surface p-2">
                <div class="h-16 w-12 shrink-0 overflow-hidden rounded bg-surface-alt">
                    <?php if ($game['cover_url']): ?>
                        <img src="<?= htmlspecialchars($game['cover_url']) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
                    <?php endif ?>
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold leading-tight text-text"><?= htmlspecialchars($game['name']) ?></p>
                    <p class="text-xs text-neon-cyan"><?= htmlspecialchars($game['display_date']) ?></p>
                    <?php if (!empty($game['platforms'])): ?>
                        <p class="text-[10px] text-muted"><?= htmlspecialchars(implode(' · ', $game['platforms'])) ?></p>
                    <?php endif ?>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <?php if (!empty($anticipated)): ?>
    <div class="mt-4 border-t border-border pt-3">
        <h3 class="mb-2 text-xs uppercase tracking-wider text-neon-magenta">Más esperados</h3>
        <ol class="space-y-1">
            <?php foreach ($anticipated as $index => $game): ?>
                <li class="flex items-center justify-between gap-2 text-xs">
                    <span class="truncate text-text"><?= $index + 1 ?>. <?= htmlspecialchars($game['name']) ?></span>
                    <span class="shrink-0 text-muted"><?= number_format($game['hypes']) ?> seguidores</span>
                </li>
            <?php endforeach ?>
        </ol>
    </div>
    <?php endif ?>
</div>
```

- [ ] **Step 4: Replace the homepage genre grid**

In `site/templates/home.php`, replace the whole block from `<?php $bannerConfig = site()->alvAffBanners(); ...` through the closing `</div>` of the genre grid with:

```php
<?php snippet('home-upcoming-releases') ?>
<?php snippet('affiliate-banner', ['itemCount' => 1]) ?>
```

The resulting file must keep the charts row and the posts row unchanged. Final content:

```php
<?php snippet('header') ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8" data-home-charts-row>
    <?php snippet('steam-stats-tabs') ?>
    <?php snippet('twitch-stats-tabs') ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8" data-home-posts-row>
    <?php snippet('hero', ['latestPosts' => $latestPosts]) ?>
    <?php if ($latestPosts->count() > 0): ?>
        <?php snippet('home-latest-posts', ['posts' => $latestPosts]) ?>
    <?php else: ?>
        <div class="hidden lg:block" aria-hidden="true"></div>
    <?php endif ?>
</div>

<?php snippet('home-upcoming-releases') ?>
<?php snippet('affiliate-banner', ['itemCount' => 1]) ?>

<?php snippet('footer') ?>
```

- [ ] **Step 5: Clean up the homepage controller**

Replace the body of `site/controllers/home.php` with:

```php
<?php

return function ($site) {
    $games = $site->find('games')->children()->children()->children()->filterBy('intendedTemplate', 'game')->sortBy('title', 'asc');

    $latestPosts = $games->children()
        ->filter(fn($p) => in_array($p->intendedTemplate()->name(), ['news', 'guide'], true))
        ->sortBy('date', 'desc')
        ->limit(5);

    return [
        'latestPosts' => $latestPosts,
    ];
};
```

- [ ] **Step 6: Add the header link**

In `site/snippets/header.php`, immediately after the closing `</a>` of the `/steam-stats` link (the `<a>` wrapping the bar-chart SVG), insert:

```php
                <a href="/lanzamientos" class="text-xs text-neon-cyan hover:text-white">Lanzamientos</a>
```

- [ ] **Step 7: Run the integration tests**

Run: `vendor/bin/phpunit --testsuite integration --filter HomePageReleasesTest`
Expected: PASS (2 tests).

Also run the existing homepage test to catch regressions:

Run: `vendor/bin/phpunit --testsuite integration --filter HomePageTwitchTest`
Expected: PASS.

- [ ] **Step 8: Commit (only with user approval)**

```bash
git add site/plugins/alv-releases/snippets/home-upcoming-releases.php site/templates/home.php site/controllers/home.php site/snippets/header.php tests/phpunit/Integration/HomePageReleasesTest.php
git commit -m "feat(releases): homepage upcoming module replaces genre grid"
```

---

### Task 6: `/games` genre filter and meta cleanup

**Files:**
- Modify: `site/templates/games.php` (replace whole file)
- Modify: `content/games/games.txt`
- Test: `tests/phpunit/Integration/GamesPageFilterTest.php`

- [ ] **Step 1: Write the failing integration test**

Create `tests/phpunit/Integration/GamesPageFilterTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class GamesPageFilterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();

        mkdir(RouteTestApp::content('games'), 0775, true);
        file_put_contents(
            RouteTestApp::content('games/games.txt'),
            "Title: Todos los juegos\n\n----\n\nTemplate: games\n"
        );

        $games = [
            '2024/03/alpha-quest' => 'RPG, Aventura',
            '2024/04/beta-blaster' => 'Acción',
        ];

        foreach ($games as $path => $genres) {
            $dir = RouteTestApp::content('games/' . $path);
            mkdir($dir, 0775, true);
            file_put_contents(
                $dir . '/game.txt',
                "Title: " . ucfirst(basename($path)) . "\n\n----\n\nTemplate: game\n\n----\n\nGenres: {$genres}\n"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testChipsRenderWithCounts(): void
    {
        $html = RouteTestApp::app()->page('games')->render();

        $this->assertStringContainsString('data-genre-filter', $html);
        $this->assertStringContainsString('Todos (2)', $html);
        $this->assertStringContainsString('Acción (1)', $html);
        $this->assertStringContainsString('Aventura (1)', $html);
        $this->assertStringContainsString('RPG (1)', $html);
    }

    public function testCardsExposeGenresForFiltering(): void
    {
        $html = RouteTestApp::app()->page('games')->render();

        $this->assertStringContainsString('data-genre-item', $html);
        $this->assertStringContainsString('data-genres="[&quot;RPG&quot;,&quot;Aventura&quot;]"', $html);
        $this->assertStringContainsString('data-genres="[&quot;Acción&quot;]"', $html);
    }

    public function testSpanishHeading(): void
    {
        $html = RouteTestApp::app()->page('games')->render();

        $this->assertStringContainsString('Todos los juegos', $html);
        $this->assertStringNotContainsString('All Games', $html);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --testsuite integration --filter GamesPageFilterTest`
Expected: FAIL — no `data-genre-filter`.

- [ ] **Step 3: Implement the games template**

Replace `site/templates/games.php` with:

```php
<?php snippet('header') ?>

<?php
$allGames = $page->children()->children()->children()->filterBy('intendedTemplate', 'game')->sortBy('title', 'asc');

$allGenres = [];
foreach ($allGames as $game) {
    foreach ($game->genreList() as $genre) {
        $genre = trim($genre);
        if ($genre === '') continue;
        $allGenres[$genre] = ($allGenres[$genre] ?? 0) + 1;
    }
}
ksort($allGenres);
?>

<h1 class="text-2xl font-bold text-text mb-6">Todos los juegos</h1>

<?php if (count($allGenres) > 1): ?>
<div data-genre-filter class="mb-6 flex flex-wrap gap-2">
    <button type="button" data-genre="*" aria-pressed="true"
            class="cursor-pointer rounded-lg border border-neon-cyan px-3 py-1.5 text-xs text-neon-cyan transition">
        Todos (<?= $allGames->count() ?>)
    </button>
    <?php foreach ($allGenres as $genre => $count): ?>
        <button type="button" data-genre="<?= htmlspecialchars($genre) ?>" aria-pressed="false"
                class="cursor-pointer rounded-lg border border-border px-3 py-1.5 text-xs text-muted transition">
            <?= htmlspecialchars($genre) ?> (<?= $count ?>)
        </button>
    <?php endforeach ?>
</div>
<?php endif ?>

<?php if ($allGames->count() > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($allGames as $game): ?>
        <?php
        $genres = array_values(array_filter($game->genreList()));
        ?>
        <div data-genre-item data-genres="<?= htmlspecialchars(json_encode($genres, JSON_UNESCAPED_UNICODE)) ?>">
            <?php snippet('game-card', ['game' => $game]) ?>
        </div>
    <?php endforeach ?>
</div>

<script>
    (function() {
        var root = document.querySelector('[data-genre-filter]');
        if (!root) return;

        var chips = Array.prototype.slice.call(root.querySelectorAll('[data-genre]'));
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-genre-item]'));

        chips.forEach(function(chip) {
            chip.addEventListener('click', function() {
                var selected = chip.getAttribute('data-genre');

                chips.forEach(function(candidate) {
                    var active = candidate === chip;
                    candidate.classList.toggle('border-neon-cyan', active);
                    candidate.classList.toggle('text-neon-cyan', active);
                    candidate.classList.toggle('border-border', !active);
                    candidate.classList.toggle('text-muted', !active);
                    candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
                });

                items.forEach(function(item) {
                    var genres = [];
                    try {
                        genres = JSON.parse(item.getAttribute('data-genres') || '[]');
                    } catch (e) {}
                    item.classList.toggle('hidden', selected !== '*' && genres.indexOf(selected) === -1);
                });
            });
        });
    })();
</script>
<?php else: ?>
<p class="text-muted">No games added yet.</p>
<?php endif ?>

<?php snippet('footer') ?>
```

- [ ] **Step 4: Fill the games page meta**

Replace `content/games/games.txt` with:

```
Title: Todos los juegos

----

Summary: Catálogo completo de videojuegos de Diario.Games: fichas, guías, noticias y estadísticas de Steam y Twitch.

----

Template: games
```

- [ ] **Step 5: Run the integration test**

Run: `vendor/bin/phpunit --testsuite integration --filter GamesPageFilterTest`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit (only with user approval)**

```bash
git add site/templates/games.php content/games/games.txt tests/phpunit/Integration/GamesPageFilterTest.php
git commit -m "feat(games): genre filter chips and meta cleanup"
```

---

### Task 7: E2E coverage and docs

**Files:**
- Create: `tests/e2e/fixtures/releases.json`
- Create: `tests/e2e/specs/releases.spec.js`
- Modify: `tests/e2e/app-server.sh` (environment)
- Modify: `tests/e2e/seed-content.php` (second game gets a genre for chips)
- Create: `site/plugins/alv-releases/README.md`

- [ ] **Step 1: Create the e2e fixture**

Create `tests/e2e/fixtures/releases.json`:

```json
{
  "upcoming": [
    {
      "igdb_id": 5001,
      "slug": "neon-drift",
      "name": "Neon Drift",
      "release_date": "2026-11-15",
      "display_date": "15 de noviembre de 2026",
      "month_key": "2026-11",
      "month_label": "Noviembre 2026",
      "hypes": 321,
      "cover_url": null,
      "platforms": ["PC", "PS5"],
      "genres": ["Carreras"],
      "local_url": null
    },
    {
      "igdb_id": 5002,
      "slug": "starfall",
      "name": "Starfall",
      "release_date": "2026-11-28",
      "display_date": "28 de noviembre de 2026",
      "month_key": "2026-11",
      "month_label": "Noviembre 2026",
      "hypes": 150,
      "cover_url": null,
      "platforms": ["PC"],
      "genres": ["RPG"],
      "local_url": null
    },
    {
      "igdb_id": 5003,
      "slug": "shadow-realm",
      "name": "Shadow Realm",
      "release_date": "2026-12-10",
      "display_date": "10 de diciembre de 2026",
      "month_key": "2026-12",
      "month_label": "Diciembre 2026",
      "hypes": 90,
      "cover_url": null,
      "platforms": ["Xbox"],
      "genres": ["Aventura"],
      "local_url": null
    }
  ],
  "anticipated": [
    {
      "igdb_id": 5004,
      "slug": "most-wanted",
      "name": "Most Wanted",
      "release_date": null,
      "display_date": "Q2 2027",
      "month_key": "2027-q2",
      "month_label": "Q2 2027",
      "hypes": 5000,
      "cover_url": null,
      "platforms": ["PC"],
      "genres": ["RPG"],
      "local_url": null
    }
  ],
  "releasedThisWeek": [
    {
      "igdb_id": 2,
      "slug": "e2e-second",
      "name": "E2E Second",
      "release_date": "2024-04-01",
      "display_date": "1 de abril de 2024",
      "month_key": "2024-04",
      "month_label": "Abril 2024",
      "hypes": 1,
      "cover_url": null,
      "platforms": ["PC"],
      "genres": ["Acción"],
      "local_url": null
    }
  ]
}
```

- [ ] **Step 2: Create the e2e spec**

Create `tests/e2e/specs/releases.spec.js`:

```js
import { test, expect } from '@playwright/test'

test('homepage shows upcoming releases and links to the calendar', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' })

  const module = page.locator('[data-home-releases]')
  await expect(module).toBeVisible()
  await expect(module.locator('[data-home-release]')).toHaveCount(3)
  await expect(module.getByRole('link', { name: /Ver calendario completo/ })).toHaveAttribute('href', '/lanzamientos')
  await expect(module).toContainText('Neon Drift')
  await expect(module).toContainText('Más esperados')
})

test('releases page groups by month and switches tabs', async ({ page }) => {
  await page.goto('/lanzamientos', { waitUntil: 'domcontentloaded' })

  await expect(page.locator('h1')).toContainText('Próximos lanzamientos de videojuegos')

  const dateRows = page.locator('[data-releases-content="date"] [data-release-row]')
  await expect(dateRows).toHaveCount(3)
  await expect(page.locator('[data-releases-content="date"]')).toContainText('Noviembre 2026')
  await expect(page.locator('[data-releases-content="date"]')).toContainText('Diciembre 2026')

  await page.locator('[data-releases-tab="hype"]').click()
  await expect(page.locator('[data-releases-content="hype"] [data-release-row]')).toHaveCount(1)
  await expect(page.locator('[data-releases-content="hype"]')).toContainText('Most Wanted')

  await expect(page.locator('text=Datos de IGDB')).toBeVisible()
})

test('games page filters cards by genre chip', async ({ page }) => {
  await page.goto('/games', { waitUntil: 'domcontentloaded' })

  await expect(page.locator('[data-genre-item]')).toHaveCount(2)

  await page.locator('[data-genre="RPG"]').click()
  await expect(page.locator('[data-genre-item]:visible')).toHaveCount(1)
  await expect(page.locator('[data-genre-item]:visible')).toContainText('E2E Game')

  await page.locator('[data-genre="*"]').click()
  await expect(page.locator('[data-genre-item]:visible')).toHaveCount(2)
})
```

- [ ] **Step 3: Wire the fixture into the e2e server**

In `tests/e2e/app-server.sh`, inside the final `exec env \` block, add after the `TWITCH_STATS_FIXTURE=...` line:

```
  RELEASES_FIXTURE="$ROOT/tests/e2e/fixtures/releases.json" \
```

- [ ] **Step 4: Give the second e2e game a genre**

In `tests/e2e/seed-content.php`, change:

```php
    '2024/04/e2e-second' => [
        'Title' => 'E2E Second',
        'ReleaseDate' => '2024-04-01',
        'IgdbId' => '2',
        'Screenshots' => 'shot_1',
    ],
```

to:

```php
    '2024/04/e2e-second' => [
        'Title' => 'E2E Second',
        'ReleaseDate' => '2024-04-01',
        'IgdbId' => '2',
        'Genres' => 'Acción',
        'Screenshots' => 'shot_1',
    ],
```

- [ ] **Step 5: Run the e2e suite**

Run: `bun run test:e2e`
Expected: 10 tests pass (7 existing + 3 new).

- [ ] **Step 6: Write the plugin README**

Create `site/plugins/alv-releases/README.md`:

```markdown
# alv-releases

Upcoming video game releases for diario.games, sourced from the IGDB API.

## What it provides

- `site()->releases()` — `Alv\Releases\Releases` service
- `/lanzamientos` — releases calendar page (tabs: Por fecha / Más esperados)
- `home-upcoming-releases` snippet — homepage module
- `POST /lanzamientos-warm?key=...` — refreshes the cached dataset

## Options

| Option | Env | Default | Description |
|---|---|---|---|
| `alv.releases.cache-ttl` | `RELEASES_CACHE_TTL` | `21600` | Dataset cache TTL in seconds |
| `alv.releases.warm-key` | `RELEASES_WARM_KEY` | falls back to `STEAM_STATS_WARM_KEY` | Key required by the warm route |
| `alv.releases.fixture-file` | `RELEASES_FIXTURE` | `''` | JSON fixture that bypasses IGDB (used by tests) |

## Cron

Warm the cache every 6 hours:

```bash
php scripts/collect-releases.php
```

or, when HTTP cron is preferred:

```bash
curl -X POST "https://diario.games/lanzamientos-warm?key=$RELEASES_WARM_KEY"
```

## Data

Three IGDB queries are cached together:

- upcoming releases (`first_release_date > now`, main games only)
- most anticipated (`hypes > 0`, sorted by `hypes desc`)
- released in the last 7 days

Unreleased games are never linked to internal pages. Released games link to
their diario.games page or fall back to `/games/by-igdb-id/{id}`.

Attribution ("Datos de IGDB") is rendered on `/lanzamientos` as required by IGDB.
```

- [ ] **Step 7: Run the full fast suite**

Run: `bun run test:all`
Expected: Vitest (30), PHPUnit (151 + new tests), Playwright (10) all pass.

- [ ] **Step 8: Commit (only with user approval)**

```bash
git add tests/e2e/fixtures/releases.json tests/e2e/specs/releases.spec.js tests/e2e/app-server.sh tests/e2e/seed-content.php site/plugins/alv-releases/README.md
git commit -m "test(releases): e2e coverage and plugin docs"
```

---

## Self-Review Notes

- **Spec coverage:** homepage module (Task 5), `/lanzamientos` with tabs/JSON-LD/attribution/affiliate banners (Task 4), IGDB datasets with date precision and `isExcluded` (Task 2), cache + warm route + CLI + cron docs (Task 3, 7), `/games` chips + meta (Task 6), header link (Task 5), no external links (parts render as `<article>`/`<div>`, only the released strip links internally), error handling via `try/catch` + empty states (Tasks 2, 4, 5), tests at all three levels (Tasks 1–7).
- **Type consistency:** `Releases` public API (`getUpcoming`, `getAnticipated`, `getReleasedThisWeek`, `getMonths`, `warm`) is used identically in templates, snippets and tests. Normalized keys (`igdb_id`, `display_date`, `month_key`, `month_label`, `cover_url`, `platforms`, `genres`, `local_url`) are consistent across tasks and fixtures.
- **Affiliate banners:** `/lanzamientos` inserts a grid banner after every month group (matches the old homepage pattern); the homepage calls a standalone banner with `itemCount => 1` per the spec decision "both places".
- **No placeholders:** every code step contains the full file content or exact replacement.

---

## Revision 2 — three columns, filters, and the live-API fix

**Trigger:** the live `/lanzamientos` page showed no games. Live IGDB queries revealed `category = 0` matches nothing (IGDB stores `category` as `null` for main games), and the homepage module needed the three IGDB-style lists in columns.

**Changes:**

1. **`Releases` service** — queries now use `(category = 0 | category = null)`; datasets are `recentlyReleased` (last 30 days, `hypes desc`, limit 100), `upcoming` (notable only, date asc, limit 200) and `anticipated` (`hypes desc`, limit 50); cache key bumped to `dataset-v3`.
2. **Notable upcoming** — `getNotableUpcoming()` keeps `hypes >= alv.releases.notable-hypes` (default 20) and removes games already in `anticipated`.
3. **Homepage** — `home-upcoming-releases.php` renders three columns (Recién lanzados / Próximos lanzamientos / Más esperados), 8 per column; only released games are linked.
4. **`/lanzamientos`** — tabs/month groups replaced by three sections plus genre filter chips (same client-side pattern as `/games`); empty sections auto-hide.
5. **Tests** — `RecordingReleasesClient` (new `tests/Support/`) records `where` clauses to guard the NULL-aware filter; unit, integration and e2e tests updated; e2e fixture renamed to the new dataset keys.

**Verification:** PHPUnit, Vitest and Playwright suites green; live IGDB queries return data (GTA VI, Fable, The Witcher IV, Marvel's Wolverine, Control Resonant).
