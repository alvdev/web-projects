<?php

declare(strict_types=1);

namespace Tests\Integration;

use Alv\TwitchStats\TwitchStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class TwitchStatsPageTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/twitch-page-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'games' => [
                ['id' => '1', 'name' => 'Fixture Game', 'box_art_url' => 'https://x/1-{width}x{height}.jpg', 'igdb_id' => 1],
                ['id' => '2', 'name' => 'Import Game', 'box_art_url' => '', 'igdb_id' => 999],
                ['id' => '3', 'name' => 'No Igdb', 'box_art_url' => '', 'igdb_id' => null],
            ],
            'streamers' => [
                ['user_id' => 'u1', 'user_login' => 'ibai', 'user_name' => 'Ibai', 'game_id' => '1', 'game_name' => 'Fixture Game', 'viewer_count' => 100],
                ['user_id' => 'u2', 'user_login' => 'rubius', 'user_name' => 'Rubius', 'game_id' => '1', 'game_name' => 'Fixture Game', 'viewer_count' => 50],
            ],
            'spanish' => [
                ['user_id' => 'u3', 'user_login' => 'auronplay', 'user_name' => 'AuronPlay', 'game_id' => '1', 'game_name' => 'Fixture Game', 'viewer_count' => 70],
            ],
            'tracker_games' => ['1' => ['avg_viewers' => 120, 'avg_channels' => 10, 'rank' => 2, 'hours_watched' => 5000]],
            'tracker_channels' => ['ibai' => ['avg_viewers' => 90, 'max_viewers' => 200, 'followers_total' => 17000000, 'rank' => 25]],
            'avatars' => ['u1' => 'a1.jpg', 'u2' => 'a2.jpg', 'u3' => 'a3.jpg'],
        ]));

        RouteTestApp::boot([
            'alv.twitch-stats.fixture-file' => self::$fixtureFile,
        ]);

        $gameDir = RouteTestApp::content('games/2024/03/alpha-quest');
        mkdir($gameDir, 0775, true);
        file_put_contents(
            $gameDir . '/game.txt',
            "Title: Alpha Quest\n\n----\n\nTemplate: game\n\n----\n\nIgdbId: 1\n"
        );

        $db = new TwitchStatsDB(RouteTestApp::tmp() . '/twitch_stats.db');
        $now = time();
        $db->insertSnapshot('game', '1', $now - 7200, 100, 1);
        $db->insertSnapshot('game', '1', $now - 3600, 150, 1);
        $db->insertSnapshot('streamer', 'u1', $now - 7200, 50, 2, 'Fixture Game');
        $db->insertSnapshot('streamer', 'u1', $now - 3600, 100, 1, 'Fixture Game');

        RouteTestApp::app()->cache('alv/twitch-stats.cache')->set('warm-last-run', $now - 120, 30);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        RouteTestApp::shutdown();
    }

    private function renderPage(): string
    {
        return (string) RouteTestApp::call('twitch-stats');
    }

    public function testPageRendersHeaderTabsAndRows(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('Twitch Charts', $html);
        $this->assertStringContainsString('data-twitch-page-tab="games"', $html);
        $this->assertStringContainsString('data-twitch-page-tab="streamers"', $html);
        $this->assertStringContainsString('data-twitch-page-tab="spanish"', $html);
        $this->assertSame(6, substr_count($html, 'data-twitch-page-row'));
        $this->assertStringContainsString('Media 30d', $html);
    }

    public function testPageShowsTrackerColumnsAndSparklines(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('Horas 30d', $html);
        $this->assertStringContainsString('Seguidores', $html);
        $this->assertStringContainsString('120', $html);
        $this->assertStringContainsString('17M', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('+50', $html);
    }

    public function testPageShowsUpdatedIndicatorAndAttribution(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('minutes-ago', $html);
        $this->assertStringContainsString('Datos de Twitch', $html);
        $this->assertStringContainsString('/twitch-stats', $html);
    }
}
