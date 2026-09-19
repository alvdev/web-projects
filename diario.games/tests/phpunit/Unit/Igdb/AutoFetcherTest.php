<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use DiarioGames\IGDB\AutoFetcher;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableGameImporter;

final class AutoFetcherTest extends TestCase
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
            'slug' => 'auto-game',
            'name' => 'Auto Game',
            'first_release_date' => mktime(0, 0, 0, 3, 15, 2024),
            'summary' => 'Summary.',
            'platforms' => [6],
            'genres' => [12],
            'themes' => [1],
            'involved_companies' => [],
            'screenshots' => [['id' => 1]],
            'videos' => [],
            'cover' => 1,
            'rating' => 80,
            'aggregated_rating' => 81,
            'websites' => [],
        ], $overrides);
    }

    public function testRunImportsUntilMaxGames(): void
    {
        $client = new FakeIGDBClient();
        $client->gamesResponse = [
            $this->gameData(['slug' => 'auto-1']),
            $this->gameData(['slug' => 'auto-2']),
        ];
        $importer = new TestableGameImporter($client, $this->gamesDir());
        $fetcher = new AutoFetcher($client, $importer);

        $result = $fetcher->run(1);

        $this->assertSame(['imported' => 1, 'skipped' => 0], $result);
        $this->assertDirectoryExists($this->gamesDir() . '/2024/03/auto-1');
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/auto-2');
    }

    public function testRunCountsSkippedGames(): void
    {
        $client = new FakeIGDBClient();
        $client->gamesResponse = [
            $this->gameData(['slug' => 'no-media', 'screenshots' => [], 'videos' => []]),
        ];
        $importer = new TestableGameImporter($client, $this->gamesDir());
        $fetcher = new AutoFetcher($client, $importer);

        $result = $fetcher->run();

        $this->assertSame(['imported' => 0, 'skipped' => 1], $result);
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/no-media');
    }
}
