<?php

declare(strict_types=1);

namespace Tests\Unit\TwitchStats;

use Alv\TwitchStats\TwitchStats;
use Alv\TwitchStats\TwitchStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempTwitchDatabase;

final class TwitchStatsAssemblyTest extends TestCase
{
    use TempTwitchDatabase;

    private string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempTwitchDatabase();

        $this->fixtureFile = $this->tempTwitchDir . '/fixture.json';
        file_put_contents($this->fixtureFile, json_encode([
            'games' => [
                ['id' => '1', 'name' => 'Game One', 'box_art_url' => 'https://x/1-{width}x{height}.jpg', 'igdb_id' => 1],
                ['id' => '2', 'name' => 'Game Two', 'box_art_url' => '', 'igdb_id' => null],
            ],
            'streamers' => [
                ['user_id' => 'u1', 'user_login' => 'ibai', 'user_name' => 'Ibai', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 100],
                ['user_id' => 'u2', 'user_login' => 'rubius', 'user_name' => 'Rubius', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 50],
            ],
            'spanish' => [
                ['user_id' => 'u3', 'user_login' => 'auronplay', 'user_name' => 'AuronPlay', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 70],
            ],
            'tracker_games' => [
                '1' => ['avg_viewers' => 120, 'avg_channels' => 10, 'rank' => 2, 'hours_watched' => 5000],
            ],
            'tracker_channels' => [
                'ibai' => ['avg_viewers' => 90, 'max_viewers' => 200, 'followers' => 5, 'followers_total' => 17000000, 'rank' => 25],
            ],
            'avatars' => ['u1' => 'a1.jpg', 'u2' => 'a2.jpg', 'u3' => 'a3.jpg'],
        ]));
    }

    protected function tearDown(): void
    {
        $this->tearDownTempTwitchDatabase();
    }

    private function stats(?TwitchStatsDB $db = null): TwitchStats
    {
        return new TwitchStats(
            ['fixture_file' => $this->fixtureFile, 'history_ttl' => 604800],
            null,
            null,
            $db ?? new TwitchStatsDB($this->tempTwitchPath)
        );
    }

    public function testTopGamesMergesLiveAggregationTrackerAndHistory(): void
    {
        $db = new TwitchStatsDB($this->tempTwitchPath);
        $now = time();
        $db->insertSnapshot('game', '1', $now - 7200, 100, 1);
        $db->insertSnapshot('game', '1', $now - 3600, 150, 1);

        $games = $this->stats($db)->getTopGames(10);

        $this->assertCount(2, $games);

        $first = $games[0];
        $this->assertSame(1, $first['rank']);
        $this->assertSame('1', $first['id']);
        $this->assertSame('Game One', $first['name']);
        $this->assertSame('https://x/1-144x192.jpg', $first['box_art_url']);
        $this->assertSame(1, $first['igdb_id']);
        $this->assertSame(150, $first['viewers']);
        $this->assertSame(2, $first['live_channels']);
        $this->assertSame(120, $first['avg_viewers']);
        $this->assertSame(2, $first['twitch_rank']);
        $this->assertSame(5000, $first['hours_watched']);
        $this->assertSame(50.0, $first['change_pct']);
        $this->assertCount(2, $first['history']);

        $second = $games[1];
        $this->assertSame(0, $second['viewers']);
        $this->assertNull($second['avg_viewers']);
        $this->assertNull($second['change_pct']);
    }

    public function testTopStreamersMergesAvatarsTrackerAndHistory(): void
    {
        $db = new TwitchStatsDB($this->tempTwitchPath);
        $now = time();
        $db->insertSnapshot('streamer', 'u1', $now - 7200, 50, 2, 'Game One');
        $db->insertSnapshot('streamer', 'u1', $now - 3600, 100, 1, 'Game One');

        $streamers = $this->stats($db)->getTopStreamers(10);

        $this->assertCount(2, $streamers);

        $first = $streamers[0];
        $this->assertSame(1, $first['rank']);
        $this->assertSame('ibai', $first['login']);
        $this->assertSame('Ibai', $first['name']);
        $this->assertSame('https://www.twitch.tv/ibai', $first['url']);
        $this->assertSame('a1.jpg', $first['avatar_url']);
        $this->assertSame('Game One', $first['game_name']);
        $this->assertSame(100, $first['viewers']);
        $this->assertSame(90, $first['avg_viewers']);
        $this->assertSame(17000000, $first['followers_total']);
        $this->assertSame(25, $first['twitch_rank']);
        $this->assertSame(100.0, $first['change_pct']);

        $second = $streamers[1];
        $this->assertNull($second['avg_viewers']);
        $this->assertSame('a2.jpg', $second['avatar_url']);
        $this->assertNull($second['change_pct']);
    }

    public function testTopSpanishUsesSpanishStreamsOnly(): void
    {
        $spanish = $this->stats()->getTopSpanish(10);

        $this->assertCount(1, $spanish);
        $this->assertSame('auronplay', $spanish[0]['login']);
        $this->assertSame(70, $spanish[0]['viewers']);
    }

    public function testEmptyCredentialsWithoutFixtureReturnEmptyLists(): void
    {
        $stats = new TwitchStats(
            ['client_id' => '', 'client_secret' => ''],
            null,
            null,
            new TwitchStatsDB($this->tempTwitchPath)
        );

        $this->assertSame([], $stats->getTopGames(10));
        $this->assertSame([], $stats->getTopStreamers(10));
        $this->assertSame([], $stats->getTopSpanish(10));
    }
}
