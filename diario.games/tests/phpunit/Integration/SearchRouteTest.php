<?php

declare(strict_types=1);

namespace Tests\Integration;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class SearchRouteTest extends TestCase
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

Summary: A quest.

----

ReleaseDate: 2024-03-15

----

Platforms: PC (Microsoft Windows), PlayStation 5

----

IgdbId: 111

----

Screenshots: shot_1

----

Websites: 1:https://store.steampowered.com/app/570/
TXT);

        $db = new SteamStatsDB(RouteTestApp::tmp() . '/steam_stats.db');
        $db->upsertGame(570, 'alpha-quest', 'Alpha Quest', 111);
        $db->upsertGame(998, 'beta-blaster', 'Beta Blaster', 222);
        $db->replaceChartEntries([
            ['rank' => 1, 'appid' => 999, 'name' => 'Gamma Racer', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
        ], time());
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testLocalPageSearchReturnsExistsAndSteamFlags(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'alpha']);

        $this->assertFalse($result['fromIgdb']);
        $this->assertCount(1, $result['results']);

        $entry = $result['results'][0];
        $this->assertSame('alpha-quest', $entry['slug']);
        $this->assertSame('Alpha Quest', $entry['name']);
        $this->assertTrue($entry['exists']);
        $this->assertTrue($entry['hasSteam']);
        $this->assertSame('2024', $entry['year']);
        $this->assertSame('PC, PS 5', $entry['platforms']);
    }

    public function testTrackedSteamGameSearchFallsBackToDatabase(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'beta']);

        $this->assertCount(1, $result['results']);

        $entry = $result['results'][0];
        $this->assertSame('beta-blaster', $entry['slug']);
        $this->assertSame('Beta Blaster', $entry['name']);
        $this->assertTrue($entry['hasSteam']);
        $this->assertFalse($entry['exists']);
        $this->assertSame(
            'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/998/library_600x900.jpg',
            $entry['cover']
        );
    }

    public function testChartEntrySearchReturnsSlugifiedName(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'gamma']);

        $this->assertCount(1, $result['results']);

        $entry = $result['results'][0];
        $this->assertSame('gamma-racer', $entry['slug']);
        $this->assertSame('Gamma Racer', $entry['name']);
        $this->assertFalse($entry['hasSteam']);
        $this->assertFalse($entry['exists']);
    }

    public function testIgdbSourceWithoutCredentialsReturnsEmpty(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => 'zzz', 'source' => 'igdb']);

        $this->assertTrue($result['fromIgdb']);
        $this->assertSame([], $result['results']);
    }

    public function testEmptyQueryReturnsEmptyResults(): void
    {
        $result = RouteTestApp::call('steam-stats-api/search', ['q' => '']);

        $this->assertSame([], $result['results']);
        $this->assertFalse($result['fromIgdb']);
    }
}
