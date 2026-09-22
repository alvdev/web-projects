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

    public function testParseDealsCanonicalizesEpicShopName(): void
    {
        $adapter = new ItadAdapter('test-key');

        $results = $adapter->parseDeals([[
            'shop' => ['name' => 'Epic Game Store'],
            'price' => ['amount' => 7.5, 'currency' => 'EUR'],
            'regular' => ['amount' => 7.5],
            'cut' => 0,
            'platforms' => [],
            'url' => 'https://itad.example/epic',
        ]]);

        $this->assertCount(1, $results);
        $this->assertSame('Epic Games Store', $results[0]['storeName']);
        $this->assertSame('epicgames', $results[0]['storeLogo']);
    }

    public function testParseDealsKeepsEaStoreAndNewShops(): void
    {
        $adapter = new ItadAdapter('test-key');

        $shopLogos = [
            'EA Store'        => 'ea',
            'DLGamer'         => 'dlgamer',
            'Muve'            => 'muve',
            'Zapagames'       => 'zapagames',
            'Microsoft Store' => 'microsoft',
            'PlanetPlay'      => 'planetplay',
            'Gamesload'       => 'gamesload',
            'PlayerLand'      => 'playerland',
            'Playsum'         => 'playsum',
        ];

        $deals = [];
        foreach ($shopLogos as $shop => $logo) {
            $deals[] = [
                'shop' => ['name' => $shop],
                'price' => ['amount' => 9.99, 'currency' => 'EUR'],
                'regular' => ['amount' => 19.99],
                'cut' => 50,
                'platforms' => [],
                'url' => 'https://itad.example/' . $logo,
            ];
        }

        $results = $adapter->parseDeals($deals);

        $this->assertSame(array_keys($shopLogos), array_column($results, 'storeName'));
        $this->assertSame(array_values($shopLogos), array_column($results, 'storeLogo'));
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
