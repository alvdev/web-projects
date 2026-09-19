<?php

declare(strict_types=1);

namespace Tests\Integration;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class GameDataRouteTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();

        mkdir(RouteTestApp::content('games/2024/03/alpha-quest'), 0775, true);
        file_put_contents(RouteTestApp::content('games/2024/03/alpha-quest/game.txt'), <<<'TXT'
Title: Alpha Quest

----

Template: game

----

ReleaseDate: 2024-03-15

----

IgdbId: 111

----

Screenshots: shot_1
TXT);

        $db = new SteamStatsDB(RouteTestApp::tmp() . '/steam_stats.db');
        $db->upsertGame(570, 'alpha-quest', 'Alpha Quest', 111);

        $now = time();
        $db->insertPlayerCount(570, $now - 2 * 86400 - 60, 10);
        $db->insertPlayerCount(570, $now - 86400, 20);
        $db->insertPlayerCount(570, $now - 3600, 30);
        $db->insertPlayerCount(570, $now - 300, 40);
        $db->upsertGamePeak(570, 999, $now - 10 * 86400);
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    private function assertRangesMatch(array $expected, array $actual): void
    {
        $this->assertSame(array_keys($expected), array_keys($actual));
        foreach ($expected as $key => $points) {
            $this->assertSame($points, $actual[$key], "Range {$key} differs");
        }
    }

    public function testGameDataRouteReturnsPlayersAndRanges(): void
    {
        $result = RouteTestApp::call('steam-stats-api/game/alpha-quest/data');

        $this->assertSame('alpha-quest', $result['game']['slug']);
        $this->assertSame(40, $result['current']);
        $this->assertSame(40, $result['peak_24h']);
        $this->assertSame(40, $result['peak_3m']);
        $this->assertSame(999, $result['peak_all_time']);

        $this->assertCount(11, $result['ranges']);
        $this->assertArrayHasKey('48h', $result['ranges']);
        $this->assertArrayHasKey('max', $result['ranges']);

        $hourly = $result['ranges']['48h'];
        $this->assertSame(20, (int)$hourly[0]['p']);
        $this->assertSame(30, (int)$hourly[1]['p']);
        $this->assertSame(40, (int)$hourly[2]['p']);
    }

    public function testDailyRangeDropsIncompleteCurrentPeriod(): void
    {
        $result = RouteTestApp::call('steam-stats-api/game/alpha-quest/data');

        $points = $result['ranges']['1m'];
        $this->assertNotEmpty($points);

        $todayStart = strtotime('today 00:00:00');
        $this->assertLessThan($todayStart, (int)end($points)['timestamp']);
    }

    public function testGameDataRouteReturnsErrorForUnknownSlug(): void
    {
        $result = RouteTestApp::call('steam-stats-api/game/does-not-exist/data');

        $this->assertSame(['error' => 'not found'], $result);
    }

    public function testSteamChartDataSiteMethodMatchesRouteRanges(): void
    {
        $route = RouteTestApp::call('steam-stats-api/game/alpha-quest/data');
        $method = RouteTestApp::app()->site()->steamChartData('alpha-quest');

        $this->assertNotNull($method);
        $this->assertSame($route['current'], $method['current']);
        $this->assertSame($route['peak_24h'], $method['peak_24h']);
        $this->assertSame($route['peak_3m'], $method['peak_3m']);
        $this->assertSame($route['peak_all_time'], $method['peak_all_time']);
        $this->assertRangesMatch($route['ranges'], $method['ranges']);

        $this->assertArrayHasKey('available_tabs', $method);
        $this->assertArrayHasKey('peak_all_time_age', $method);
        $this->assertArrayNotHasKey('available_tabs', $route);
    }
}
