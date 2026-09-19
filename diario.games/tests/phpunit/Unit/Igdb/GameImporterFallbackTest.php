<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableGameImporter;

final class GameImporterFallbackTest extends TestCase
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

    private function gamesDir(): string
    {
        return $this->tempDatabaseDir . '/games';
    }

    private function gameData(array $overrides = []): array
    {
        return array_merge([
            'id' => 1234,
            'slug' => 'test-game',
            'name' => 'Test Game',
            'first_release_date' => mktime(0, 0, 0, 3, 15, 2024),
            'summary' => 'A test game summary.',
            'platforms' => [6],
            'genres' => [12],
            'themes' => [1],
            'involved_companies' => [
                ['developer' => true, 'publisher' => false, 'company' => ['name' => 'Dev Studio']],
            ],
            'screenshots' => [['id' => 1]],
            'videos' => [],
            'cover' => 1,
            'rating' => 88.5,
            'aggregated_rating' => 90.0,
            'websites' => [],
        ], $overrides);
    }

    public function testImportBySlugUsesDirectLookup(): void
    {
        $client = new FakeIGDBClient();
        $client->gamesBySlug['direct-game'] = $this->gameData(['slug' => 'direct-game']);

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertSame('direct-game', $importer->importBySlug('direct-game'));
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/direct-game');
    }

    public function testImportBySlugWithFallbackUsesExactSearchMatch(): void
    {
        $client = new FakeIGDBClient();
        $client->searchResults = [$this->gameData(['slug' => 'search-game'])];

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertSame('search-game', $importer->importBySlugWithFallback('search-game'));
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/search-game');
    }

    public function testImportBySlugWithFallbackImportsUnderRequestedSlug(): void
    {
        $client = new FakeIGDBClient();
        $client->searchResults = [$this->gameData(['slug' => 'different-igdb-slug'])];

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertSame('requested-game', $importer->importBySlugWithFallback('requested-game'));
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/requested-game');
    }

    public function testImportBySlugWithFallbackReturnsNullWhenNothingFound(): void
    {
        $client = new FakeIGDBClient();

        $importer = new TestableGameImporter($client, $this->gamesDir());

        $this->assertNull($importer->importBySlugWithFallback('unknown-game'));
    }

    public function testImportRegistersSteamGameWithLivePlayerCount(): void
    {
        $client = new FakeIGDBClient();
        $importer = new TestableGameImporter($client, $this->gamesDir());
        $importer->steamAppIdValid = true;
        $importer->liveSteamPlayers = 12345;

        $data = $this->gameData([
            'websites' => [
                ['category' => 1, 'url' => 'https://store.steampowered.com/app/570/'],
            ],
        ]);

        $this->assertSame('test-game', $importer->import($data));

        $db = new SteamStatsDB($this->tempDatabasePath);
        $game = $db->getGameByAppId(570);

        $this->assertNotNull($game);
        $this->assertSame('test-game', $game['slug']);
        $this->assertSame(12345, $db->getCurrentPlayers(570));
        $this->assertSame(['https://store.steampowered.com/app/570/'], $importer->iconsFetched);
    }
}
