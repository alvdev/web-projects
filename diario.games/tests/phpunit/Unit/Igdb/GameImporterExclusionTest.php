<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use DiarioGames\IGDB\GameImporter;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class GameImporterExclusionTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testExcludesSeasonPassesBattlePassesAndDlcPacks(): void
    {
        $this->assertTrue(GameImporter::isExcluded(['name' => 'Game Season Pass']));
        $this->assertTrue(GameImporter::isExcluded(['name' => 'Battle Pass Deluxe']));
        $this->assertTrue(GameImporter::isExcluded(['name' => 'DLC Pack 2']));
    }

    public function testKeepsRegularGamesAndMissingNames(): void
    {
        $this->assertFalse(GameImporter::isExcluded(['name' => 'Doom']));
        $this->assertFalse(GameImporter::isExcluded(['name' => 'Expansion Pack']));
        $this->assertFalse(GameImporter::isExcluded([]));
    }
}
