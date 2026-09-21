# Kirby Routes & Site Methods — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Lock in the `steam-stats-api/*` HTTP routes and the `steamChartData` site method with hermetic integration tests: booted Kirby with temp roots/content/DB, injected chart spawner, no live APIs, no background processes.

**Architecture:** A reusable `Tests\Support\RouteTestApp` boots a Kirby `App` with temp cache/media/sessions/accounts roots, a temp content tree, an empty IGDB config, and `STEAM_STATS_DB_PATH` pointed at a temp SQLite file; `RouteTestApp::call()` dispatches routes with query params by cloning the app with a fresh `Request`. The only production change is an option seam for the rankings spawn: `alv.steam-stats.charts-spawner` (callable) replaces the `exec()` call when provided.

**Tech Stack:** PHP 8.4, PHPUnit 11, Kirby 5, PDO SQLite.

**Spec:** `docs/superpowers/specs/2026-09-17-regression-test-suite-design.md` (Phase 3, routes/site-methods portion). CLI + `.mjs` parsers are Plan 6; E2E Plan 7.

**Scope decisions (YAGNI):**
- Deferred: the `games/by-appid` config route and the catch-all slug import route (they need a redirect-capture harness and IGDB fakes already unit-tested in Plan 2); `steam-stats-warm`/`steam-stats-update-history` (they call live Steam paths; the underlying methods are covered in Plan 4); `stats-most-played`/`trending-growth` cache pre-warming.
- `steam-stats-api/collect` requires the warm key and a live collector — skipped.

**Prerequisites:** Plans 1–4 merged. Run tests from `diario.games/`. Never write to `content/`, `sqlite/steam_stats.db`, `storage/`, `media/`, `site/cache`.

**Execution notes (2026-09-17):** The committed code is authoritative; deviations from the snippets below:
- `RouteTestApp::call()` swaps the cached `App::$request` via a `ReflectionProperty` instead of `$app->clone(['request' => null])`. Cloning constructs a new `App` mid-test, which re-registers error/exception handlers and makes PHPUnit mark every test risky.
- The rankings red-phase run must hold `/tmp/steamdb-charts-browser.lock`; without the spawner seam an un-held lock would launch real background `php` processes.
- The stale-chunk test requests chunk 0 (rank 1) because the route derives `maxChunk` from the total entry count, so a high sparse rank is treated as out of range.
- Actual counts: rankings 6/24, search 5/23, game data 4/34, import 5/10; full suite after Plan 5 = 146 tests / 461 assertions / 1 skipped.

---

### Task 1: Route harness + rankings freshness matrix

**Files:**
- Create: `tests/Support/RouteTestApp.php`
- Create: `tests/phpunit/Integration/RankingsRouteTest.php`
- Modify: `site/plugins/alv-steam-stats/index.php` (rankings route spawn)

- [x] **Step 1: Create the route harness**

`tests/Support/RouteTestApp.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Kirby\Cms\App;

final class RouteTestApp
{
    public static array $spawns = [];
    private static ?App $app = null;
    private static string $tmp = '';

    public static function boot(array $options = []): void
    {
        PluginClasses::load();

        self::$tmp = sys_get_temp_dir() . '/diario-routes-' . bin2hex(random_bytes(6));
        foreach (['cache', 'media', 'sessions', 'accounts', 'content'] as $dir) {
            mkdir(self::$tmp . '/' . $dir, 0775, true);
        }
        file_put_contents(self::$tmp . '/content/site.txt', "Title: Test Site\n");

        putenv('STEAM_STATS_DB_PATH=' . self::$tmp . '/steam_stats.db');

        self::$app = new App([
            'roots' => [
                'index' => dirname(__DIR__, 2),
                'content' => self::$tmp . '/content',
                'cache' => self::$tmp . '/cache',
                'media' => self::$tmp . '/media',
                'sessions' => self::$tmp . '/sessions',
                'accounts' => self::$tmp . '/accounts',
            ],
            'options' => array_replace_recursive([
                'debug' => false,
                'igdb' => ['client_id' => '', 'client_secret' => ''],
                'alv.steam-stats.charts-ttl' => 900,
                'alv.steam-stats.charts-spawner' => function (int $chunk): void {
                    self::$spawns[] = $chunk;
                },
            ], $options),
        ]);
    }

    public static function call(string $path, array $query = [], string $method = 'GET'): mixed
    {
        $_GET = $query;
        $app = self::$app->clone(['request' => null]);
        $result = $app->call($path, $method);
        $_GET = [];

        return $result;
    }

    public static function app(): App
    {
        return self::$app;
    }

    public static function tmp(): string
    {
        return self::$tmp;
    }

    public static function content(string $path = ''): string
    {
        return self::$tmp . '/content' . ($path !== '' ? '/' . $path : '');
    }

    public static function shutdown(): void
    {
        putenv('STEAM_STATS_DB_PATH');
        Files::removeDir(self::$tmp);
        self::$app = null;
        self::$spawns = [];
    }
}
```

- [x] **Step 2: Write the failing rankings tests**

`tests/phpunit/Integration/RankingsRouteTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class RankingsRouteTest extends TestCase
{
    private const LOCK = '/tmp/steamdb-charts-browser.lock';

    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    protected function setUp(): void
    {
        @unlink(self::LOCK);
        RouteTestApp::$spawns = [];
        RouteTestApp::app()->cache('alv/steam-stats.cache')->remove('charts-spawn');
    }

    private function db(): SteamStatsDB
    {
        return new SteamStatsDB(RouteTestApp::tmp() . '/steam_stats.db');
    }

    public function testMissingChunkTriggersSpawnAndReportsPending(): void
    {
        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

        $this->assertSame(0, $result['chunk']);
        $this->assertSame('missing', $result['status']);
        $this->assertFalse($result['fresh']);
        $this->assertSame(900, $result['ttl']);
        $this->assertTrue($result['refresh_pending']);
        $this->assertSame([], $result['rows']);
        $this->assertSame([0], RouteTestApp::$spawns);
    }

    public function testFreshChunkServesRowsWithoutSpawning(): void
    {
        $db = $this->db();
        $db->replaceChartEntries([
            ['rank' => 101, 'appid' => 501, 'name' => 'Fresh Game', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
        ], time());
        $db->markChartChunksFresh(101, time());

        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 1]);

        $this->assertSame('fresh', $result['status']);
        $this->assertTrue($result['fresh']);
        $this->assertFalse($result['refresh_pending']);
        $this->assertSame([], RouteTestApp::$spawns);
        $this->assertSame(501, (int)$result['rows'][0]['appid']);
    }

    public function testStaleChunkTriggersSpawn(): void
    {
        $db = $this->db();
        $db->replaceChartEntries([
            ['rank' => 1, 'appid' => 502, 'name' => 'Stale Game', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
        ], time() - 10000);
        $db->markChartChunksFresh(1, time() - 10000);

        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

        $this->assertFalse($result['fresh']);
        $this->assertTrue($result['refresh_pending']);
        $this->assertSame([0], RouteTestApp::$spawns);
        $this->assertSame(502, (int)$result['rows'][0]['appid']);
    }

    public function testLockedRefreshDoesNotSpawn(): void
    {
        touch(self::LOCK);

        try {
            $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

            $this->assertTrue($result['refresh_pending']);
            $this->assertSame([], RouteTestApp::$spawns);
        } finally {
            @unlink(self::LOCK);
        }
    }

    public function testSpawnCooldownSuppressesSpawn(): void
    {
        RouteTestApp::app()->cache('alv/steam-stats.cache')
            ->set('charts-spawn', ['value' => time(), 'timestamp' => time()]);

        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

        $this->assertFalse($result['refresh_pending']);
        $this->assertSame([], RouteTestApp::$spawns);
    }

    public function testChunkBeyondMaxNeverSpawns(): void
    {
        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 9]);

        $this->assertSame('missing', $result['status']);
        $this->assertFalse($result['refresh_pending']);
        $this->assertSame([], RouteTestApp::$spawns);
        $this->assertSame([], $result['rows']);
    }
}
```

Note: tests run in declaration order. Test 2 leaves entries covering chunk 0, so chunk 9 is out of range in the final test. The stale test uses rank 1 (chunk 0) because `maxChunk` derives from the total entry count, not the requested chunk.

- [x] **Step 3: Run to verify red**

Run: `vendor/bin/phpunit --filter RankingsRouteTest`
Expected: FAIL. Without the spawner option the route calls `@exec(...)` for chunks 0 and 2 — check `RouteTestApp::$spawns` stays empty (no assertion passes), and `refresh_pending` is still true. The `@` suppresses errors; no visible side effects besides a spawned `php` process per stale call. To avoid spawning during the red run, keep the lock file touched:

```bash
touch /tmp/steamdb-charts-browser.lock && vendor/bin/phpunit --filter RankingsRouteTest; rm -f /tmp/steamdb-charts-browser.lock
```

Expected: the first assertion (`assertSame([0], RouteTestApp::$spawns)`) fails; no process is spawned because the lock is present.

- [x] **Step 4: Add the spawner option to the route**

In `site/plugins/alv-steam-stats/index.php`, replace the spawn block (lines 93-109) with:

```php
                    } elseif ($cooldownOk) {
                        $spawner = option('alv.steam-stats.charts-spawner');

                        if (is_callable($spawner)) {
                            $spawner($chunk);
                        } else {
                            $php = PHP_BINDIR . '/php';
                            if (!is_executable($php)) $php = PHP_BINARY;
                            if (!is_executable($php)) $php = 'php';

                            $script = dirname(__DIR__, 3) . '/scripts/collect-steam-stats.php';
                            $cmd = 'nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($script)
                                . ' charts ' . $chunk . ' > /dev/null 2>&1 &';
                            @exec($cmd);
                        }

                        try {
                            kirby()->cache('alv/steam-stats.cache')
                                ->set('charts-spawn', ['value' => time(), 'timestamp' => time()]);
                        } catch (\Throwable $e) {}

                        $refreshPending = true;
                    }
```

- [x] **Step 5: Run green + full suite + lint**

Run: `vendor/bin/phpunit --filter RankingsRouteTest` → `OK (6 tests, 24 assertions)`.
Run: `composer test`, `php -l site/plugins/alv-steam-stats/index.php`.

- [x] **Step 6: Commit**

```bash
git add site/plugins/alv-steam-stats/index.php tests/Support/RouteTestApp.php tests/phpunit/Integration/RankingsRouteTest.php
git commit -m "test: cover rankings route freshness matrix"
```

---

### Task 2: Search route tests

**Files:**
- Create: `tests/phpunit/Integration/SearchRouteTest.php`

No production changes. The harness from Task 1 is reused; fixture content is written per class.

- [x] **Step 1: Write the tests**

`tests/phpunit/Integration/SearchRouteTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class SearchRouteTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();

        mkdir(RouteTestApp::content('games/2024/03/alpha-quest'), 0775, true);
        file_put_contents(RouteTestApp::content('games/2024/03/alpha-quest/game.txt'), <<<'TXT'
Title: Alpha Quest

----

Template: game

----

Summary: A quest.

----

ReleaseDate: 2024-03-15

----

Platforms: PC (Microsoft Windows), PlayStation 5

----

IgdbId: 111

----

Screenshots: shot_1

----

Websites: 1:https://store.steampowered.com/app/570/
TXT);

        $db = new SteamStatsDB(RouteTestApp::tmp() . '/steam_stats.db');
        $db->upsertGame(570, 'alpha-quest', 'Alpha Quest', 111);
        $db->upsertGame(998, 'beta-blaster', 'Beta Blaster', 222);
        $db->replaceChartEntries([
            ['rank' => 1, 'appid' => 999, 'name' => 'Gamma Racer', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
        ], time());
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testLocalPageSearchReturnsExistsAndSteamFlags(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'alpha']);

        $this->assertFalse($result['fromIgdb']);
        $this->assertCount(1, $result['results']);

        $entry = $result['results'][0];
        $this->assertSame('alpha-quest', $entry['slug']);
        $this->assertSame('Alpha Quest', $entry['name']);
        $this->assertTrue($entry['exists']);
        $this->assertTrue($entry['hasSteam']);
        $this->assertSame('2024', $entry['year']);
        $this->assertSame('PC, PS 5', $entry['platforms']);
    }

    public function testTrackedSteamGameSearchFallsBackToDatabase(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'beta']);

        $this->assertCount(1, $result['results']);

        $entry = $result['results'][0];
        $this->assertSame('beta-blaster', $entry['slug']);
        $this->assertSame('Beta Blaster', $entry['name']);
        $this->assertTrue($entry['hasSteam']);
        $this->assertFalse($entry['exists']);
        $this->assertSame(
            'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/998/library_600x900.jpg',
            $entry['cover']
        );
    }

    public function testChartEntrySearchReturnsSlugifiedName(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'gamma']);

        $this->assertCount(1, $result['results']);

        $entry = $result['results'][0];
        $this->assertSame('gamma-racer', $entry['slug']);
        $this->assertSame('Gamma Racer', $entry['name']);
        $this->assertFalse($entry['hasSteam']);
        $this->assertFalse($entry['exists']);
    }

    public function testIgdbSourceWithoutCredentialsReturnsEmpty(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'zzz', 'source' => 'igdb']);

        $this->assertTrue($result['fromIgdb']);
        $this->assertSame([], $result['results']);
    }

    public function testEmptyQueryReturnsEmptyResults(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => '']);

        $this->assertSame([], $result['results']);
        $this->assertFalse($result['fromIgdb']);
    }
}
```

- [x] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter SearchRouteTest`
Expected: `OK (5 tests, 23 assertions)`. If a platform string differs, check `normalizePlatformNames` output before adjusting anything.

- [x] **Step 3: Full suite + commit**

```bash
composer test
git add tests/phpunit/Integration/SearchRouteTest.php
git commit -m "test: cover steam search route with fixture content and db"
```

---

### Task 3: Game data route + `steamChartData` parity

**Files:**
- Create: `tests/phpunit/Integration/GameDataRouteTest.php`

No production changes.

- [x] **Step 1: Write the tests**

`tests/phpunit/Integration/GameDataRouteTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class GameDataRouteTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();

        mkdir(RouteTestApp::content('games/2024/03/alpha-quest'), 0775, true);
        file_put_contents(RouteTestApp::content('games/2024/03/alpha-quest/game.txt'), <<<'TXT'
Title: Alpha Quest

----

Template: game

----

ReleaseDate: 2024-03-15

----

IgdbId: 111

----

Screenshots: shot_1
TXT);

        $db = new SteamStatsDB(RouteTestApp::tmp() . '/steam_stats.db');
        $db->upsertGame(570, 'alpha-quest', 'Alpha Quest', 111);

        $now = time();
        $db->insertPlayerCount(570, $now - 2 * 86400 - 60, 10);
        $db->insertPlayerCount(570, $now - 86400, 20);
        $db->insertPlayerCount(570, $now - 3600, 30);
        $db->insertPlayerCount(570, $now - 300, 40);
        $db->upsertGamePeak(570, 999, $now - 10 * 86400);
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    private function assertRangesMatch(array $expected, array $actual): void
    {
        $this->assertSame(array_keys($expected), array_keys($actual));
        foreach ($expected as $key => $points) {
            $this->assertSame($points, $actual[$key], "Range {$key} differs");
        }
    }

    public function testGameDataRouteReturnsPlayersAndRanges(): void
    {
        $result = RouteTestApp::call('steam-stats-api/game/alpha-quest/data');

        $this->assertSame('alpha-quest', $result['game']['slug']);
        $this->assertSame(40, $result['current']);
        $this->assertSame(40, $result['peak_24h']);
        $this->assertSame(40, $result['peak_3m']);
        $this->assertSame(999, $result['peak_all_time']);

        $this->assertCount(11, $result['ranges']);
        $this->assertArrayHasKey('48h', $result['ranges']);
        $this->assertArrayHasKey('max', $result['ranges']);

        $hourly = $result['ranges']['48h'];
        $this->assertSame(20, (int)$hourly[0]['p']);
        $this->assertSame(30, (int)$hourly[1]['p']);
        $this->assertSame(40, (int)$hourly[2]['p']);
    }

    public function testDailyRangeDropsIncompleteCurrentPeriod(): void
    {
        $result = RouteTestApp::call('steam-stats-api/game/alpha-quest/data');

        $points = $result['ranges']['1m'];
        $this->assertNotEmpty($points);

        $todayStart = strtotime('today 00:00:00');
        $this->assertLessThan($todayStart, (int)end($points)['timestamp']);
    }

    public function testGameDataRouteReturnsErrorForUnknownSlug(): void
    {
        $result = RouteTestApp::call('steam-stats-api/game/does-not-exist/data');

        $this->assertSame(['error' => 'not found'], $result);
    }

    public function testSteamChartDataSiteMethodMatchesRouteRanges(): void
    {
        $route = RouteTestApp::call('steam-stats-api/game/alpha-quest/data');
        $method = RouteTestApp::app()->site()->steamChartData('alpha-quest');

        $this->assertNotNull($method);
        $this->assertSame($route['current'], $method['current']);
        $this->assertSame($route['peak_24h'], $method['peak_24h']);
        $this->assertSame($route['peak_3m'], $method['peak_3m']);
        $this->assertSame($route['peak_all_time'], $method['peak_all_time']);
        $this->assertRangesMatch($route['ranges'], $method['ranges']);

        $this->assertArrayHasKey('available_tabs', $method);
        $this->assertArrayHasKey('peak_all_time_age', $method);
        $this->assertArrayNotHasKey('available_tabs', $route);
    }
}
```

Note: `steamChartData` calls `page('games/2024/03/alpha-quest')` and `kirby()->cache(...)`; the fixture content provides the page, and the booted app has a temp cache root.

- [x] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter GameDataRouteTest`
Expected: `OK (4 tests, 25+ assertions)`. If `steamChartData` tries the live API, the seeded `current` (40) prevents the fallback — do not weaken assertions; investigate instead.

- [x] **Step 3: Full suite + commit**

```bash
composer test
git add tests/phpunit/Integration/GameDataRouteTest.php
git commit -m "test: cover game data route and chart-data parity"
```

---

### Task 4: Import routes + capsule media asset

**Files:**
- Create: `tests/phpunit/Integration/ImportRoutesTest.php`

No production changes.

- [x] **Step 1: Write the tests**

`tests/phpunit/Integration/ImportRoutesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kirby\Http\Response;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class ImportRoutesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testImportGameRequiresSlug(): void
    {
        $result = RouteTestApp::call('steam-stats-api/import-game');

        $this->assertSame(['error' => 'slug required'], $result);
    }

    public function testImportGameWithoutIgdbCredentialsFailsBeforeSpawning(): void
    {
        $result = RouteTestApp::call('steam-stats-api/import-game', ['slug' => 'alpha-quest']);

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('igdb_credentials', $result['error']);

        $progress = RouteTestApp::app()->cache('alv/steam-stats.cache')
            ->get('import-progress.' . $result['id']);

        $this->assertTrue($progress['error']);
        $this->assertStringContainsString('IGDB', $progress['text']);
    }

    public function testImportProgressUnknownId(): void
    {
        $result = RouteTestApp::call('steam-stats-api/import-progress/unknown-id');

        $this->assertSame(['phase' => 'unknown', 'text' => 'Esperando...'], $result);
    }

    public function testImportProgressReturnsCachedState(): void
    {
        RouteTestApp::app()->cache('alv/steam-stats.cache')->set('import-progress.test-id', [
            'phase' => 'metadata',
            'text' => 'Obteniendo información...',
        ]);

        $result = RouteTestApp::call('steam-stats-api/import-progress/test-id');

        $this->assertSame('metadata', $result['phase']);
        $this->assertSame('Obteniendo información...', $result['text']);
    }

    public function testSteamCapsuleMediaReturns404WhenMissing(): void
    {
        $result = RouteTestApp::call('media/steam-capsule/unknown-game.jpg');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(404, $result->code());
    }
}
```

- [x] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter ImportRoutesTest`
Expected: `OK (5 tests, 10 assertions)`.

- [x] **Step 3: Full suite + verification + commit**

Run: `composer test`, then:

```bash
git status --short | head
php -r '$p=new PDO("sqlite:sqlite/steam_stats.db"); echo "fixtures: ".$p->query("SELECT COUNT(*) FROM store_prices WHERE slug=\"game\"")->fetchColumn()."\n";'
```

Expected: only the new test file untracked; `fixtures: 0`.

```bash
git add tests/phpunit/Integration/ImportRoutesTest.php
git commit -m "test: cover import routes and capsule media fallback"
```

---

## Plan 5 done when

- `composer test` passes: ~146 tests, 1 skipped.
- Only production change is the `alv.steam-stats.charts-spawner` option seam in the rankings route (default behavior unchanged: `exec`).
- No background processes spawned, no live API calls, no writes to production data.

## Next plans

6. CLI + `.mjs` scraper parser tests (`STEAM_STATS_DB_PATH`/`STEAM_STATS_SKIP_EXTRAS` env seams, extracted parser modules, subprocess contracts).
7. Playwright E2E (build + test servers, route mocking, charts/favorites/search).

---

**Status: completed (2026-09-21).** All tasks executed and merged to `main`; see the Execution notes at the top for recorded deviations. Checkboxes were ticked retroactively after completion.
