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
