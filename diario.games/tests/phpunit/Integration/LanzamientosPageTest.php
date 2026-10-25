<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class LanzamientosPageTest extends TestCase
{
    private static string $fixtureFile;

    private static function game(int $id, string $name, array $genres, string $date, ?string $localUrl = null, int $hypes = 50): array
    {
        return [
            'igdb_id' => $id,
            'slug' => 'game-' . $id,
            'name' => $name,
            'release_date' => $date,
            'display_date' => $date,
            'month_key' => substr($date, 0, 7),
            'month_label' => $date,
            'hypes' => $hypes,
            'cover_url' => null,
            'platforms' => ['PC'],
            'genres' => $genres,
            'local_url' => $localUrl,
        ];
    }

    public static function setUpBeforeClass(): void
    {
        $recent = [];
        for ($i = 0; $i < 35; $i++) {
            $recent[] = self::game(
                1000 + $i,
                'Recent ' . ($i + 1),
                $i % 2 === 0 ? ['RPG'] : ['Acción'],
                '2026-09-' . str_pad((string) (1 + $i % 28), 2, '0', STR_PAD_LEFT),
                $i === 0 ? '/alpha-quest' : null,
                100 - $i
            );
        }

        $upcoming = [];
        for ($i = 0; $i < 5; $i++) {
            $upcoming[] = self::game(2000 + $i, 'Upcoming ' . ($i + 1), ['RPG'], '2026-11-' . str_pad((string) (1 + $i), 2, '0', STR_PAD_LEFT), null, 50);
        }

        $anticipated = [];
        for ($i = 0; $i < 5; $i++) {
            $anticipated[] = self::game(3000 + $i, 'Anticipated ' . ($i + 1), ['Aventura'], '2027', null, 500 - $i);
        }

        self::$fixtureFile = sys_get_temp_dir() . '/releases-page-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'recentlyReleased' => $recent,
            'upcoming' => $upcoming,
            'anticipated' => $anticipated,
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

    private function hiddenLoadIndexes(string $html): array
    {
        preg_match_all('/data-load-index="(\d+)"[^>]*class="[^"]*\bhidden\b/', $html, $matches);
        $indexes = array_map('intval', $matches[1]);
        sort($indexes);

        return $indexes;
    }

    public function testPageRendersThreeColumnsWithProgressiveReveal(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('Próximos lanzamientos de videojuegos', $html);
        $this->assertStringContainsString('data-releases-section="recent"', $html);
        $this->assertStringContainsString('data-releases-section="upcoming"', $html);
        $this->assertStringContainsString('data-releases-section="anticipated"', $html);
        $this->assertSame(45, substr_count($html, 'data-release-row'));
        $this->assertSame(3, substr_count($html, '<div data-load-sentinel'));
        $this->assertSame([30, 31, 32, 33, 34], $this->hiddenLoadIndexes($html));
        $this->assertStringNotContainsString('data-releases-tab', $html);
    }

    public function testGenreChipsAndRowsExposeGenres(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('data-releases-filter', $html);
        $this->assertStringContainsString('Todos (45)', $html);
        $this->assertStringContainsString('RPG (23)', $html);
        $this->assertStringContainsString('Acción (17)', $html);
        $this->assertStringContainsString('Aventura (5)', $html);
        $this->assertStringContainsString('data-genres="[&quot;RPG&quot;]"', $html);
    }

    public function testReleasedRowsLinkInternallyAndUpcomingStaysUnlinked(): void
    {
        $html = $this->renderPage();

        $this->assertSame(35, substr_count($html, '<a data-release-row'));
        $this->assertSame(10, substr_count($html, '<div data-release-row '));
        $this->assertStringContainsString('href="/alpha-quest"', $html);
        $this->assertStringContainsString('href="/games/by-igdb-id/1001"', $html);
        $this->assertStringNotContainsString('href="/games/by-igdb-id/2000"', $html);
        $this->assertStringNotContainsString('href="/games/by-igdb-id/3000"', $html);
    }

    public function testJsonLdAttributionAndMetaDescription(): void
    {
        $html = $this->renderPage();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"ItemList"', $html);
        $this->assertStringContainsString('"releaseDate":"2026-09-01"', $html);
        $this->assertStringContainsString('Datos de IGDB', $html);
        $this->assertMatchesRegularExpression(
            '~<meta name="description" content="Calendario de próximos lanzamientos[^"]*"~',
            $html
        );
    }
}
