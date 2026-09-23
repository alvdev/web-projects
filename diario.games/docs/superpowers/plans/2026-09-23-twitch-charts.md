# Twitch Charts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Twitch charts homepage widget (Juegos / Streamers / En español) plus a full `/twitch-stats` page, using the official Twitch Helix API, TwitchTracker's documented basic API, and self-collected SQLite history.

**Architecture:** New `alv-twitch-stats` Kirby plugin mirroring `alv-steam-stats`: transport classes (`TwitchClient`, `TwitchTrackerClient`), a SQLite store (`TwitchStatsDB`), a snapshot collector, and an orchestrator (`TwitchStats`) with a `fixture_file` setting that all test levels use to avoid live network calls. Caches live in the `alv/twitch-stats.cache` namespace.

**Tech Stack:** PHP 8.4 / Kirby 5, SQLite (PDO), Tailwind utility classes, PHPUnit 11, Playwright.

**Spec:** `docs/superpowers/specs/2026-09-23-twitch-charts-design.md`

**Conventions:** TDD (RED → verify fail → GREEN → verify pass → commit). Run PHP tests with `composer test` (all) or `vendor/bin/phpunit --filter <Name>`. No comments in code. Spanish UI copy.

---

## File Structure

| File | Responsibility |
|---|---|
| `site/plugins/alv-twitch-stats/index.php` | Registration: classes, snippets, templates, routes, siteMethods, options |
| `site/plugins/alv-twitch-stats/classes/TwitchClient.php` | Helix transport + OAuth token cache + parsers |
| `site/plugins/alv-twitch-stats/classes/TwitchTrackerClient.php` | TwitchTracker summary transport + parser |
| `site/plugins/alv-twitch-stats/classes/TwitchStatsDB.php` | SQLite metadata + viewer snapshots + queries |
| `site/plugins/alv-twitch-stats/classes/TwitchStatsCollector.php` | Hourly snapshot + prune |
| `site/plugins/alv-twitch-stats/classes/TwitchStats.php` | Ranking assembly, TTL caches, tracker enrichment, history merge, fixture seam |
| `site/plugins/alv-twitch-stats/snippets/twitch-stats-tabs.php` | Homepage widget |
| `site/plugins/alv-twitch-stats/templates/twitch-stats.php` | Full rankings page |
| `site/plugins/alv-twitch-stats/blueprints/twitch-stats.yml` | Panel TTL settings |
| `site/plugins/alv-twitch-stats/README.md` | Setup + cron docs |
| `scripts/collect-twitch-stats.php` | CLI `snapshot` / `prune` |
| `site/config/config.php` | Options, cache namespace, reserved slugs, `games/by-igdb-id` route |
| `site/templates/home.php` | Replace `data-home-reserved` with widget |
| `tests/Support/FakeTwitchClient.php` | Test double for collector/assembly unit tests |
| `tests/Support/TempTwitchDatabase.php` | Temp SQLite trait (env `TWITCH_STATS_DB_PATH`) |
| `tests/phpunit/Unit/TwitchStats/*` | Parser/DB/collector/assembly tests |
| `tests/phpunit/Integration/*` | Homepage + page route tests |
| `tests/phpunit/Cli/CollectTwitchStatsCliTest.php` | CLI test |
| `tests/e2e/seed-twitch.php`, `tests/e2e/specs/twitch.spec.js`, `tests/e2e/fixtures/twitch.json` | E2E fixtures + specs |

---

## Task 1: TwitchClient (Helix transport + parsers)

**Files:**
- Create: `site/plugins/alv-twitch-stats/classes/TwitchClient.php`
- Create: `tests/phpunit/Unit/TwitchStats/TwitchClientParseTest.php`
- Modify: `tests/Support/PluginClasses.php` (add class paths)

- [ ] **Step 1: Write the failing parser test**

```php
<?php
declare(strict_types=1);
namespace Tests\Unit\TwitchStats;

use Alv\TwitchStats\TwitchClient;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class TwitchClientParseTest extends TestCase
{
    public static function setUpBeforeClass(): void { PluginClasses::load(); }

    public function testParseTopGamesKeepsIgdbIdAndNormalizesBoxArt(): void
    {
        $json = ['data' => [[
            'id' => '509658', 'name' => 'Just Chatting',
            'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/509658-{width}x{height}.jpg',
            'igdb_id' => '509658',
        ]]];

        $games = TwitchClient::parseTopGames($json);

        $this->assertSame([[
            'id' => '509658',
            'name' => 'Just Chatting',
            'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/509658-144x192.jpg',
            'igdb_id' => 509658,
        ]], $games);
    }

    public function testParseTopGamesWithoutIgdbIdUsesNull(): void
    {
        $games = TwitchClient::parseTopGames(['data' => [[
            'id' => '509672', 'name' => 'IRL', 'box_art_url' => 'https://x/{width}x{height}.jpg',
        ]]]);
        $this->assertNull($games[0]['igdb_id']);
    }

    public function testParseStreamsCastsViewerCountAndKeepsLanguageFields(): void
    {
        $streams = TwitchClient::parseStreams(['data' => [[
            'user_id' => 'u1', 'user_login' => 'ibai', 'user_name' => 'Ibai',
            'game_id' => '509658', 'game_name' => 'Just Chatting', 'viewer_count' => '44300',
        ]]]);

        $this->assertSame('ibai', $streams[0]['user_login']);
        $this->assertSame(44300, $streams[0]['viewer_count']);
        $this->assertSame('Just Chatting', $streams[0]['game_name']);
    }

    public function testParseAvatarsMapsUserIdToImage(): void
    {
        $map = TwitchClient::parseAvatars(['data' => [
            ['id' => 'u1', 'profile_image_url' => 'https://x/a.jpg'],
            ['id' => 'u2', 'profile_image_url' => 'https://x/b.jpg'],
        ]]);
        $this->assertSame(['u1' => 'https://x/a.jpg', 'u2' => 'https://x/b.jpg'], $map);
    }

    public function testParseTopGamesHandlesMalformedPayload(): void
    {
        $this->assertSame([], TwitchClient::parseTopGames([]));
        $this->assertSame([], TwitchClient::parseStreams(['data' => 'nope']));
        $this->assertSame([], TwitchClient::parseAvatars(['data' => [['id' => '']]]));
    }

    public function testIsConfiguredRequiresBothCredentials(): void
    {
        $this->assertFalse((new TwitchClient('', ''))->isConfigured());
        $this->assertFalse((new TwitchClient('id', ''))->isConfigured());
        $this->assertTrue((new TwitchClient('id', 'secret'))->isConfigured());
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit --filter TwitchClientParseTest`
Expected: FAIL (class not found / require path missing).

- [ ] **Step 3: Implement `TwitchClient`**

Public API (non-final for test doubles):
```php
class TwitchClient
{
    public function __construct(string $clientId, string $clientSecret, ?string $tokenPath = null, int $timeout = 10)
    public function isConfigured(): bool
    public function getTopGames(int $first = 100): array
    public function getStreams(int $first = 100, string $language = ''): array
    public function getAvatars(array $userIds): array
    public static function parseTopGames(array $json): array
    public static function parseStreams(array $json): array
    public static function parseAvatars(array $json): array
    public static function boxArt(string $template, int $width = 144, int $height = 192): string
}
```
Implementation notes:
- `tokenPath` defaults to `dirname(__DIR__, 4) . '/storage/twitch_token.json'`.
- `authenticate()` mirrors `IGDBClient::authenticate()` (client-credentials POST to `https://id.twitch.tv/oauth2/token`, token cached as JSON `{token, expires_at}`, refresh 300 s before expiry).
- `get(string $url): ?string` uses curl with headers `Client-Id`, `Authorization: Bearer {token}`, `Accept: application/json`, 10 s timeout; returns null on non-200.
- `getTopGames` → `https://api.twitch.tv/helix/games/top?first={n}`, then `parseTopGames`.
- `getStreams` → `/helix/streams?first={n}` plus `&language=es` when `$language !== ''`.
- `getAvatars` → `/helix/users?id=…` (repeat per 100 ids), `parseAvatars`.
- `boxArt` replaces `{width}`/`{height}`.
- `isConfigured` = both credentials non-empty. All public getters return `[]` when not configured or on transport error (never throw).

- [ ] **Step 4: Add the class to `tests/Support/PluginClasses.php`**

Append `'/site/plugins/alv-twitch-stats/classes/TwitchClient.php'` before the SteamStats entries.

- [ ] **Step 5: Run to verify it passes**

Run: `vendor/bin/phpunit --filter TwitchClientParseTest`
Expected: PASS (6 tests).

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-twitch-stats/classes/TwitchClient.php tests/phpunit/Unit/TwitchStats/TwitchClientParseTest.php tests/Support/PluginClasses.php
git commit -m "feat(twitch): add Helix client and parsers"
```

---

## Task 2: TwitchTrackerClient

**Files:**
- Create: `site/plugins/alv-twitch-stats/classes/TwitchTrackerClient.php`
- Create: `tests/phpunit/Unit/TwitchStats/TwitchTrackerClientTest.php`
- Modify: `tests/Support/PluginClasses.php`

- [ ] **Step 1: Write the failing test**

```php
final class TwitchTrackerClientTest extends TestCase
{
    public static function setUpBeforeClass(): void { PluginClasses::load(); }

    public function testParseSummaryCastsNumericFields(): void
    {
        $summary = TwitchTrackerClient::parseSummary('{"avg_viewers":272891,"avg_channels":4421,"rank":1,"hours_watched":45936718}');
        $this->assertSame(272891, $summary['avg_viewers']);
        $this->assertSame(1, $summary['rank']);
    }

    public function testParseSummaryReturnsNullForInvalidJson(): void
    {
        $this->assertNull(TwitchTrackerClient::parseSummary('<html>challenge</html>'));
        $this->assertNull(TwitchTrackerClient::parseSummary(''));
        $this->assertNull(TwitchTrackerClient::parseSummary('{}'));
    }

    public function testGetGameSummaryUsesInjectedHttp(): void
    {
        $seen = [];
        $client = new TwitchTrackerClient(function (string $url) use (&$seen): string {
            $seen[] = $url;
            return '{"avg_viewers":100,"avg_channels":2,"rank":4,"hours_watched":900}';
        });

        $summary = $client->getGameSummary(509658);

        $this->assertSame('https://twitchtracker.com/api/games/summary/509658', $seen[0]);
        $this->assertSame(100, $summary['avg_viewers']);
    }

    public function testGetChannelSummaryReturnsNullOnTransportFailure(): void
    {
        $client = new TwitchTrackerClient(fn (string $url): ?string => null);
        $this->assertNull($client->getChannelSummary('ibai'));
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit --filter TwitchTrackerClientTest`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement `TwitchTrackerClient`**

```php
class TwitchTrackerClient
{
    public function __construct(private ?\Closure $http = null, private int $timeout = 10) {}
    public function getGameSummary(string|int $twitchId): ?array;   // /api/games/summary/{id}
    public function getChannelSummary(string $login): ?array;       // /api/channels/summary/{login}
    public static function parseSummary(?string $body): ?array;     // null unless numeric avg_viewers+rank
    protected function fetch(string $url): ?string;                 // injected closure or curl, UA "DiarioGames/1.0 (+https://diario.games)"
}
```
- `parseSummary` requires keys `avg_viewers` and `rank` both numeric, else null.
- URL-encode logins with `rawurlencode`.

- [ ] **Step 4: Register the class in `PluginClasses.php`**, then run:

Run: `vendor/bin/phpunit --filter TwitchTrackerClientTest`
Expected: PASS (4 tests).

- [ ] **Step 5: Commit**

```bash
git add site/plugins/alv-twitch-stats/classes/TwitchTrackerClient.php tests/phpunit/Unit/TwitchStats/TwitchTrackerClientTest.php tests/Support/PluginClasses.php
git commit -m "feat(twitch): add TwitchTracker summary client"
```

---

## Task 3: TwitchStatsDB (SQLite)

**Files:**
- Create: `site/plugins/alv-twitch-stats/classes/TwitchStatsDB.php`
- Create: `tests/Support/TempTwitchDatabase.php`
- Create: `tests/phpunit/Unit/TwitchStats/TwitchStatsDbTest.php`
- Modify: `tests/Support/PluginClasses.php`

- [ ] **Step 1: Write the failing test**

Cover: schema creation; `upsertGame` idempotent; `upsertStreamer` idempotent; `insertSnapshot` dedupes on `(type,id,timestamp)`; `hasSnapshotAt`; `getSnapshots` ordered by timestamp and filtered by `since`; `getHistorySeries` groups by entity; `getGameByIgdbId`; `getStreamer`; `pruneBefore` returns deleted count; `normalizeEntityType` rejects unknown types.

Key assertions:
```php
$db->upsertGame('509658', 'Just Chatting', 509658, 'https://img/art.jpg');
$db->upsertGame('509658', 'Just Chatting', 509658, 'https://img/art.jpg');
$this->assertSame(1, $db->countGames());

$db->insertSnapshot('game', '509658', 1000, 500, 1);
$db->insertSnapshot('game', '509658', 1000, 999, 2); // ignored (same timestamp)
$this->assertSame(500, $db->getSnapshots('game', '509658', 0)[0]['viewers']);

$this->assertTrue($db->hasSnapshotAt('game', 1000));
$this->assertFalse($db->hasSnapshotAt('streamer', 1000));

$db->insertSnapshot('streamer', 'u1', 1000, 10, 1, 'Just Chatting');
$series = $db->getHistorySeries('streamer', 0);
$this->assertSame([1000], array_column($series['u1'], 'timestamp'));

$this->assertSame(2, $db->pruneBefore(2000));
```

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit --filter TwitchStatsDbTest`
Expected: FAIL (class not found).

- [ ] **Step 3: Implement `TwitchStatsDB`** with the schema from the spec, `?string $dbPath` defaulting to `getenv('TWITCH_STATS_DB_PATH') ?: dirname(__DIR__, 4) . '/sqlite/twitch_stats.db'`, WAL mode, and the public methods listed above. `getHistorySeries(string $type, int $since): array` returns `[entityId => [{timestamp,viewers,rank}]]`.

- [ ] **Step 4: Add `TempTwitchDatabase` trait** (copy of `TempDatabase` swapping the env var/path to `TWITCH_STATS_DB_PATH` / `twitch_stats.db`), register class in `PluginClasses.php`.

- [ ] **Step 5: Run to verify it passes**

Run: `vendor/bin/phpunit --filter TwitchStatsDbTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add site/plugins/alv-twitch-stats/classes/TwitchStatsDB.php tests/Support/TempTwitchDatabase.php tests/phpunit/Unit/TwitchStats/TwitchStatsDbTest.php tests/Support/PluginClasses.php
git commit -m "feat(twitch): add SQLite stats store"
```

---

## Task 4: TwitchStatsCollector + FakeTwitchClient

**Files:**
- Create: `site/plugins/alv-twitch-stats/classes/TwitchStatsCollector.php`
- Create: `tests/Support/FakeTwitchClient.php`
- Create: `tests/phpunit/Unit/TwitchStats/TwitchStatsCollectorTest.php`
- Modify: `tests/Support/PluginClasses.php`

- [ ] **Step 1: Write the failing test**

```php
final class TwitchStatsCollectorTest extends TestCase
{
    use TempTwitchDatabase;
    public static function setUpBeforeClass(): void { PluginClasses::load(); }
    protected function setUp(): void { $this->setUpTempDatabase(); }
    protected function tearDown(): void { $this->tearDownTempDatabase(); }

    public function testSnapshotWritesGamesStreamersAndSpanishOncePerHour(): void
    {
        $db = new TwitchStatsDB($this->tempDatabasePath);
        $client = new FakeTwitchClient(
            games: [['id' => '1', 'name' => 'Game', 'box_art_url' => 'a.jpg', 'igdb_id' => 1]],
            streams: [['user_id' => 'u1', 'user_login' => 'ibai', 'user_name' => 'Ibai', 'game_id' => '1', 'game_name' => 'Game', 'viewer_count' => 100]],
            spanish: [['user_id' => 'u2', 'user_login' => 'rubius', 'user_name' => 'Rubius', 'game_id' => '1', 'game_name' => 'Game', 'viewer_count' => 50]],
            avatars: ['u1' => 'a1.jpg', 'u2' => 'a2.jpg'],
        );
        $collector = new TwitchStatsCollector($client, $db);

        $first = $collector->snapshot(3661);   // hour slot 3600
        $this->assertSame(['games' => 1, 'streamers' => 1, 'spanish' => 1, 'skipped' => false], $first);
        $this->assertSame(3, $db->countSnapshots());
        $this->assertSame('a1.jpg', $db->getStreamer('u1')['avatar_url']);

        $second = $collector->snapshot(3700);  // same hour slot
        $this->assertTrue($second['skipped']);
        $this->assertSame(3, $db->countSnapshots());
    }

    public function testSnapshotWithUnconfiguredClientSkips(): void
    {
        $collector = new TwitchStatsCollector(new TwitchClient('', ''), new TwitchStatsDB($this->tempDatabasePath));
        $this->assertSame(['games' => 0, 'streamers' => 0, 'spanish' => 0, 'skipped' => true], $collector->snapshot(1000));
    }
}
```

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit --filter TwitchStatsCollectorTest`
Expected: FAIL.

- [ ] **Step 3: Implement `FakeTwitchClient`** (extends `TwitchClient`, overrides `isConfigured` → true, `getTopGames`, `getStreams` — returning `$spanish` for `language === 'es'`, `getAvatars`).

- [ ] **Step 4: Implement `TwitchStatsCollector`**

```php
class TwitchStatsCollector
{
    public function __construct(private TwitchClient $client, private ?TwitchStatsDB $db = null)
    public function snapshot(?int $now = null): array   // hour-slot guard; upsert + insert snapshots; returns counts
    public function prune(?int $now = null): int        // removes snapshots older than history_ttl option (default 90 days)
}
```
Snapshot types: `game`, `streamer`, `streamer_es`. Streamers upserted with language `''` / `'es'`. Returns `skipped => true` when client unconfigured or hour already snapped.

- [ ] **Step 5: Run tests, then commit**

Run: `vendor/bin/phpunit --filter "TwitchStatsCollectorTest|TwitchStatsDbTest"` → PASS

```bash
git add site/plugins/alv-twitch-stats/classes/TwitchStatsCollector.php tests/Support/FakeTwitchClient.php tests/phpunit/Unit/TwitchStats/TwitchStatsCollectorTest.php tests/Support/PluginClasses.php
git commit -m "feat(twitch): add snapshot collector"
```

---

## Task 5: TwitchStats assembly + fixture seam

**Files:**
- Create: `site/plugins/alv-twitch-stats/classes/TwitchStats.php`
- Create: `tests/phpunit/Unit/TwitchStats/TwitchStatsAssemblyTest.php`
- Modify: `tests/Support/PluginClasses.php`

- [ ] **Step 1: Write the failing test**

Use a temp fixture JSON (write to `tests/.tmp/twitch-fixture.json`) and a cached-file-less TwitchStats. Assertions:
- `getTopGames(10)` returns rows with `rank` (1-based), `name`, `box_art_url`, `viewers`, `avg_viewers` (from `tracker_games`), `igdb_id`, `history` points and `change_pct` from seeded DB snapshots.
- `getTopStreamers(10)` returns `avatar_url` (from fixture avatars), `avg_viewers`, `followers_total` (tracker_channels), `url` = `https://www.twitch.tv/{login}`.
- `getTopSpanish(10)` only contains `spanish` fixture rows.
- Empty client credentials + no fixture → all getters return `[]`.

Seeded history (DB): game `1` snapshots at now-7200 → 100 viewers and now-3600 → 150 → `change_pct` = 50.0.

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit --filter TwitchStatsAssemblyTest`
Expected: FAIL.

- [ ] **Step 3: Implement `TwitchStats`**

```php
class TwitchStats
{
    public function __construct(
        private array $settings = [],
        private ?TwitchClient $client = null,
        private ?TwitchTrackerClient $tracker = null,
        private ?TwitchStatsDB $db = null,
    ) {}

    public function getTopGames(int $limit = 10): array;
    public function getTopStreamers(int $limit = 10): array;
    public function getTopSpanish(int $limit = 10): array;
}
```
Settings keys: `client_id`, `client_secret`, `cache_ttl` (300), `tracker_ttl` (21600), `history_ttl` (604800), `tracker_limit` (20), `fixture_file` ('').

Behavior:
- `liveGames()/liveStreamers()/liveSpanish()` check `fixture_file` first; otherwise cache key `top-games`/`top-streamers`/`top-spanish` (TTL `cache_ttl`) around client calls.
- Tracker enrichment only for the first `tracker_limit` rows; cache key `tracker.game.{id}` / `tracker.channel.{login}` TTL `tracker_ttl`; failures set `avg_viewers` etc. to null (templates render `–`).
- Avatars: fetch map via client for the streamer rows and cache key `avatars` TTL 86400; merge into rows.
- History: one `getHistorySeries()` query per entity type; compute `change_pct` from first/last snapshots (>0 denominators only), else null.
- Row shape (games): `rank, id, name, box_art_url, viewers, igdb_id, avg_viewers, avg_channels, twitch_rank, hours_watched, history, change_pct`.
- Row shape (streamers): `rank, user_id, login, name, url, avatar_url, game_name, viewers, avg_viewers, max_viewers, followers, followers_total, twitch_rank, history, change_pct`.
- Caches via `kirby()->cache('alv/twitch-stats.cache')`; unit tests run without Kirby → wrap cache access in a `try/catch` helper returning null/no-op when `kirby()` unavailable. (Same pattern as `SteamStats`.)

- [ ] **Step 4: Run tests, then commit**

Run: `vendor/bin/phpunit --filter TwitchStatsAssemblyTest` → PASS

```bash
git add site/plugins/alv-twitch-stats/classes/TwitchStats.php tests/phpunit/Unit/TwitchStats/TwitchStatsAssemblyTest.php tests/Support/PluginClasses.php
git commit -m "feat(twitch): assemble rankings with tracker enrichment and history"
```

---

## Task 6: Plugin registration, config wiring, warm endpoint

**Files:**
- Create: `site/plugins/alv-twitch-stats/index.php`
- Create: `site/plugins/alv-twitch-stats/blueprints/twitch-stats.yml`
- Modify: `site/config/config.php`
- Create: `tests/phpunit/Integration/TwitchWarmRouteTest.php`

- [ ] **Step 1: Write the failing integration test**

Boot `RouteTestApp` with a fixture file option and assert:
- `RouteTestApp::call('twitch-stats-warm', ['key' => 'test-key'], 'POST')` returns `['status' => 'ok', ...]` and rejects a bad key with `['error' => 'unauthorized']`.
- After warm, `RouteTestApp::app()->cache('alv/twitch-stats.cache')` has `warm-last-run`.
- `twitch-stats-api/...` not needed.

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit --filter TwitchWarmRouteTest`
Expected: FAIL (route 404).

- [ ] **Step 3: Implement `index.php`**

`App::plugin('alv/twitch-stats', [...])` with:
- `snippets` → `twitch-stats-tabs`
- `templates` → `twitch-stats`
- `routes`:
  - `twitch-stats` GET → `Page::factory(['slug' => 'twitch-stats', 'template' => 'twitch-stats', 'content' => ['title' => 'Twitch Charts']])->render()`
  - `twitch-stats-warm` POST → key check against `option('alv.twitch-stats.warm-key')`; then `site()->twitchStats()` fetch the 3 lists (warm caches), `TwitchStatsCollector::snapshot()`, `prune()`, set `warm-last-run`, return `['status' => 'ok', 'snapshot' => [...]]`
- `siteMethods`:
  - `twitchStatsSettings()` → credentials from `option('igdb')`, TTLs from options, `fixture_file` from `option('alv.twitch-stats.fixture-file', '')`
  - `twitchStats()` → `new \Alv\TwitchStats\TwitchStats($settings)`
- `options` defaults for cache + TTLs

- [ ] **Step 4: Wire `site/config/config.php`**

```php
'alv.twitch-stats.warm-key' => env('TWITCH_STATS_WARM_KEY', env('STEAM_STATS_WARM_KEY', '')),
'alv.twitch-stats.cache-ttl' => (int) env('TWITCH_STATS_CACHE_TTL', 300),
'alv.twitch-stats.tracker-ttl' => (int) env('TWITCH_STATS_TRACKER_TTL', 21600),
'alv.twitch-stats.history-ttl' => (int) env('TWITCH_STATS_HISTORY_TTL', 7776000),
'alv.twitch-stats.fixture-file' => env('TWITCH_STATS_FIXTURE', ''),
'cache.alv/twitch-stats.cache' => ['type' => 'file', 'active' => true],
```
Add `'twitch-stats'` to both reserved-slug arrays at lines 99 and 145.

- [ ] **Step 5: Run tests + commit**

Run: `vendor/bin/phpunit --filter TwitchWarmRouteTest` → PASS

```bash
git add site/plugins/alv-twitch-stats/index.php site/plugins/alv-twitch-stats/blueprints/twitch-stats.yml site/config/config.php tests/phpunit/Integration/TwitchWarmRouteTest.php
git commit -m "feat(twitch): register plugin routes and caches"
```

---

## Task 7: Homepage widget

**Files:**
- Create: `site/plugins/alv-twitch-stats/snippets/twitch-stats-tabs.php`
- Modify: `site/templates/home.php` (replace `data-home-reserved` block)
- Modify: `tests/phpunit/Integration/HomePagePostsTest.php:90-97`
- Modify: `tests/phpunit/Integration/HomePageFallbackTest.php:55`
- Create: `tests/phpunit/Integration/HomePageTwitchTest.php`

- [ ] **Step 1: Update the two existing reserved-column tests to assert the widget**

Replace the `data-home-reserved` assertions with:
```php
$this->assertStringContainsString('data-twitch-widget', $rowOne);
$this->assertStringContainsString('data-twitch-tab="games"', $rowOne);
```
(and analogous in `HomePageFallbackTest`).

- [ ] **Step 2: Write the failing new test**

Boot `RouteTestApp` with a fixture file; render home; assert:
- `data-twitch-widget` present in the charts row segment
- 3 tab buttons: `data-twitch-tab="games"|"streamers"|"spanish"`
- 10 rows per tab: `substr_count($html, 'data-twitch-row')` count per content block
- game row links: fixture game with `igdb_id` matching an existing test game page links to its slug; a game without a page links to `/games/by-igdb-id/{id}` and carries `data-importing`
- empty state renders when no credentials/fixture (covered by fallback test)

- [ ] **Step 3: Run both to verify they fail**

Run: `vendor/bin/phpunit --filter "HomePageTwitchTest|HomePagePostsTest|HomePageFallbackTest"`
Expected: new test FAIL; updated existing tests FAIL.

- [ ] **Step 4: Implement the snippet**

- `$stats = site()->twitchStats();` fetch 10 per list.
- Wrap in `data-twitch-widget`; card classes identical to `steam-stats-tabs`.
- Tabs: `twitch-tab` buttons with `data-twitch-tab`, active = neon-cyan; Spanish active = neon-magenta; streamers = neon-green. Content divs `data-twitch-tab-content`.
- Game row: rank badge, box art (`w-20 h-7.5 object-cover rounded`), name link, viewers now, `Media 30d` (`–` when null). Link resolver: build `$igdbToSlug` map from `site()->index()->filterBy('intendedTemplate', 'game')` using `IgdbId`; if `igdb_id` and map hit → `/{slug}`; if `igdb_id` and miss → `/games/by-igdb-id/{id}` + `data-importing rel="nofollow"`; if no `igdb_id` → `https://www.twitch.tv/directory/category/{id}` with `target="_blank" rel="noopener"`.
- Streamer row: rank badge, avatar, name linking to `https://www.twitch.tv/{login}` (`target="_blank" rel="noopener"`), category, viewers, `Media 30d`.
- Formatting helper `twitchFormatViewers(int|null)` (K/M).
- Footer: link `/twitch-stats` ("Ver rankings completos →") + note "Datos de Twitch · Medias 30d: TwitchTracker".
- Empty state per tab: `No hay datos`.
- Inline tab JS scoped to `data-twitch-widget` (mirror `steam-stats-tabs` tab logic; no favorites).
- In `home.php`, replace the reserved div with `<?php snippet('twitch-stats-tabs') ?>` (no `hidden lg:block`).

- [ ] **Step 5: Run tests to verify they pass**, then commit

Run: `vendor/bin/phpunit --filter "HomePageTwitchTest|HomePagePostsTest|HomePageFallbackTest"` → PASS

```bash
git add site/plugins/alv-twitch-stats/snippets/twitch-stats-tabs.php site/templates/home.php tests/phpunit/Integration/HomePageTwitchTest.php tests/phpunit/Integration/HomePagePostsTest.php tests/phpunit/Integration/HomePageFallbackTest.php
git commit -m "feat(twitch): add homepage charts widget"
```

---

## Task 8: Full `/twitch-stats` page

**Files:**
- Create: `site/plugins/alv-twitch-stats/templates/twitch-stats.php`
- Create: `tests/phpunit/Integration/TwitchStatsPageTest.php`

- [ ] **Step 1: Write the failing test**

Boot `RouteTestApp` with fixture; `RouteTestApp::call('twitch-stats')`; assert:
- 200 render includes `Twitch Charts`, the 3 tabs, 100 `data-twitch-page-row` per tab when fixture has 100 rows (fixture can use 3 rows; assert `count($fixtureRows['games'])` rows instead).
- Rows include `Media 30d` and sparkline `<svg` when history seeded.
- Page includes `warm-last-run` indicator when cache has it.
- No credentials + no fixture: page renders with "No hay datos".

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit --filter TwitchStatsPageTest` → FAIL

- [ ] **Step 3: Implement the template**

- `snippet('header')`, then `$stats = site()->twitchStats();` with limits 100.
- Header: `Twitch Charts`, subtitle, "Last updated X min ago" (copy Steam pattern), attribution line.
- Tabs (same classes as steam page, colors cyan/green/magenta), each content block server-renders rows with:
  - Games columns: rank / box art / name / Ahora / Media 30d / Horas 30d / Twitch rank / 7d sparkline + %.
  - Streamers & Spanish columns: rank / avatar / name / category / Ahora / Media 30d / Máx. 30d / Seguidores / sparkline + %.
- Sparkline helper `twitchSparkline(array $history, int $width = 100, int $height = 30): string` (same math as `steamSparkline`, stroke `#a970ff`).
- `twitchFormatViewers()`, `twitchFormatChange()` helpers.
- Tab JS scoped to the page container.
- `snippet('footer')`.

- [ ] **Step 4: Run test to verify pass**, then commit

Run: `vendor/bin/phpunit --filter TwitchStatsPageTest` → PASS

```bash
git add site/plugins/alv-twitch-stats/templates/twitch-stats.php tests/phpunit/Integration/TwitchStatsPageTest.php
git commit -m "feat(twitch): add full rankings page"
```

---

## Task 9: `games/by-igdb-id` route

**Files:**
- Modify: `site/config/config.php` (add route after `games/by-appid`)
- Create: `tests/phpunit/Integration/ByIgdbIdRouteTest.php`

- [ ] **Step 1: Write the failing test**

- Existing game with `IgdbId: 1` → `RouteTestApp::call('games/by-igdb-id/1')` redirects to `/alpha-quest` (assert via TestableRouteApp redirect capture or by asserting no 404 + `go()` behavior; follow the pattern used by existing route tests — if no redirect capture exists, assert the route returns null/404 when IGDB is unconfigured and the fallback is a redirect to twitch directory).
- With empty IGDB credentials and unknown id → response is a redirect (`\Kirby\Http\Response` with 302 `Location: https://www.twitch.tv/directory/category/999999`).

- [ ] **Step 2: Run to verify it fails**, then **Step 3: implement** the route per spec:

```php
'pattern' => 'games/by-igdb-id/(:num)',
'method' => 'GET',
'action' => function (string $igdbIdStr) {
    $igdbId = (int) $igdbIdStr;
    foreach (site()->index()->filterBy('intendedTemplate', 'game') as $g) {
        if ((int) $g->content()->get('IgdbId')->value() === $igdbId) go('/' . $g->slug(), 301);
    }
    $config = kirby()->option('igdb');
    if (!empty($config['client_id']) && !empty($config['client_secret'])) {
        try {
            require_once dirname(__DIR__, 2) . '/site/plugins/alv-igdb/classes/IGDBClient.php';
            $client = new \DiarioGames\IGDB\IGDBClient($config['client_id'], $config['client_secret']);
            $gameData = $client->fetchGameById($igdbId);
            if (!empty($gameData['slug'])) go('/' . $gameData['slug'], 302);
        } catch (\Throwable $e) {
            error_log('by-igdb-id slug lookup failed for ' . $igdbId . ': ' . $e->getMessage());
        }
    }
    go('https://www.twitch.tv/directory/category/' . $igdbId, 302);
}
```
(Adjust `require_once` path to match the existing by-appid route style.)

- [ ] **Step 4: Run test, commit**

```bash
git add site/config/config.php tests/phpunit/Integration/ByIgdbIdRouteTest.php
git commit -m "feat(twitch): add by-igdb-id import entry route"
```

---

## Task 10: CLI + docs

**Files:**
- Create: `scripts/collect-twitch-stats.php`
- Create: `site/plugins/alv-twitch-stats/README.md`
- Modify: `.env.example`
- Create: `tests/phpunit/Cli/CollectTwitchStatsCliTest.php`

- [ ] **Step 1: Write the failing CLI test** mirroring `CollectSteamStatsCliTest` (run via `Tests\Support\CliRunner`): `php scripts/collect-twitch-stats.php` with an unconfigured client prints `Snapshot skipped (Twitch credentials not configured).`; `prune` prints `Pruned N snapshots.`; unknown mode prints usage.
- [ ] **Step 2: Implement the script** (boots Kirby like `collect-steam-stats.php`, reads `igdb` options, `snapshot` default, `prune` mode).
- [ ] **Step 3: Add `.env.example` lines** `TWITCH_STATS_WARM_KEY=` and `TWITCH_STATS_DB_PATH=`.
- [ ] **Step 4: Write README** with data sources, cron `*/5 * * * * curl -s -X POST https://site/twitch-stats-warm -d "key=..."`, CLI usage, fixture option note.
- [ ] **Step 5: Run test + commit**

Run: `vendor/bin/phpunit --filter CollectTwitchStatsCliTest` → PASS

```bash
git add scripts/collect-twitch-stats.php site/plugins/alv-twitch-stats/README.md .env.example tests/phpunit/Cli/CollectTwitchStatsCliTest.php
git commit -m "feat(twitch): add CLI collector and docs"
```

---

## Task 11: E2E fixtures + specs

**Files:**
- Create: `tests/e2e/fixtures/twitch.json`
- Create: `tests/e2e/seed-twitch.php`
- Modify: `tests/e2e/app-server.sh` (seed Twitch DB + export `TWITCH_STATS_FIXTURE`, `TWITCH_STATS_DB_PATH`)
- Modify: `tests/e2e/index.php` (pass `alv.twitch-stats.fixture-file` from env)
- Create: `tests/e2e/specs/twitch.spec.js`

- [ ] **Step 1: Fixture JSON** with 3 games (one `igdb_id` matching seeded `e2e-game` = 1, one unmatched, one without `igdb_id`), 3 streamers, 2 Spanish, tracker maps, avatars; plus history seeded by `seed-twitch.php` into `twitch_stats.db`.
- [ ] **Step 2: `seed-twitch.php`** uses `TwitchStatsDB` to upsert + insert snapshots for the fixture entities.
- [ ] **Step 3: Playwright spec**

```js
test('homepage twitch widget switches tabs', async ({ page }) => {
  await page.goto('/', { waitUntil: 'domcontentloaded' })
  await expect(page.locator('[data-twitch-widget]')).toBeVisible()
  await expect(page.locator('[data-twitch-tab-content="games"] [data-twitch-row]')).toHaveCount(3)
  await page.locator('[data-twitch-tab="spanish"]').click()
  await expect(page.locator('[data-twitch-tab-content="spanish"] [data-twitch-row]')).toHaveCount(2)
})

test('twitch stats page renders tabs and rows', async ({ page }) => {
  await page.goto('/twitch-stats', { waitUntil: 'domcontentloaded' })
  await expect(page.locator('h1')).toContainText('Twitch Charts')
  await page.locator('[data-twitch-page-tab="streamers"]').click()
  await expect(page.locator('[data-twitch-page-content="streamers"] [data-twitch-page-row]').first()).toBeVisible()
})
```

- [ ] **Step 4: Run e2e**

Run: `bun run test:e2e`
Expected: all specs pass (existing 7 + new 2).

- [ ] **Step 5: Commit**

```bash
git add tests/e2e
git commit -m "test(twitch): add e2e fixtures and specs"
```

---

## Task 12: Full verification + spec correction

- [ ] **Step 1: Remove the unimplemented `twitch-stats-api/refresh` mention from the spec** (page is server-rendered; no refresh route) — edit `docs/superpowers/specs/2026-09-23-twitch-charts-design.md` and commit.
- [ ] **Step 2: Run the full fast suite**

```bash
composer test && bun run test:unit && bun run test:e2e
```

- [ ] **Step 3: Manual smoke (real credentials)** — `TWITCH_STATS_WARM_KEY` unset currently; run:
  `php scripts/collect-twitch-stats.php snapshot` and verify rows in `sqlite/twitch_stats.db`; visit `/twitch-stats` locally.
- [ ] **Step 4: Final commit** if any fixes were needed.
