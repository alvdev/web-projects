<?php

declare(strict_types=1);

namespace Tests\Integration;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class RankingsRouteTest extends TestCase
{
    private const LOCK = '/tmp/steamdb-charts-browser.lock';

    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    protected function setUp(): void
    {
        @unlink(self::LOCK);
        RouteTestApp::$spawns = [];
        RouteTestApp::app()->cache('alv/steam-stats.cache')->remove('charts-spawn');
    }

    private function db(): SteamStatsDB
    {
        return new SteamStatsDB(RouteTestApp::tmp() . '/steam_stats.db');
    }

    public function testMissingChunkTriggersSpawnAndReportsPending(): void
    {
        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

        $this->assertSame(0, $result['chunk']);
        $this->assertSame('missing', $result['status']);
        $this->assertFalse($result['fresh']);
        $this->assertSame(900, $result['ttl']);
        $this->assertTrue($result['refresh_pending']);
        $this->assertSame([], $result['rows']);
        $this->assertSame([0], RouteTestApp::$spawns);
    }

    public function testFreshChunkServesRowsWithoutSpawning(): void
    {
        $db = $this->db();
        $db->replaceChartEntries([
            ['rank' => 101, 'appid' => 501, 'name' => 'Fresh Game', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
        ], time());
        $db->markChartChunksFresh(101, time());

        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 1]);

        $this->assertSame('fresh', $result['status']);
        $this->assertTrue($result['fresh']);
        $this->assertFalse($result['refresh_pending']);
        $this->assertSame([], RouteTestApp::$spawns);
        $this->assertSame(501, (int)$result['rows'][0]['appid']);
    }

    public function testStaleChunkTriggersSpawn(): void
    {
        $db = $this->db();
        $db->replaceChartEntries([
            ['rank' => 1, 'appid' => 502, 'name' => 'Stale Game', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
        ], time() - 10000);
        $db->markChartChunksFresh(1, time() - 10000);

        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

        $this->assertFalse($result['fresh']);
        $this->assertTrue($result['refresh_pending']);
        $this->assertSame([0], RouteTestApp::$spawns);
        $this->assertSame(502, (int)$result['rows'][0]['appid']);
    }

    public function testLockedRefreshDoesNotSpawn(): void
    {
        touch(self::LOCK);

        try {
            $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

            $this->assertTrue($result['refresh_pending']);
            $this->assertSame([], RouteTestApp::$spawns);
        } finally {
            @unlink(self::LOCK);
        }
    }

    public function testSpawnCooldownSuppressesSpawn(): void
    {
        RouteTestApp::app()->cache('alv/steam-stats.cache')
            ->set('charts-spawn', ['value' => time(), 'timestamp' => time()]);

        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 0]);

        $this->assertFalse($result['refresh_pending']);
        $this->assertSame([], RouteTestApp::$spawns);
    }

    public function testChunkBeyondMaxNeverSpawns(): void
    {
        $result = RouteTestApp::call('steam-stats-api/rankings', ['chunk' => 9]);

        $this->assertSame('missing', $result['status']);
        $this->assertFalse($result['refresh_pending']);
        $this->assertSame([], RouteTestApp::$spawns);
        $this->assertSame([], $result['rows']);
    }
}
