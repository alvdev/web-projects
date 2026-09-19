<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsCollector;
use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSteamStatsCollector;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsCollectorCollectTest extends TestCase
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

    public function testParseGameTxtExtractsSteamGameFields(): void
    {
        $content = "Title: Alpha Game\n\n----\n\nIgdbId: 111\n\n----\n\nWebsites: 1:https://store.steampowered.com/app/570/\n\n----\n";

        $parsed = SteamStatsCollector::parseGameTxt($content, 'alpha-game');

        $this->assertSame([
            'appid' => 570,
            'slug' => 'alpha-game',
            'name' => 'Alpha Game',
            'igdbId' => 111,
        ], $parsed);
    }

    public function testParseGameTxtReturnsNullWithoutSteamLink(): void
    {
        $this->assertNull(SteamStatsCollector::parseGameTxt("Title: Beta\n\n----\n", 'beta'));
    }

    public function testParseGameTxtFallsBackToSlugWithoutTitleAndNullIgdbId(): void
    {
        $content = "Websites: 1:https://store.steampowered.com/app/730/\n";

        $parsed = SteamStatsCollector::parseGameTxt($content, 'fallback-slug');

        $this->assertSame('fallback-slug', $parsed['name']);
        $this->assertNull($parsed['igdbId']);
    }

    public function testCollectScansFixtureGamesAndStoresPlayerCounts(): void
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        mkdir($gamesDir . '/2024/03/alpha', 0775, true);
        mkdir($gamesDir . '/2024/03/beta', 0775, true);

        file_put_contents(
            $gamesDir . '/2024/03/alpha/game.txt',
            "Title: Alpha Game\n\n----\n\nIgdbId: 111\n\n----\n\nWebsites: 1:https://store.steampowered.com/app/570/\n"
        );
        file_put_contents(
            $gamesDir . '/2024/03/beta/game.txt',
            "Title: Beta Game\n"
        );

        $collector = new FakeSteamStatsCollector('test-key');
        $collector->playerCounts = [570 => 12345];

        $stats = $collector->collect($gamesDir);

        $this->assertSame(2, $stats['scanned']);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame([], $stats['errors']);

        $db = new SteamStatsDB($this->tempDatabasePath);
        $game = $db->getGameByAppId(570);

        $this->assertNotNull($game);
        $this->assertSame('alpha', $game['slug']);
        $this->assertSame('Alpha Game', $game['name']);
        $this->assertSame(12345, $db->getCurrentPlayers(570));
    }

    public function testCollectRecordsErrorsForUnavailableCounts(): void
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        mkdir($gamesDir . '/2024/03/alpha', 0775, true);
        file_put_contents(
            $gamesDir . '/2024/03/alpha/game.txt',
            "Title: Alpha Game\n\n----\n\nWebsites: 1:https://store.steampowered.com/app/570/\n"
        );

        $collector = new FakeSteamStatsCollector('test-key');
        $collector->playerCounts = [570 => null];

        $stats = $collector->collect($gamesDir);

        $this->assertSame(1, $stats['scanned']);
        $this->assertSame(0, $stats['updated']);
        $this->assertSame([570], $stats['errors']);
    }
}
