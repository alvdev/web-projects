<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbGamesTest extends TestCase
{
    use TempDatabase;

    private SteamStatsDB $db;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
        $this->db = new SteamStatsDB($this->tempDatabasePath);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    public function testUpsertGameInsertsAndReadsBack(): void
    {
        $this->db->upsertGame(730, 'counter-strike-2', 'Counter-Strike 2', 1020);

        $game = $this->db->getGameBySlug('counter-strike-2');
        $this->assertSame(730, (int)$game['appid']);
        $this->assertSame('Counter-Strike 2', $game['name']);
        $this->assertSame(1020, (int)$game['igdb_id']);
        $this->assertSame(730, (int)$this->db->getGameByAppId(730)['appid']);
        $this->assertSame(730, (int)$this->db->getGameByIgdbId(1020)['appid']);
        $this->assertNull($this->db->getGameBySlug('missing-game'));
    }

    public function testUpsertGameKeepsCleanSlugWhenNewSlugIsDuplicate(): void
    {
        $this->db->upsertGame(1, 'foo', 'Foo');
        $this->db->upsertGame(1, 'foo--2', 'Foo');

        $this->assertSame('foo', $this->db->getGameByAppId(1)['slug']);
    }

    public function testUpsertGameUpgradesDuplicateSlugToCleanSlug(): void
    {
        $this->db->upsertGame(2, 'bar--2', 'Bar');
        $this->db->upsertGame(2, 'bar', 'Bar');

        $this->assertSame('bar', $this->db->getGameByAppId(2)['slug']);
    }

    public function testUpsertGamePreservesExistingIgdbIdWhenNullPassed(): void
    {
        $this->db->upsertGame(3, 'baz', 'Baz', 555);
        $this->db->upsertGame(3, 'baz', 'Baz');

        $this->assertSame(555, (int)$this->db->getGameByAppId(3)['igdb_id']);
    }

    public function testNormalizeSlugConvertsRomanNumerals(): void
    {
        $this->assertSame('final-fantasy-16', SteamStatsDB::normalizeSlug('final-fantasy-xvi'));
        $this->assertSame('civilization-6', SteamStatsDB::normalizeSlug('civilization-vi'));
        $this->assertSame('grand-theft-auto-5', SteamStatsDB::normalizeSlug('grand-theft-auto-v'));
        $this->assertSame('kingdom-hearts-3', SteamStatsDB::normalizeSlug('kingdom-hearts-iii'));
        $this->assertSame('halo-3', SteamStatsDB::normalizeSlug('halo-3'));
    }

    public function testSlugLookupNormalizesRomanNumerals(): void
    {
        $this->db->upsertGame(4, 'my-game-iv', 'My Game IV');

        $this->assertSame('my-game-4', $this->db->getGameBySlug('my-game-iv')['slug']);
        $this->assertNotNull($this->db->getGameBySlug('my-game-4'));
    }

    public function testSetYearMonthCreatesAndUpdatesIndexRow(): void
    {
        $this->db->setYearMonth('indexed-game', '2024-03', 42);
        $this->assertSame('2024-03', $this->db->getYearMonth('indexed-game'));
        $this->assertSame(42, (int)$this->db->getGameBySlug('indexed-game')['igdb_id']);

        $this->db->setYearMonth('indexed-game', '2025-01');
        $this->assertSame('2025-01', $this->db->getYearMonth('indexed-game'));
        $this->assertSame(42, (int)$this->db->getGameBySlug('indexed-game')['igdb_id']);
        $this->assertNull($this->db->getYearMonth('unknown-game'));
    }

    public function testSearchGamesMatchesSubstringOrderedByShortestName(): void
    {
        $this->db->upsertGame(1, 'portal', 'Portal');
        $this->db->upsertGame(2, 'portal-2', 'Portal 2');
        $this->db->upsertGame(3, 'other', 'Other');

        $this->assertSame(
            ['Portal', 'Portal 2'],
            array_column($this->db->searchGames('portal'), 'name')
        );
    }

    public function testGetAllGamesAndAppids(): void
    {
        $this->db->upsertGame(10, 'ten', 'Ten');
        $this->db->upsertGame(20, 'twenty', 'Twenty');

        $this->assertCount(2, $this->db->getAllGames());

        $appids = array_map('intval', $this->db->getAllAppids());
        sort($appids);
        $this->assertSame([10, 20], $appids);
    }

    public function testBackfillFailureCounters(): void
    {
        $this->db->upsertGame(5, 'stale', 'Stale');
        $this->assertSame(0, $this->db->getBackfillFailures(5));

        $this->db->incrementBackfillFailures(5, 'timeout');
        $this->db->incrementBackfillFailures(5, 'timeout');
        $this->assertSame(2, $this->db->getBackfillFailures(5));

        $this->db->resetBackfillFailures(5);
        $this->assertSame(0, $this->db->getBackfillFailures(5));
    }
}
