<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableGameImporter;

final class GameImporterImportTest extends TestCase
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

    private function importer(?FakeIGDBClient $client = null): TestableGameImporter
    {
        return new TestableGameImporter($client ?? new FakeIGDBClient(), $this->gamesDir());
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
                ['developer' => false, 'publisher' => true, 'company' => ['name' => 'Pub Studio']],
            ],
            'screenshots' => [['id' => 1]],
            'videos' => [],
            'cover' => 1,
            'rating' => 88.5,
            'aggregated_rating' => 90.0,
            'websites' => [],
        ], $overrides);
    }

    public function testImportWritesShardedContentAndMedia(): void
    {
        $importer = $this->importer();

        $this->assertSame('test-game', $importer->import($this->gameData()));

        $dir = $this->gamesDir() . '/2024/03/test-game';
        $this->assertDirectoryExists($dir);

        $content = file_get_contents($dir . '/game.txt');
        $this->assertStringContainsString('Title: Test Game', $content);
        $this->assertStringContainsString('Summary: [es] A test game summary.', $content);
        $this->assertStringContainsString('ReleaseDate: 2024-03-15', $content);
        $this->assertStringContainsString('Developer: Dev Studio', $content);
        $this->assertStringContainsString('Publisher: Pub Studio', $content);
        $this->assertStringContainsString('Genres: RPG', $content);
        $this->assertStringContainsString('Tags: Acción', $content);
        $this->assertStringContainsString('Platforms: PC', $content);
        $this->assertStringContainsString('IgdbId: 1234', $content);
        $this->assertStringContainsString('Screenshots: shot_1, shot_2', $content);

        $this->assertFileExists($dir . '/test-game.jpg');
        $this->assertFileExists($dir . '/test-game.jpg.txt');
        $this->assertFileExists($dir . '/test-game-hero.jpg');
        $this->assertFileExists($dir . '/test-game-hero.jpg.txt');
        $this->assertFileExists($dir . '/screenshot-0.jpg');
        $this->assertFileExists($dir . '/screenshot-1.jpg');
        $this->assertFileExists($dir . '/screenshot-0.jpg.txt');

        $this->assertSame('2024/03', \DiarioGames\IGDB\resolveGamePath('test-game'));
        $this->assertSame(['A test game summary.'], $importer->translated);
    }

    public function testImportReturnsNullWhenPlatformsAreNotAllowed(): void
    {
        $client = new FakeIGDBClient();
        $client->platforms = [['id' => 99, 'name' => 'Commodore 64']];

        $this->assertNull($this->importer($client)->import($this->gameData()));
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/test-game');
    }

    public function testImportReturnsNullWithoutScreenshotsOrVideos(): void
    {
        $data = $this->gameData(['screenshots' => [], 'videos' => []]);

        $this->assertNull($this->importer()->import($data));
        $this->assertDirectoryDoesNotExist($this->gamesDir() . '/2024/03/test-game');
    }

    public function testReimportingExistingGameDoesNotDuplicateContent(): void
    {
        $importer = $this->importer();

        $this->assertSame('test-game', $importer->import($this->gameData()));
        $this->assertSame('test-game', $importer->import($this->gameData()));

        $content = file_get_contents($this->gamesDir() . '/2024/03/test-game/game.txt');
        $this->assertSame(1, substr_count($content, 'Title: Test Game'));
        $this->assertSame('2024/03', \DiarioGames\IGDB\resolveGamePath('test-game'));
    }
}
