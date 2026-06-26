# Steam Stats Cron Pre-Warming Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Decouple Steam data fetching from page rendering via a cron-driven pre-warming endpoint so `/steam-stats` loads instantly.

**Architecture:** A `POST /steam-stats-warm` route calls existing `SteamStats` methods (getMostPlayed, getTrending, updatePlayerHistory) to populate all caches. A system cron job hits this endpoint every 30 minutes. The page loads from cache in ~50ms. An optional "last updated X min ago" badge shows data freshness.

**Tech Stack:** Kirby CMS 5, PHP 8.1+, crontab

---

### Task 1: Add warm key config and env var

**Files:**
- Modify: `site/config/config.php`
- Modify: `.env.example`

- [ ] **Step 1: Add env variable to `.env.example`**

Append to `.env.example`:
```
STEAM_STATS_WARM_KEY=choose_a_secret_key
```

- [ ] **Step 2: Register the option in `config.php`**

Add after the `alv.steam-stats.api-key` line in `site/config/config.php`:
```php
'alv.steam-stats.warm-key' => env('STEAM_STATS_WARM_KEY', ''),
```

- [ ] **Step 3: Add to `.env` for local dev**

Also add to `.env` (actual env file, not checked in) so local testing works. Don't commit `.env`.

- [ ] **Step 4: Commit**

```bash
git add site/config/config.php .env.example
git commit -m "feat(steam-stats): add warm key config option"
```

---

### Task 2: Add warm endpoint route

**Files:**
- Modify: `site/plugins/alv-steam-stats/index.php` (add new route, remove passive hook)
- Modify: `site/plugins/alv-steam-stats/classes/SteamStats.php` (increase default cache TTL)
- Modify: `site/blueprints/site.yml` (increase default cache TTL in Panel)

- [ ] **Step 1: Add the `POST /steam-stats-warm` route**

In `site/plugins/alv-steam-stats/index.php`, after the `steam-stats-update-history` route (line 38), add a new route:

```php
[
    'pattern' => 'steam-stats-warm',
    'method' => 'POST',
    'action' => function () {
        $key = get('key');
        $expectedKey = option('alv.steam-stats.warm-key');
        if ($expectedKey && $key !== $expectedKey) {
            return ['error' => 'unauthorized'];
        }

        $stats = site()->steamStats();
        $stats->getMostPlayed(100);
        $stats->getTrending(100);
        $stats->updatePlayerHistory();

        kirby()->cache('alv/steam-stats.cache')
            ->set('warm-last-run', ['value' => time(), 'timestamp' => time()]);

        return ['status' => 'ok'];
    }
],
```

- [ ] **Step 2: Remove the passive `route:before` hook**

In `site/plugins/alv-steam-stats/index.php`, remove lines 40-53 (the `'hooks' => ['route:before' => function () { ... }]` entry). This hook is redundant now — the cron warms data every 30 minutes. Also remove the trailing comma from the `'routes'` array if needed.

The hooks section should be removed entirely from the plugin config array.

Before:
```php
'routes' => [...],
'hooks' => [
    'route:before' => function () {
        $cache = kirby()->cache('alv/steam-stats.cache');
        $lastUpdate = $cache->get('history-last-update');
        $now = time();
        if ($lastUpdate === null || ($now - $lastUpdate['timestamp']) > 21600) {
            $stats = site()->steamStats();
            $stats->updatePlayerHistory();
            $cache->set('history-last-update', ['timestamp' => $now]);
        }
    }
],
```

After:
```php
'routes' => [...],
```

- [ ] **Step 3: Increase default cache TTL**

In `SteamStats.php` constructor (line 16), change default from 3600 to 7200:

```php
$this->cacheTtl = $settings['cache_ttl'] ?? 7200;
```

In `site/blueprints/site.yml` line 75, change default from 3600 to 7200:

```yaml
default: 7200
help: "How long to cache most played data (default: 2 hours)"
```

- [ ] **Step 4: Test the warm endpoint locally**

Start the dev server and run:

```bash
curl -s -X POST http://localhost:5173/steam-stats-warm -d "key=YOUR_WARM_KEY"
```

Expected response:
```json
{"status":"ok"}
```

- [ ] **Step 5: Test that the page loads from cache**

Visit `/steam-stats` in the browser. Should render instantly (check Network tab — should be < 100ms).

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-steam-stats/index.php site/plugins/alv-steam-stats/classes/SteamStats.php site/blueprints/site.yml
git commit -m "feat(steam-stats): add warm endpoint, increase TTL, remove passive hook"
```

---

### Task 3: Add "last updated" badge to page

**Files:**
- Modify: `site/plugins/alv-steam-stats/templates/steam-stats.php`
- Modify: `site/plugins/alv-steam-stats/snippets/steam-stats-tabs.php`

- [ ] **Step 1: Add "last updated" badge to the full page template**

In `site/plugins/alv-steam-stats/templates/steam-stats.php`, after the subtitle `<p class="text-sm text-muted mt-1">` (line 42), add:

```php
<?php
$warmCache = kirby()->cache('alv/steam-stats.cache')->get('warm-last-run');
$lastRun = $warmCache['value'] ?? null;
if ($lastRun):
    $ago = time() - $lastRun;
?>
<p class="text-xs text-muted mt-2">
    Last updated <span id="minutes-ago"><?= round($ago / 60) ?></span> min ago
</p>
<script>
(function(){
    var ts = <?= $lastRun ?>;
    var el = document.getElementById('minutes-ago');
    if (el) {
        el.textContent = Math.round((Date.now() / 1000 - ts) / 60);
        setInterval(function(){
            el.textContent = Math.round((Date.now() / 1000 - ts) / 60);
        }, 60000);
    }
})();
</script>
<?php endif ?>
```

- [ ] **Step 2: Add badge to widget snippet (optional but consistent)**

In `site/plugins/alv-steam-stats/snippets/steam-stats-tabs.php`, after the title/header area (around line 50, before the tab buttons), add the same badge markup. Read $lastRun the same way.

- [ ] **Step 3: Verify both pages show the badge**

Visit `/steam-stats` and the homepage (which embeds the widget). Confirm the badge displays "Last updated X min ago" with the correct count.

- [ ] **Step 4: Commit**

```bash
git add site/plugins/alv-steam-stats/templates/steam-stats.php site/plugins/alv-steam-stats/snippets/steam-stats-tabs.php
git commit -m "feat(steam-stats): add last-updated badge to templates"
```

---

### Task 4: Configure cron on server

**Files:**
- Modify: `site/plugins/alv-steam-stats/README.md` (update cron documentation)

- [ ] **Step 1: Update README with new cron entry**

In `site/plugins/alv-steam-stats/README.md`, replace the old cron example with:

```
# Steam Stats - Cron Setup

Add to crontab to warm caches every 30 minutes:

*/30 * * * * curl -s -X POST https://diario.games/steam-stats-warm -d "key=YOUR_STEAM_STATS_WARM_KEY" >/dev/null 2>&1

This pre-fetches all Steam data (most played, trending, player counts, game details, history)
so the /steam-stats page always loads instantly from cache.

Optional: verify the endpoint works:
curl -s -X POST https://diario.games/steam-stats-warm -d "key=YOUR_KEY"
```

- [ ] **Step 2: Install crontab on production server**

SSH into the server and run:
```bash
crontab -e
```

Add the entry from step 1 using the actual warm key from `.env`.

- [ ] **Step 3: Verify cron is working**

Wait 1-2 minutes after setting up the cron, then visit `/steam-stats`. Check the "Last updated" badge shows a recent timestamp.

- [ ] **Step 4: Commit README update**

```bash
git add site/plugins/alv-steam-stats/README.md
git commit -m "docs(steam-stats): update cron documentation for warm endpoint"
```
