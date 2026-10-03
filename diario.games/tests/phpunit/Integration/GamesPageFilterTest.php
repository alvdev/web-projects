<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class GamesPageFilterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();

        mkdir(RouteTestApp::content('games'), 0775, true);
        file_put_contents(
            RouteTestApp::content('games/games.txt'),
            "Title: Todos los juegos\n\n----\n\nTemplate: games\n\n----\n\nMetaDescription: Catálogo completo de videojuegos de Diario.Games.\n"
        );

        $games = [
            '2024/03/alpha-quest' => 'RPG, Aventura',
            '2024/04/beta-blaster' => 'Acción',
        ];

        foreach ($games as $path => $genres) {
            $dir = RouteTestApp::content('games/' . $path);
            mkdir($dir, 0775, true);
            file_put_contents(
                $dir . '/game.txt',
                "Title: " . ucfirst(basename($path)) . "\n\n----\n\nTemplate: game\n\n----\n\nGenres: {$genres}\n"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testChipsRenderWithCounts(): void
    {
        $html = RouteTestApp::app()->page('games')->render();

        $this->assertStringContainsString('data-genre-filter', $html);
        $this->assertStringContainsString('Todos (2)', $html);
        $this->assertStringContainsString('Acción (1)', $html);
        $this->assertStringContainsString('Aventura (1)', $html);
        $this->assertStringContainsString('RPG (1)', $html);
    }

    public function testCardsExposeGenresForFiltering(): void
    {
        $html = RouteTestApp::app()->page('games')->render();

        $this->assertStringContainsString('data-genre-item', $html);
        $this->assertStringContainsString('data-genres="[&quot;RPG&quot;,&quot;Aventura&quot;]"', $html);
        $this->assertStringContainsString('data-genres="[&quot;Acción&quot;]"', $html);
    }

    public function testSpanishHeading(): void
    {
        $html = RouteTestApp::app()->page('games')->render();

        $this->assertStringContainsString('Todos los juegos', $html);
        $this->assertStringNotContainsString('All Games', $html);
    }

    public function testMetaDescriptionIsRendered(): void
    {
        $html = RouteTestApp::app()->page('games')->render();

        $this->assertMatchesRegularExpression(
            '~<meta name="description" content="Catálogo completo[^"]*"~',
            $html
        );
    }
}
