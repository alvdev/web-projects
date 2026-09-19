# Prices & Affiliate Banners Test Seams — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Lock in price-adapter parsing, `PriceFetcher` caching/orchestration, and affiliate-banner config/placement with hermetic tests — no live ITAD/G2A/InstantGaming calls.

**Architecture:** Extract pure parsers and small transport seams from the three price adapters (`ItadAdapter::parseDeals` + `httpPostJson`, `G2AAdapter::buildPriceFromOffers` + protected `getToken`/`getOffers`, `InstantGamingAdapter::pickBestHit` public + protected `searchAlgolia`). `PriceFetcher` is tested through its public `fetch()` with a `FakePriceDb` (in-memory subclass, no SQLite) and `FakeStoreAdapter`. Affiliate-banner config parsing and placement move into a pure `Alv\AffBanners\AffiliateBanners` class; the snippet keeps rendering and its `$GLOBALS` dedupe. A small Kirby integration test renders the banner snippet from temp site content.

**Tech Stack:** PHP 8.4, PHPUnit 11, Kirby 5.

**Spec:** `docs/superpowers/specs/2026-09-17-regression-test-suite-design.md` (Phase 2, prices & banners portion). Steam collectors are Plan 4; Kirby routes Plan 5; CLI Plan 6; E2E Plan 7.

**Deviation from spec (recorded):** `PriceFetcher::formatResults`/`anyExpired`/`adapterStoreAllCached` stay `private` — tests exercise them through the public `fetch()` with fakes, so widening visibility is unnecessary (YAGNI). Fixtures are inline arrays shaped like recorded API responses rather than committed JSON files; same determinism, less maintenance.

**Prerequisites:** Plans 1 and 2 merged. Run tests from `diario.games/`. Every DB test uses the `TempDatabase` trait (env-isolated temp SQLite) and `PluginClasses::load()`.

**Safety rules:** production changes additive with identical defaults; no live API calls; no writes to `content/`, `sqlite/`, `storage/`, `media/`, `site/cache`.

---

### Task 1: ItadAdapter parser + transport seams

**Files:**
- Create: `tests/Support/FakeItadAdapter.php`
- Create: `tests/phpunit/Unit/Prices/ItadAdapterTest.php`
- Modify: `site/plugins/alv-prices/classes/adapters/ItadAdapter.php`

- [ ] **Step 1: Create the fake adapter**

`tests/Support/FakeItadAdapter.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\Adapters\ItadAdapter;

class FakeItadAdapter extends ItadAdapter
{
    public array $getResponses = [];
    public ?string $postResponse = null;

    protected function httpGet(string $url): ?string
    {
        foreach ($this->getResponses as $needle => $json) {
            if (str_contains($url, $needle)) {
                return $json;
            }
        }
        return null;
    }

    protected function httpPostJson(string $url, string $jsonBody): ?string
    {
        return $this->postResponse;
    }
}
```

- [ ] **Step 2: Write the failing tests**

`tests/phpunit/Unit/Prices/ItadAdapterTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

use Alv\Prices\Adapters\ItadAdapter;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeItadAdapter;
use Tests\Support\PluginClasses;

final class ItadAdapterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    private function dealsFixture(): array
    {
        return [
            [
                'shop' => ['name' => 'Steam'],
                'price' => ['amount' => 9.99, 'currency' => 'EUR'],
                'regular' => ['amount' => 19.99],
                'cut' => 50,
                'platforms' => [['name' => 'PC'], ['name' => 'Linux']],
                'url' => 'https://itad.example/steam/deal',
            ],
            [
                'shop' => ['name' => 'Unknown Shop'],
                'price' => ['amount' => 1.0, 'currency' => 'EUR'],
                'regular' => ['amount' => 1.0],
                'cut' => 0,
                'platforms' => [],
                'url' => 'https://itad.example/unknown',
            ],
            [
                'shop' => ['name' => 'GOG'],
                'price' => ['amount' => 0, 'currency' => 'EUR'],
                'regular' => ['amount' => 5.0],
                'cut' => 10,
                'platforms' => [],
                'url' => 'https://itad.example/gog',
            ],
        ];
    }

    public function testParseDealsMapsKnownStoresOnly(): void
    {
        $adapter = new ItadAdapter('test-key');

        $results = $adapter->parseDeals($this->dealsFixture());

        $this->assertCount(1, $results);
        $this->assertSame('Steam', $results[0]['storeName']);
        $this->assertSame('steam', $results[0]['storeLogo']);
        $this->assertEqualsWithDelta(9.99, $results[0]['price'], 0.001);
        $this->assertEqualsWithDelta(19.99, $results[0]['initialPrice'], 0.001);
        $this->assertSame(50, $results[0]['discount']);
        $this->assertSame('EUR', $results[0]['currency']);
        $this->assertSame('https://itad.example/steam/deal', $results[0]['url']);
        $this->assertSame('PC, Linux', $results[0]['platforms']);
    }

    public function testParseDealsNullsRedundantInitialPriceAndZeroDiscount(): void
    {
        $adapter = new ItadAdapter('test-key');

        $results = $adapter->parseDeals([[
            'shop' => ['name' => 'GOG'],
            'price' => ['amount' => 5.0, 'currency' => 'EUR'],
            'regular' => ['amount' => 5.0],
            'cut' => 0,
            'platforms' => [],
            'url' => 'https://itad.example/gog',
        ]]);

        $this->assertCount(1, $results);
        $this->assertNull($results[0]['initialPrice']);
        $this->assertNull($results[0]['discount']);
    }

    public function testFetchAllPricesUsesLookupAndDealsEndpoints(): void
    {
        $adapter = new FakeItadAdapter('test-key');
        $adapter->getResponses['appid=570'] = json_encode([
            'found' => true,
            'game' => ['id' => 'uuid-570'],
        ]);
        $adapter->postResponse = json_encode([
            ['deals' => $this->dealsFixture()],
        ]);

        $results = $adapter->fetchAllPrices('Dota 2', 570);

        $this->assertCount(1, $results);
        $this->assertSame('Steam', $results[0]['storeName']);
    }

    public function testFetchAllPricesReturnsEmptyWhenLookupFails(): void
    {
        $adapter = new FakeItadAdapter('test-key');

        $this->assertSame([], $adapter->fetchAllPrices('Unknown', null));
    }

    public function testAppendAffiliateAddsStoreParamAndStripsTracking(): void
    {
        $adapter = new ItadAdapter('test-key');

        $this->assertSame(
            'https://www.fanatical.com/en/game/foo?ref=AFF1&foo=bar',
            $adapter->appendAffiliate(
                'https://www.fanatical.com/en/game/foo?ref=old&utm_source=x&foo=bar',
                'Fanatical',
                'AFF1'
            )
        );

        $this->assertSame(
            'https://www.greenmangaming.com/games/foo?utm_source=affiliate&utm_medium=link&utm_campaign=AFF2',
            $adapter->appendAffiliate('https://www.greenmangaming.com/games/foo', 'GreenManGaming', 'AFF2')
        );
    }

    public function testBuildFallbackUrlUsesStoreDomainAndRef(): void
    {
        $adapter = new ItadAdapter('test-key');

        $this->assertSame('https://humblebundle.com/', $adapter->buildFallbackUrl('Humble Store', ''));
        $this->assertSame('https://humblebundle.com/?ref=AFF', $adapter->buildFallbackUrl('Humble Store', 'AFF'));
    }

    public function testResolveStoreUrlWithoutAffiliateReturnsItadUrl(): void
    {
        $adapter = new ItadAdapter('test-key');

        $this->assertSame(
            'https://itad.example/deal',
            $adapter->resolveStoreUrl('https://itad.example/deal', 'Steam')
        );
    }

    public function testResolveStoreUrlFallsBackWhenItadUrlMissing(): void
    {
        $adapter = new ItadAdapter('test-key', ['Steam' => 'AFF']);

        $this->assertSame(
            'https://store.steampowered.com/?ref=AFF',
            $adapter->resolveStoreUrl('', 'Steam')
        );
    }
}
```

- [ ] **Step 3: Run to verify the seams are missing**

Run: `vendor/bin/phpunit --filter ItadAdapterTest`
Expected: FAIL — `httpPostJson` is not defined in the fake's parent scope? No: the fake defines it, but `parseDeals`, `appendAffiliate`, `buildFallbackUrl`, `resolveStoreUrl` are `private` in the parent → `Call to private method` / `undefined method` errors. No network calls or writes occur.

- [ ] **Step 4: Implement the seams**

In `site/plugins/alv-prices/classes/adapters/ItadAdapter.php`:

Replace the body of `fetchPrices()` (lines 102-168) with:

```php
    private function fetchPrices(string $gameId, string $gameName, ?int $appid): array
    {
        $url = self::BASE . "/games/prices/v3?key={$this->apiKey}&country={$this->country}";

        $response = $this->httpPostJson($url, json_encode([$gameId]));
        if ($response === null) {
            return [];
        }

        $data = json_decode($response, true);
        if (!$data || empty($data[0]['deals'])) {
            return [];
        }

        return $this->parseDeals($data[0]['deals']);
    }

    public function parseDeals(array $deals): array
    {
        $results = [];
        foreach ($deals as $deal) {
            $shopName = $deal['shop']['name'] ?? '';
            $storeInfo = $this->storeMap[$shopName] ?? null;
            if ($storeInfo === null) {
                continue;
            }

            $price = $deal['price']['amount'] ?? null;
            if ($price === null || $price <= 0) {
                continue;
            }

            $regular = $deal['regular']['amount'] ?? $price;
            $cut = (int)($deal['cut'] ?? 0);

            $platformNames = [];
            foreach ($deal['platforms'] ?? [] as $p) {
                $platformNames[] = $p['name'];
            }

            $storeUrl = $this->resolveStoreUrl($deal['url'] ?? '', $shopName);

            $results[] = [
                'storeName'    => $shopName,
                'storeLogo'    => $storeInfo['logo'],
                'price'        => (float) $price,
                'initialPrice' => $regular !== $price ? (float) $regular : null,
                'discount'     => $cut > 0 ? $cut : null,
                'currency'     => $deal['price']['currency'] ?? $this->currency,
                'url'          => $storeUrl,
                'platforms'    => implode(', ', $platformNames),
            ];
        }

        return $results;
    }

    protected function httpPostJson(string $url, string $jsonBody): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; DiarioGames/1.0)',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        return $response;
    }
```

Change visibility `private` → `public` for exactly: `resolveStoreUrl`, `appendAffiliate`, `buildFallbackUrl`. Do not change `lookupGame` or `followRedirect`.

- [ ] **Step 5: Run to verify green**

Run: `vendor/bin/phpunit --filter ItadAdapterTest`
Expected: `OK (8 tests, 20 assertions)`.

- [ ] **Step 6: Full suite + lint**

Run: `composer test` (expect 82 tests) and `php -l site/plugins/alv-prices/classes/adapters/ItadAdapter.php`.

- [ ] **Step 7: Commit**

```bash
git add site/plugins/alv-prices/classes/adapters/ItadAdapter.php tests/Support/FakeItadAdapter.php tests/phpunit/Unit/Prices/ItadAdapterTest.php
git commit -m "refactor(prices): extract ITAD deal parser and transport seam"
```

---

### Task 2: G2AAdapter parser seam

**Files:**
- Create: `tests/Support/FakeG2AAdapter.php`
- Create: `tests/phpunit/Unit/Prices/G2AAdapterTest.php`
- Modify: `site/plugins/alv-prices/classes/adapters/G2AAdapter.php`

- [ ] **Step 1: Create the fake adapter**

`tests/Support/FakeG2AAdapter.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\Adapters\G2AAdapter;

class FakeG2AAdapter extends G2AAdapter
{
    public ?string $token = 'token-1';
    public ?array $offers = null;

    protected function getToken(): ?string
    {
        return $this->token;
    }

    protected function getOffers(string $token, string $productId): ?array
    {
        return $this->offers;
    }
}
```

- [ ] **Step 2: Write the failing tests**

`tests/phpunit/Unit/Prices/G2AAdapterTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

use Alv\Prices\Adapters\G2AAdapter;
use Alv\Prices\StorePriceDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeG2AAdapter;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class G2AAdapterTest extends TestCase
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

    public function testFetchPriceBuildsUrlFromCheapestOffer(): void
    {
        $db = new StorePriceDB($this->tempDatabasePath);
        $db->upsertG2aProduct('pid-1', 'Cyberpunk 2077');

        $adapter = new FakeG2AAdapter('client', 'secret');
        $adapter->offers = [
            'name' => 'Cyberpunk 2077',
            'currency' => 'EUR',
            'offers' => [
                ['price' => 12.34],
                ['price' => 20.0],
            ],
        ];

        $price = $adapter->fetchPrice('Cyberpunk 2077');

        $this->assertNotNull($price);
        $this->assertEqualsWithDelta(12.34, $price['price'], 0.001);
        $this->assertSame('EUR', $price['currency']);
        $this->assertSame('https://www.g2a.com/cyberpunk-2077/p/pid-1', $price['url']);
        $this->assertNull($price['initialPrice']);
        $this->assertNull($price['discount']);
    }

    public function testFetchPriceReturnsNullWithoutCatalogMapping(): void
    {
        $adapter = new FakeG2AAdapter('client', 'secret');
        $adapter->offers = ['name' => 'Whatever', 'offers' => [['price' => 1.0]]];

        $this->assertNull($adapter->fetchPrice('Unknown Game'));
    }

    public function testFetchPriceReturnsNullWhenTokenMissing(): void
    {
        $db = new StorePriceDB($this->tempDatabasePath);
        $db->upsertG2aProduct('pid-2', 'Some Game');

        $adapter = new FakeG2AAdapter('client', 'secret');
        $adapter->token = null;

        $this->assertNull($adapter->fetchPrice('Some Game'));
    }

    public function testBuildPriceFromOffersRejectsZeroPriceAndEmptyOffers(): void
    {
        $adapter = new G2AAdapter('client', 'secret');

        $this->assertNull($adapter->buildPriceFromOffers(
            ['name' => 'Game', 'offers' => [['price' => 0]]],
            'Game',
            'pid-3'
        ));

        $this->assertNull($adapter->buildPriceFromOffers(
            ['name' => 'Game', 'offers' => []],
            'Game',
            'pid-3'
        ));
    }

    public function testBuildPriceFromOffersFallsBackToGameNameForSlug(): void
    {
        $adapter = new G2AAdapter('client', 'secret');

        $price = $adapter->buildPriceFromOffers(
            ['offers' => [['price' => 5.0]]],
            'Some Game: Deluxe!',
            'pid-4'
        );

        $this->assertNotNull($price);
        $this->assertSame('https://www.g2a.com/some-game-deluxe/p/pid-4', $price['url']);
    }
}
```

Note: `setUpTempDatabase()` calls `_db(true)` which constructs `SteamStatsDB`; that's fine. `G2AAdapter::fetchPrice` reads `StorePriceDB` from env — the temp DB seeded by the test via explicit path is the same file.

- [ ] **Step 3: Run to verify the seam is missing**

Run: `vendor/bin/phpunit --filter G2AAdapterTest`
Expected: FAIL — `buildPriceFromOffers` undefined; `getToken`/`getOffers` private (fake overrides are fine, but the parent `fetchPrice` uses `$this` so they will be called on the fake — overriding private methods does not affect the parent's call; `fetchPrice` will call the parent's private curl methods → network). To keep the failing run safe, run with a guard:

```bash
G2A_CLIENT_ID= G2A_API_KEY= vendor/bin/phpunit --filter G2AAdapterTest
```

Even so, `fetchPrice` would attempt network. Instead run only the two `buildPriceFromOffers` tests for the red phase:

```bash
vendor/bin/phpunit --filter 'G2AAdapterTest::testBuildPriceFromOffers'
```

Expected: FAIL — undefined method `buildPriceFromOffers`.

- [ ] **Step 4: Implement the seam**

In `site/plugins/alv-prices/classes/adapters/G2AAdapter.php`:

Replace the tail of `fetchPrice()` (lines 60-80):

```php
        $offers = $this->getOffers($token, $productId);
        if ($offers === null || empty($offers['offers'])) {
            return null;
        }

        return $this->buildPriceFromOffers($offers, $gameName, $productId);
    }

    public function buildPriceFromOffers(array $offers, string $gameName, string $productId): ?array
    {
        $cheapest = $offers['offers'][0] ?? [];
        $price = (float)($cheapest['price'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        $slug = $this->slugify($offers['name'] ?? $gameName);
        $url = 'https://www.g2a.com/' . $slug . '/p/' . $productId;

        return [
            'price'        => $price,
            'initialPrice' => null,
            'discount'     => null,
            'currency'     => $offers['currency'] ?? 'EUR',
            'url'          => $url,
        ];
    }
```

Change `private` → `protected` for `getToken` and `getOffers`. Keep `slugify` private.

- [ ] **Step 5: Run to verify green**

Run: `vendor/bin/phpunit --filter G2AAdapterTest`
Expected: `OK (5 tests, 12 assertions)` — no network, because `getToken`/`getOffers` are now overridden by the fake.

- [ ] **Step 6: Full suite + lint + commit**

```bash
composer test
php -l site/plugins/alv-prices/classes/adapters/G2AAdapter.php
git add site/plugins/alv-prices/classes/adapters/G2AAdapter.php tests/Support/FakeG2AAdapter.php tests/phpunit/Unit/Prices/G2AAdapterTest.php
git commit -m "refactor(prices): extract G2A offer parser seam"
```

---

### Task 3: InstantGamingAdapter parser seam

**Files:**
- Create: `tests/Support/FakeInstantGamingAdapter.php`
- Create: `tests/phpunit/Unit/Prices/InstantGamingAdapterTest.php`
- Modify: `site/plugins/alv-prices/classes/adapters/InstantGamingAdapter.php`

- [ ] **Step 1: Create the fake adapter**

`tests/Support/FakeInstantGamingAdapter.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\Adapters\InstantGamingAdapter;

class FakeInstantGamingAdapter extends InstantGamingAdapter
{
    public array $hits = [];

    protected function searchAlgolia(string $query): array
    {
        return $this->hits;
    }
}
```

- [ ] **Step 2: Write the failing tests**

`tests/phpunit/Unit/Prices/InstantGamingAdapterTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

use Alv\Prices\Adapters\InstantGamingAdapter;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeInstantGamingAdapter;
use Tests\Support\PluginClasses;

final class InstantGamingAdapterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testPickBestHitRequiresExactNameAndPicksCheapest(): void
    {
        $adapter = new FakeInstantGamingAdapter();

        $best = $adapter->pickBestHit([
            ['name' => 'Test Game', 'price_eur' => 10.0, 'has_stock' => true],
            ['name' => 'Test Game', 'price_eur' => 8.0, 'has_stock' => true],
            ['name' => 'Test Game Deluxe', 'price_eur' => 5.0, 'has_stock' => true],
        ], 'Test Game');

        $this->assertNotNull($best);
        $this->assertEqualsWithDelta(8.0, (float)$best['price_eur'], 0.001);
    }

    public function testPickBestHitSkipsOutOfStockAndZeroPrice(): void
    {
        $adapter = new FakeInstantGamingAdapter();

        $this->assertNull($adapter->pickBestHit([
            ['name' => 'Test Game', 'price_eur' => 5.0, 'has_stock' => false],
            ['name' => 'Test Game', 'price_eur' => 0, 'has_stock' => true],
        ], 'Test Game'));
    }

    public function testPickBestHitHandlesMissingNamesAndEmptyHits(): void
    {
        $adapter = new FakeInstantGamingAdapter();

        $this->assertNull($adapter->pickBestHit([], 'Test Game'));
        $this->assertNull($adapter->pickBestHit([['name' => '', 'price_eur' => 5.0]], 'Test Game'));
    }

    public function testFetchPriceBuildsUrlWithAffiliateId(): void
    {
        $adapter = new FakeInstantGamingAdapter('diario');
        $adapter->hits = [[
            'name' => 'Test Game',
            'price_eur' => 19.99,
            'default_retail' => 39.99,
            'discount' => 50,
            'seo_name' => 'test-game',
            'prod_id' => '123',
            'has_stock' => true,
        ]];

        $price = $adapter->fetchPrice('Test Game');

        $this->assertNotNull($price);
        $this->assertEqualsWithDelta(19.99, $price['price'], 0.001);
        $this->assertEqualsWithDelta(39.99, $price['initialPrice'], 0.001);
        $this->assertSame(50, $price['discount']);
        $this->assertSame('https://www.instant-gaming.com/en/123-test-game/?igr=diario', $price['url']);
    }

    public function testFetchPriceReturnsNullWithoutExactHit(): void
    {
        $adapter = new FakeInstantGamingAdapter('diario');
        $adapter->hits = [['name' => 'Other Game', 'price_eur' => 5.0, 'has_stock' => true]];

        $this->assertNull($adapter->fetchPrice('Test Game'));
    }
}
```

- [ ] **Step 3: Run to verify the seams are missing**

Run: `vendor/bin/phpunit --filter InstantGamingAdapterTest`
Expected: FAIL — `pickBestHit` is private (`Call to private method`), and `searchAlgolia` in the fake does not override the parent's private method (so `fetchPrice` tests would hit the network). No network happens on the failing assertion because `pickBestHit` fails first for the direct tests; the two `fetchPrice` tests would network — run only the direct ones for red:

```bash
vendor/bin/phpunit --filter 'InstantGamingAdapterTest::testPickBestHit'
```

Expected: FAIL with `Call to private method`.

- [ ] **Step 4: Implement the seams**

In `site/plugins/alv-prices/classes/adapters/InstantGamingAdapter.php`:
- Change `private function searchAlgolia(` to `protected function searchAlgolia(`.
- Change `private function pickBestHit(` to `public function pickBestHit(`.

No logic changes.

- [ ] **Step 5: Run to verify green**

Run: `vendor/bin/phpunit --filter InstantGamingAdapterTest`
Expected: `OK (5 tests, 11 assertions)` — no network (fake overrides `searchAlgolia`).

- [ ] **Step 6: Full suite + lint + commit**

```bash
composer test
php -l site/plugins/alv-prices/classes/adapters/InstantGamingAdapter.php
git add site/plugins/alv-prices/classes/adapters/InstantGamingAdapter.php tests/Support/FakeInstantGamingAdapter.php tests/phpunit/Unit/Prices/InstantGamingAdapterTest.php
git commit -m "refactor(prices): open InstantGaming parser for tests"
```

---

### Task 4: PriceFetcher orchestration tests

**Files:**
- Create: `tests/Support/FakePriceDb.php`
- Create: `tests/Support/FakeStoreAdapter.php`
- Create: `tests/phpunit/Unit/Prices/PriceFetcherTest.php`

No production changes in this task.

- [ ] **Step 1: Create the fakes**

`tests/Support/FakePriceDb.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\StorePriceDB;

class FakePriceDb extends StorePriceDB
{
    public array $rows = [];
    public array $upserts = [];
    public ?int $now = null;

    public function __construct()
    {
    }

    public function getAllPrices(string $slug): array
    {
        return $this->rows[$slug] ?? [];
    }

    public function getPrice(string $slug, string $store): ?array
    {
        foreach ($this->rows[$slug] ?? [] as $row) {
            if ($row['store'] === $store) {
                return $row;
            }
        }
        return null;
    }

    public function upsertPrice(string $slug, string $store, array $priceData): void
    {
        $this->upserts[] = ['slug' => $slug, 'store' => $store, 'priceData' => $priceData];

        $row = [
            'slug'             => $slug,
            'store'            => $store,
            'url'              => $priceData['url'] ?? '',
            'price'            => $priceData['price'] ?? null,
            'initial_price'    => $priceData['initialPrice'] ?? null,
            'discount_percent' => $priceData['discount'] ?? 0,
            'currency'         => $priceData['currency'] ?? 'EUR',
            'platforms'        => $priceData['platforms'] ?? '',
            'scraped_at'       => $this->now ?? time(),
        ];

        $this->rows[$slug] = array_values(array_filter(
            $this->rows[$slug] ?? [],
            fn ($r) => $r['store'] !== $store
        ));
        $this->rows[$slug][] = $row;
    }

    public function isExpired(int $scrapedAt, int $ttl = 86400, ?int $now = null): bool
    {
        return (($now ?? $this->now ?? time()) - $scrapedAt) >= $ttl;
    }
}
```

`tests/Support/FakeStoreAdapter.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\StoreAdapter;

class FakeStoreAdapter extends StoreAdapter
{
    public int $fetchCalls = 0;

    public function __construct(
        private string $storeName,
        private ?array $price,
        private bool $throws = false
    ) {
    }

    public function getName(): string
    {
        return $this->storeName;
    }

    public function getLogo(): string
    {
        return strtolower(str_replace(' ', '-', $this->storeName));
    }

    public function searchGame(string $gameName): ?string
    {
        return null;
    }

    public function scrapePrice(string $url): ?array
    {
        return null;
    }

    public function getAffiliateUrl(string $gameName): string
    {
        return '';
    }

    public function fetchPrice(string $gameName, ?int $appid = null): ?array
    {
        $this->fetchCalls++;
        if ($this->throws) {
            throw new \RuntimeException('adapter failure');
        }
        return $this->price;
    }
}
```

- [ ] **Step 2: Write the tests**

`tests/phpunit/Unit/Prices/PriceFetcherTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

use Alv\Prices\PriceFetcher;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePriceDb;
use Tests\Support\FakeStoreAdapter;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class PriceFetcherTest extends TestCase
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

    private function priceData(float $price, string $url = 'https://shop.example/deal'): array
    {
        return [
            'price' => $price,
            'initialPrice' => null,
            'discount' => null,
            'currency' => 'EUR',
            'url' => $url,
        ];
    }

    public function testFetchStoresAdapterPricesAndMapsLogos(): void
    {
        $db = new FakePriceDb();
        $fetcher = new PriceFetcher($db, 86400);
        $adapter = new FakeStoreAdapter('GOG', $this->priceData(5.0));
        $fetcher->register($adapter);

        $results = $fetcher->fetch('game', 'Game');

        $this->assertCount(1, $results);
        $this->assertSame('GOG', $results[0]['storeName']);
        $this->assertSame('gogdotcom', $results[0]['storeLogo']);
        $this->assertEqualsWithDelta(5.0, $results[0]['price'], 0.001);
        $this->assertSame(1, $adapter->fetchCalls);
        $this->assertCount(1, $db->upserts);
    }

    public function testFetchSkipsFreshCachedStore(): void
    {
        $db = new FakePriceDb();
        $db->rows['game'] = [[
            'slug' => 'game',
            'store' => 'GOG',
            'url' => 'https://gog.com/deal',
            'price' => 4.5,
            'initial_price' => null,
            'discount_percent' => 0,
            'currency' => 'EUR',
            'platforms' => '',
            'scraped_at' => time(),
        ]];

        $fetcher = new PriceFetcher($db, 86400);
        $adapter = new FakeStoreAdapter('GOG', $this->priceData(1.0));
        $fetcher->register($adapter);

        $results = $fetcher->fetch('game', 'Game');

        $this->assertSame(0, $adapter->fetchCalls);
        $this->assertEqualsWithDelta(4.5, $results[0]['price'], 0.001);
    }

    public function testFetchRefreshesExpiredCachedStore(): void
    {
        $db = new FakePriceDb();
        $db->now = time();
        $db->rows['game'] = [[
            'slug' => 'game',
            'store' => 'GOG',
            'url' => 'https://gog.com/old',
            'price' => 4.5,
            'initial_price' => null,
            'discount_percent' => 0,
            'currency' => 'EUR',
            'platforms' => '',
            'scraped_at' => time() - 200000,
        ]];

        $fetcher = new PriceFetcher($db, 86400);
        $adapter = new FakeStoreAdapter('GOG', $this->priceData(2.5));
        $fetcher->register($adapter);

        $results = $fetcher->fetch('game', 'Game');

        $this->assertSame(1, $adapter->fetchCalls);
        $this->assertEqualsWithDelta(2.5, $results[0]['price'], 0.001);
    }

    public function testFetchDropsNullPricesAndSortsAscending(): void
    {
        $db = new FakePriceDb();
        $db->rows['game'] = [
            [
                'slug' => 'game', 'store' => 'Steam', 'url' => 'https://steam.example',
                'price' => null, 'initial_price' => null, 'discount_percent' => 0,
                'currency' => 'EUR', 'platforms' => '', 'scraped_at' => time(),
            ],
            [
                'slug' => 'game', 'store' => 'GOG', 'url' => 'https://gog.example',
                'price' => 5.0, 'initial_price' => null, 'discount_percent' => 0,
                'currency' => 'EUR', 'platforms' => '', 'scraped_at' => time(),
            ],
            [
                'slug' => 'game', 'store' => 'Fanatical', 'url' => 'https://fanatical.example',
                'price' => 3.0, 'initial_price' => null, 'discount_percent' => 0,
                'currency' => 'EUR', 'platforms' => '', 'scraped_at' => time(),
            ],
        ];

        $fetcher = new PriceFetcher($db, 86400);

        $results = $fetcher->fetch('game', 'Game');

        $this->assertSame(['Fanatical', 'GOG'], array_column($results, 'storeName'));
        $this->assertSame(['fanatical', 'gogdotcom'], array_column($results, 'storeLogo'));
    }

    public function testFetchSurvivesAdapterException(): void
    {
        $db = new FakePriceDb();
        $fetcher = new PriceFetcher($db, 86400);
        $fetcher->register(new FakeStoreAdapter('GOG', null, true));

        $this->assertSame([], $fetcher->fetch('game', 'Game'));
    }
}
```

- [ ] **Step 3: Run the tests**

Run: `vendor/bin/phpunit --filter PriceFetcherTest`
Expected: `OK (5 tests, 14 assertions)`.

If `testFetchSurvivesAdapterException` fails, ensure the adapter is registered with `throws = true` (third constructor arg).

- [ ] **Step 4: Full suite**

Run: `composer test`
Expected: all pass (92 tests).

- [ ] **Step 5: Commit**

```bash
git add tests/Support/FakePriceDb.php tests/Support/FakeStoreAdapter.php tests/phpunit/Unit/Prices/PriceFetcherTest.php
git commit -m "test: cover PriceFetcher caching and adapter orchestration"
```

---

### Task 5: AffiliateBanners extraction + tests

**Files:**
- Create: `site/plugins/alv-aff-banners/classes/AffiliateBanners.php`
- Create: `tests/phpunit/Unit/AffBanners/AffiliateBannersTest.php`
- Modify: `site/plugins/alv-aff-banners/index.php`
- Modify: `site/plugins/alv-aff-banners/snippets/affiliate-banner.php`
- Modify: `tests/Support/PluginClasses.php`

- [ ] **Step 1: Write the failing tests**

`tests/phpunit/Unit/AffBanners/AffiliateBannersTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\AffBanners;

use Alv\AffBanners\AffiliateBanners;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class AffiliateBannersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testIsEnabledHandlesMissingAndStringValues(): void
    {
        $this->assertTrue(AffiliateBanners::isEnabled(null));
        $this->assertTrue(AffiliateBanners::isEnabled(''));
        $this->assertTrue(AffiliateBanners::isEnabled('true'));
        $this->assertTrue(AffiliateBanners::isEnabled('1'));
        $this->assertFalse(AffiliateBanners::isEnabled('false'));
        $this->assertFalse(AffiliateBanners::isEnabled('0'));
        $this->assertFalse(AffiliateBanners::isEnabled(false));
    }

    public function testParseProgramsPipeFrequency(): void
    {
        $programs = AffiliateBanners::parsePrograms([[
            'name' => 'Program A',
            'enabled' => 'true',
            'frequency' => '2|5|9',
            'type' => 'instant-gaming',
            'affiliate_id' => 'aff-a',
        ]]);

        $this->assertCount(1, $programs);
        $this->assertSame('Program A', $programs[0]['name']);
        $this->assertTrue($programs[0]['enabled']);
        $this->assertSame(2, $programs[0]['sm_position']);
        $this->assertSame(5, $programs[0]['md_position']);
        $this->assertSame(9, $programs[0]['xl_position']);
        $this->assertSame('instant-gaming', $programs[0]['type']);
        $this->assertSame('aff-a', $programs[0]['affiliate_id']);
        $this->assertSame('Ofertas destacadas', $programs[0]['banner_label']);
        $this->assertSame('Patrocinado', $programs[0]['banner_sponsor']);
    }

    public function testParseProgramsNumericFrequencyDerivesPositions(): void
    {
        $programs = AffiliateBanners::parsePrograms([['name' => 'Numeric', 'frequency' => 6]]);

        $this->assertSame(1, $programs[0]['sm_position']);
        $this->assertSame(3, $programs[0]['md_position']);
        $this->assertSame(6, $programs[0]['xl_position']);
    }

    public function testParseProgramsStringFrequencyAndClamping(): void
    {
        $programs = AffiliateBanners::parsePrograms([
            ['name' => 'String', 'frequency' => 'sm:3 md:7 xl:11'],
            ['name' => 'Zero', 'frequency' => '0|0|0'],
        ]);

        $this->assertSame(3, $programs[0]['sm_position']);
        $this->assertSame(7, $programs[0]['md_position']);
        $this->assertSame(11, $programs[0]['xl_position']);

        $this->assertSame(1, $programs[1]['sm_position']);
        $this->assertSame(1, $programs[1]['md_position']);
        $this->assertSame(1, $programs[1]['xl_position']);
    }

    public function testParseProgramsDefaultsAndDisabledFlag(): void
    {
        $programs = AffiliateBanners::parsePrograms([
            ['name' => 'Defaults'],
            ['name' => 'Off', 'enabled' => 'false'],
            ['name' => 'ZeroString', 'enabled' => '0'],
        ]);

        $this->assertSame(1, $programs[0]['sm_position']);
        $this->assertSame(2, $programs[0]['md_position']);
        $this->assertSame(4, $programs[0]['xl_position']);
        $this->assertTrue($programs[0]['enabled']);
        $this->assertFalse($programs[1]['enabled']);
        $this->assertFalse($programs[2]['enabled']);
    }

    public function testMatchingProgramsUsesSmThenMdThenXlPrecedence(): void
    {
        $programs = AffiliateBanners::parsePrograms([[
            'name' => 'A',
            'frequency' => '2|2|4',
        ]]);

        $matchSm = AffiliateBanners::matchingPrograms(2, $programs);
        $this->assertCount(1, $matchSm);
        $this->assertSame('sm', $matchSm[0]['_bpType']);

        $matchXl = AffiliateBanners::matchingPrograms(4, $programs);
        $this->assertCount(1, $matchXl);
        $this->assertSame('xl', $matchXl[0]['_bpType']);

        $this->assertSame([], AffiliateBanners::matchingPrograms(3, $programs));
    }

    public function testMatchingProgramsSkipsDisabledAndNonMatching(): void
    {
        $programs = AffiliateBanners::parsePrograms([
            ['name' => 'Off', 'frequency' => '1|2|4', 'enabled' => 'false'],
            ['name' => 'On', 'frequency' => '1|2|4'],
        ]);

        $matches = AffiliateBanners::matchingPrograms(4, $programs);

        $this->assertCount(1, $matches);
        $this->assertSame('On', $matches[0]['name']);
    }
}
```

- [ ] **Step 2: Run to verify the class is missing**

Run: `vendor/bin/phpunit --filter AffiliateBannersTest`
Expected: FAIL — `Class "Alv\AffBanners\AffiliateBanners" not found` (or the loader fatal for the missing file). Before running, add the file to the loader in Step 3? No: run first to confirm red, then create.

- [ ] **Step 3: Create the class**

`site/plugins/alv-aff-banners/classes/AffiliateBanners.php`:

```php
<?php

namespace Alv\AffBanners;

final class AffiliateBanners
{
    public static function isEnabled(mixed $raw, bool $default = true): bool
    {
        if ($raw === null || $raw === '') {
            return $default;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    public static function parsePrograms(array $raw): array
    {
        $programs = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            [$sm, $md, $xl] = self::parseFrequency($item['frequency'] ?? '1|2|4');
            $enabledRaw = $item['enabled'] ?? true;

            $programs[] = [
                'name'           => $item['name'] ?? '',
                'enabled'        => is_string($enabledRaw)
                    ? filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN)
                    : (bool) $enabledRaw,
                'sm_position'    => max(1, $sm),
                'md_position'    => max(1, $md),
                'xl_position'    => max(1, $xl),
                'type'           => $item['type'] ?? 'instant-gaming',
                'affiliate_id'   => $item['affiliate_id'] ?? '',
                'banner_label'   => $item['banner_label'] ?? 'Ofertas destacadas',
                'banner_sponsor' => $item['banner_sponsor'] ?? 'Patrocinado',
            ];
        }

        return $programs;
    }

    public static function matchingPrograms(int $itemCount, array $programs): array
    {
        $matching = [];

        foreach ($programs as $program) {
            if (empty($program['enabled'])) {
                continue;
            }

            $bpType = null;

            if ($itemCount === $program['sm_position']) {
                $bpType = 'sm';
            } elseif ($itemCount === $program['md_position']) {
                $bpType = 'md';
            } elseif ($itemCount === $program['xl_position']) {
                $bpType = 'xl';
            }

            if ($bpType === null) {
                continue;
            }

            $program['_bpType'] = $bpType;
            $matching[] = $program;
        }

        return $matching;
    }

    private static function parseFrequency(mixed $raw): array
    {
        $sm = 1;
        $md = 2;
        $xl = 4;

        if (is_string($raw) && str_contains($raw, '|')) {
            $parts = explode('|', $raw);
            $sm = (int) ($parts[0] ?? 1);
            $md = (int) ($parts[1] ?? 2);
            $xl = (int) ($parts[2] ?? 4);
        } elseif (is_numeric($raw)) {
            $xl = (int) $raw;
            $md = intdiv($xl, 2);
            $sm = max(1, intdiv($md, 2));
        } elseif (is_string($raw)) {
            if (preg_match('/sm:\s*(\d+)/i', $raw, $m)) $sm = (int) $m[1];
            if (preg_match('/md:\s*(\d+)/i', $raw, $m)) $md = (int) $m[1];
            if (preg_match('/xl:\s*(\d+)/i', $raw, $m)) $xl = (int) $m[1];
        }

        return [$sm, $md, $xl];
    }
}
```

- [ ] **Step 4: Add the class to the test loader**

In `tests/Support/PluginClasses.php`, add to the `$files` array after the alv-ai entry:

```php
            '/site/plugins/alv-aff-banners/classes/AffiliateBanners.php',
```

- [ ] **Step 5: Run to verify green**

Run: `vendor/bin/phpunit --filter AffiliateBannersTest`
Expected: `OK (7 tests, 36 assertions)`.

- [ ] **Step 6: Wire the plugin site method**

In `site/plugins/alv-aff-banners/index.php`, add at the top (after `use Kirby\Cms\App;`):

```php
require_once __DIR__ . '/classes/AffiliateBanners.php';
```

and add `use Alv\AffBanners\AffiliateBanners;`.

Replace the `alvAffBanners` site method body with:

```php
        'alvAffBanners' => function () {
            $enabledField = $this->alv_aff_banner_enabled();

            return [
                'enabled' => AffiliateBanners::isEnabled(
                    $enabledField->isNotEmpty() ? $enabledField->value() : null
                ),
                'programs' => AffiliateBanners::parsePrograms($this->alv_aff_programs()->yaml()),
            ];
        },
```

- [ ] **Step 7: Wire the snippet placement**

In `site/plugins/alv-aff-banners/snippets/affiliate-banner.php`, replace the matching block (lines 37-60) with:

```php
$matching = [];

foreach (AffiliateBanners::matchingPrograms($itemCount, array_values($enabledPrograms)) as $program) {
    $showKey = $program['name'] . '_' . $program['_bpType'];
    if (in_array($showKey, $GLOBALS['alv_aff_banners_shown'])) continue;

    $GLOBALS['alv_aff_banners_shown'][] = $showKey;
    $matching[] = $program;
}

if (empty($matching)) return;
```

The snippet is loaded by Kirby after the plugin file (which `require_once`s the class), so `AffiliateBanners` is available. Keep the `$grouped` block unchanged.

- [ ] **Step 8: Full suite + lint + commit**

```bash
composer test
php -l site/plugins/alv-aff-banners/classes/AffiliateBanners.php
php -l site/plugins/alv-aff-banners/index.php
php -l site/plugins/alv-aff-banners/snippets/affiliate-banner.php
git add site/plugins/alv-aff-banners/classes/AffiliateBanners.php site/plugins/alv-aff-banners/index.php site/plugins/alv-aff-banners/snippets/affiliate-banner.php tests/Support/PluginClasses.php tests/phpunit/Unit/AffBanners/AffiliateBannersTest.php
git commit -m "refactor(banners): extract config parsing and placement matching"
```

---

### Task 6: Banner render integration test

**Files:**
- Create: `tests/phpunit/Integration/AffiliateBannerRenderTest.php`

- [ ] **Step 1: Write the test**

`tests/phpunit/Integration/AffiliateBannerRenderTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kirby\Cms\App;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;

final class AffiliateBannerRenderTest extends TestCase
{
    private static App $kirby;
    private static string $tmp;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 3);

        self::$tmp = sys_get_temp_dir() . '/diario-banner-' . bin2hex(random_bytes(6));
        foreach (['media', 'sessions', 'accounts', 'cache', 'content'] as $dir) {
            mkdir(self::$tmp . '/' . $dir, 0775, true);
        }

        file_put_contents(self::$tmp . '/content/site.txt', <<<'TXT'
Title: Test Site

----

Alv-aff-banner-enabled: true

----

Alv-aff-programs:

- 
  name: Test Program
  enabled: 'true'
  type: instant-gaming
  affiliate_id: testaff
  banner_label: Ofertas
  banner_sponsor: Patrocinado
  frequency: '1|2|4'
TXT);

        putenv('STEAM_STATS_DB_PATH=' . self::$tmp . '/steam_stats.db');

        self::$kirby = new App([
            'roots' => [
                'index' => $root,
                'content' => self::$tmp . '/content',
                'cache' => self::$tmp . '/cache',
                'media' => self::$tmp . '/media',
                'sessions' => self::$tmp . '/sessions',
                'accounts' => self::$tmp . '/accounts',
            ],
            'options' => [
                'debug' => false,
            ],
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        putenv('STEAM_STATS_DB_PATH');
        Files::removeDir(self::$tmp);
    }

    protected function setUp(): void
    {
        unset(
            $GLOBALS['alv_aff_banners_init'],
            $GLOBALS['alv_aff_banners_shown'],
            $GLOBALS['alv_aff_scripts_emitted']
        );
    }

    public function testBannerRendersForMatchingItemCount(): void
    {
        $html = snippet('affiliate-banner', ['grid' => true, 'itemCount' => 2], true);

        $this->assertStringContainsString('ig-aff-banner-test-program-md', $html);
        $this->assertStringContainsString("igr: 'testaff'", $html);
        $this->assertStringContainsString('loader.js', $html);
    }

    public function testBannerRendersNothingForNonMatchingItemCount(): void
    {
        $html = snippet('affiliate-banner', ['grid' => true, 'itemCount' => 3], true);

        $this->assertSame('', $html);
    }
}
```

- [ ] **Step 2: Run the test**

Run: `vendor/bin/phpunit --filter AffiliateBannerRenderTest`
Expected: `OK (2 tests, 4 assertions)`.

If the site fields are not read, check that `Alv-aff-programs` YAML parses (Kirby maps `Alv-aff-programs` to the `alv_aff_programs` field).

- [ ] **Step 3: Full suite**

Run: `composer test`
Expected: all pass — 101 tests, ~250 assertions, 1 skipped.

- [ ] **Step 4: Verify isolation**

Run:

```bash
git status --short | head
php -r '$p=new PDO("sqlite:sqlite/steam_stats.db"); echo "fixtures: ".$p->query("SELECT COUNT(*) FROM store_prices WHERE slug=\"game\"")->fetchColumn()."\n";'
```

Expected: only the new test file untracked; `fixtures: 0`.

- [ ] **Step 5: Commit**

```bash
git add tests/phpunit/Integration/AffiliateBannerRenderTest.php
git commit -m "test: render affiliate banner from fixture site content"
```

---

## Plan 3 done when

- `composer test` passes: ~101 tests, 1 skipped.
- Production diff: `ItadAdapter.php`, `G2AAdapter.php`, `InstantGamingAdapter.php`, `AffiliateBanners.php` (new), `index.php`, `affiliate-banner.php` — all additive or pure extraction with identical defaults.
- No live price API calls and no writes to production data.

## Next plans

4. Steam stats seams (collector parse extraction, HTTP fakes, `SteamStats` cache wrapper; optional: replace the smoke test's `priceComparison` stub now that PriceFetcher is testable).
5. Kirby route/site-method integration (rankings/search/chart-data routes, fake `exec`).
6. CLI + `.mjs` scraper parser tests.
7. Playwright E2E.
