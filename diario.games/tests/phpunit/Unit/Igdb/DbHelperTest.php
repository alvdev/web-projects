<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class DbHelperTest extends TestCase
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

    public function testDbSingletonResetsAndPicksUpNewEnvPath(): void
    {
        $pathA = $this->tempDatabaseDir . '/a.db';
        $pathB = $this->tempDatabaseDir . '/b.db';

        putenv('STEAM_STATS_DB_PATH=' . $pathA);
        \DiarioGames\IGDB\_db(true);
        $first = \DiarioGames\IGDB\_db();

        $this->assertNotNull($first);
        $this->assertFileExists($pathA);

        $first->setYearMonth('probe-game', '2024-05');
        $this->assertSame('2024-05', $first->getYearMonth('probe-game'));

        putenv('STEAM_STATS_DB_PATH=' . $pathB);
        $this->assertSame('2024-05', \DiarioGames\IGDB\_db()->getYearMonth('probe-game'));

        \DiarioGames\IGDB\_db(true);
        $second = \DiarioGames\IGDB\_db();

        $this->assertNotNull($second);
        $this->assertFileExists($pathB);
        $this->assertNull($second->getYearMonth('probe-game'));
    }
}
