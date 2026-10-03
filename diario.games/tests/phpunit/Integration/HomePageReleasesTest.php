<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class HomePageReleasesTest extends TestCase
{
    private static string $fixtureFile;
    private static string $emptyFixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/releases-home-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'recentlyReleased' => [
                [
                    'igdb_id' => 11,
                    'slug' => 'alpha-quest',
                    'name' => 'Alpha Quest',
                    'release_date' => '2026-09-25',
                    'display_date' => '25 de septiembre de 2026',
                    'month_key' => '2026-09',
                    'month_label' => 'Septiembre 2026',
                    'hypes' => 292,
                    'cover_url' => null,
                    'platforms' => ['PC'],
                    'genres' => ['RPG'],
                    'local_url' => '/alpha-quest',
                ],
                [
                    'igdb_id' => 12,
                    'slug' => 'just-out',
                    'name' => 'Just Out',
                    'release_date' => '2026-09-20',
                    'display_date' => '20 de septiembre de 2026',
                    'month_key' => '2026-09',
                    'month_label' => 'Septiembre 2026',
                    'hypes' => 80,
                    'cover_url' => null,
                    'platforms' => ['PS5'],
                    'genres' => ['Acción'],
                    'local_url' => null,
                ],
            ],
            'upcoming' => [
                [
                    'igdb_id' => 21,
                    'slug' => 'coming-one',
                    'name' => 'Coming One',
                    'release_date' => '2026-11-01',
                    'display_date' => '1 de noviembre de 2026',
                    'month_key' => '2026-11',
                    'month_label' => 'Noviembre 2026',
                    'hypes' => 60,
                    'cover_url' => null,
                    'platforms' => ['PC'],
                    'genres' => ['RPG'],
                    'local_url' => null,
                ],
                [
                    'igdb_id' => 22,
                    'slug' => 'coming-two',
                    'name' => 'Coming Two',
                    'release_date' => '2026-12-01',
                    'display_date' => '1 de diciembre de 2026',
                    'month_key' => '2026-12',
                    'month_label' => 'Diciembre 2026',
                    'hypes' => 25,
                    'cover_url' => null,
                    'platforms' => ['Xbox'],
                    'genres' => ['Aventura'],
                    'local_url' => null,
                ],
            ],
            'anticipated' => [
                [
                    'igdb_id' => 31,
                    'slug' => 'most-hyped',
                    'name' => 'Most Hyped',
                    'release_date' => null,
                    'display_date' => 'Q1 2027',
                    'month_key' => '2027-q1',
                    'month_label' => 'Q1 2027',
                    'hypes' => 900,
                    'cover_url' => null,
                    'platforms' => ['PC'],
                    'genres' => ['RPG'],
                    'local_url' => null,
                ],
            ],
        ]));

        self::$emptyFixtureFile = sys_get_temp_dir() . '/releases-home-empty-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$emptyFixtureFile, json_encode([
            'recentlyReleased' => [],
            'upcoming' => [],
            'anticipated' => [],
        ]));

        RouteTestApp::boot([
            'alv.releases.fixture-file' => self::$fixtureFile,
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
            "Title: Alpha Quest\n\n----\n\nTemplate: game\n\n----\n\nIgdbId: 11\n\n----\n\nGenres: Acción\n"
        );
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        @unlink(self::$emptyFixtureFile);
        RouteTestApp::shutdown();
    }

    public function testHomepageRendersThreeColumnsAndCalendarLink(): void
    {
        $html = RouteTestApp::app()->page('home')->render();

        $this->assertStringContainsString('data-home-releases', $html);
        $this->assertSame(3, substr_count($html, 'data-home-column='));
        $this->assertSame(2, substr_count($html, '<a data-home-release'));
        $this->assertSame(3, substr_count($html, '<div data-home-release '));
        $this->assertStringContainsString('Recién lanzados', $html);
        $this->assertStringContainsString('Próximos lanzamientos', $html);
        $this->assertStringContainsString('Más esperados', $html);
        $this->assertStringContainsString('Alpha Quest', $html);
        $this->assertStringContainsString('Coming One', $html);
        $this->assertStringContainsString('Most Hyped', $html);
        $this->assertStringContainsString('900 seguidores', $html);
        $this->assertStringContainsString('href="/lanzamientos"', $html);
    }

    public function testRecentlyReleasedRowsLinkInternallyAndOthersDoNot(): void
    {
        $html = RouteTestApp::app()->page('home')->render();

        $this->assertStringContainsString('href="/alpha-quest"', $html);
        $this->assertStringContainsString('href="/games/by-igdb-id/12"', $html);
        $this->assertStringNotContainsString('href="/games/by-igdb-id/21"', $html);
        $this->assertStringNotContainsString('href="/games/by-igdb-id/31"', $html);
    }

    public function testModuleHiddenWhenAllDatasetsAreEmpty(): void
    {
        RouteTestApp::app()->cache('alv/releases.cache')->remove('dataset-v3');

        $original = file_get_contents(self::$fixtureFile);
        file_put_contents(self::$fixtureFile, file_get_contents(self::$emptyFixtureFile));

        try {
            $html = RouteTestApp::app()->page('home')->render();
            $this->assertStringNotContainsString('data-home-releases', $html);
        } finally {
            file_put_contents(self::$fixtureFile, $original);
        }
    }
}
