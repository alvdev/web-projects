<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class HomePageFallbackTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();

        mkdir(RouteTestApp::content('home'), 0775, true);
        file_put_contents(
            RouteTestApp::content('home/home.txt'),
            "Title: Inicio\n\n----\n\nTemplate: home\n"
        );

        $gameDir = RouteTestApp::content('games/2024/03/alpha-quest');
        mkdir($gameDir, 0775, true);
        file_put_contents(
            $gameDir . '/game.txt',
            "Title: Alpha Quest\n\n----\n\nTemplate: game\n\n----\n\nGenres: Acción\n"
        );
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testHomeFallsBackToTopGameWhenNoPostsExist(): void
    {
        $html = RouteTestApp::app()->page('home')->render();

        $this->assertStringContainsString('Alpha Quest', $html);
        $this->assertStringContainsString('site-fav', $html);
    }

    public function testTickerAndSpotlightAreAbsentWhenNoPostsExist(): void
    {
        $html = RouteTestApp::app()->page('home')->render();

        $this->assertStringNotContainsString('data-home-ticker', $html);
        $this->assertStringNotContainsString('data-spotlight', $html);
    }

    public function testReservedBlankStillRendersWithoutPosts(): void
    {
        $html = RouteTestApp::app()->page('home')->render();

        $this->assertStringContainsString('data-home-reserved', $html);
    }
}
