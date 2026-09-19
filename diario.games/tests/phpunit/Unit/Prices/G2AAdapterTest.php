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
