<?php

declare(strict_types=1);

namespace Tests\Unit\TwitchStats;

use Alv\TwitchStats\TwitchStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempTwitchDatabase;

final class TwitchStatsDbTest extends TestCase
{
    use TempTwitchDatabase;

    private TwitchStatsDB $db;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempTwitchDatabase();
        $this->db = new TwitchStatsDB($this->tempTwitchPath);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempTwitchDatabase();
    }

    public function testUpsertGameIsIdempotentAndLookupByIgdbId(): void
    {
        $this->db->upsertGame('509658', 'Just Chatting', 509658, 'https://img/art.jpg');
        $this->db->upsertGame('509658', 'Just Chatting', 509658, 'https://img/art2.jpg');

        $this->assertSame(1, $this->db->countGames());
        $game = $this->db->getGameByIgdbId(509658);
        $this->assertSame('Just Chatting', $game['name']);
        $this->assertSame('https://img/art2.jpg', $game['box_art_url']);
        $this->assertNull($this->db->getGameByIgdbId(999));
    }

    public function testUpsertStreamerStoresAvatarAndLanguage(): void
    {
        $this->db->upsertStreamer('u1', 'ibai', 'Ibai', 'https://img/a.jpg', 'es');
        $this->db->upsertStreamer('u1', 'ibai', 'Ibai', 'https://img/b.jpg', 'es');

        $streamer = $this->db->getStreamer('u1');

        $this->assertSame('ibai', $streamer['login']);
        $this->assertSame('https://img/b.jpg', $streamer['avatar_url']);
        $this->assertSame('es', $streamer['language']);
        $this->assertNull($this->db->getStreamer('nope'));
    }

    public function testInsertSnapshotIgnoresDuplicateTimestamps(): void
    {
        $this->db->insertSnapshot('game', '509658', 1000, 500, 1);
        $this->db->insertSnapshot('game', '509658', 1000, 999, 2);

        $this->assertSame(1, $this->db->countSnapshots());
        $this->assertSame(500, $this->db->getSnapshots('game', '509658', 0)[0]['viewers']);
    }

    public function testInsertSnapshotRejectsUnknownEntityType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->db->insertSnapshot('nonsense', 'x', 1000, 1, 1);
    }

    public function testHasSnapshotAtIsScopedToEntityType(): void
    {
        $this->db->insertSnapshot('game', '1', 1000, 10, 1);

        $this->assertTrue($this->db->hasSnapshotAt('game', 1000));
        $this->assertFalse($this->db->hasSnapshotAt('streamer', 1000));
        $this->assertFalse($this->db->hasSnapshotAt('game', 2000));
    }

    public function testGetSnapshotsFiltersBySinceAndOrdersAscending(): void
    {
        $this->db->insertSnapshot('game', '1', 3000, 30, 1);
        $this->db->insertSnapshot('game', '1', 1000, 10, 2);
        $this->db->insertSnapshot('game', '1', 2000, 20, 3);
        $this->db->insertSnapshot('game', '2', 2000, 5, 4);

        $rows = $this->db->getSnapshots('game', '1', 1500);

        $this->assertSame([2000, 3000], array_column($rows, 'timestamp'));
        $this->assertSame([20, 30], array_column($rows, 'viewers'));
    }

    public function testGetHistorySeriesGroupsByEntity(): void
    {
        $this->db->insertSnapshot('streamer', 'u1', 1000, 10, 1, 'Just Chatting');
        $this->db->insertSnapshot('streamer', 'u1', 2000, 20, 2, 'Just Chatting');
        $this->db->insertSnapshot('streamer', 'u2', 1000, 5, 3, 'Dota 2');
        $this->db->insertSnapshot('game', '1', 1000, 99, 1);

        $series = $this->db->getHistorySeries('streamer', 0);

        $this->assertSame(['u1', 'u2'], array_keys($series));
        $this->assertSame([1000, 2000], array_column($series['u1'], 'timestamp'));
        $this->assertSame('Dota 2', $series['u2'][0]['game_name']);
    }

    public function testPruneBeforeDeletesOldSnapshotsAndReturnsCount(): void
    {
        $this->db->insertSnapshot('game', '1', 1000, 10, 1);
        $this->db->insertSnapshot('game', '1', 2000, 20, 1);
        $this->db->insertSnapshot('streamer', 'u1', 1500, 5, 1);

        $this->assertSame(2, $this->db->pruneBefore(1800));
        $this->assertSame(1, $this->db->countSnapshots());
        $this->assertSame([2000], array_column($this->db->getSnapshots('game', '1', 0), 'timestamp'));
    }

    public function testDefaultPathComesFromEnvironment(): void
    {
        $db = new TwitchStatsDB();

        $this->assertFileExists($this->tempTwitchPath);
        $this->assertSame(0, $db->countSnapshots());
    }
}
