# CLI & SteamDB Scraper Parser Seams — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Lock in the `collect-steam-stats.php` CLI contract (argv, exit codes, locks, skip-extras) with hermetic subprocess tests, and extract the five duplicated SteamDB `.mjs` parsers into a shared module with Vitest fixture tests.

**Architecture:** The CLI gains two env seams — `STEAM_STATS_CONTENT_DIR` (scan a fixture content tree) and `STEAM_STATS_SKIP_EXTRAS` (stop before the network-heavy warm/capsule/peaks fall-through) — plus a `Tests\Support\CliRunner` that runs the script via `proc_open` with env overrides. A new `scripts/lib/steamdb-parsers.mjs` exports `loadEnv`, `buildProxyUrl`, `sleep`, `mergeGraphPoints`, `mergeDailyHourlyPoints`, `computePeak`, `parseDataTableRows`, `parseDomPeak`; the four scraper scripts import them, deleting ~120 lines of duplication.

**Tech Stack:** PHP 8.4, PHPUnit 11, Node 22 / bun, Vitest.

**Spec:** `docs/superpowers/specs/2026-09-17-regression-test-suite-design.md` (Phase 2 CLI/scraper portion: "PHP subprocess tests ... CLI scripts & SteamDB scrapers"). Playwright E2E is Plan 7.

**Scope decisions (YAGNI):**
- Scrapers are never launched in tests (browser/Cloudflare); only the extracted pure parsers are tested.
- `backfill` mode is not exercised as a subprocess (live steamcharts); its mapping is covered by Plan 4 unit tests.
- The missing-API-key exit path is not subprocess-tested: `loadenv` overwrites process env from `.env`, so the key cannot be unset from the outside without touching real config. The guard is a two-line check; leaving it untested is deliberate.
- `import-game-cli.php`, `clear-game.php`, `seed.php`, `regenerate-summaries.php` remain untested by subprocess in this plan (they need more path/credentials seams); revisit later if needed.

**Prerequisites:** Plans 1–5 merged. Run tests from `diario.games/`. Never write to `content/`, `sqlite/steam_stats.db`, `storage/`, `media/`, `site/cache`.

**Execution notes (2026-09-17):** The committed code is authoritative; deviations from the snippets below:
- `scripts/inspect-steamdb-charts.mjs` was also wired to the shared parsers (it had the same `loadEnv`/`buildProxyUrl` duplication); zero local copies remain across `scripts/*.mjs`.
- The history scraper's Highstock stderr log line ("Using Highstock data") was dropped during extraction; stdout JSON is unchanged.
- Task 1 red phase: the safe `--filter` regex actually matches 4 tests (two `testHistoryBySlug*`, one `testSteamDbPeak*`, one `testChartsMode*`), all pass pre-seam.
- Actual counts: CLI 5 tests / 15 assertions; parser tests 15; full suites after Plan 6 = PHP 151 tests / 476 assertions / 1 skipped, Vitest 30 tests.

---

### Task 1: CLI env seams + subprocess contract tests

**Files:**
- Create: `tests/Support/CliRunner.php`
- Create: `tests/phpunit/Cli/CollectSteamStatsCliTest.php`
- Modify: `scripts/collect-steam-stats.php`

- [x] **Step 1: Create the subprocess runner**

`tests/Support/CliRunner.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

final class CliRunner
{
    public static function run(array $args, array $env = []): array
    {
        $root = dirname(__DIR__, 2);
        $script = $root . '/scripts/collect-steam-stats.php';

        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg((string) $arg);
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes, $root, array_merge(getenv(), $env));
        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start CLI');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            'exit' => proc_close($process),
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
```

- [x] **Step 2: Write the failing tests**

`tests/phpunit/Cli/CollectSteamStatsCliTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Cli;

use PHPUnit\Framework\TestCase;
use Tests\Support\CliRunner;
use Tests\Support\Files;
use Tests\Support\TempDatabase;

final class CollectSteamStatsCliTest extends TestCase
{
    use TempDatabase;

    private const CHARTS_LOCK = '/tmp/steamdb-charts-browser.lock';

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        @unlink(self::CHARTS_LOCK);
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    private function fixtureEnv(): array
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        mkdir($gamesDir . '/2024/03/alpha', 0775, true);
        file_put_contents(
            $gamesDir . '/2024/03/alpha/game.txt',
            "Title: Alpha Game\n\n----\n\nTemplate: game\n"
        );
        file_put_contents($gamesDir . '/2024/03/alpha/screenshot-0.jpg.txt', "Template: screenshot\n");

        return [
            'STEAM_STATS_DB_PATH' => $this->tempDatabasePath,
            'STEAM_STATS_CONTENT_DIR' => $gamesDir,
            'STEAM_STATS_SKIP_EXTRAS' => '1',
        ];
    }

    public function testCollectModeScansFixtureContentAndSkipsExtras(): void
    {
        $result = CliRunner::run(['collect'], $this->fixtureEnv());

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('Scanned: 1', $result['stdout']);
        $this->assertStringContainsString('Updated: 0', $result['stdout']);
        $this->assertStringNotContainsString('Caches warmed', $result['stdout']);
        $this->assertStringNotContainsString('All-time peaks', $result['stdout']);
        $this->assertStringNotContainsString('Imported ', $result['stdout']);
    }

    public function testChartsModeSkipsWhenLockIsFresh(): void
    {
        @unlink(self::CHARTS_LOCK);
        touch(self::CHARTS_LOCK);

        $result = CliRunner::run(['charts', '0'], $this->fixtureEnv());

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('already running', $result['stdout']);
        $this->assertFileExists(self::CHARTS_LOCK);
    }

    public function testHistoryBySlugRequiresSlug(): void
    {
        $result = CliRunner::run(['steamdb-history-by-slug'], $this->fixtureEnv());

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Usage: php collect-steam-stats.php steamdb-history-by-slug', $result['stdout']);
    }

    public function testHistoryBySlugUnknownGameExitsWithError(): void
    {
        $result = CliRunner::run(['steamdb-history-by-slug', 'unknown-game'], $this->fixtureEnv());

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Game not found for slug: unknown-game', $result['stdout']);
    }

    public function testSteamDbPeakWithoutAppidPrintsUsage(): void
    {
        $result = CliRunner::run(['steamdb-peak', '0'], $this->fixtureEnv());

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('Usage: php collect-steam-stats.php steamdb-peak <appid>', $result['stdout']);
    }
}
```

- [x] **Step 3: Run to verify red**

Run: `vendor/bin/phpunit --testsuite cli`
Expected: FAIL — `testCollectModeScansFixtureContentAndSkipsExtras` sees `Scanned: 52` (real content dir ignored) and the extras run against live APIs. **Hold the charts lock for the whole red run to avoid spawning scrapes**, and set `STEAM_STATS_SKIP_EXTRAS` is not yet honored — the extras WILL run (network). To keep the red phase safe, run only the argv/lock tests first:

```bash
vendor/bin/phpunit --filter 'CollectSteamStatsCliTest::(testHistoryBySlug|testSteamDbPeak|testChartsMode)'
```

Expected: 3 pass (no seams needed), 0 failures; the seam tests are committed to run after implementation. (The `collect` test's red phase requires live-crawlable extras, so it is verified green only after Step 4.)

- [x] **Step 4: Implement the env seams**

In `scripts/collect-steam-stats.php`:

After `$mode = $argv[1] ?? 'collect';` insert:

```php
$contentDir = getenv('STEAM_STATS_CONTENT_DIR') ?: null;
$skipExtras = (bool) getenv('STEAM_STATS_SKIP_EXTRAS');
```

In the `player-update` branch, change `$stats = $collector->collect();` to `$stats = $collector->collect($contentDir);`.

In the final `else` branch, change `$stats = $collector->collect();` to `$stats = $collector->collect($contentDir);`.

After the errors echo (immediately before the `// Import scraped top-100 games...` comment) insert:

```php
if ($skipExtras) {
    exit(0);
}
```

- [x] **Step 5: Run green + full suite + lint**

Run: `vendor/bin/phpunit --testsuite cli`
Expected: `OK (5 tests, 13 assertions)` — no network, no spawned processes (charts lock is unlinked in `tearDown`; the collect test scans only temp content).

Run `composer test` and `php -l scripts/collect-steam-stats.php`.

- [x] **Step 6: Commit**

```bash
git add scripts/collect-steam-stats.php tests/Support/CliRunner.php tests/phpunit/Cli/CollectSteamStatsCliTest.php
git commit -m "test: add cli subprocess harness and env seams"
```

---

### Task 2: Shared `.mjs` parsers + Vitest tests

**Files:**
- Create: `scripts/lib/steamdb-parsers.mjs`
- Create: `tests/js/steamdb-parsers.test.js`

No changes to the scraper scripts in this task (they still have their local copies; Task 3 wires them).

- [x] **Step 1: Create the parser module**

`scripts/lib/steamdb-parsers.mjs`:

```js
import { readFileSync } from 'node:fs';

export function loadEnv(envPath) {
    try {
        const content = readFileSync(envPath, 'utf-8');
        const env = {};
        for (const line of content.split('\n')) {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('#')) continue;
            const eqIdx = trimmed.indexOf('=');
            if (eqIdx === -1) continue;
            env[trimmed.slice(0, eqIdx)] = trimmed.slice(eqIdx + 1);
        }
        return env;
    } catch {
        return {};
    }
}

export function buildProxyUrl(env) {
    const host = env.PROXY_HOST, port = env.PROXY_PORT, user = env.PROXY_USER, pass = env.PROXY_PASS;
    if (!host || !port) return null;
    const p = { server: `http://${host}:${port}` };
    if (user && pass) {
        p.username = user;
        p.password = pass;
    }
    return p;
}

export function sleep(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

function addPoints(points, seen, data) {
    if (!data) return;
    for (let i = 0; i < data.values.length; i++) {
        const secondTs = data.start + i * data.step;
        if (seen.has(secondTs)) continue;
        seen.add(secondTs);
        points.push([secondTs * 1000, data.values[i]]);
    }
}

export function mergeGraphPoints(dailyData, hourlyData, highstockRaw) {
    const points = [];
    const seen = new Set();
    addPoints(points, seen, hourlyData);
    addPoints(points, seen, dailyData);
    points.sort((a, b) => a[0] - b[0]);

    if (highstockRaw && highstockRaw.length > points.length) {
        const hs = [];
        for (const pt of highstockRaw) {
            if (!pt || pt.length < 2 || pt[1] <= 0) continue;
            hs.push([pt[0], pt[1]]);
        }
        hs.sort((a, b) => a[0] - b[0]);
        return hs;
    }

    return points;
}

export function mergeDailyHourlyPoints(dailyData, hourlyData) {
    const points = [];
    const seen = new Set();
    addPoints(points, seen, dailyData);
    addPoints(points, seen, hourlyData);
    points.sort((a, b) => a[0] - b[0]);
    return points;
}

export function computePeak(values, start, step) {
    let peak = 0;
    let maxIdx = 0;
    const length = values?.length ?? 0;
    for (let i = 0; i < length; i++) {
        if (values[i] > peak) {
            peak = values[i];
            maxIdx = i;
        }
    }
    if (peak === 0) return null;
    return { peak, timestamp: start + maxIdx * step };
}

export function parseDataTableRows(data, names, maxRows) {
    const num = (cell) => {
        if (cell && typeof cell === 'object') {
            const raw = cell['@data-sort'] !== undefined ? cell['@data-sort'] : cell.display;
            return parseInt(String(raw).replace(/[^\d]/g, ''), 10) || 0;
        }
        return parseInt(String(cell ?? '0').replace(/[^\d]/g, ''), 10) || 0;
    };

    const rows = [];
    for (let i = 0; i < data.length && rows.length < maxRows; i++) {
        const row = data[i];
        if (!Array.isArray(row) || row.length < 6) continue;
        const logoHtml = String(row[1] ?? '');
        const nameHtml = String(row[2] ?? '');
        const match = (logoHtml + ' ' + nameHtml).match(/\/app\/(\d+)\//);
        if (!match) continue;

        rows.push({
            rank: rows.length + 1,
            appid: parseInt(match[1], 10),
            name: names?.[i] ?? '',
            current: num(row[3]),
            peak_24h: num(row[4]),
            peak_all_time: num(row[5]),
        });
    }

    return rows;
}

export function parseDomPeak(text) {
    const match = String(text ?? '').match(/([\d,]+)\s*\n?\s*all-time/i);
    return match ? parseInt(match[1].replace(/,/g, ''), 10) : null;
}
```

- [x] **Step 2: Write the tests**

`tests/js/steamdb-parsers.test.js`:

```js
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { describe, expect, it } from 'vitest'
import {
  buildProxyUrl,
  computePeak,
  loadEnv,
  mergeDailyHourlyPoints,
  mergeGraphPoints,
  parseDataTableRows,
  parseDomPeak,
} from '../../scripts/lib/steamdb-parsers.mjs'

describe('loadEnv', () => {
  it('parses key=value lines and ignores comments and blanks', () => {
    const dir = mkdtempSync(join(tmpdir(), 'steamdb-env-'))
    const file = join(dir, '.env')
    writeFileSync(file, '# comment\n\nPROXY_HOST=1.2.3.4\nPROXY_PORT=8080\nKEY=value=with=equals\nBADLINE\n')

    try {
      expect(loadEnv(file)).toEqual({
        PROXY_HOST: '1.2.3.4',
        PROXY_PORT: '8080',
        KEY: 'value=with=equals',
      })
    } finally {
      rmSync(dir, { recursive: true, force: true })
    }
  })

  it('returns an empty object for missing files', () => {
    expect(loadEnv('/nonexistent/.env')).toEqual({})
  })
})

describe('buildProxyUrl', () => {
  it('returns null without host or port', () => {
    expect(buildProxyUrl({})).toBeNull()
    expect(buildProxyUrl({ PROXY_HOST: '1.2.3.4' })).toBeNull()
  })

  it('builds server url and adds credentials when present', () => {
    expect(buildProxyUrl({ PROXY_HOST: '1.2.3.4', PROXY_PORT: '8080' })).toEqual({
      server: 'http://1.2.3.4:8080',
    })
    expect(buildProxyUrl({ PROXY_HOST: '1.2.3.4', PROXY_PORT: '8080', PROXY_USER: 'u', PROXY_PASS: 'p' })).toEqual({
      server: 'http://1.2.3.4:8080',
      username: 'u',
      password: 'p',
    })
  })
})

describe('mergeGraphPoints', () => {
  const daily = { start: 1000, step: 10, values: [5, 6] }
  const hourly = { start: 1000, step: 5, values: [1, 2, 3] }

  it('dedupes by second-timestamp, hourly first, sorted ascending', () => {
    expect(mergeGraphPoints(daily, hourly, null)).toEqual([
      [1000000, 1],
      [1005000, 2],
      [1010000, 3],
    ])
  })

  it('uses highstock data when it is longer than the merged points', () => {
    const highstock = [[2000000, 10], [1000000, 20], [3000000, 30], [4000000, 40]]

    expect(mergeGraphPoints(daily, hourly, highstock)).toEqual([
      [1000000, 20],
      [2000000, 10],
      [3000000, 30],
      [4000000, 40],
    ])
  })

  it('filters invalid highstock points and keeps merged data when not longer', () => {
    expect(mergeGraphPoints(daily, hourly, [[1000000, 0]])).toEqual([
      [1000000, 1],
      [1005000, 2],
      [1010000, 3],
    ])
  })

  it('handles missing data sets', () => {
    expect(mergeGraphPoints(null, null, null)).toEqual([])
  })
})

describe('mergeDailyHourlyPoints', () => {
  it('adds daily points first, then unseen hourly points', () => {
    const daily = { start: 1000, step: 10, values: [5, 6] }
    const hourly = { start: 1000, step: 5, values: [1, 2] }

    expect(mergeDailyHourlyPoints(daily, hourly)).toEqual([
      [1000000, 5],
      [1005000, 2],
      [1010000, 6],
    ])
  })
})

describe('computePeak', () => {
  it('returns the max value with its timestamp', () => {
    expect(computePeak([1, 9, 4], 1000, 60)).toEqual({ peak: 9, timestamp: 1060 })
  })

  it('returns null for empty or all-zero series', () => {
    expect(computePeak([], 1000, 60)).toBeNull()
    expect(computePeak([0, 0], 1000, 60)).toBeNull()
    expect(computePeak(undefined, 1000, 60)).toBeNull()
  })
})

describe('parseDataTableRows', () => {
  it('maps rows, parses numbers and object cells, assigns ranks', () => {
    const data = [
      ['1', '<a href="/app/730/"><img src="x"></a>', '<a href="/app/730/">Counter-Strike 2</a>', { display: '1,234', '@data-sort': '1234' }, '5,000', '1,000,000'],
      ['2', '<a href="/app/570/">x</a>', '<a href="/app/570/">Dota 2</a>', '42', '50', '800'],
    ]
    const names = ['Counter-Strike 2', 'Dota 2']

    expect(parseDataTableRows(data, names, 10)).toEqual([
      { rank: 1, appid: 730, name: 'Counter-Strike 2', current: 1234, peak_24h: 5000, peak_all_time: 1000000 },
      { rank: 2, appid: 570, name: 'Dota 2', current: 42, peak_24h: 50, peak_all_time: 800 },
    ])
  })

  it('skips short rows and rows without an appid, honoring maxRows', () => {
    const data = [
      ['1'],
      ['1', 'no app link', 'Game', '1', '2', '3'],
      ['1', '<a href="/app/10/">x</a>', 'Ten', '1', '2', '3'],
      ['2', '<a href="/app/20/">x</a>', 'Twenty', '1', '2', '3'],
    ]

    const rows = parseDataTableRows(data, ['', '', 'Ten', 'Twenty'], 1)

    expect(rows).toEqual([
      { rank: 1, appid: 10, name: 'Ten', current: 1, peak_24h: 2, peak_all_time: 3 },
    ])
  })
})

describe('parseDomPeak', () => {
  it('extracts the all-time peak number', () => {
    expect(parseDomPeak('Players\n1,234,567\nall-time peak')).toBe(1234567)
  })

  it('returns null when the pattern is missing', () => {
    expect(parseDomPeak('nothing here')).toBeNull()
    expect(parseDomPeak(null)).toBeNull()
  })
})
```

- [x] **Step 3: Run the tests**

Run: `bunx vitest run tests/js/steamdb-parsers.test.js`
Expected: `15 tests` pass (all green).

- [x] **Step 4: Full JS suite + commit**

```bash
bun run test:unit
git add scripts/lib/steamdb-parsers.mjs tests/js/steamdb-parsers.test.js
git commit -m "test: extract shared steamdb parser module with fixtures"
```

---

### Task 3: Wire scraper scripts to the shared parsers

**Files:**
- Modify: `scripts/scrape-steamdb-history.mjs`
- Modify: `scripts/fetch-steamdb-peak.mjs`
- Modify: `scripts/backfill-steamdb.mjs`
- Modify: `scripts/scrape-steamdb-charts.mjs`

- [x] **Step 1: Wire the history scraper**

In `scripts/scrape-steamdb-history.mjs`:
- Add import: `import { buildProxyUrl, loadEnv, mergeGraphPoints, sleep } from './lib/steamdb-parsers.mjs';`
- Remove the local `loadEnv` and `buildProxyUrl` definitions and the local `sleep` function.
- Replace `const env = loadEnv();` with `const env = loadEnv(resolve(__dirname, '..', '.env'));`
- Remove the now-unused `readFileSync` import (keep `resolve`, `dirname`, `fileURLToPath`).
- Replace the points-merging block (from `const points = [];` through the Highstock override and its logs) with:

```js
        const points = mergeGraphPoints(dailyData, hourlyData, highstockRaw);

        console.error(`[scrape-steamdb] Total: ${points.length} data points`);
```

- [x] **Step 2: Wire the peak scraper**

In `scripts/fetch-steamdb-peak.mjs`:
- Add import: `import { buildProxyUrl, computePeak, loadEnv, sleep } from './lib/steamdb-parsers.mjs';`
- Remove local `loadEnv`, `buildProxyUrl`, `sleep`.
- Replace `const env = loadEnv();` with `const env = loadEnv(resolve(__dirname, '..', '.env'));`
- Remove the unused `readFileSync` import.
- Replace the max-scan block (lines 118-129) with:

```js
        const { start, step, values } = apiJson.data;
        const peak = computePeak(values, start, step);

        if (!peak) {
            return { success: false, reason: 'zero-peak' };
        }

        console.log(JSON.stringify(peak));
        return { success: true };
```

- [x] **Step 3: Wire the backfill scraper**

In `scripts/backfill-steamdb.mjs`:
- Add import: `import { buildProxyUrl, loadEnv, mergeDailyHourlyPoints } from './lib/steamdb-parsers.mjs';`
- Remove local `loadEnv` and `buildProxyUrl`.
- Replace `const env = loadEnv();` with `const env = loadEnv(resolve(__dirname, '..', '.env'));`
- Remove the unused `readFileSync` import.
- Replace the points-merging block (lines 85-106) with:

```js
const points = mergeDailyHourlyPoints(dailyData, hourlyData);

console.error(`Got ${points.length} data points`);
process.stdout.write(JSON.stringify(points));
```

- [x] **Step 4: Wire the charts scraper**

In `scripts/scrape-steamdb-charts.mjs`:
- Add import: `import { buildProxyUrl, loadEnv, parseDataTableRows, sleep } from './lib/steamdb-parsers.mjs';`
- Remove local `loadEnv`, `buildProxyUrl`, `sleep`.
- Replace `const env = loadEnv();` with `const env = loadEnv(resolve(__dirname, '..', '.env'));`
- Remove the unused `readFileSync` import.
- Replace the `page.evaluate` body (lines 105-153) with:

```js
                extracted = await page.evaluate((maxRows) => {
                try {
                    if (typeof window.$ !== 'function' || !window.$.fn || !window.$.fn.dataTable) return null;
                    const table = window.$('#table-apps');
                    if (!table.length || !window.$.fn.dataTable.isDataTable(table)) return null;

                    const api = table.DataTable();
                    const total = api.rows().count();
                    if (!total) return null;

                    const strip = (html) => {
                        const d = document.createElement('div');
                        d.innerHTML = html || '';
                        return d.textContent.replace(/\s+/g, ' ').trim();
                    };

                    const data = api.rows().data().toArray();
                    const names = data.map((row) => strip(Array.isArray(row) ? row[2] : ''));

                    return { total, data, names };
                } catch (e) {
                    return null;
                }
            }, limit);
```

Then, immediately after the retry `while` loop (before `if (!extracted)`), convert the raw payload into rows:

```js
        if (extracted) {
            const rows = parseDataTableRows(extracted.data, extracted.names, limit);
            extracted = rows.length ? { total: extracted.total, rows } : null;
        }
```

- [x] **Step 5: Syntax-check every script**

Run:

```bash
node --check scripts/scrape-steamdb-history.mjs
node --check scripts/fetch-steamdb-peak.mjs
node --check scripts/backfill-steamdb.mjs
node --check scripts/scrape-steamdb-charts.mjs
node --check scripts/lib/steamdb-parsers.mjs
```

Expected: no output (success) for all five.

- [x] **Step 6: Full suites + commit**

Run: `bun run test:unit` (parsers tests still green) and `composer test` (PHP untouched, green).

```bash
git add scripts/scrape-steamdb-history.mjs scripts/fetch-steamdb-peak.mjs scripts/backfill-steamdb.mjs scripts/scrape-steamdb-charts.mjs
git commit -m "refactor(scrapers): use shared steamdb parsers"
```

---

## Plan 6 done when

- `composer test` passes: ~151 tests, 1 skipped (5 new CLI tests).
- `bun run test:unit` passes: ~30 tests (15 new parser tests).
- Production diff: env seams in `collect-steam-stats.php` (defaults unchanged when env absent), new `scripts/lib/steamdb-parsers.mjs`, and imports replacing duplicated functions in four scrapers.
- No scraper/browser launches, no live APIs in tests.

## Next plan

7. Playwright E2E: build + test servers (`php -S` + upstream mock), route mocking for `/steam-stats-api/*`, chart/range/timezone/share interactions, header search + import overlay, favorites persistence.

---

**Status: completed (2026-09-21).** All tasks executed and merged to `main`; see the Execution notes at the top for recorded deviations. Checkboxes were ticked retroactively after completion.
