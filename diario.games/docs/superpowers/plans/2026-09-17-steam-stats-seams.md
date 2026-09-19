# Steam Stats Collector & Facade Seams — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lock in `SteamStatsCollector` parsing/backfill and `SteamStats` scrape/trending behavior with hermetic tests — temp SQLite, fake HTTP/node seams, no live Steam/steamcharts/SteamDB calls.

**Architecture:** Extract pure mappers (`parseGameTxt`, `mapSteamchartsPoints`, `mapSteamDbHistory`, `parseStatsHtml`, `parseMostPlayedJson`) and small protected seams (`fetchCurrentPlayers`, `fetchSteamchartsChartData`, `runNodeScript`, cache/detail/history accessors) so `Tests\Support\FakeSteamStatsCollector` and `Tests\Support\TestableSteamStats` can drive the public methods. `runNodeScript` replaces three near-duplicate `exec()` blocks. Tests use `TempDatabase` (env-isolated SQLite) and `PluginClasses::load()`.

**Tech Stack:** PHP 8.4, PHPUnit 11, PDO SQLite.

**Spec:** `docs/superpowers/specs/2026-09-17-regression-test-suite-design.md` (Phase 2, steam stats portion). Routes (`steam-stats-api/*`) are Plan 5; CLI + `.mjs` parsers Plan 6; E2E Plan 7.

**Scope decisions (YAGNI):** `downloadCapsule` (Steam appdetails + image resize + real content writes) is not tested here; its HTTP path is covered indirectly by the steam-stats base-URL seam in a later plan. `SteamStatsDB` behavior is already covered by Plan 1. Cache TTL internals are only tested through fakes; real file-cache behavior belongs to Plan 5's booted-Kirby tests.

**Prerequisites:** Plans 1–3 merged. Run tests from `diario.games/`. Never write to `content/`, `sqlite/steam_stats.db`, `storage/`, `media/`, `site/cache`. SteamDB lock tests use unique fake appids in `/tmp` and clean up in `finally`.

**Execution notes (2026-09-17):** The committed code is authoritative; deviations from the snippets below:
- `collect()` test slug is the directory name (`alpha`), not a slugified title — the collector uses `basename(dirname(...))`. Test expectation corrected.
- Red-phase runs for `backfill`/`collectSteamDB*` must be scoped to the pure mapper tests (`--filter ...::testMap...`) — running whole classes before the seams exist triggers live HTTP/`exec`.
- `SteamStatsTrendingTest` pre-boots a Kirby `App` with temp roots in `setUpBeforeClass`: the DB-overlay path (`getAllPlayerDataCached`) calls `kirby()`, and constructing an App mid-test registers error/exception handlers, which PHPUnit reports as risky. Booting once before tests avoids the handler change.
- `getMostPlayed` prefers the scraped entry's name over the appdetails name (`$entry['name'] ?? $detail['name']`); the test pins that.
- Actual counts: collector collect 5/14, backfill 2/5, SteamDB 7/15, parse 3/4, trending 3/19; full suite after Plan 4 = 126 tests / 370 assertions / 1 skipped.

---

### Task 1: Collector content parsing + `collect()`

**Files:**
- Create: `tests/Support/FakeSteamStatsCollector.php`
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsCollectorCollectTest.php`
- Modify: `site/plugins/alv-steam-stats/classes/SteamStatsCollector.php`

- [ ] **Step 1: Create the fake collector**

`tests/Support/FakeSteamStatsCollector.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\SteamStats\SteamStatsCollector;

class FakeSteamStatsCollector extends SteamStatsCollector
{
    public array $playerCounts = [];
    public array $chartDataResponses = [];
    public array $nodeResponses = [];
    public array $nodeCalls = [];

    protected function fetchCurrentPlayers(int $appid): ?int
    {
        return $this->playerCounts[$appid] ?? null;
    }

    protected function fetchSteamchartsChartData(int $appid): ?string
    {
        return $this->chartDataResponses[$appid] ?? null;
    }

    protected function runNodeScript(string $scriptPath, array $args): ?string
    {
        $this->nodeCalls[] = basename($scriptPath);
        return $this->nodeResponses[basename($scriptPath)] ?? null;
    }
}
```

This class intentionally overrides methods added in Tasks 2 and 3; declaring them now is harmless (they are plain methods until the parent grows them).

- [ ] **Step 2: Write the failing tests**

`tests/phpunit/Unit/SteamStats/SteamStatsCollectorCollectTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsCollector;
use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSteamStatsCollector;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsCollectorCollectTest extends TestCase
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

    public function testParseGameTxtExtractsSteamGameFields(): void
    {
        $content = "Title: Alpha Game\n\n----\n\nIgdbId: 111\n\n----\n\nWebsites: 1:https://store.steampowered.com/app/570/\n\n----\n";

        $parsed = SteamStatsCollector::parseGameTxt($content, 'alpha-game');

        $this->assertSame([
            'appid' => 570,
            'slug' => 'alpha-game',
            'name' => 'Alpha Game',
            'igdbId' => 111,
        ], $parsed);
    }

    public function testParseGameTxtReturnsNullWithoutSteamLink(): void
    {
        $this->assertNull(SteamStatsCollector::parseGameTxt("Title: Beta\n\n----\n", 'beta'));
    }

    public function testParseGameTxtFallsBackToSlugWithoutTitleAndNullIgdbId(): void
    {
        $content = "Websites: 1:https://store.steampowered.com/app/730/\n";

        $parsed = SteamStatsCollector::parseGameTxt($content, 'fallback-slug');

        $this->assertSame('fallback-slug', $parsed['name']);
        $this->assertNull($parsed['igdbId']);
    }

    public function testCollectScansFixtureGamesAndStoresPlayerCounts(): void
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        mkdir($gamesDir . '/2024/03/alpha', 0775, true);
        mkdir($gamesDir . '/2024/03/beta', 0775, true);

        file_put_contents(
            $gamesDir . '/2024/03/alpha/game.txt',
            "Title: Alpha Game\n\n----\n\nIgdbId: 111\n\n----\n\nWebsites: 1:https://store.steampowered.com/app/570/\n"
        );
        file_put_contents(
            $gamesDir . '/2024/03/beta/game.txt',
            "Title: Beta Game\n"
        );

        $collector = new FakeSteamStatsCollector('test-key');
        $collector->playerCounts = [570 => 12345];

        $stats = $collector->collect($gamesDir);

        $this->assertSame(2, $stats['scanned']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame([], $stats['errors']);

        $db = new SteamStatsDB($this->tempDatabasePath);
        $game = $db->getGameByAppId(570);

        $this->assertNotNull($game);
        $this->assertSame('alpha-game', $game['slug']);
        $this->assertSame('Alpha Game', $game['name']);
        $this->assertSame(12345, $db->getCurrentPlayers(570));
    }

    public function testCollectRecordsErrorsForUnavailableCounts(): void
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        mkdir($gamesDir . '/2024/03/alpha', 0775, true);
        file_put_contents(
            $gamesDir . '/2024/03/alpha/game.txt',
            "Title: Alpha Game\n\n----\n\nWebsites: 1:https://store.steampowered.com/app/570/\n"
        );

        $collector = new FakeSteamStatsCollector('test-key');
        $collector->playerCounts = [570 => null];

        $stats = $collector->collect($gamesDir);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame([570], $stats['errors']);
    }
}
```

- [ ] **Step 3: Run to verify red**

Run: `vendor/bin/phpunit --filter SteamStatsCollectorCollectTest`
Expected: FAIL — `Call to undefined method SteamStatsCollector::parseGameTxt()` (and `collect()` ignores the games-dir argument). No network, no writes outside temp.

- [ ] **Step 4: Implement**

In `site/plugins/alv-steam-stats/classes/SteamStatsCollector.php`:

Replace the head of `collect()` (lines 16-48) with:

```php
    public function collect(?string $gamesDir = null): array
    {
        $gamesDir ??= dirname(__DIR__, 4) . '/content/games';
        $stats = ['scanned' => 0, 'updated' => 0, 'errors' => []];

        $recursive = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($gamesDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($recursive as $item) {
            if ($item->getFilename() !== 'game.txt') continue;

            $slug = basename(dirname($item->getPathname()));
            $content = file_get_contents($item->getPathname());

            $stats['scanned']++;

            $parsed = self::parseGameTxt($content, $slug);
            if ($parsed !== null) {
                $this->db->upsertGame($parsed['appid'], $parsed['slug'], $parsed['name'], $parsed['igdbId']);
            }
        }
```

Then keep the existing appid fetch loop unchanged, and add this static method after `collect()`:

```php
    public static function parseGameTxt(string $content, string $fallbackSlug): ?array
    {
        if (!preg_match('/store\.steampowered\.com\/app\/(\d+)/i', $content, $m)) {
            return null;
        }

        $appid = (int) $m[1];

        preg_match('/^Title:\s*(.+)/m', $content, $tm);
        $name = trim($tm[1] ?? $fallbackSlug);

        $igdbId = null;
        if (preg_match('/^IgdbId:\s*(\d+)/m', $content, $im)) {
            $igdbId = (int) $im[1];
        }

        return ['appid' => $appid, 'slug' => $fallbackSlug, 'name' => $name, 'igdbId' => $igdbId];
    }
```

Change `private function fetchCurrentPlayers(` to `protected function fetchCurrentPlayers(`.

- [ ] **Step 5: Run green + full suite + lint**

Run: `vendor/bin/phpunit --filter SteamStatsCollectorCollectTest` → `OK (5 tests, 15 assertions)`.
Run: `composer test` → all pass.
Run: `php -l site/plugins/alv-steam-stats/classes/SteamStatsCollector.php`.

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-steam-stats/classes/SteamStatsCollector.php tests/Support/FakeSteamStatsCollector.php tests/phpunit/Unit/SteamStats/SteamStatsCollectorCollectTest.php
git commit -m "refactor(steam-stats): extract collector content parser"
```

---

### Task 2: Collector chart-data backfill

**Files:**
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsCollectorBackfillTest.php`
- Modify: `site/plugins/alv-steam-stats/classes/SteamStatsCollector.php`

- [ ] **Step 1: Write the failing tests**

`tests/phpunit/Unit/SteamStats/SteamStatsCollectorBackfillTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsCollector;
use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSteamStatsCollector;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsCollectorBackfillTest extends TestCase
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
        $this->tearDownTempDatabase();
    }

    public function testMapSteamchartsPointsFiltersAndConvertsMilliseconds(): void
    {
        $mapped = SteamStatsCollector::mapSteamchartsPoints([
            [1700000000000, 10],
            [1700003600000, '20'],
            [1700007200000],
            'invalid',
        ]);

        $this->assertSame([
            ['timestamp' => 1700000000, 'count' => 10],
            ['timestamp' => 1700003600, 'count' => 20],
        ], $mapped);
    }

    public function testBackfillInsertsPointsAndRecordsErrors(): void
    {
        $db = new SteamStatsDB($this->tempDatabasePath);
        $db->upsertGame(100, 'one', 'One');
        $db->upsertGame(200, 'two', 'Two');

        $collector = new FakeSteamStatsCollector('test-key');
        $collector->chartDataResponses = [
            100 => json_encode([[1700000000000, 10], [1700003600000, 20]]),
            200 => null,
        ];

        $stats = $collector->backfill();

        $this->assertSame(1, $stats['fetched']);
        $this->assertSame(2, $stats['inserted']);
        $this->assertSame([200], $stats['errors']);

        $this->assertSame(
            [['timestamp' => 1700000000, 'p' => 10], ['timestamp' => 1700003600, 'p' => 20]],
            $db->getPlayerCounts(100, 0)
        );
    }
}
```

- [ ] **Step 2: Run to verify red**

Run: `vendor/bin/phpunit --filter SteamStatsCollectorBackfillTest`
Expected: FAIL — `Call to undefined method SteamStatsCollector::mapSteamchartsPoints()`.

- [ ] **Step 3: Implement**

In `SteamStatsCollector::backfill()`, replace the inline curl block (lines 243-265) with the seam call and mapper:

```php
            $response = $this->fetchSteamchartsChartData($appid);

            if ($response === null) {
                $stats['errors'][] = $appid;
                $log && $log("  No data, skipping");
                continue;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || empty($data)) {
                $stats['errors'][] = $appid;
                continue;
            }

            $stats['fetched']++;
            $inserted = 0;

            foreach (self::mapSteamchartsPoints($data) as $point) {
                $this->db->insertPlayerCountIfMissing($appid, $point['timestamp'], $point['count']);
                $inserted++;
            }
```

Add these methods after `backfill()`:

```php
    public static function mapSteamchartsPoints(array $points): array
    {
        $mapped = [];

        foreach ($points as $point) {
            if (!is_array($point) || !isset($point[0], $point[1])) continue;

            $mapped[] = [
                'timestamp' => (int) ($point[0] / 1000),
                'count'     => (int) $point[1],
            ];
        }

        return $mapped;
    }

    protected function fetchSteamchartsChartData(int $appid): ?string
    {
        $url = "https://steamcharts.com/app/{$appid}/chart-data.json";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        return $response;
    }
```

Preserve the existing `usleep(500000)` at the end of the loop.

- [ ] **Step 4: Run green + full suite + lint**

Run: `vendor/bin/phpunit --filter SteamStatsCollectorBackfillTest` → `OK (2 tests, 5 assertions)` (the test sleeps ~1s by design).
Run: `composer test`, `php -l .../SteamStatsCollector.php`.

- [ ] **Step 5: Commit**

```bash
git add site/plugins/alv-steam-stats/classes/SteamStatsCollector.php tests/phpunit/Unit/SteamStats/SteamStatsCollectorBackfillTest.php
git commit -m "refactor(steam-stats): extract steamcharts backfill mapping"
```

---

### Task 3: Collector SteamDB node-script seam

**Files:**
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsCollectorSteamDbTest.php`
- Modify: `site/plugins/alv-steam-stats/classes/SteamStatsCollector.php`

- [ ] **Step 1: Write the failing tests**

`tests/phpunit/Unit/SteamStats/SteamStatsCollectorSteamDbTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsCollector;
use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSteamStatsCollector;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsCollectorSteamDbTest extends TestCase
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
        $this->tearDownTempDatabase();
    }

    public function testMapSteamDbHistoryHandlesOldFormat(): void
    {
        $mapped = SteamStatsCollector::mapSteamDbHistory([
            [1700000000000, 10],
            [1700003600000, 0],
            [1700007200000, 25],
        ]);

        $this->assertSame([
            ['timestamp' => 1700000000, 'count' => 10],
            ['timestamp' => 1700007200, 'count' => 25],
        ], $mapped['points']);
        $this->assertSame(25, $mapped['peak']);
        $this->assertSame(1700007200, $mapped['peak_timestamp']);
    }

    public function testMapSteamDbHistoryPrefersHigherDomPeak(): void
    {
        $mapped = SteamStatsCollector::mapSteamDbHistory([
            'points' => [[1700000000000, 10]],
            'peak_all_time' => 999,
        ]);

        $this->assertSame(999, $mapped['peak']);
        $this->assertSame(0, $mapped['peak_timestamp']);
    }

    public function testMapSteamDbHistoryKeepsPointPeakWhenDomPeakLower(): void
    {
        $mapped = SteamStatsCollector::mapSteamDbHistory([
            'points' => [[1700000000000, 50]],
            'peak_all_time' => 20,
        ]);

        $this->assertSame(50, $mapped['peak']);
        $this->assertSame(1700000000, $mapped['peak_timestamp']);
    }

    public function testCollectSteamDbPeakParsesNodeOutput(): void
    {
        $collector = new FakeSteamStatsCollector('test-key');
        $collector->nodeResponses = [
            'fetch-steamdb-peak.mjs' => '{"peak":123,"timestamp":456}',
        ];

        $this->assertSame(
            ['peak' => 123, 'timestamp' => 456],
            $collector->collectSteamDBPeak(570)
        );
        $this->assertSame(['fetch-steamdb-peak.mjs'], $collector->nodeCalls);
    }

    public function testCollectSteamDbPeakReturnsNullForInvalidOutput(): void
    {
        $collector = new FakeSteamStatsCollector('test-key');
        $collector->nodeResponses = ['fetch-steamdb-peak.mjs' => 'not-json'];

        $this->assertNull($collector->collectSteamDBPeak(570));
    }

    public function testCollectSteamDbHistoryInsertsPointsAndCleansLock(): void
    {
        $appid = 999000001;
        $collector = new FakeSteamStatsCollector('test-key');
        $collector->nodeResponses = [
            'scrape-steamdb-history.mjs' => '{"points":[[1700000000000,10],[1700003600000,25]],"peak_all_time":0}',
        ];

        $result = $collector->collectSteamDBHistory($appid);

        $this->assertSame(['points' => 2, 'peak' => 25], $result);
        $this->assertFileDoesNotExist(sys_get_temp_dir() . '/steamdb-backfill-' . $appid . '.lock');

        $db = new SteamStatsDB($this->tempDatabasePath);
        $this->assertSame(25, $db->getGamePeak($appid));
    }

    public function testCollectSteamDbHistorySkipsWhenLockExists(): void
    {
        $appid = 999000002;
        $lock = sys_get_temp_dir() . '/steamdb-backfill-' . $appid . '.lock';
        @unlink($lock);
        touch($lock);

        try {
            $collector = new FakeSteamStatsCollector('test-key');
            $collector->nodeResponses = [
                'scrape-steamdb-history.mjs' => '{"points":[[1700000000000,10]],"peak_all_time":0}',
            ];

            $this->assertNull($collector->collectSteamDBHistory($appid));
            $this->assertSame([], $collector->nodeCalls);
        } finally {
            @unlink($lock);
        }
    }
}
```

- [ ] **Step 2: Run to verify red**

Run: `vendor/bin/phpunit --filter SteamStatsCollectorSteamDbTest`
Expected: FAIL — `Call to undefined method SteamStatsCollector::mapSteamDbHistory()`.

- [ ] **Step 3: Implement**

Replace `collectSteamDBPeak()` (lines 195-214) with:

```php
    public function collectSteamDBPeak(int $appid): ?array
    {
        $scriptPath = dirname(__DIR__, 4) . '/scripts/fetch-steamdb-peak.mjs';
        $stdout = $this->runNodeScript($scriptPath, [$appid]);
        if ($stdout === null) return null;

        $data = json_decode($stdout, true);
        if (!$data || empty($data['peak']) || empty($data['timestamp'])) return null;

        return ['peak' => (int)$data['peak'], 'timestamp' => (int)$data['timestamp']];
    }

    protected function runNodeScript(string $scriptPath, array $args): ?string
    {
        if (!file_exists($scriptPath)) return null;

        $nodeBin = $this->findNodeBinary();
        if (!$nodeBin) return null;

        $cmd = escapeshellarg($nodeBin) . ' ' . escapeshellarg($scriptPath);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg((string)$arg);
        }
        $cmd .= ' 2>/dev/null';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) return null;

        return implode('', $output);
    }
```

Replace the body of `collectSteamDBHistory()` from the script check through the insert loop (lines 295-355) with:

```php
        $scriptPath = dirname(__DIR__, 4) . '/scripts/scrape-steamdb-history.mjs';

        touch($lockFile);

        $stdout = $this->runNodeScript($scriptPath, [$appid]);
        if ($stdout === null) {
            @unlink($lockFile);
            return null;
        }

        $raw = json_decode($stdout, true);
        if (!is_array($raw) || empty($raw)) {
            @unlink($lockFile);
            return null;
        }

        $mapped = self::mapSteamDbHistory($raw);

        $inserted = 0;
        foreach ($mapped['points'] as $point) {
            $this->db->insertPlayerCount($appid, $point['timestamp'], $point['count']);
            $inserted++;
        }

        if ($mapped['peak'] > 0) {
            $this->db->upsertGamePeak($appid, $mapped['peak'], $mapped['peak_timestamp']);
        }

        @unlink($lockFile);
        return ['points' => $inserted, 'peak' => $mapped['peak']];
    }

    public static function mapSteamDbHistory(array $raw): array
    {
        if (isset($raw['points'])) {
            $data = $raw['points'];
            $domPeak = (int)($raw['peak_all_time'] ?? 0);
        } else {
            $data = $raw;
            $domPeak = 0;
        }

        $points = [];
        $peak = 0;
        $peakTs = null;

        foreach ($data as $point) {
            if (!is_array($point) || !isset($point[0], $point[1])) continue;
            $ts = (int)($point[0] / 1000);
            $count = (int)$point[1];
            if ($count <= 0) continue;

            $points[] = ['timestamp' => $ts, 'count' => $count];

            if ($count > $peak) {
                $peak = $count;
                $peakTs = $ts;
            }
        }

        if ($domPeak > $peak) {
            $peak = $domPeak;
            $peakTs = 0;
        }

        return ['points' => $points, 'peak' => $peak, 'peak_timestamp' => $peakTs];
    }
```

Replace `collectSteamDBCharts()`'s script/exec block (lines 367-378) with:

```php
        $scriptPath = dirname(__DIR__, 4) . '/scripts/scrape-steamdb-charts.mjs';

        $stdout = $this->runNodeScript($scriptPath, [$limit]);
        if ($stdout === null) return null;

        $data = json_decode($stdout, true);
        if (!is_array($data) || empty($data['rows'])) return null;
```

Keep the return expression unchanged.

Do not change `backfillSteamDBHistory()` or `downloadCapsule()`.

- [ ] **Step 4: Run green + full suite + lint**

Run: `vendor/bin/phpunit --filter SteamStatsCollectorSteamDbTest` → `OK (7 tests, 14 assertions)`.
Run: `composer test`, `php -l .../SteamStatsCollector.php`.

- [ ] **Step 5: Commit**

```bash
git add site/plugins/alv-steam-stats/classes/SteamStatsCollector.php tests/phpunit/Unit/SteamStats/SteamStatsCollectorSteamDbTest.php
git commit -m "refactor(steam-stats): centralize steamdb node script seam"
```

---

### Task 4: SteamStats scrape parsers

**Files:**
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsParseTest.php`
- Modify: `site/plugins/alv-steam-stats/classes/SteamStats.php`

- [ ] **Step 1: Write the failing tests**

`tests/phpunit/Unit/SteamStats/SteamStatsParseTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStats;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class SteamStatsParseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    private function statsHtmlFixture(): string
    {
        return <<<'HTML'
<table>
<tr class="player_count_row">
    <td class="right"><span>1,234,567</span></td>
    <td class="right"><span>2,000,000</span></td>
    <td>Current</td>
    <td><a href="https://store.steampowered.com/app/730/">Counter-Strike &amp; Friends</a></td>
</tr>
<tr class="player_count_row">
    <td><span>42,000</span></td>
    <td><span>50,000</span></td>
    <td>Current</td>
    <td><a href="https://store.steampowered.com/app/570/?snr=1">Dota 2</a></td>
</tr>
</table>
HTML;
    }

    public function testParseStatsHtmlExtractsGames(): void
    {
        $games = SteamStats::parseStatsHtml($this->statsHtmlFixture());

        $this->assertSame([
            ['appid' => 730, 'name' => 'Counter-Strike & Friends', 'current_players' => 1234567, 'peak_today' => 2000000],
            ['appid' => 570, 'name' => 'Dota 2', 'current_players' => 42000, 'peak_today' => 50000],
        ], $games);
    }

    public function testParseStatsHtmlReturnsEmptyForUnrelatedHtml(): void
    {
        $this->assertSame([], SteamStats::parseStatsHtml('<html><body>nope</body></html>'));
    }

    public function testParseMostPlayedJsonReturnsRanks(): void
    {
        $ranks = [['rank' => 1, 'appid' => 570, 'peak_in_game' => 800000]];

        $this->assertSame($ranks, SteamStats::parseMostPlayedJson(['response' => ['ranks' => $ranks]]));
        $this->assertSame([], SteamStats::parseMostPlayedJson([]));
    }
}
```

- [ ] **Step 2: Run to verify red**

Run: `vendor/bin/phpunit --filter SteamStatsParseTest`
Expected: FAIL — `Call to undefined method SteamStats::parseStatsHtml()`.

- [ ] **Step 3: Implement**

In `SteamStats.php`, replace the parsing tail of `fetchGameListFromStats()` (lines 248-262) with `return self::parseStatsHtml($response);` and add after it:

```php
    public static function parseStatsHtml(string $html): array
    {
        $games = [];
        $pattern = '/<tr class="player_count_row[^>]*">\s*<td[^>]*>\s*<span[^>]*>([\d,]+)<\/span>\s*<\/td>\s*<td[^>]*>\s*<span[^>]*>([\d,]+)<\/span>\s*<\/td>\s*<td[^>]*>[^<]*<\/td>\s*<td[^>]*>\s*<a[^>]*href="[^"]*app\/(\d+)\/[^"]*"[^>]*>([^<]+)<\/a>/s';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $games[] = [
                    'appid' => (int)$match[3],
                    'name' => html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'current_players' => (int)str_replace(',', '', $match[1]),
                    'peak_today' => (int)str_replace(',', '', $match[2]),
                ];
            }
        }

        return $games;
    }
```

Replace the JSON tail of `fetchMostPlayedFromSteam()` (lines 282-283) with:

```php
        $data = json_decode($response, true);

        return self::parseMostPlayedJson($data ?? []);
    }

    public static function parseMostPlayedJson(array $data): array
    {
        return $data['response']['ranks'] ?? [];
    }
```

Change visibility `private` → `protected` for `fetchGameListFromStats` and `fetchMostPlayedFromSteam`.

- [ ] **Step 4: Run green + full suite + lint**

Run: `vendor/bin/phpunit --filter SteamStatsParseTest` → `OK (3 tests, 4 assertions)`.
Run: `composer test`, `php -l .../SteamStats.php`.

- [ ] **Step 5: Commit**

```bash
git add site/plugins/alv-steam-stats/classes/SteamStats.php tests/phpunit/Unit/SteamStats/SteamStatsParseTest.php
git commit -m "refactor(steam-stats): extract stats html and json parsers"
```

---

### Task 5: SteamStats trending + most-played tests

**Files:**
- Create: `tests/Support/TestableSteamStats.php`
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsTrendingTest.php`
- Modify: `site/plugins/alv-steam-stats/classes/SteamStats.php`

- [ ] **Step 1: Create the test double**

`tests/Support/TestableSteamStats.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\SteamStats\SteamStats;

class TestableSteamStats extends SteamStats
{
    public array $cacheStore = [];
    public array $detailsById = [];
    public array $histories = [];

    protected function getCached(string $key, int $ttl)
    {
        return $this->cacheStore[$key] ?? null;
    }

    protected function setCache(string $key, $value): void
    {
        $this->cacheStore[$key] = $value;
    }

    protected function fetchGameDetails(array $appids): array
    {
        $result = [];
        foreach ($appids as $appid) {
            if (isset($this->detailsById[$appid])) {
                $result[$appid] = $this->detailsById[$appid];
            }
        }
        return $result;
    }

    protected function getPlayerHistory(int $appid): array
    {
        return $this->histories[$appid] ?? [];
    }
}
```

- [ ] **Step 2: Write the failing tests**

`tests/phpunit/Unit/SteamStats/SteamStatsTrendingTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableSteamStats;

final class SteamStatsTrendingTest extends TestCase
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
        $this->tearDownTempDatabase();
    }

    private function stats(): TestableSteamStats
    {
        return new TestableSteamStats(['api_key' => '', 'cache_ttl' => 3600]);
    }

    private function seedTrendingData(): void
    {
        $db = new SteamStatsDB($this->tempDatabasePath);
        $now = time();

        $db->upsertGame(100, 'fast', 'Fast Game');
        $db->upsertGame(200, 'slow', 'Slow Game');
        $db->upsertGame(300, 'new', 'New Game');

        $db->insertPlayerCount(100, $now - 8 * 86400, 100);
        $db->insertPlayerCount(100, $now - 86400, 150);
        $db->insertPlayerCount(200, $now - 8 * 86400, 300);
        $db->insertPlayerCount(200, $now - 86400, 300);
        $db->insertPlayerCount(300, $now - 86400, 1000);
    }

    public function testGetTrendingComputesGrowthAndSortsDescending(): void
    {
        $this->seedTrendingData();

        $stats = $this->stats();
        $stats->detailsById = [
            100 => ['name' => 'Fast Game', 'capsule_image' => 'https://cdn/100.jpg'],
            200 => ['name' => 'Slow Game', 'capsule_image' => 'https://cdn/200.jpg'],
            300 => ['name' => 'New Game', 'capsule_image' => 'https://cdn/300.jpg'],
        ];
        $stats->histories = [100 => [['timestamp' => 1, 'players' => 2]]];

        $result = $stats->getTrending(10, 0);

        $this->assertSame([300, 100, 200], array_column($result, 'appid'));

        $this->assertSame(1, $result[0]['rank']);
        $this->assertTrue($result[0]['is_new']);
        $this->assertEqualsWithDelta(1000.0, $result[0]['growth_pct'], 0.001);

        $this->assertFalse($result[1]['is_new']);
        $this->assertEqualsWithDelta(50.0, $result[1]['growth_pct'], 0.001);
        $this->assertSame('Fast Game', $result[1]['name']);
        $this->assertSame([['timestamp' => 1, 'players' => 2]], $result[1]['history']);

        $this->assertEqualsWithDelta(0.0, $result[2]['growth_pct'], 0.001);
    }

    public function testGetTrendingServesCachedResultsWithResetRanks(): void
    {
        $stats = $this->stats();
        $stats->cacheStore['trending-growth'] = [
            ['appid' => 5, 'name' => 'Cached A', 'growth_pct' => 10],
            ['appid' => 6, 'name' => 'Cached B', 'growth_pct' => 5],
        ];

        $result = $stats->getTrending(1, 0);

        $this->assertCount(1, $result);
        $this->assertSame(5, $result[0]['appid']);
        $this->assertSame(1, $result[0]['rank']);
    }

    public function testGetMostPlayedUsesScrapedListAndDetails(): void
    {
        $stats = $this->stats();
        $stats->cacheStore['stats-most-played'] = [
            ['appid' => 570, 'name' => 'Scraped Name', 'current_players' => 10, 'peak_today' => 20],
            ['appid' => 730, 'name' => 'Second', 'current_players' => 5, 'peak_today' => 6],
        ];
        $stats->detailsById = [
            570 => ['name' => 'Dota 2', 'capsule_image' => 'https://cdn/570.jpg'],
        ];

        $result = $stats->getMostPlayed(1);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['rank']);
        $this->assertSame(570, $result[0]['appid']);
        $this->assertSame('Dota 2', $result[0]['name']);
        $this->assertSame('https://cdn/570.jpg', $result[0]['capsule_image']);
        $this->assertSame(10, $result[0]['current_players']);
        $this->assertSame(20, $result[0]['peak_players']);
    }
}
```

- [ ] **Step 3: Run to verify red**

Run: `vendor/bin/phpunit --filter SteamStatsTrendingTest`
Expected: FAIL — `Error: Call to undefined function kirby()`. The test double's `protected` overrides are legal but have no effect while the parent methods are `private` (private calls resolve to the parent's own method), so `getCached()` reaches `kirby()->cache(...)` instead of the fake. No network.

- [ ] **Step 4: Widen visibility in `SteamStats.php`**

Change `private` → `protected` for exactly: `fetchMostPlayedFromSteam` is already protected from Task 4 — leave it; widen these:
`getMostPlayedFallback`, `fetchCurrentPlayers`, `fetchGameDetails`, `getCurrentPlayers`, `getPlayerHistory`, `getCached`, `setCache`.

No logic changes.

- [ ] **Step 5: Run green + full suite + lint**

Run: `vendor/bin/phpunit --filter SteamStatsTrendingTest` → `OK (3 tests, 19 assertions)`.
Run: `composer test`, `php -l .../SteamStats.php`.

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-steam-stats/classes/SteamStats.php tests/Support/TestableSteamStats.php tests/phpunit/Unit/SteamStats/SteamStatsTrendingTest.php
git commit -m "refactor(steam-stats): open facade seams for trending tests"
```

---

## Plan 4 done when

- `composer test` passes: 126 tests, 1 skipped.
- Production diff: `SteamStatsCollector.php` and `SteamStats.php` only — parsers extracted, visibility widened, `runNodeScript` centralization; no behavior change.
- No live Steam/steamcharts/SteamDB calls; no writes to production data or real content.

## Next plans

5. Kirby route/site-method integration (`steam-stats-api/rankings`, search merge, chart-data ranges, fake `exec`; booted Kirby + temp DB; replace the PageSmokeTest `priceComparison` stub once `PriceFetcher` has an injectable seam if desired).
6. CLI + `.mjs` scraper parser tests (`STEAM_STATS_DB_PATH`/`SKIP_EXTRAS` env seams, extracted parser modules).
7. Playwright E2E (build + test servers, route mocking, charts/favorites/search).
