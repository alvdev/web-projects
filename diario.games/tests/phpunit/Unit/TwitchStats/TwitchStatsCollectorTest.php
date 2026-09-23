<?php

declare(strict_types=1);

namespace Tests\Unit\TwitchStats;

use Alv\TwitchStats\TwitchClient;
use Alv\TwitchStats\TwitchStatsCollector;
use Alv\TwitchStats\TwitchStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTwitchClient;
use Tests\Support\PluginClasses;
use Tests\Support\TempTwitchDatabase;

final class TwitchStatsCollectorTest extends TestCase
{
    use TempTwitchDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempTwitchDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempTwitchDatabase();
    }

    private function fakeClient(): FakeTwitchClient
    {
        return new FakeTwitchClient(
            games: [
                ['id' => '1', 'name' => 'Game One', 'box_art_url' => 'a.jpg', 'igdb_id' => 1],
                ['id' => '2', 'name' => 'Game Two', 'box_art_url' => 'b.jpg', 'igdb_id' => null],
            ],
            streams: [
                ['user_id' => 'u1', 'user_login' => 'ibai', 'user_name' => 'Ibai', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 100],
                ['user_id' => 'u2', 'user_login' => 'rubius', 'user_name' => 'Rubius', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 50],
            ],
            spanish: [
                ['user_id' => 'u3', 'user_login' => 'auronplay', 'user_name' => 'AuronPlay', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 70],
            ],
            avatars: ['u1' => 'a1.jpg', 'u2' => 'a2.jpg', 'u3' => 'a3.jpg'],
        );
    }

    public function testSnapshotWritesGamesStreamersAndSpanishOncePerHour(): void
    {
        $db = new TwitchStatsDB($this->tempTwitchPath);
        $collector = new TwitchStatsCollector($this->fakeClient(), $db);

        $first = $collector->snapshot(3661);

        $this->assertSame(['games' => 2, 'streamers' => 2, 'spanish' => 1, 'skipped' => false], $first);
        $this->assertSame(5, $db->countSnapshots());
        $this->assertSame('a1.jpg', $db->getStreamer('u1')['avatar_url']);
        $this->assertSame('es', $db->getStreamer('u3')['language']);

        $gameSnapshot = $db->getSnapshots('game', '1', 0);
        $this->assertSame(150, $gameSnapshot[0]['viewers']);
        $this->assertSame(3600, $gameSnapshot[0]['timestamp']);

        $second = $collector->snapshot(3700);

        $this->assertTrue($second['skipped']);
        $this->assertSame(5, $db->countSnapshots());
    }

    public function testSnapshotWithUnconfiguredClientSkips(): void
    {
        $collector = new TwitchStatsCollector(new TwitchClient('', ''), new TwitchStatsDB($this->tempTwitchPath));

        $this->assertSame(
            ['games' => 0, 'streamers' => 0, 'spanish' => 0, 'skipped' => true],
            $collector->snapshot(1000)
        );
    }

    public function testPruneRemovesSnapshotsOlderThanRetention(): void
    {
        $db = new TwitchStatsDB($this->tempTwitchPath);
        $collector = new TwitchStatsCollector(new TwitchClient('', ''), $db, 86400);
        $now = time();

        $db->insertSnapshot('game', '1', $now - 172800, 10, 1);
        $db->insertSnapshot('game', '1', $now - 3600, 20, 1);

        $this->assertSame(1, $collector->prune($now));
        $this->assertSame(1, $db->countSnapshots());
    }
}
