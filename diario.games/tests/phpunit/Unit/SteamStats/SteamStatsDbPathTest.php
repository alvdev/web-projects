<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbPathTest extends TestCase
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
        $db = new SteamStatsDB($explicitPath);
        $db->upsertGame(730, 'counter-strike-2', 'Counter-Strike 2');

        $this->assertFileExists($explicitPath);
        $this->assertSame('counter-strike-2', $db->getGameBySlug('counter-strike-2')['slug']);

        $envDb = new SteamStatsDB($this->tempDatabasePath);
        $this->assertNull($envDb->getGameBySlug('counter-strike-2'));
    }

    public function testConstructorHonorsEnvOverride(): void
    {
        $db = new SteamStatsDB();
        $this->assertFileExists($this->tempDatabasePath);

        $db->upsertGame(570, 'dota-2', 'Dota 2');

        $direct = new SteamStatsDB($this->tempDatabasePath);
        $this->assertSame('dota-2', $direct->getGameBySlug('dota-2')['slug']);
    }
}
