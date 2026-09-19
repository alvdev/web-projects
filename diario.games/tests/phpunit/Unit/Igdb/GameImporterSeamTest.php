<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use DiarioGames\IGDB\GameImporter;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class GameImporterSeamTest extends TestCase
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
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    public function testConstructorUsesInjectedGamesDir(): void
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        $importer = new GameImporter(new FakeIGDBClient(), $gamesDir);

        $property = new \ReflectionProperty(GameImporter::class, 'gamesDir');
        $property->setAccessible(true);

        $this->assertSame($gamesDir, $property->getValue($importer));
    }
}
