<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class HomePageTwitchTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/twitch-home-fixture-' . bin2hex(random_bytes(4)) . '.json';
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
            'tracker_games' => ['1' => ['avg_viewers' => 120, 'rank' => 2]],
            'avatars' => ['u1' => 'a1.jpg', 'u2' => 'a2.jpg', 'u3' => 'a3.jpg'],
        ]));

        RouteTestApp::boot([
            'alv.twitch-stats.fixture-file' => self::$fixtureFile,
        ]);

        mkdir(RouteTestApp::content('home'), 0775, true);
        file_put_contents(
            RouteTestApp::content('home/home.txt'),
            "Title: Inicio\n\n----\n\nTemplate: home\n"
        );

        $gameDir = RouteTestApp::content('games/2024/03/alpha-quest');
        mkdir($gameDir, 0775, true);
        file_put_contents(
            $gameDir . '/game.txt',
            "Title: Alpha Quest\n\n----\n\nTemplate: game\n\n----\n\nIgdbId: 1\n\n----\n\nGenres: Acción\n"
        );
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        RouteTestApp::shutdown();
    }

    private function renderHome(): string
    {
        return RouteTestApp::app()->page('home')->render();
    }

    private function widget(string $html): string
    {
        $start = strpos($html, 'data-twitch-widget');
        $this->assertNotFalse($start, 'Twitch widget missing from homepage');

        $end = strpos($html, 'data-home-posts-row', $start);
        $this->assertNotFalse($end, 'Posts row missing after Twitch widget');

        return substr($html, $start, $end - $start);
    }

    public function testWidgetRendersThreeTabs(): void
    {
        $widget = $this->widget($this->renderHome());

        $this->assertStringContainsString('data-twitch-tab="games"', $widget);
        $this->assertStringContainsString('data-twitch-tab="streamers"', $widget);
        $this->assertStringContainsString('data-twitch-tab="spanish"', $widget);
        $this->assertSame(6, substr_count($widget, 'data-twitch-row'));
    }

    public function testGameRowsLinkToExistingPagesAndImportRoutes(): void
    {
        $widget = $this->widget($this->renderHome());

        $this->assertMatchesRegularExpression('~href="[^"]*/alpha-quest"[^>]*>~', $widget);
        $this->assertMatchesRegularExpression('~href="[^"]*/games/by-igdb-id/999"[^>]*data-importing~', $widget);
        $this->assertStringContainsString('https://www.twitch.tv/directory/category/3', $widget);
        $this->assertStringContainsString('Media 30d', $widget);
    }

    public function testStreamerRowsLinkToTwitchAndShowSpanishTab(): void
    {
        $widget = $this->widget($this->renderHome());

        $this->assertStringContainsString('https://www.twitch.tv/ibai', $widget);
        $this->assertStringContainsString('https://www.twitch.tv/auronplay', $widget);
        $this->assertStringContainsString('auronplay', $widget);
    }

    public function testFooterLinksToFullRankingsAndCreditsTwitch(): void
    {
        $widget = $this->widget($this->renderHome());

        $this->assertStringContainsString('href="/twitch-stats"', $widget);
        $this->assertStringContainsString('Datos de Twitch', $widget);
    }
}
