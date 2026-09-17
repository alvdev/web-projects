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

        $db->upsertUrl('game', 'steam', 'https://store.steampowered.com/app/1');
        $this->assertSame(
            'https://store.steampowered.com/app/1',
            $db->getPrice('game', 'steam')['url']
        );

        $envDb = new StorePriceDB($this->tempDatabasePath);
        $this->assertNull($envDb->getPrice('game', 'steam'));
    }

    public function testConstructorHonorsEnvOverride(): void
    {
        $db = new StorePriceDB();
        $this->assertFileExists($this->tempDatabasePath);

        $db->upsertUrl('game', 'g2a', 'https://www.g2a.com/game');

        $direct = new StorePriceDB($this->tempDatabasePath);
        $this->assertSame('https://www.g2a.com/game', $direct->getPrice('game', 'g2a')['url']);
    }
}
