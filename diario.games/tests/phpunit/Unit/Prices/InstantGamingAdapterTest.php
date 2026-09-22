<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

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

    public function testPickBestHitMatchesPunctuationAndSeparatorVariants(): void
    {
        $adapter = new FakeInstantGamingAdapter();

        $best = $adapter->pickBestHit([
            ['name' => 'Call to Arms: Gates of Hell - Ostfront - Deluxe Edition', 'price_eur' => 5.0, 'has_stock' => true],
            ['name' => 'Call to Arms - Gates of Hell: Ostfront', 'price_eur' => 26.83, 'has_stock' => true],
        ], 'Call to Arms: Gates of Hell - Ostfront');

        $this->assertNotNull($best);
        $this->assertSame('Call to Arms - Gates of Hell: Ostfront', $best['name']);
    }

    public function testPickBestHitIgnoresAccentsTrademarksAndExtraSpaces(): void
    {
        $adapter = new FakeInstantGamingAdapter();

        $best = $adapter->pickBestHit([
            ['name' => 'Pokemon   Test', 'price_eur' => 9.99, 'has_stock' => true],
        ], 'Pokémon™ Test');

        $this->assertNotNull($best);
        $this->assertSame('Pokemon   Test', $best['name']);
    }

    public function testPickBestHitStillRejectsDifferentEditions(): void
    {
        $adapter = new FakeInstantGamingAdapter();

        $this->assertNull($adapter->pickBestHit([
            ['name' => 'Call to Arms - Gates of Hell: Ostfront - Deluxe Edition', 'price_eur' => 5.0, 'has_stock' => true],
        ], 'Call to Arms: Gates of Hell - Ostfront'));
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
