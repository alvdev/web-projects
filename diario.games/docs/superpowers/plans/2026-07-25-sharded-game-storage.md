# Sharded Game Storage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restructure game content from flat `content/games/{slug}/` to sharded `content/games/{year}/{month}/{slug}/` while keeping root-level URLs unchanged.

**Architecture:** A slug-to-path index (`data/games/index.php`) maps each slug to its year/month shard. Routes use the index to resolve `/{slug}` URLs to the correct nested Kirby page. The importer writes to sharded paths and atomically updates the index.

**Tech Stack:** Kirby 5.4.4, PHP 8.1+

---

### File Map

| Action | File | Purpose |
|--------|------|---------|
| Modify | `.gitignore` | Add `data/games/` |
| Modify | `site/plugins/alv-igdb/classes/helpers.php` | Add index helper functions |
| Modify | `site/plugins/alv-igdb/classes/GameImporter.php` | Write to sharded paths, update index |
| Modify | `site/config/config.php` | Resolve slugs via index in routes |
| Create | `scripts/migrate-games-to-sharded.php` | One-shot migration of existing 15 games |

**No changes needed:** `site/models/game.php` (url() override already exists), `site/templates/game.php`, `site/blueprints/pages/game.yml`, `AutoFetcher.php`, `IGDBClient.php`

---

### Task 1: Add index helpers to `helpers.php` + gitignore update

**Files:**
- Modify: `site/plugins/alv-igdb/classes/helpers.php`
- Modify: `.gitignore`

**Purpose:** Add functions to load, query, and save the slug→path index. These are used by both the routes and the importer.

- [ ] **Step 1: Add `data/games/` to `.gitignore`**

Edit `.gitignore` — append after line 67 (`content/`):

```
data/games/
```

- [ ] **Step 2: Add helper functions to `helpers.php`**

Insert after the `downloadImage()` function (before the closing of the file, after line 113):

```php
function getGameIndexPath(): string
{
    return dirname(__DIR__, 4) . '/data/games/index.php';
}

function getGameIndexIgdbPath(): string
{
    return dirname(__DIR__, 4) . '/data/games/index-igdb.php';
}

function loadGameIndex(): array
{
    $path = getGameIndexPath();
    if (!file_exists($path)) return [];
    return include $path;
}

function loadGameIndexIgdb(): array
{
    $path = getGameIndexIgdbPath();
    if (!file_exists($path)) return [];
    return include $path;
}

function resolveGamePath(string $slug): ?string
{
    $index = loadGameIndex();
    return $index[$slug] ?? null;
}

function resolveGameByIgdbId(int $igdbId): ?string
{
    $index = loadGameIndexIgdb();
    return $index[$igdbId] ?? null;
}

function saveGameIndex(array $index): void
{
    $path = getGameIndexPath();
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $tmp = $path . '.tmp';
    file_put_contents($tmp, '<?php return ' . var_export($index, true) . ';' . "\n");
    rename($tmp, $path);

    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($path, true);
    }
}

function saveGameIndexIgdb(array $index): void
{
    $path = getGameIndexIgdbPath();
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $tmp = $path . '.tmp';
    file_put_contents($tmp, '<?php return ' . var_export($index, true) . ';' . "\n");
    rename($tmp, $path);

    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($path, true);
    }
}

function addGameToIndex(string $slug, string $yearMonth, int $igdbId): void
{
    $index = loadGameIndex();
    $index[$slug] = $yearMonth;
    saveGameIndex($index);

    if ($igdbId > 0) {
        $igdbIndex = loadGameIndexIgdb();
        $igdbIndex[$igdbId] = $slug;
        saveGameIndexIgdb($igdbIndex);
    }
}

function deriveYearMonth(string $releaseDate): array
{
    if (preg_match('/^(\d{4})-(\d{2})/', $releaseDate, $m)) {
        return [$m[1], $m[2]];
    }
    if (preg_match('/^(\d{4})/', $releaseDate, $m)) {
        return [$m[1], '00'];
    }
    return ['00', '00'];
}
```

- [ ] **Step 3: Verify syntax**

```bash
php -l site/plugins/alv-igdb/classes/helpers.php
```

Expected: `No syntax errors detected`

- [ ] **Step 4: Commit**

```bash
git add .gitignore site/plugins/alv-igdb/classes/helpers.php
git commit -m "feat: add game index helper functions for sharded storage"
```

---

### Task 2: Update `GameImporter` to write to sharded paths

**Files:**
- Modify: `site/plugins/alv-igdb/classes/GameImporter.php`

**Purpose:** Route all game writes through sharded `{gamesDir}/{year}/{month}/{slug}/` paths and update the index on every import. Replace the O(n) `glob("*/game.txt")` duplicate-IgdbId check with O(1) index lookup.

- [ ] **Step 1: Replace the duplicate-IgdbId check (lines 127-139)**

Replace this block:

```php
        // If another directory already exists with the same IgdbId, use that instead
        $gameId = $gameData['id'] ?? null;
        if ($gameId) {
            $existing = glob("{$this->gamesDir}/*/game.txt");
            foreach ($existing as $path) {
                $content = file_get_contents($path);
                if ($content !== false && preg_match('/^IgdbId:\s*' . preg_quote($gameId, '/') . '\s*$/m', $content)) {
                    $existingSlug = basename(dirname($path));
                    echo "  skipped: {$gameData['name']} (already exists at /{$existingSlug})\n";
                    return $existingSlug;
                }
            }
        }
```

With:

```php
        // If another directory already exists with the same IgdbId, skip
        $gameId = $gameData['id'] ?? null;
        if ($gameId) {
            $existingSlug = \DiarioGames\IGDB\resolveGameByIgdbId((int) $gameId);
            if ($existingSlug) {
                echo "  skipped: {$gameData['name']} (already exists at /{$existingSlug})\n";
                return $existingSlug;
            }
        }
```

- [ ] **Step 2: Derive year/month and build the sharded directory path (around line 120)**

Replace line 120:
```php
        $dir = "{$this->gamesDir}/{$slug}";
```

With:

```php
        [$year, $month] = \DiarioGames\IGDB\deriveYearMonth($releaseDate);
        $yearMonth = "{$year}/{$month}";
        $dir = "{$this->gamesDir}/{$yearMonth}/{$slug}";
```

Note: `$releaseDate` is assigned on lines 165-168 and needs to be available before the directory path is built. Move the release date derivation up — after the `$slug` assignment on line 109, before the directory creation logic.

Edit: After line 109 (`$rawSlug = $gameData['slug'];`), insert:

```php
        $releaseDate = '';
        if (!empty($gameData['first_release_date'])) {
            $releaseDate = date('Y-m-d', $gameData['first_release_date']);
        }
```

Then remove the duplicate release date assignment on lines 165-168:

```php
        $releaseDate = '';
        if (!empty($gameData['first_release_date'])) {
            $releaseDate = date('Y-m-d', $gameData['first_release_date']);
        }
```

- [ ] **Step 3: Add index update at the end of `import()`**

After the `registerSteamGame` call (line 233), before `return $slug;`, insert:

```php
        \DiarioGames\IGDB\addGameToIndex($slug, $yearMonth, (int) $gameId);
```

- [ ] **Step 4: Verify syntax**

```bash
php -l site/plugins/alv-igdb/classes/GameImporter.php
```

Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add site/plugins/alv-igdb/classes/GameImporter.php
git commit -m "feat: write games to sharded year/month paths with index tracking"
```

---

### Task 3: Update `config.php` routes to use the index

**Files:**
- Modify: `site/config/config.php`

**Purpose:** Route 3 (catch-all `(:any)`) and Route 4 (`(:any)/(:all)`) currently use `page('games/' . $slug)` which assumes a flat content tree. Update them to look up the year/month from the index and build the full path.

Note: `helpers.php` is already loaded by the plugin (`site/plugins/alv-igdb/index.php`) on every request, so `resolveGamePath()` is available without an extra `require_once`. But the existing route code on line 99 does `require_once` for safety — we keep the pattern and add `resolveGamePath` usage after it.

- [ ] **Step 1: Update Route 3 (catch-all `(:any)`) — lines 84-137**

Replace the page lookup on lines 100-113:

```php
                $page = page('games/' . $slug);
                if ($page) {
                    $canonical = \DiarioGames\IGDB\romanToDigits($slug);
                    if ($canonical !== $slug) {
                        $oldDir = $igdbRoot . '/content/games/' . $slug;
                        $newDir = $igdbRoot . '/content/games/' . $canonical;
                        if (is_dir($oldDir) && !is_dir($newDir)) {
                            rename($oldDir, $newDir);
                        }
                        go('/' . $canonical, 301);
                    }
                    return $page;
                }
```

With:

```php
                $yearMonth = \DiarioGames\IGDB\resolveGamePath($slug);
                $page = $yearMonth ? page('games/' . $yearMonth . '/' . $slug) : null;
                if ($page) {
                    return $page;
                }
```

- [ ] **Step 2: Update the by-appid route (Route 2, line 59)**

Replace line 59:
```php
                    if ($game && page('games/' . $game['slug'])) {
```

With:
```php
                    $appSlug = $game['slug'] ?? '';
                    $appYearMonth = $appSlug ? \DiarioGames\IGDB\resolveGamePath($appSlug) : null;
                    if ($game && $appYearMonth && page('games/' . $appYearMonth . '/' . $appSlug)) {
```

- [ ] **Step 3: Update Route 4 (child pages `(:any)/(:all)`) — lines 138-157**

Replace line 147:
```php
                $game = page('games/' . $parentSlug);
```

With:

```php
                $yearMonth = \DiarioGames\IGDB\resolveGamePath($parentSlug);
                $game = $yearMonth ? page('games/' . $yearMonth . '/' . $parentSlug) : null;
```

- [ ] **Step 4: Verify syntax**

```bash
php -l site/config/config.php
```

Expected: `No syntax errors detected`

- [ ] **Step 5: Commit**

```bash
git add site/config/config.php
git commit -m "feat: resolve game slugs via sharded index in routes"
```

---

### Task 4: Migration script for existing games

**Files:**
- Create: `scripts/migrate-games-to-sharded.php`

**Purpose:** Move the 15 existing games from `content/games/{slug}/` to `content/games/{year}/{month}/{slug}/` and build the initial index.

- [ ] **Step 1: Create the migration script**

```php
#!/usr/bin/env php
<?php
/**
 * Migrate existing flat game directories to sharded year/month/slug structure.
 *
 * Usage: php scripts/migrate-games-to-sharded.php
 */

$gamesDir = __DIR__ . '/../content/games';

if (!is_dir($gamesDir)) {
    echo "Error: content/games directory not found.\n";
    exit(1);
}

$entries = scandir($gamesDir);
$index = [];
$igdbIndex = [];
$moved = 0;
$skipped = 0;

foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') continue;

    $oldPath = "{$gamesDir}/{$entry}";

    // Skip non-directories (e.g. games.txt, any loose files)
    if (!is_dir($oldPath)) continue;

    // Skip directories that are already sharded (year-named dirs)
    if (preg_match('/^\d{4}$/', $entry)) continue;

    $gameFile = "{$oldPath}/game.txt";
    if (!file_exists($gameFile)) {
        echo "  skip: {$entry} (no game.txt)\n";
        $skipped++;
        continue;
    }

    $content = file_get_contents($gameFile);
    $releaseDate = '';
    if (preg_match('/^ReleaseDate:\s*(.+)$/m', $content, $m)) {
        $releaseDate = trim($m[1]);
    }

    $igdbId = 0;
    if (preg_match('/^IgdbId:\s*(\d+)/m', $content, $m)) {
        $igdbId = (int) $m[1];
    }

    // Derive year/month
    $year = '00';
    $month = '00';
    if (preg_match('/^(\d{4})-(\d{2})/', $releaseDate, $m)) {
        $year = $m[1];
        $month = $m[2];
    } elseif (preg_match('/^(\d{4})/', $releaseDate, $m)) {
        $year = $m[1];
    }

    $newDir = "{$gamesDir}/{$year}/{$month}/{$entry}";
    $newParent = "{$gamesDir}/{$year}/{$month}";

    if (!is_dir($newParent)) {
        mkdir($newParent, 0755, true);
    }

    echo "  move: {$entry} -> games/{$year}/{$month}/{$entry}\n";
    rename($oldPath, $newDir);
    $moved++;

    $index[$entry] = "{$year}/{$month}";
    if ($igdbId > 0) {
        $igdbIndex[$igdbId] = $entry;
    }
}

// Write index files
$indexDir = __DIR__ . '/../data/games';
if (!is_dir($indexDir)) {
    mkdir($indexDir, 0755, true);
}

$indexPath = "{$indexDir}/index.php";
file_put_contents($indexPath, '<?php return ' . var_export($index, true) . ';' . "\n");
echo "\nWrote {$indexPath} (" . count($index) . " entries)\n";

$igdbPath = "{$indexDir}/index-igdb.php";
file_put_contents($igdbPath, '<?php return ' . var_export($igdbIndex, true) . ';' . "\n");
echo "Wrote {$igdbPath} (" . count($igdbIndex) . " entries)\n";

echo "\nDone: {$moved} moved, {$skipped} skipped.\n";
```

- [ ] **Step 2: Run the migration script**

```bash
php scripts/migrate-games-to-sharded.php
```

Expected output: 15 games moved, 0 skipped. Index files created in `data/games/`.

- [ ] **Step 3: Verify the new directory structure**

```bash
find content/games -maxdepth 1 -type d | wc -l
```

Expected: only a few entries (the year dirs: 00, 2023, etc. + maybe some leftover dirs) — no individual game slugs at the top level.

```bash
ls -la data/games/
```

Expected: `index.php` and `index-igdb.php` exist.

- [ ] **Step 4: Remove empty leftover directories (if any)**

```bash
find content/games -maxdepth 1 -type d -empty -delete 2>/dev/null; echo "done"
```

- [ ] **Step 5: Commit**

```bash
git add scripts/migrate-games-to-sharded.php
git commit -m "feat: add migration script for sharded game storage"
```

---

### Task 5: End-to-end verification

**Purpose:** Verify the site works correctly with the new sharded structure.

- [ ] **Step 1: Start the dev server**

```bash
php -S localhost:8888 -t . index.php &
```

Wait a few seconds for the server to start.

- [ ] **Step 2: Test game detail page**

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/counter-strike-2
```

Expected: `200`

- [ ] **Step 3: Test games listing page**

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/games
```

Expected: `200`

- [ ] **Step 4: Test child page routing (if any guides/news exist)**

```bash
# Test that the (:any)/(:all) route still works
curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/counter-strike-2/some-nonexistent-page
```

Expected: `404` (not 500 — the route resolves correctly, just no such child page)

- [ ] **Step 5: Test genre route (ensure system pages unaffected)**

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/genre/shooter
```

Expected: `200`

- [ ] **Step 6: Test home page**

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/
```

Expected: `200`

- [ ] **Step 7: Test that on-the-fly import still works (new game slug)**

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8888/some-nonexistent-slug-xyz
```

Expected: `404` or `200` (404 if IGDB not configured, 200 if it finds and imports the game). Either way, it should NOT produce a PHP error.

- [ ] **Step 8: Stop the dev server**

```bash
kill %1 2>/dev/null; echo "done"
```

- [ ] **Step 9: Verify Panel loads (requires browser, check manually)**

Open `http://localhost:8888/panel` in a browser. Confirm:
- The Games section in the page tree shows year/month containers
- Can navigate to a game under its year/month
- Panel search finds games

- [ ] **Step 10: Clean up and final commit (if any fixes needed)**

---

### Summary

After all tasks are complete:

| Item | Before | After |
|------|--------|-------|
| Content layout | `content/games/{slug}/` | `content/games/{year}/{month}/{slug}/` |
| Max dir entries | 100K+ (broken) | <5K per directory |
| URLs | `/{slug}` | `/{slug}` (unchanged) |
| $page->url() | `/{slug}` | `/{slug}` (unchanged, already overridden) |
| Import write path | Flat dir | Sharded dir + index update |
| Duplicate IGDB check | O(n) glob scan | O(1) index lookup |
| Panel tree | Flat list | Year → Month → Game (hierarchical) |
