<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class TwitchWarmRouteTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/twitch-warm-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'games' => [['id' => '1', 'name' => 'Game One', 'box_art_url' => '', 'igdb_id' => 1]],
            'streamers' => [['user_id' => 'u1', 'user_login' => 'ibai', 'user_name' => 'Ibai', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 100]],
            'spanish' => [],
        ]));

        RouteTestApp::boot([
            'alv.twitch-stats.warm-key' => 'test-key',
            'alv.twitch-stats.fixture-file' => self::$fixtureFile,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        RouteTestApp::shutdown();
    }

    public function testWarmRejectsWrongKey(): void
    {
        $result = RouteTestApp::call('twitch-stats-warm', ['key' => 'nope'], 'POST');

        $this->assertSame(['error' => 'unauthorized'], $result);
    }

    public function testWarmRefreshesCachesAndReturnsSnapshotStatus(): void
    {
        $result = RouteTestApp::call('twitch-stats-warm', ['key' => 'test-key'], 'POST');

        $this->assertSame('ok', $result['status']);
        $this->assertArrayHasKey('snapshot', $result);
        $this->assertArrayHasKey('pruned', $result);

        $lastRun = RouteTestApp::app()->cache('alv/twitch-stats.cache')->get('warm-last-run');
        $this->assertIsInt($lastRun);
        $this->assertGreaterThan(0, $lastRun);
    }

    public function testTwitchStatsSiteMethodUsesFixture(): void
    {
        $stats = RouteTestApp::app()->site()->twitchStats();
        $games = $stats->getTopGames(10);

        $this->assertCount(1, $games);
        $this->assertSame('Game One', $games[0]['name']);
    }
}
