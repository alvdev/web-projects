<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class LanzamientosPageTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/releases-page-fixture-' . bin2hex(random_bytes(4)) . '.json';
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

        RouteTestApp::boot([
            'alv.releases.fixture-file' => self::$fixtureFile,
        ]);

        mkdir(RouteTestApp::content('games'), 0775, true);
        file_put_contents(
            RouteTestApp::content('games/games.txt'),
            "Title: Todos los juegos\n\n----\n\nTemplate: games\n"
        );
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        RouteTestApp::shutdown();
    }

    private function renderPage(): string
    {
        return (string) RouteTestApp::call('lanzamientos');
    }

    public function testPageRendersThreeSectionsAndNoTabs(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('Próximos lanzamientos de videojuegos', $html);
        $this->assertStringContainsString('data-releases-section="recent"', $html);
        $this->assertStringContainsString('data-releases-section="upcoming"', $html);
        $this->assertStringContainsString('data-releases-section="anticipated"', $html);
        $this->assertSame(5, substr_count($html, 'data-release-row'));
        $this->assertStringNotContainsString('data-releases-tab', $html);
    }

    public function testGenreChipsAndRowsExposeGenres(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('data-releases-filter', $html);
        $this->assertStringContainsString('Todos (5)', $html);
        $this->assertStringContainsString('RPG (3)', $html);
        $this->assertStringContainsString('Acción (1)', $html);
        $this->assertStringContainsString('Aventura (1)', $html);
        $this->assertStringContainsString('data-genres="[&quot;RPG&quot;]"', $html);
    }

    public function testReleasedRowsLinkInternallyAndUpcomingStaysUnlinked(): void
    {
        $html = $this->renderPage();

        $this->assertSame(2, substr_count($html, '<a data-release-row'));
        $this->assertSame(3, substr_count($html, '<div data-release-row'));
        $this->assertStringContainsString('href="/alpha-quest"', $html);
        $this->assertStringContainsString('href="/games/by-igdb-id/12"', $html);
        $this->assertStringNotContainsString('href="/games/by-igdb-id/21"', $html);
        $this->assertStringNotContainsString('href="/games/by-igdb-id/31"', $html);
    }

    public function testJsonLdAttributionAndMetaDescription(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"ItemList"', $html);
        $this->assertStringContainsString('"releaseDate":"2026-09-25"', $html);
        $this->assertStringContainsString('Datos de IGDB', $html);
        $this->assertMatchesRegularExpression(
            '~<meta name="description" content="Calendario de próximos lanzamientos[^"]*"~',
            $html
        );
    }
}
