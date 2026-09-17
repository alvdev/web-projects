<?php

declare(strict_types=1);

namespace Tests\Unit\Prices;

use Alv\Prices\StorePriceDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class StorePriceDbPathTest extends TestCase
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

    public function testConstructorPrefersExplicitPathOverEnv(): void
    {
        $explicitPath = $this->tempDatabaseDir . '/explicit.db';
        $db = new StorePriceDB($explicitPath);
        $this->assertFileExists($explicitPath);

        $db->upsertPrice('game', 'steam', [
            'url' => 'https://store.steampowered.com/app/1',
            'price' => 12.34,
        ]);
        $row = $db->getPrice('game', 'steam');
        $this->assertNotNull($row);
        $this->assertSame('https://store.steampowered.com/app/1', $row['url']);
        $this->assertEqualsWithDelta(12.34, (float)$row['price'], 0.001);

        $envDb = new StorePriceDB($this->tempDatabasePath);
        $this->assertNull($envDb->getPrice('game', 'steam'));
    }

    public function testConstructorHonorsEnvOverride(): void
    {
        $db = new StorePriceDB();
        $this->assertFileExists($this->tempDatabasePath);

        $db->upsertPrice('game', 'g2a', [
            'url' => 'https://www.g2a.com/game',
            'price' => 9.99,
        ]);

        $direct = new StorePriceDB($this->tempDatabasePath);
        $directRow = $direct->getPrice('game', 'g2a');
        $this->assertNotNull($directRow);
        $this->assertSame('https://www.g2a.com/game', $directRow['url']);
        $this->assertEqualsWithDelta(9.99, (float)$directRow['price'], 0.001);
    }
}
