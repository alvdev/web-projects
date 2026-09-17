# Test Infrastructure & Foundation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Install the test toolchain (PHPUnit 11, Vitest) and add the first hermetic tests that lock in pure logic and SQLite behavior, including the `STEAM_STATS_DB_PATH` isolation seam.

**Architecture:** PHPUnit runs via Composer (dev dependency); test files live under `tests/phpunit/`. Tests load plugin classes explicitly through a `Tests\Support\PluginClasses` loader (no Kirby boot yet). Every DB test uses a per-test temp SQLite file via a `TempDatabase` trait. The only production change is additive: optional `$dbPath` constructor params on `SteamStatsDB`/`StorePriceDB` resolving `STEAM_STATS_DB_PATH`, plus a `_db(true)` reset flag. Vitest runs pure JS unit tests for `assets/src/js/timezones.js`.

**Tech Stack:** PHP 8.4, PHPUnit 11, PDO SQLite, Vitest (Node 22 / bun 1.4), existing bun lockfile.

**Spec:** `docs/superpowers/specs/2026-09-17-regression-test-suite-design.md` (Phases 1 and seam 1 only; later phases get their own plans).

---

## File Structure

- `composer.json` — add `require-dev: phpunit/phpunit` + `autoload-dev` for `Tests\Support`.
- `phpunit.xml` — PHPUnit config, three suites (`unit`, `integration`, `cli`).
- `tests/bootstrap.php` — TZ=UTC, composer autoload, `tests/.tmp` creation.
- `tests/Support/PluginClasses.php` — explicit `require_once` loader for plugin classes.
- `tests/Support/TempDatabase.php` — per-test temp DB dir + `STEAM_STATS_DB_PATH` env management.
- `tests/phpunit/Unit/Igdb/HelpersTest.php` — pure IGDB helper tests.
- `tests/phpunit/Unit/Igdb/GameImporterExclusionTest.php` — `isExcluded` tests.
- `tests/phpunit/Unit/SteamStats/SteamStatsDbPathTest.php` — DB path seam tests.
- `tests/phpunit/Unit/SteamStats/SteamStatsDbGamesTest.php` — games/index tests.
- `tests/phpunit/Unit/SteamStats/SteamStatsDbPlayersTest.php` — counts/aggregation/peak tests.
- `tests/phpunit/Unit/SteamStats/SteamStatsDbChartsTest.php` — chart chunk/entry tests.
- `tests/phpunit/Unit/Igdb/DbHelperTest.php` — `_db()` reset + env test.
- `tests/phpunit/Unit/Prices/StorePriceDbPathTest.php` — DB path seam tests.
- `tests/phpunit/Unit/Prices/StorePriceDbTest.php` — price/catalog behavior tests.
- `tests/js/timezones.test.js` — Vitest for `assets/src/js/timezones.js`.
- `vitest.config.js` — Vitest config.
- `site/plugins/alv-steam-stats/classes/SteamStatsDB.php` — seam 1 (modify).
- `site/plugins/alv-prices/classes/StorePriceDB.php` — seam 1 (modify).
- `site/plugins/alv-igdb/classes/helpers.php` — `_db()` reset flag (modify).
- `.gitignore` — ignore `tests/.tmp/`.

**Safety rule for every seam task:** run the new test *before* implementing the seam. The test is written so the first failing assertion happens before any fixture data is written, so the failing run cannot pollute `sqlite/steam_stats.db`.

---

### Task 1: PHPUnit infrastructure + IGDB pure tests

**Files:**
- Create: `tests/phpunit/Unit/Igdb/HelpersTest.php`
- Create: `tests/phpunit/Unit/Igdb/GameImporterExclusionTest.php`
- Create: `phpunit.xml`
- Create: `tests/bootstrap.php`
- Create: `tests/Support/PluginClasses.php`
- Create: `tests/phpunit/Integration/.gitkeep`
- Create: `tests/phpunit/Cli/.gitkeep`
- Modify: `composer.json`
- Modify: `.gitignore`

- [ ] **Step 1: Write the test files**

`tests/phpunit/Unit/Igdb/HelpersTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

use function DiarioGames\IGDB\deriveYearMonth;
use function DiarioGames\IGDB\igdbImageUrl;
use function DiarioGames\IGDB\normalizePlatformNames;
use function DiarioGames\IGDB\platformCategory;
use function DiarioGames\IGDB\romanToDigits;
use function DiarioGames\IGDB\slugify;

final class HelpersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testSlugifyBuildsUrlSafeSlug(): void
    {
        $this->assertSame('the-legend-of-zelda-breath-of-the-wild', slugify('The Legend of Zelda: Breath of the Wild'));
        $this->assertSame('half-life-2', slugify('Half-Life 2'));
        $this->assertSame('foo', slugify('  Foo!!!  '));
        $this->assertSame('', slugify('   '));
    }

    public function testRomanToDigitsConvertsStandaloneNumerals(): void
    {
        $this->assertSame('final-fantasy-16', romanToDigits('final-fantasy-xvi'));
        $this->assertSame('grand-theft-auto-5', romanToDigits('grand-theft-auto-v'));
        $this->assertSame('civilization-6', romanToDigits('civilization-vi'));
        $this->assertSame('kingdom-hearts-3', romanToDigits('kingdom-hearts-iii'));
        $this->assertSame('halo-3', romanToDigits('halo-3'));
        $this->assertSame('doom', romanToDigits('doom'));
    }

    public function testDeriveYearMonth(): void
    {
        $this->assertSame(['2024', '03'], deriveYearMonth('2024-03-15'));
        $this->assertSame(['2024', '00'], deriveYearMonth('2024'));
        $this->assertSame(['2024', '00'], deriveYearMonth('2024-3'));
        $this->assertSame(['00', '00'], deriveYearMonth('TBA'));
        $this->assertSame(['00', '00'], deriveYearMonth(''));
    }

    public function testIgdbImageUrl(): void
    {
        $this->assertSame(
            'https://images.igdb.com/igdb/image/upload/t_cover_big/abc123.jpg',
            igdbImageUrl('abc123')
        );
        $this->assertSame(
            'https://images.igdb.com/igdb/image/upload/t_screenshot_huge/abc123.jpg',
            igdbImageUrl('abc123', 'screenshot_huge')
        );
    }

    public function testNormalizePlatformNamesGroupsAndOrders(): void
    {
        $this->assertSame(
            'PC, PS 4, Xbox One',
            normalizePlatformNames('PC (Microsoft Windows), PlayStation 4, Xbox One')
        );
        $this->assertSame(
            'PS 4|5|Vita',
            normalizePlatformNames('PlayStation 4, PlayStation 5, PlayStation Vita')
        );
        $this->assertSame('Switch', normalizePlatformNames('Nintendo Switch'));
        $this->assertSame(
            'Switch 1|2',
            normalizePlatformNames('Nintendo Switch, Nintendo Switch 2')
        );
        $this->assertSame('Xbox X|S|One', normalizePlatformNames('Xbox (X|S, One)'));
        $this->assertSame('Xbox X|S', normalizePlatformNames('Xbox Series X|S'));
        $this->assertSame(
            'PC, PS 5, Android',
            normalizePlatformNames('PC (Microsoft Windows), PlayStation 5, Android')
        );
        $this->assertSame(
            'PC',
            normalizePlatformNames('Legacy Mobile Device, PC (Microsoft Windows)')
        );
        $this->assertSame('', normalizePlatformNames('   '));
    }

    public function testPlatformCategoryBuckets(): void
    {
        $this->assertSame(0, platformCategory('PC'));
        $this->assertSame(0, platformCategory('linux'));
        $this->assertSame(0, platformCategory('Mac'));
        $this->assertSame(1, platformCategory('PlayStation 5'));
        $this->assertSame(1, platformCategory('Switch'));
        $this->assertSame(2, platformCategory('Android'));
        $this->assertSame(2, platformCategory('iOS'));
    }
}
```

`tests/phpunit/Unit/Igdb/GameImporterExclusionTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use DiarioGames\IGDB\GameImporter;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class GameImporterExclusionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testExcludesSeasonPassesBattlePassesAndDlcPacks(): void
    {
        $this->assertTrue(GameImporter::isExcluded(['name' => 'Game Season Pass']));
        $this->assertTrue(GameImporter::isExcluded(['name' => 'Battle Pass Deluxe']));
        $this->assertTrue(GameImporter::isExcluded(['name' => 'DLC Pack 2']));
    }

    public function testKeepsRegularGamesAndMissingNames(): void
    {
        $this->assertFalse(GameImporter::isExcluded(['name' => 'Doom']));
        $this->assertFalse(GameImporter::isExcluded(['name' => 'Expansion Pack']));
        $this->assertFalse(GameImporter::isExcluded([]));
    }
}
```

- [ ] **Step 2: Run PHPUnit to verify it does not exist yet**

Run: `vendor/bin/phpunit --testsuite unit`
Expected: `No such file or directory` (PHPUnit is not installed yet).

- [ ] **Step 3: Install PHPUnit**

Run: `composer require --dev phpunit/phpunit:^11.5 --no-interaction`
Expected: installs `phpunit/phpunit` and creates `vendor/bin/phpunit`.

- [ ] **Step 4: Add autoload-dev to composer.json**

Modify `composer.json` — add after the `config` block (keep valid JSON):

```json
    "autoload-dev": {
        "psr-4": {
            "Tests\\Support\\": "tests/Support/"
        }
    },
```

Then run: `composer dump-autoload`
Expected: `Generating autoload files` with no errors.

- [ ] **Step 5: Create the test support loader**

`tests/Support/PluginClasses.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

final class PluginClasses
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $root = dirname(__DIR__, 2);

        $files = [
            '/site/plugins/alv-ai/classes/AIClient.php',
            '/site/plugins/alv-steam-stats/classes/SteamStatsDB.php',
            '/site/plugins/alv-steam-stats/classes/SteamStats.php',
            '/site/plugins/alv-steam-stats/classes/SteamStatsCollector.php',
            '/site/plugins/alv-prices/classes/StorePriceDB.php',
            '/site/plugins/alv-igdb/classes/helpers.php',
            '/site/plugins/alv-igdb/classes/IGDBClient.php',
            '/site/plugins/alv-igdb/classes/GameImporter.php',
            '/site/plugins/alv-igdb/classes/AutoFetcher.php',
        ];

        foreach ($files as $file) {
            require_once $root . $file;
        }

        self::$loaded = true;
    }
}
```

- [ ] **Step 6: Create bootstrap and PHPUnit config**

`tests/bootstrap.php`:

```php
<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

require dirname(__DIR__) . '/vendor/autoload.php';

$tmp = __DIR__ . '/.tmp';
if (!is_dir($tmp) && !mkdir($tmp, 0775, true) && !is_dir($tmp)) {
    throw new RuntimeException('Unable to create tests/.tmp');
}
```

`phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         cacheDirectory="tests/.tmp/phpunit.cache"
         colors="true"
         displayDetailsOnTestsThatTriggerWarnings="true"
         displayDetailsOnTestsThatTriggerDeprecations="true">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/phpunit/Unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/phpunit/Integration</directory>
        </testsuite>
        <testsuite name="cli">
            <directory>tests/phpunit/Cli</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Create empty placeholder files so PHPUnit does not error on missing directories:
`tests/phpunit/Integration/.gitkeep` and `tests/phpunit/Cli/.gitkeep` (empty files).

- [ ] **Step 7: Add composer script and gitignore entry**

Add to `composer.json` `scripts` (create the block if absent):

```json
    "scripts": {
        "test": "phpunit",
        "test:unit": "phpunit --testsuite unit"
    },
```

Append to `.gitignore`:

```
/tests/.tmp/
```

- [ ] **Step 8: Run the unit suite**

Run: `composer test:unit`
Expected: `OK` with 8 tests, 20+ assertions, 0 failures.

- [ ] **Step 9: Commit**

```bash
git add composer.json composer.lock phpunit.xml tests .gitignore
git commit -m "test: add phpunit setup and igdb pure unit tests"
```

---

### Task 2: Vitest infrastructure + timezone unit tests

**Files:**
- Create: `vitest.config.js`
- Create: `tests/js/timezones.test.js`
- Modify: `package.json` (bun-managed deps + scripts)

- [ ] **Step 1: Write the test file**

`tests/js/timezones.test.js`:

```js
import { describe, expect, it } from 'vitest'
import {
  findCityMatch,
  findCountryMatch,
  getCountryForTimezone,
  getDisplayLabel,
  getUtcOffset,
  isValidTimezone,
  normalize,
} from '../../assets/src/js/timezones.js'

describe('normalize', () => {
  it('lowercases and strips accents', () => {
    expect(normalize('México')).toBe('mexico')
    expect(normalize('España')).toBe('espana')
    expect(normalize('SÃO PAULO')).toBe('sao paulo')
  })
})

describe('findCityMatch', () => {
  it('matches exact, prefix and substring keys', () => {
    expect(findCityMatch('Madrid')).toBe('Europe/Madrid')
    expect(findCityMatch('londres')).toBe('Europe/London')
    expect(findCityMatch('amst')).toBe('Europe/Amsterdam')
    expect(findCityMatch('sterdam')).toBe('Europe/Amsterdam')
  })

  it('returns null for empty or unknown queries', () => {
    expect(findCityMatch('')).toBeNull()
    expect(findCityMatch('  ')).toBeNull()
    expect(findCityMatch('zzzzzzz')).toBeNull()
  })
})

describe('findCountryMatch', () => {
  it('matches exact and accent-insensitive keys', () => {
    expect(findCountryMatch('España')).toEqual(['Europe/Madrid', 'Atlantic/Canary', 'Africa/Ceuta'])
    expect(findCountryMatch('espana')).toEqual(['Europe/Madrid', 'Atlantic/Canary', 'Africa/Ceuta'])
    expect(findCountryMatch('mex')).toEqual(findCountryMatch('Mexico'))
  })

  it('returns null for unknown queries', () => {
    expect(findCountryMatch('zzzzzzz')).toBeNull()
  })
})

describe('getCountryForTimezone', () => {
  it('finds the first country for a timezone', () => {
    expect(getCountryForTimezone('Europe/Madrid')).toBe('España')
  })

  it('returns null for unknown timezones', () => {
    expect(getCountryForTimezone('Etc/GMT+5')).toBeNull()
  })
})

describe('getDisplayLabel', () => {
  it('combines country and zone name', () => {
    expect(getDisplayLabel('Europe/Madrid')).toBe('España - Madrid')
  })

  it('returns the raw timezone when no country is known', () => {
    expect(getDisplayLabel('Etc/GMT+5')).toBe('Etc/GMT+5')
  })
})

describe('isValidTimezone', () => {
  it('accepts valid IANA zones and UTC', () => {
    expect(isValidTimezone('UTC')).toBe(true)
    expect(isValidTimezone('Europe/Madrid')).toBe(true)
    expect(isValidTimezone('America/Argentina/Buenos_Aires')).toBe(true)
  })

  it('rejects invalid zones', () => {
    expect(isValidTimezone('Not/AZone')).toBe(false)
    expect(isValidTimezone('')).toBe(false)
  })
})

describe('getUtcOffset', () => {
  it('returns +00:00 for UTC', () => {
    expect(getUtcOffset('UTC')).toBe('+00:00')
  })

  it('returns hour offsets for real zones', () => {
    expect(getUtcOffset('Europe/Madrid')).toMatch(/^\+[12](:00)?$/)
    expect(getUtcOffset('America/New_York')).toMatch(/^-[45](:00)?$/)
  })

  it('falls back to +00:00 for invalid zones', () => {
    expect(getUtcOffset('Not/AZone')).toBe('+00:00')
  })
})
```

- [ ] **Step 2: Install Vitest and jsdom**

Run: `bun add -d vitest jsdom`
Expected: `package.json` gets `vitest` and `jsdom` in `devDependencies`, `bun.lock` updated.

- [ ] **Step 3: Create Vitest config**

`vitest.config.js`:

```js
import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    environment: 'node',
    include: ['tests/js/**/*.test.js'],
  },
})
```

- [ ] **Step 4: Run the JS suite**

Run: `bunx vitest run`
Expected: all tests pass (14 tests).

- [ ] **Step 5: Wire package.json scripts**

Add to `package.json` `scripts`:

```json
        "test:unit": "vitest run",
        "test": "bun run test:unit && composer test"
```

- [ ] **Step 6: Verify the script wiring**

Run: `bun run test:unit`
Expected: Vitest passes.

- [ ] **Step 7: Commit**

```bash
git add package.json bun.lock vitest.config.js tests/js
git commit -m "test: add vitest setup and timezone unit tests"
```

---

### Task 3: SteamStatsDB DB-path seam

**Files:**
- Create: `tests/Support/TempDatabase.php`
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsDbPathTest.php`
- Modify: `site/plugins/alv-steam-stats/classes/SteamStatsDB.php:9-22`

- [ ] **Step 1: Create the TempDatabase trait**

`tests/Support/TempDatabase.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

trait TempDatabase
{
    protected string $tempDatabaseDir;
    protected string $tempDatabasePath;

    protected function setUpTempDatabase(): void
    {
        $this->tempDatabaseDir = sys_get_temp_dir() . '/diario-tests-' . bin2hex(random_bytes(6));
        if (!mkdir($this->tempDatabaseDir, 0775, true) && !is_dir($this->tempDatabaseDir)) {
            throw new \RuntimeException('Could not create temp dir: ' . $this->tempDatabaseDir);
        }

        $this->tempDatabasePath = $this->tempDatabaseDir . '/steam_stats.db';
        putenv('STEAM_STATS_DB_PATH=' . $this->tempDatabasePath);

        if (function_exists('DiarioGames\\IGDB\\_db')) {
            \DiarioGames\IGDB\_db(true);
        }
    }

    protected function tearDownTempDatabase(): void
    {
        putenv('STEAM_STATS_DB_PATH');

        if (function_exists('DiarioGames\\IGDB\\_db')) {
            \DiarioGames\IGDB\_db(true);
        }

        foreach (glob($this->tempDatabaseDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDatabaseDir);
    }
}
```

- [ ] **Step 2: Write the failing seam test**

`tests/phpunit/Unit/SteamStats/SteamStatsDbPathTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbPathTest extends TestCase
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

    public function testConstructorCreatesDatabaseAtGivenPath(): void
    {
        $db = new SteamStatsDB($this->tempDatabasePath);

        $this->assertFileExists($this->tempDatabasePath);
        $db->upsertGame(730, 'counter-strike-2', 'Counter-Strike 2');
        $this->assertSame('counter-strike-2', $db->getGameBySlug('counter-strike-2')['slug']);
    }

    public function testConstructorHonorsEnvOverride(): void
    {
        new SteamStatsDB();

        $this->assertFileExists($this->tempDatabasePath);
    }
}
```

- [ ] **Step 3: Run it to verify it fails safely**

Run: `vendor/bin/phpunit --filter SteamStatsDbPathTest`
Expected: FAIL. The first assertion (`assertFileExists`) fails before any fixture row is written. Opening the real DB runs only idempotent `CREATE TABLE IF NOT EXISTS` statements; no data rows are written.

- [ ] **Step 4: Implement the seam**

Replace the constructor in `site/plugins/alv-steam-stats/classes/SteamStatsDB.php` (lines 9-22):

```php
    public function __construct(?string $dbPath = null)
    {
        $dbPath ??= (getenv('STEAM_STATS_DB_PATH') ?: dirname(__DIR__, 4) . '/sqlite/steam_stats.db');

        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new \PDO('sqlite:' . $dbPath, options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA synchronous=NORMAL');

        $this->createTables();
    }
```

- [ ] **Step 5: Run to verify it passes**

Run: `vendor/bin/phpunit --filter SteamStatsDbPathTest`
Expected: `OK (2 tests, 3 assertions)`.

- [ ] **Step 6: Syntax check**

Run: `php -l site/plugins/alv-steam-stats/classes/SteamStatsDB.php`
Expected: `No syntax errors detected`.

- [ ] **Step 7: Commit**

```bash
git add site/plugins/alv-steam-stats/classes/SteamStatsDB.php tests/Support/TempDatabase.php tests/phpunit/Unit/SteamStats/SteamStatsDbPathTest.php
git commit -m "refactor(steam-stats): allow db path override for tests"
```

---

### Task 4: StorePriceDB DB-path seam

**Files:**
- Create: `tests/phpunit/Unit/Prices/StorePriceDbPathTest.php`
- Modify: `site/plugins/alv-prices/classes/StorePriceDB.php:9-24`

- [ ] **Step 1: Write the failing seam test**

`tests/phpunit/Unit/Prices/StorePriceDbPathTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

use Alv\Prices\StorePriceDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class StorePriceDbPathTest extends TestCase
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

    public function testConstructorCreatesDatabaseAtGivenPath(): void
    {
        $db = new StorePriceDB($this->tempDatabasePath);

        $this->assertFileExists($this->tempDatabasePath);
        $db->upsertUrl('game', 'steam', 'https://store.steampowered.com/app/1');
        $this->assertSame('https://store.steampowered.com/app/1', $db->getPrice('game', 'steam')['url']);
    }

    public function testConstructorHonorsEnvOverride(): void
    {
        new StorePriceDB();

        $this->assertFileExists($this->tempDatabasePath);
    }
}
```

- [ ] **Step 2: Run it to verify it fails safely**

Run: `vendor/bin/phpunit --filter StorePriceDbPathTest`
Expected: FAIL on `assertFileExists` before any data is written. Opening the real DB runs only idempotent `CREATE TABLE IF NOT EXISTS` statements; no data rows are written.

- [ ] **Step 3: Implement the seam**

Replace the constructor in `site/plugins/alv-prices/classes/StorePriceDB.php` (lines 9-24):

```php
    public function __construct(?string $dbPath = null)
    {
        $dbPath ??= (getenv('STEAM_STATS_DB_PATH') ?: dirname(__DIR__, 4) . '/sqlite/steam_stats.db');

        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new \PDO('sqlite:' . $dbPath, options: [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA synchronous=NORMAL');

        $this->createTable();
    }
```

- [ ] **Step 4: Run to verify it passes**

Run: `vendor/bin/phpunit --filter StorePriceDbPathTest`
Expected: `OK (2 tests, 3 assertions)`.

- [ ] **Step 5: Syntax check**

Run: `php -l site/plugins/alv-prices/classes/StorePriceDB.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-prices/classes/StorePriceDB.php tests/phpunit/Unit/Prices/StorePriceDbPathTest.php
git commit -m "refactor(prices): allow db path override for tests"
```

---

### Task 5: `_db()` reset seam

**Files:**
- Create: `tests/phpunit/Unit/Igdb/DbHelperTest.php`
- Modify: `site/plugins/alv-igdb/classes/helpers.php:175-186`

- [ ] **Step 1: Write the failing test**

`tests/phpunit/Unit/Igdb/DbHelperTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class DbHelperTest extends TestCase
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

    public function testDbSingletonResetsAndPicksUpNewEnvPath(): void
    {
        $pathA = $this->tempDatabaseDir . '/a.db';
        $pathB = $this->tempDatabaseDir . '/b.db';

        putenv('STEAM_STATS_DB_PATH=' . $pathA);
        \DiarioGames\IGDB\_db(true);
        $first = \DiarioGames\IGDB\_db();

        $this->assertNotNull($first);
        $this->assertFileExists($pathA);
        $first->setYearMonth('probe-game', '2024-05');
        $this->assertSame('2024-05', $first->getYearMonth('probe-game'));

        putenv('STEAM_STATS_DB_PATH=' . $pathB);
        $this->assertSame('2024-05', \DiarioGames\IGDB\_db()->getYearMonth('probe-game'));

        \DiarioGames\IGDB\_db(true);
        $second = \DiarioGames\IGDB\_db();

        $this->assertNotNull($second);
        $this->assertFileExists($pathB);
        $this->assertNull($second->getYearMonth('probe-game'));
    }
}
```

- [ ] **Step 2: Run it to verify it fails safely**

Run: `vendor/bin/phpunit --filter DbHelperTest`
Expected: FAIL on `assertFileExists($pathA)` (the env is ignored before the seam), before `setYearMonth` writes anything. Opening the real DB runs only idempotent `CREATE TABLE IF NOT EXISTS` statements; no data rows are written.

- [ ] **Step 3: Implement the reset flag**

Replace `_db()` in `site/plugins/alv-igdb/classes/helpers.php` (lines 175-186):

```php
function _db(bool $reset = false): ?\Alv\SteamStats\SteamStatsDB
{
    static $instance = null;

    if ($reset) {
        $instance = null;
        return null;
    }

    if ($instance === null) {
        try {
            $instance = new \Alv\SteamStats\SteamStatsDB();
        } catch (\Throwable $e) {
            return null;
        }
    }

    return $instance;
}
```

- [ ] **Step 4: Run to verify it passes**

Run: `vendor/bin/phpunit --filter DbHelperTest`
Expected: `OK (1 test, 6 assertions)`.

- [ ] **Step 5: Syntax check**

Run: `php -l site/plugins/alv-igdb/classes/helpers.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-igdb/classes/helpers.php tests/phpunit/Unit/Igdb/DbHelperTest.php
git commit -m "refactor(igdb): make _db singleton resettable for tests"
```

---

### Task 6: SteamStatsDB games/index tests

**Files:**
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsDbGamesTest.php`

- [ ] **Step 1: Write the tests**

`tests/phpunit/Unit/SteamStats/SteamStatsDbGamesTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbGamesTest extends TestCase
{
    use TempDatabase;

    private SteamStatsDB $db;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
        $this->db = new SteamStatsDB($this->tempDatabasePath);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    public function testUpsertGameInsertsAndReadsBack(): void
    {
        $this->db->upsertGame(730, 'counter-strike-2', 'Counter-Strike 2', 1020);

        $game = $this->db->getGameBySlug('counter-strike-2');
        $this->assertSame(730, (int)$game['appid']);
        $this->assertSame('Counter-Strike 2', $game['name']);
        $this->assertSame(1020, (int)$game['igdb_id']);
        $this->assertSame(730, (int)$this->db->getGameByAppId(730)['appid']);
        $this->assertSame(730, (int)$this->db->getGameByIgdbId(1020)['appid']);
        $this->assertNull($this->db->getGameBySlug('missing-game'));
    }

    public function testUpsertGameKeepsCleanSlugWhenNewSlugIsDuplicate(): void
    {
        $this->db->upsertGame(1, 'foo', 'Foo');
        $this->db->upsertGame(1, 'foo--2', 'Foo');

        $this->assertSame('foo', $this->db->getGameByAppId(1)['slug']);
    }

    public function testUpsertGameUpgradesDuplicateSlugToCleanSlug(): void
    {
        $this->db->upsertGame(2, 'bar--2', 'Bar');
        $this->db->upsertGame(2, 'bar', 'Bar');

        $this->assertSame('bar', $this->db->getGameByAppId(2)['slug']);
    }

    public function testUpsertGamePreservesExistingIgdbIdWhenNullPassed(): void
    {
        $this->db->upsertGame(3, 'baz', 'Baz', 555);
        $this->db->upsertGame(3, 'baz', 'Baz');

        $this->assertSame(555, (int)$this->db->getGameByAppId(3)['igdb_id']);
    }

    public function testNormalizeSlugConvertsRomanNumerals(): void
    {
        $this->assertSame('final-fantasy-16', SteamStatsDB::normalizeSlug('final-fantasy-xvi'));
        $this->assertSame('civilization-6', SteamStatsDB::normalizeSlug('civilization-vi'));
        $this->assertSame('grand-theft-auto-5', SteamStatsDB::normalizeSlug('grand-theft-auto-v'));
        $this->assertSame('kingdom-hearts-3', SteamStatsDB::normalizeSlug('kingdom-hearts-iii'));
        $this->assertSame('halo-3', SteamStatsDB::normalizeSlug('halo-3'));
    }

    public function testSlugLookupNormalizesRomanNumerals(): void
    {
        $this->db->upsertGame(4, 'my-game-iv', 'My Game IV');

        $this->assertSame('my-game-4', $this->db->getGameBySlug('my-game-iv')['slug']);
        $this->assertNotNull($this->db->getGameBySlug('my-game-4'));
    }

    public function testSetYearMonthCreatesAndUpdatesIndexRow(): void
    {
        $this->db->setYearMonth('indexed-game', '2024-03', 42);
        $this->assertSame('2024-03', $this->db->getYearMonth('indexed-game'));
        $this->assertSame(42, (int)$this->db->getGameBySlug('indexed-game')['igdb_id']);

        $this->db->setYearMonth('indexed-game', '2025-01');
        $this->assertSame('2025-01', $this->db->getYearMonth('indexed-game'));
        $this->assertSame(42, (int)$this->db->getGameBySlug('indexed-game')['igdb_id']);
        $this->assertNull($this->db->getYearMonth('unknown-game'));
    }

    public function testSearchGamesMatchesSubstringOrderedByShortestName(): void
    {
        $this->db->upsertGame(1, 'portal', 'Portal');
        $this->db->upsertGame(2, 'portal-2', 'Portal 2');
        $this->db->upsertGame(3, 'other', 'Other');

        $this->assertSame(
            ['Portal', 'Portal 2'],
            array_column($this->db->searchGames('portal'), 'name')
        );
    }

    public function testGetAllGamesAndAppids(): void
    {
        $this->db->upsertGame(10, 'ten', 'Ten');
        $this->db->upsertGame(20, 'twenty', 'Twenty');

        $this->assertCount(2, $this->db->getAllGames());

        $appids = array_map('intval', $this->db->getAllAppids());
        sort($appids);
        $this->assertSame([10, 20], $appids);
    }

    public function testBackfillFailureCounters(): void
    {
        $this->db->upsertGame(5, 'stale', 'Stale');
        $this->assertSame(0, $this->db->getBackfillFailures(5));

        $this->db->incrementBackfillFailures(5, 'timeout');
        $this->db->incrementBackfillFailures(5, 'timeout');
        $this->assertSame(2, $this->db->getBackfillFailures(5));

        $this->db->resetBackfillFailures(5);
        $this->assertSame(0, $this->db->getBackfillFailures(5));
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter SteamStatsDbGamesTest`
Expected: `OK (10 tests, 25+ assertions)`.

- [ ] **Step 3: Commit**

```bash
git add tests/phpunit/Unit/SteamStats/SteamStatsDbGamesTest.php
git commit -m "test: cover steam games index and slug normalization"
```

---

### Task 7: SteamStatsDB player/aggregation tests

**Files:**
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsDbPlayersTest.php`

- [ ] **Step 1: Write the tests**

`tests/phpunit/Unit/SteamStats/SteamStatsDbPlayersTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbPlayersTest extends TestCase
{
    use TempDatabase;

    private SteamStatsDB $db;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
        $this->db = new SteamStatsDB($this->tempDatabasePath);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    public function testInsertPlayerCountUpsertsByAppidAndTimestamp(): void
    {
        $this->db->insertPlayerCount(100, 1700000000, 10);
        $this->db->insertPlayerCount(100, 1700000000, 25);

        $this->assertSame(25, $this->db->getCurrentPlayers(100));
    }

    public function testInsertPlayerCountIfMissingKeepsExistingValue(): void
    {
        $this->db->insertPlayerCountIfMissing(100, 1700000000, 10);
        $this->db->insertPlayerCountIfMissing(100, 1700000000, 99);

        $this->assertSame(10, $this->db->getCurrentPlayers(100));
    }

    public function testGetRecentPlayerCountsReturnsAscendingOrderRespectingLimit(): void
    {
        $this->db->insertPlayerCount(100, 1000, 1);
        $this->db->insertPlayerCount(100, 2000, 2);
        $this->db->insertPlayerCount(100, 3000, 3);

        $this->assertSame(
            [['timestamp' => 2000, 'players' => 2], ['timestamp' => 3000, 'players' => 3]],
            $this->db->getRecentPlayerCounts(100, 2)
        );
    }

    public function testGetRecentPlayerCountsFallsBackToPlayerHistory(): void
    {
        $this->db->upsertPlayerHistory(200, 1000, 5);
        $this->db->upsertPlayerHistory(200, 2000, 8);

        $this->assertSame(
            [['timestamp' => 1000, 'players' => 5], ['timestamp' => 2000, 'players' => 8]],
            $this->db->getRecentPlayerCounts(200)
        );
    }

    public function testDeletePlayerHistoryBefore(): void
    {
        $this->db->upsertPlayerHistory(200, 1000, 5);
        $this->db->upsertPlayerHistory(200, 2000, 8);
        $this->db->deletePlayerHistoryBefore(200, 1500);

        $this->assertSame(
            [['timestamp' => 2000, 'players' => 8]],
            $this->db->getRecentPlayerCounts(200)
        );
    }

    public function testGetPlayerCountsSinceBoundaryIsAscending(): void
    {
        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 20);

        $this->assertSame(
            [['timestamp' => 2000, 'p' => 20]],
            $this->db->getPlayerCounts(100, 1500)
        );
    }

    public function testGetDailyPeakCountsGroupsByUtcDay(): void
    {
        $day1 = 1699920000;
        $day2 = 1700006400;

        $this->db->insertPlayerCount(100, $day1 + 100, 10);
        $this->db->insertPlayerCount(100, $day1 + 200, 50);
        $this->db->insertPlayerCount(100, $day2 + 100, 30);

        $this->assertSame(
            [['timestamp' => $day1, 'p' => 50], ['timestamp' => $day2, 'p' => 30]],
            $this->db->getDailyPeakCounts(100, 0)
        );
    }

    public function testGetWeeklyPeakCountsGroupsByEpochWeek(): void
    {
        $week1 = 1700092800;
        $week2 = 1700697600;

        $this->db->insertPlayerCount(100, $week1 + 100, 10);
        $this->db->insertPlayerCount(100, $week1 + 200, 40);
        $this->db->insertPlayerCount(100, $week2 + 100, 70);

        $this->assertSame(
            [['timestamp' => $week1, 'p' => 40], ['timestamp' => $week2, 'p' => 70]],
            $this->db->getWeeklyPeakCounts(100, 0)
        );
    }

    public function testGetMonthlyPeakCountsGroupsByMonth(): void
    {
        $nov = 1698796800;
        $dec = 1701388800;

        $this->db->insertPlayerCount(100, $nov + 3600, 100);
        $this->db->insertPlayerCount(100, $nov + 7200, 250);
        $this->db->insertPlayerCount(100, $dec + 3600, 400);

        $this->assertSame(
            [
                ['month_key' => '2023-11', 'p' => 250, 'timestamp' => $nov + 3600],
                ['month_key' => '2023-12', 'p' => 400, 'timestamp' => $dec + 3600],
            ],
            $this->db->getMonthlyPeakCounts(100, 0)
        );
    }

    public function testGetWeeklyAveragesSplitsRecentAndPriorWeeks(): void
    {
        $now = time();

        $this->db->insertPlayerCount(100, $now - 86400, 100);
        $this->db->insertPlayerCount(100, $now - 2 * 86400, 200);
        $this->db->insertPlayerCount(100, $now - 8 * 86400, 50);
        $this->db->insertPlayerCount(100, $now - 9 * 86400, 60);

        $result = $this->db->getWeeklyAverages();

        $this->assertEqualsWithDelta(150.0, $result[100]['recent_avg'], 0.001);
        $this->assertEqualsWithDelta(55.0, $result[100]['prior_avg'], 0.001);
        $this->assertSame(2, $result[100]['prior_samples']);
    }

    public function testGetPeakPlayersRespectsSinceBoundary(): void
    {
        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 90);

        $this->assertSame(90, $this->db->getPeakPlayers(100, 1500));
        $this->assertSame(90, $this->db->getPeakPlayers(100, 0));
        $this->assertNull($this->db->getPeakPlayers(100, 5000));
    }

    public function testGetPeakTimestampReturnsHighestCount(): void
    {
        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 90);

        $this->assertSame(
            ['count' => 90, 'timestamp' => 2000],
            $this->db->getPeakTimestamp(100)
        );
    }

    public function testGetLatestTimestamp(): void
    {
        $this->assertNull($this->db->getLatestTimestamp(100));

        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 10);

        $this->assertSame(2000, $this->db->getLatestTimestamp(100));
    }

    public function testUpsertGamePeakIsMonotonic(): void
    {
        $this->db->upsertGamePeak(7, 500, 111);
        $this->assertSame(500, $this->db->getGamePeak(7));
        $this->assertSame(111, $this->db->getGamePeakTimestamp(7));

        $this->db->upsertGamePeak(7, 400, 222);
        $this->assertSame(500, $this->db->getGamePeak(7));
        $this->assertSame(111, $this->db->getGamePeakTimestamp(7));

        $this->db->upsertGamePeak(7, 600, 333);
        $this->assertSame(600, $this->db->getGamePeak(7));
        $this->assertSame(333, $this->db->getGamePeakTimestamp(7));
    }

    public function testUpsertGamePeakWithoutTimestampKeepsTimestampNull(): void
    {
        $this->db->upsertGamePeak(8, 100);

        $this->assertSame(100, $this->db->getGamePeak(8));
        $this->assertNull($this->db->getGamePeakTimestamp(8));
    }

    public function testGetAllPlayerDataMergesCountsPeaksAndDefaults(): void
    {
        $now = time();

        $this->db->upsertGame(100, 'played', 'Played');
        $this->db->upsertGame(200, 'unplayed', 'Unplayed');
        $this->db->insertPlayerCount(100, $now - 7200, 10);
        $this->db->insertPlayerCount(100, $now - 3600, 30);
        $this->db->upsertGamePeak(100, 999);

        $data = $this->db->getAllPlayerData();

        $this->assertSame(
            ['current_players' => 30, 'peak_24h' => 30, 'peak_all_time' => 999],
            $data[100]
        );
        $this->assertSame(
            ['current_players' => 0, 'peak_24h' => 0, 'peak_all_time' => 0],
            $data[200]
        );
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter SteamStatsDbPlayersTest`
Expected: `OK (17 tests, 30+ assertions)`.

- [ ] **Step 3: Commit**

```bash
git add tests/phpunit/Unit/SteamStats/SteamStatsDbPlayersTest.php
git commit -m "test: cover steam player counts and aggregations"
```

---

### Task 8: SteamStatsDB chart tests

**Files:**
- Create: `tests/phpunit/Unit/SteamStats/SteamStatsDbChartsTest.php`

- [ ] **Step 1: Write the tests**

`tests/phpunit/Unit/SteamStats/SteamStatsDbChartsTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbChartsTest extends TestCase
{
    use TempDatabase;

    private SteamStatsDB $db;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
        $this->db = new SteamStatsDB($this->tempDatabasePath);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    public function testReplaceChartEntriesChunksAndSkipsInvalidRows(): void
    {
        $rows = [
            ['rank' => 1, 'appid' => 10, 'name' => 'A', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
            ['rank' => 101, 'appid' => 20, 'name' => 'B', 'current' => 8, 'peak_24h' => 9, 'peak_all_time' => 10],
            ['rank' => 0, 'appid' => 30, 'name' => 'InvalidRank'],
            ['rank' => 3, 'appid' => 0, 'name' => 'InvalidAppid'],
        ];

        $inserted = $this->db->replaceChartEntries($rows, 1699999999);

        $this->assertSame(2, $inserted);
        $this->assertSame(2, $this->db->countChartEntries());
        $this->assertSame([10], array_column($this->db->getChartEntriesByChunk(0), 'appid'));
        $this->assertSame([20], array_column($this->db->getChartEntriesByChunk(1), 'appid'));
    }

    public function testReplaceChartEntriesReplacesPreviousSnapshot(): void
    {
        $this->db->replaceChartEntries(
            [['rank' => 1, 'appid' => 10, 'name' => 'A']],
            1000
        );
        $this->db->replaceChartEntries(
            [['rank' => 1, 'appid' => 11, 'name' => 'B']],
            2000
        );

        $this->assertSame(1, $this->db->countChartEntries());
        $this->assertSame([11], array_column($this->db->getChartEntriesByChunk(0), 'appid'));
    }

    public function testMarkChartChunksFreshCoversSnapshot(): void
    {
        $this->db->markChartChunksFresh(250, 1699999999);

        $chunk0 = $this->db->getChartChunk(0);
        $chunk1 = $this->db->getChartChunk(1);
        $chunk2 = $this->db->getChartChunk(2);

        $this->assertSame('fresh', $chunk0['status']);
        $this->assertSame(100, (int)$chunk0['entry_count']);
        $this->assertSame(100, (int)$chunk1['entry_count']);
        $this->assertSame(50, (int)$chunk2['entry_count']);
        $this->assertSame(1699999999, (int)$chunk0['fetched_at']);
        $this->assertNull($this->db->getChartChunk(3));
    }

    public function testMarkChartChunksFreshWithZeroEntriesCreatesNoChunks(): void
    {
        $this->db->markChartChunksFresh(0, 1699999999);

        $this->assertNull($this->db->getChartChunk(0));
    }

    public function testMarkChartChunkErrorPreservesFetchedAt(): void
    {
        $this->db->markChartChunksFresh(100, 1699999999);
        $this->db->markChartChunkError(0, 'scrape failed');

        $chunk = $this->db->getChartChunk(0);

        $this->assertSame('error', $chunk['status']);
        $this->assertSame('scrape failed', $chunk['last_error']);
        $this->assertSame(1699999999, (int)$chunk['fetched_at']);
    }

    public function testSearchChartEntriesOrdersByRankAndClampsLimit(): void
    {
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $rows[] = ['rank' => $i, 'appid' => 1000 + $i, 'name' => 'Test Game ' . $i];
        }
        $this->db->replaceChartEntries($rows, 1000);

        $hits = $this->db->searchChartEntries('Test Game', 50);

        $this->assertCount(15, $hits);
        $this->assertSame(1, (int)$hits[0]['rank']);
        $this->assertSame(15, (int)$hits[14]['rank']);
    }
}
```

- [ ] **Step 2: Run the tests**

Run: `vendor/bin/phpunit --filter SteamStatsDbChartsTest`
Expected: `OK (6 tests, 20+ assertions)`.

- [ ] **Step 3: Commit**

```bash
git add tests/phpunit/Unit/SteamStats/SteamStatsDbChartsTest.php
git commit -m "test: cover steam chart chunks and entries"
```

---

### Task 9: StorePriceDB behavior tests

**Files:**
- Create: `tests/phpunit/Unit/Prices/StorePriceDbTest.php`
- Modify: `site/plugins/alv-prices/classes/StorePriceDB.php` (`isExpired` signature)

- [ ] **Step 1: Write the tests**

`tests/phpunit/Unit/Prices/StorePriceDbTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

use Alv\Prices\StorePriceDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class StorePriceDbTest extends TestCase
{
    use TempDatabase;

    private StorePriceDB $db;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
        $this->db = new StorePriceDB($this->tempDatabasePath);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    public function testUpsertPriceAndGetAllPricesSortedAscending(): void
    {
        $this->db->upsertPrice('game', 'steam', [
            'url' => 'https://steam.example',
            'price' => 30.0,
            'initialPrice' => 40.0,
            'discount' => 25,
            'currency' => 'EUR',
        ]);
        $this->db->upsertPrice('game', 'g2a', [
            'url' => 'https://g2a.example',
            'price' => 10.0,
            'initialPrice' => 20.0,
            'discount' => 50,
            'currency' => 'EUR',
        ]);
        $this->db->upsertPrice('other', 'g2a', [
            'url' => 'https://g2a.example/other',
            'price' => 1.0,
        ]);

        $prices = $this->db->getAllPrices('game');

        $this->assertSame(['g2a', 'steam'], array_column($prices, 'store'));
        $this->assertEqualsWithDelta(10.0, (float)$prices[0]['price'], 0.001);
        $this->assertSame(25, (int)$this->db->getPrice('game', 'steam')['discount_percent']);
        $this->assertNull($this->db->getPrice('missing', 'steam'));
    }

    public function testUpsertPriceOnFreshRowUsesPayloadUrl(): void
    {
        $this->db->upsertPrice('game', 'g2a', ['url' => 'https://fresh.example', 'price' => 3.0]);

        $this->assertSame('https://fresh.example', $this->db->getPrice('game', 'g2a')['url']);
    }

    public function testUpsertUrlOnlyFillsRowsWithoutPrice(): void
    {
        $this->db->upsertUrl('game', 'g2a', 'https://first.example');
        $this->db->upsertPrice('game', 'g2a', ['url' => 'https://kept.example', 'price' => 5.0]);
        $this->db->upsertUrl('game', 'g2a', 'https://ignored.example');

        $this->assertSame('https://first.example', $this->db->getPrice('game', 'g2a')['url']);
    }

    public function testIsExpiredUsesInjectedNow(): void
    {
        $this->assertFalse($this->db->isExpired(1000, 50, 1049));
        $this->assertTrue($this->db->isExpired(1000, 50, 1050));
        $this->assertTrue($this->db->isExpired(1000, 50, 2000));
        $this->assertFalse($this->db->isExpired(1000, 50, 1000));
    }

    public function testFindG2aProductIdPrefersExactMatch(): void
    {
        $this->db->upsertG2aProduct('pid-base', 'Cyberpunk 2077');
        $this->db->upsertG2aProduct('pid-dlc', 'Cyberpunk 2077: Phantom Liberty');

        $this->assertSame('pid-base', $this->db->findG2aProductId('Cyberpunk 2077'));
        $this->assertNull($this->db->findG2aProductId('Nonexistent Game'));
    }

    public function testFindG2aProductIdFallsBackToLongestSubstringMatch(): void
    {
        $this->db->upsertG2aProduct('pid-base', 'Cyberpunk 2077');
        $this->db->upsertG2aProduct('pid-dlc', 'Cyberpunk 2077: Phantom Liberty');

        $this->assertSame('pid-dlc', $this->db->findG2aProductId('cyberpunk'));
    }

    public function testDeleteStaleG2aProductsRemovesRowsOlderThanCutoff(): void
    {
        $this->db->upsertG2aProduct('fresh', 'Fresh Game');

        $this->db->deleteStaleG2aProducts(48);
        $this->assertSame('fresh', $this->db->findG2aProductId('Fresh Game'));

        $this->db->deleteStaleG2aProducts(-1);
        $this->assertNull($this->db->findG2aProductId('Fresh Game'));
    }
}
```

- [ ] **Step 2: Run to verify `testIsExpiredUsesInjectedNow` fails**

Run: `vendor/bin/phpunit --filter StorePriceDbTest`
Expected: FAIL only in `testIsExpiredUsesInjectedNow` (the third argument is currently ignored); the other tests pass.

- [ ] **Step 3: Implement the `isExpired` seam**

Replace `isExpired` in `site/plugins/alv-prices/classes/StorePriceDB.php`:

```php
    public function isExpired(int $scrapedAt, int $ttl = 86400, ?int $now = null): bool
    {
        return (($now ?? time()) - $scrapedAt) >= $ttl;
    }
```

- [ ] **Step 4: Run to verify all pass**

Run: `vendor/bin/phpunit --filter StorePriceDbTest`
Expected: `OK (7 tests, 15+ assertions)`.

- [ ] **Step 5: Syntax check**

Run: `php -l site/plugins/alv-prices/classes/StorePriceDB.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-prices/classes/StorePriceDB.php tests/phpunit/Unit/Prices/StorePriceDbTest.php
git commit -m "test: cover store price db behavior and add isExpired now override"
```

---

### Task 10: Full-suite verification and production-data safety check

**Files:**
- No file changes expected.

- [ ] **Step 1: Capture the production DB checksum**

Run: `md5sum sqlite/steam_stats.db > /tmp/diario-db-before.txt && cat /tmp/diario-db-before.txt`
Expected: prints the checksum of the real DB.

- [ ] **Step 2: Run the full PHP suite**

Run: `composer test`
Expected: `OK` with all tests passing (50+ tests), 0 failures/errors.

- [ ] **Step 3: Run the full JS suite**

Run: `bun run test:unit`
Expected: Vitest reports all tests passing.

- [ ] **Step 4: Verify the production DB is untouched**

Run: `md5sum sqlite/steam_stats.db > /tmp/diario-db-after.txt && diff /tmp/diario-db-before.txt /tmp/diario-db-after.txt && echo "DB UNCHANGED"`
Expected: `DB UNCHANGED` (WAL files may appear/disappear; the main file must match).

- [ ] **Step 5: Lint every changed PHP file**

Run:

```bash
php -l site/plugins/alv-steam-stats/classes/SteamStatsDB.php
php -l site/plugins/alv-prices/classes/StorePriceDB.php
php -l site/plugins/alv-igdb/classes/helpers.php
```

Expected: `No syntax errors detected` for each.

- [ ] **Step 6: Manual smoke test of the live site**

Run:

```bash
php -S 127.0.0.1:8899 kirby/router.php >/tmp/diario-smoke.log 2>&1 &
SERVER_PID=$!
sleep 1
curl -s -o /dev/null -w "/ -> %{http_code}\n" http://127.0.0.1:8899/
curl -s -o /dev/null -w "/steam-stats -> %{http_code}\n" http://127.0.0.1:8899/steam-stats
curl -s -o /dev/null -w "/the-binding-of-isaac-rebirth -> %{http_code}\n" http://127.0.0.1:8899/the-binding-of-isaac-rebirth
kill $SERVER_PID
```

Expected: three `200` responses. This confirms the DB-path seams did not change default behavior.

- [ ] **Step 7: Review the default-behavior diff**

Run: `git log --oneline -12` to find the commit just before Task 1 (this plan's commit), then:

```bash
git diff <pre-plan-commit> -- site/plugins/alv-steam-stats/classes/SteamStatsDB.php site/plugins/alv-prices/classes/StorePriceDB.php site/plugins/alv-igdb/classes/helpers.php
```

Expected: only the additive parameter/reset changes shown in Tasks 3-5; the default path expression must still resolve to `<root>/sqlite/steam_stats.db`.

No commit for this task (verification only).

---

## Phase 1 done when

- `composer test` and `bun run test:unit` pass.
- The production DB checksum is unchanged after the full suite.
- The live-site smoke returns 200 for `/`, `/steam-stats`, and a game page.

## Next plans (not in scope here)

1. `2026-09-17-plugin-seams-and-integration-tests.md` — Phase 2: IGDB import with fake client, price adapters vs fixtures, steam collector/parser seams, AIClient parsers, page smoke.
2. `2026-09-17-kirby-route-integration-tests.md` — Phase 3: booted-Kirby route/site-method/banner tests.
3. `2026-09-17-cli-and-scraper-tests.md` — Phase 4: CLI subprocess tests + `.mjs` parser extraction.
4. `2026-09-17-playwright-e2e.md` — Phase 5: E2E infra and specs.
