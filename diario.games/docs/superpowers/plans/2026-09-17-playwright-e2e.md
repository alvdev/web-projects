# Playwright E2E — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Add real-browser E2E coverage for the game-page Steam chart (ranges, timezone picker, share download), header search + import overlay, and favorite persistence — running against a hermetic `php -S` server with fixture content and a seeded temp SQLite DB, with all client API calls mocked.

**Architecture:** `tests/e2e/app-server.sh` builds a temp environment (`tests/.tmp/e2e/`) with fixture content and a seeded DB, symlinks `kirby`/`site`, copies `public`/`assets` (Kirby's router only serves static files whose realpath is inside the document root, so symlinking those two breaks assets), and runs `php -S 127.0.0.1:8899` against a small E2E `index.php` that boots Kirby with explicit temp roots. A `DIARIO_DISABLE_PRICES` guard in the prices plugin prevents live price-provider calls during page render. Playwright's `webServer` starts everything; specs mock `/steam-stats-api/*` with `page.route`.

**Tech Stack:** Playwright Test 1.63 (browsers already cached), bun, Kirby 5, PHP built-in server.

**Spec:** `docs/superpowers/specs/2026-09-17-regression-test-suite-design.md` (final phase).

**Scope decisions (YAGNI):**
- The `/steam-stats` page is **not** covered: its server-side render calls `getTrending()` → `fetchGameDetails()` (live Steam store on cache miss) and `getMostPlayed()`; covering it would need a Kirby file-cache pre-seed or an upstream mock. Its API routes and `steamChartData` are already covered by Plan 5 integration tests.
- Only Chromium; `WEBSITE`-wide responsive/multiple-browser matrices are out of scope.
- No CI wiring (project is local-only by decision).

**Prerequisites:** Plans 1–6 merged. Run from `diario.games/`. `bun run test:e2e` builds assets first, so a running `npm run dev` is not required; the E2E temp root has no `.dev` file, so it always uses the built bundle (your dev server and project-root `.dev` are left untouched).

**Execution notes (2026-09-21):** The committed code is authoritative; deviations from the snippets below:
- E2E server port is **8898**, not 8899: the local dev server already listens on 8899 (`vite` 5173 + `php -S localhost:8899`), and `webServer` has `reuseExistingServer: false`. Both `app-server.sh` and `playwright.config.js` use 8898.
- One-time browser install was required: `bunx playwright install chromium` downloads the headless-shell build 1243 that `@playwright/test` 1.63 expects; the pre-existing `chromium-1228` cache (from the `playwright` scraper dependency) is not used by the test runner.
- `.gitignore` also ignores `/test-results/` and `/playwright-report/`.
- Task 2's spec was committed by the human as `ca44322f` ("test: add e2e playwrite chart test") after an interrupted controller commit; content matches the plan verbatim (note the "playwrite" typo).
- Actual E2E result: 7 passed in ~20s. Suites unchanged: PHP 151 tests / 476 assertions / 1 skipped, Vitest 30.
- Real DB isolation verified: no `e2e-game`/`e2e-second` rows in `sqlite/steam_stats.db`.

---

### Task 1: E2E infrastructure + smoke spec

**Files:**
- Create: `playwright.config.js`
- Create: `tests/e2e/app-server.sh`
- Create: `tests/e2e/index.php`
- Create: `tests/e2e/seed-content.php`
- Create: `tests/e2e/seed-db.php`
- Create: `tests/e2e/specs/smoke.spec.js`
- Modify: `site/plugins/alv-prices/index.php`
- Modify: `package.json`

- [x] **Step 1: Install Playwright Test**

Run: `bun add -d @playwright/test@^1.63.0`
Expected: devDependency added, `bun.lock` updated. Browsers are already cached (`~/.cache/ms-playwright/chromium-1228`); if `bunx playwright test` later reports a missing browser, run `bunx playwright install chromium`.

- [x] **Step 2: Add the price-fetch kill switch**

In `site/plugins/alv-prices/index.php`, at the top of the `priceComparison` closure:

```php
        'priceComparison' => function (string $slug, string $gameName): array {
            if (getenv('DIARIO_DISABLE_PRICES')) {
                return [];
            }

            try {
                $fetcher = \Alv\Prices\PriceFetcher::createFromEnv();
                return $fetcher->fetch($slug, $gameName);
            } catch (\Throwable $e) {
                error_log('priceComparison error: ' . $e->getMessage());
                return [];
            }
        },
```

- [x] **Step 3: Create the E2E bootstrap**

`tests/e2e/index.php` (copied into the temp document root by the launcher; Kirby only honors `roots` passed as constructor props, not config options):

```php
<?php

require __DIR__ . '/kirby/bootstrap.php';

$kirby = new \Kirby\Cms\App([
    'roots' => [
        'index' => __DIR__,
        'content' => __DIR__ . '/content',
        'cache' => __DIR__ . '/cache',
        'media' => __DIR__ . '/media',
        'accounts' => __DIR__ . '/accounts',
        'sessions' => __DIR__ . '/sessions',
    ],
    'options' => [
        'debug' => false,
    ],
]);

echo $kirby->render();
```

- [x] **Step 4: Create the fixture seeders**

`tests/e2e/seed-content.php`:

```php
<?php

declare(strict_types=1);

$contentDir = $argv[1] ?? '';
if ($contentDir === '') {
    fwrite(STDERR, "usage: php seed-content.php <content-dir>\n");
    exit(1);
}

@mkdir($contentDir, 0775, true);
file_put_contents($contentDir . '/site.txt', "Title: E2E Site\n");

$games = [
    '2024/03/e2e-game' => [
        'Title' => 'E2E Game',
        'ReleaseDate' => '2024-03-15',
        'IgdbId' => '1',
        'Platforms' => 'PC (Microsoft Windows)',
        'Genres' => 'RPG',
        'Tags' => 'Acción',
        'Screenshots' => 'shot_1',
        'Websites' => '1:https://store.steampowered.com/app/990001/',
    ],
    '2024/04/e2e-second' => [
        'Title' => 'E2E Second',
        'ReleaseDate' => '2024-04-01',
        'IgdbId' => '2',
        'Screenshots' => 'shot_1',
    ],
];

foreach ($games as $path => $fields) {
    $dir = $contentDir . '/games/' . $path;
    @mkdir($dir, 0775, true);

    $parts = ['Title: ' . $fields['Title'], 'Template: game'];
    foreach ($fields as $key => $value) {
        if ($key === 'Title') continue;
        $parts[] = $key . ': ' . $value;
    }

    file_put_contents($dir . '/game.txt', implode("\n\n----\n\n", $parts) . "\n");
}
```

`tests/e2e/seed-db.php`:

```php
<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/site/plugins/alv-steam-stats/classes/SteamStatsDB.php';

$dbPath = getenv('STEAM_STATS_DB_PATH');
if (!$dbPath) {
    fwrite(STDERR, "STEAM_STATS_DB_PATH is required\n");
    exit(1);
}

$db = new \Alv\SteamStats\SteamStatsDB($dbPath);
$now = time();

$db->upsertGame(990001, 'e2e-game', 'E2E Game', 1);
$db->upsertGame(990002, 'e2e-second', 'E2E Second', 2);
$db->setYearMonth('e2e-game', '2024/03', 1);
$db->setYearMonth('e2e-second', '2024/04', 2);

$db->insertPlayerCount(990001, $now - 2 * 86400, 500);
$db->insertPlayerCount(990001, $now - 86400, 1200);
$db->insertPlayerCount(990001, $now - 3600, 1500);
$db->upsertGamePeak(990001, 1800, $now - 10 * 86400);

$db->insertPlayerCount(990002, $now - 3600, 100);
```

The `setYearMonth` calls are required so the root `(:any)` route resolves the pages by DB path instead of attempting an on-the-fly IGDB import. The nonzero counts for both games prevent the footer's live-player fallback.

- [x] **Step 5: Create the server launcher**

`tests/e2e/app-server.sh` (make executable: `chmod +x`):

```bash
#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TMP="$ROOT/tests/.tmp/e2e"

rm -rf "$TMP"
mkdir -p "$TMP/content" "$TMP/cache" "$TMP/media" "$TMP/sessions" "$TMP/accounts"

php "$ROOT/tests/e2e/seed-content.php" "$TMP/content"

E2E_DB="$TMP/steam_stats.db"
STEAM_STATS_DB_PATH="$E2E_DB" php "$ROOT/tests/e2e/seed-db.php"

# Kirby resolves plugin/config paths through these symlinks; public/assets are
# copied because the router only serves static files whose realpath is inside
# the document root.
ln -s "$ROOT/kirby" "$TMP/kirby"
ln -s "$ROOT/site" "$TMP/site"
cp -r "$ROOT/public" "$TMP/public"
cp -r "$ROOT/assets" "$TMP/assets"
cp "$ROOT/tests/e2e/index.php" "$TMP/index.php"

cd "$ROOT"
exec env \
  STEAM_STATS_DB_PATH="$E2E_DB" \
  DIARIO_DISABLE_PRICES=1 \
  php -S 127.0.0.1:8899 -t "$TMP" "$ROOT/kirby/router.php"
```

`kirby/router.php` requires `$_SERVER['DOCUMENT_ROOT'] . '/index.php'`, i.e. the copied `$TMP/index.php`; `kirby/bootstrap.php` finds the autoloader via `dirname(__DIR__)` through the `kirby` symlink, and `site/config/config.php` loads the real `.env` through the `site` symlink.

- [x] **Step 6: Create the Playwright config**

`playwright.config.js`:

```js
import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: 'tests/e2e/specs',
  timeout: 30000,
  expect: { timeout: 10000 },
  fullyParallel: false,
  workers: 1,
  reporter: [['list']],
  use: {
    baseURL: 'http://127.0.0.1:8899',
    timezoneId: 'Europe/Madrid',
    trace: 'retain-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
  webServer: {
    command: 'bash tests/e2e/app-server.sh',
    url: 'http://127.0.0.1:8899/e2e-game',
    reuseExistingServer: false,
    timeout: 60000,
  },
})
```

- [x] **Step 7: Add package scripts**

In `package.json` `scripts`:

```json
        "test:e2e": "bun run build && playwright test",
        "test:all": "bun run test:unit && composer test && bun run test:e2e"
```

- [x] **Step 8: Write the smoke spec**

`tests/e2e/specs/smoke.spec.js`:

```js
import { test, expect } from '@playwright/test'

test('game page renders the steam chart with seeded data', async ({ page }) => {
  await page.goto('/e2e-game')

  await expect(page.locator('#steam-chart-section')).toBeVisible()
  await expect(page.locator('#steam-chart-canvas')).toBeVisible()
  await expect(page.locator('#steam-current')).toHaveText('1.5K')
  await expect(page.locator('#steam-peak-24h')).toHaveText('1.5K')
  await expect(page.locator('#steam-peak-3m')).toHaveText('1.5K')
  await expect(page.locator('#steam-peak-alltime')).toHaveText('1.8K')
})
```

- [x] **Step 9: Run the E2E suite**

Run: `bun run test:e2e`
Expected: build succeeds, server starts, `1 passed`. If the chart is missing, check that `public/assets/.vite/manifest.json` exists after the build and that the temp docroot has no `.dev` file. If `/e2e-game` triggers an import, verify `setYearMonth` ran in `seed-db.php` and that `$TMP/index.php` sets the content root.

- [x] **Step 10: Verify the other suites still pass and commit**

Run: `composer test` (151 tests) and `bun run test:unit` (30 tests).

```bash
git add playwright.config.js tests/e2e site/plugins/alv-prices/index.php package.json bun.lock
git commit -m "test: add playwright e2e infrastructure and smoke spec"
```

---

### Task 2: Chart interactions spec

**Files:**
- Create: `tests/e2e/specs/chart.spec.js`

- [x] **Step 1: Write the tests**

`tests/e2e/specs/chart.spec.js`:

```js
import { test, expect } from '@playwright/test'

test.describe('steam chart interactions', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/e2e-game')
    await expect(page.locator('#steam-chart-canvas')).toBeVisible()
  })

  test('switches the active range tab', async ({ page }) => {
    const tabOneMonth = page.locator('.steam-range-tab[data-range="1m"]')
    const tab48h = page.locator('.steam-range-tab[data-range="48h"]')

    await expect(tab48h).toHaveClass(/bg-neon-cyan\/20/)

    await tabOneMonth.click()

    await expect(tabOneMonth).toHaveClass(/bg-neon-cyan\/20/)
    await expect(tabOneMonth).toHaveClass(/text-neon-cyan/)
    await expect(tab48h).not.toHaveClass(/bg-neon-cyan\/20/)
  })

  test('selects a timezone from suggestions', async ({ page }) => {
    const input = page.locator('#steam-timezone-input')
    const suggestions = page.locator('#steam-tz-suggestions')

    await expect(input).toHaveValue('España - Península y Baleares')

    await input.click()
    await input.fill('Madrid')

    const option = page.locator('.steam-tz-option[data-tz="Europe/Madrid"]')
    await expect(option).toBeVisible()

    await option.click()

    await expect(input).toHaveValue('España - Península y Baleares')
    await expect(suggestions).toBeHidden()
  })

  test('share button downloads a chart image', async ({ page }) => {
    const downloadPromise = page.waitForEvent('download')

    await page.locator('#steam-share-btn').click()

    const download = await downloadPromise
    expect(download.suggestedFilename()).toBe('e2e-game-chart.png')
    await expect(page.locator('#steam-share-btn')).toHaveText('Compartir')
  })
})
```

- [x] **Step 2: Run the spec**

Run: `bunx playwright test tests/e2e/specs/chart.spec.js`
Expected: `3 passed`.

If the timezone initial value differs, check the config `timezoneId` (Europe/Madrid → `getDisplayLabel('Europe/Madrid')` = `España - Península y Baleares`). If the share test times out, confirm the chart canvas is not tainted (no external images in the game page fixture) and that downloads are accepted (Playwright default).

- [x] **Step 3: Commit**

```bash
git add tests/e2e/specs/chart.spec.js
git commit -m "test: cover chart interactions in e2e"
```

---

### Task 3: Search, import overlay, and favorites spec

**Files:**
- Create: `tests/e2e/specs/search-favorites.spec.js`

- [x] **Step 1: Write the tests**

`tests/e2e/specs/search-favorites.spec.js`:

```js
import { test, expect } from '@playwright/test'

const importCandidate = {
  slug: 'e2e-remote',
  name: 'E2E Remote Game',
  cover: '',
  platforms: 'PC',
  year: '2020',
  hasSteam: false,
  exists: false,
  igdbId: 42,
}

function mockSearch(page) {
  return page.route('**/steam-stats-api/search**', (route) => {
    const url = new URL(route.request().url())
    const isIgdb = url.searchParams.get('source') === 'igdb'

    return route.fulfill({
      json: {
        results: isIgdb ? [] : [importCandidate],
        fromIgdb: isIgdb,
      },
    })
  })
}

test('header search renders mocked results', async ({ page }) => {
  await mockSearch(page)
  await page.goto('/e2e-game')

  await page.locator('#steam-header-search input').fill('remote')

  const result = page.locator('.steam-search-results a[href="/e2e-remote"]')
  await expect(result).toBeVisible()
  await expect(result).toHaveAttribute('data-importing', '')
  await expect(result).toContainText('E2E Remote Game')
})

test('import overlay polls progress and redirects', async ({ page }) => {
  let progressCalls = 0

  await mockSearch(page)
  await page.route('**/steam-stats-api/import-game*', (route) =>
    route.fulfill({ json: { id: 'imp1', ok: true } })
  )
  await page.route('**/steam-stats-api/import-progress/imp1', (route) => {
    progressCalls++
    if (progressCalls === 1) {
      return route.fulfill({ json: { phase: 'metadata', text: 'Obteniendo información...' } })
    }
    return route.fulfill({ json: { ready: true, slug: 'e2e-second' } })
  })

  await page.goto('/e2e-game')
  await page.locator('#steam-header-search input').fill('remote')
  await page.locator('.steam-search-results a[href="/e2e-remote"]').click()

  const overlay = page.locator('[role="alertdialog"]')
  await expect(overlay).toBeVisible()
  await expect(overlay.locator('.import-progress-text')).toHaveText('Obteniendo información...')
  await expect(overlay.locator('.import-progress-text')).toHaveText('¡Listo! Redirigiendo…')

  await page.waitForURL('**/e2e-second')
})

test('favorites persist across reload', async ({ page }) => {
  await page.goto('/e2e-game')

  const star = page.locator('.site-fav[data-slug="e2e-game"]').first()
  await expect(star).toHaveText('☆')

  await star.click()
  await expect(star).toHaveText('★')

  const stored = await page.evaluate(() =>
    JSON.parse(localStorage.getItem('site-favorites-v1') || '{}')
  )
  expect(Object.keys(stored)).toEqual(['e2e-game'])
  expect(stored['e2e-game'].title).toBe('E2E Game')

  await page.reload()
  await expect(page.locator('.site-fav[data-slug="e2e-game"]').first()).toHaveText('★')
})
```

- [x] **Step 2: Run the spec**

Run: `bunx playwright test tests/e2e/specs/search-favorites.spec.js`
Expected: `3 passed`.

If the import overlay never appears, ensure the mocked search result has `exists: false` (that is what adds `data-importing`). If the redirect assertion fails, confirm `e2e-second` content exists (seeded by `seed-content.php`) and its DB row has `year_month` so the route resolves without an IGDB import.

- [x] **Step 3: Run the full E2E suite and all suites**

Run: `bun run test:e2e` → `7 passed`.
Run: `composer test` → 151 tests; `bun run test:unit` → 30 tests.

- [x] **Step 4: Verify isolation**

Run: `git status --short | head` (only the new spec untracked) and confirm the real DB has no fixture rows:

```bash
php -r '$p=new PDO("sqlite:sqlite/steam_stats.db"); echo "e2e rows: ".$p->query("SELECT COUNT(*) FROM steam_games WHERE slug IN (\"e2e-game\",\"e2e-second\")")->fetchColumn()."\n";'
```

Expected: `e2e rows: 0`.

- [x] **Step 5: Commit**

```bash
git add tests/e2e/specs/search-favorites.spec.js
git commit -m "test: cover search import and favorites in e2e"
```

---

## Plan 7 done when

- `bun run test:e2e` passes: 7 tests (1 smoke + 3 chart + 3 search/favorites).
- `composer test` (151) and `bun run test:unit` (30) still pass.
- Production diff: the `DIARIO_DISABLE_PRICES` guard only — defaults unchanged.
- Real `content/`, `sqlite/steam_stats.db`, `storage/`, `media/`, `site/cache` untouched; E2E writes stay under `tests/.tmp/e2e/`.

## Project complete

All seven implementation plans from `2026-09-17-regression-test-suite-design.md` are covered:
1. Infrastructure + foundation (Plan 1)
2. Plugin seams + integration part 1 (Plan 2)
3. Prices & banners (Plan 3)
4. Steam stats seams (Plan 4)
5. Kirby routes (Plan 5)
6. CLI + scrapers (Plan 6)
7. Playwright E2E (Plan 7)

---

**Status: completed (2026-09-21).** All tasks executed and merged to `main`; see the Execution notes at the top for recorded deviations. Checkboxes were ticked retroactively after completion.
