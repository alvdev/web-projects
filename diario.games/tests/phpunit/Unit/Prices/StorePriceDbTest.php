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
