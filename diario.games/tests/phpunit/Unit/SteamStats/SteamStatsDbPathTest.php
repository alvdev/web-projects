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

    public function testConstructorCreatesDatabaseAtGivenPath(): void
    {
        $db = new SteamStatsDB($this->tempDatabasePath);

        $this->assertFileExists($this->tempDatabasePath);
        $db->upsertGame(730, 'counter-strike-2', 'Counter-Strike 2');
        $this->assertSame('counter-strike-2', $db->getGameBySlug('counter-strike-2')['slug']);
    }

    public function testConstructorHonorsEnvOverride(): void
    {
        new SteamStatsDB();

        $this->assertFileExists($this->tempDatabasePath);
    }
}
