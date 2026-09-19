<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kirby\Cms\App;
use Kirby\Cms\Site;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;

final class PageSmokeTest extends TestCase
{
    private static App $kirby;
    private static string $tmp;
    private static mixed $originalPriceComparison = false;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 3);

        self::$tmp = sys_get_temp_dir() . '/diario-smoke-' . bin2hex(random_bytes(6));
        foreach (['cache', 'media', 'sessions', 'accounts'] as $dir) {
            mkdir(self::$tmp . '/' . $dir, 0775, true);
        }

        putenv('STEAM_STATS_DB_PATH=' . self::$tmp . '/steam_stats.db');

        self::$kirby = new App([
            'roots' => [
                'index' => $root,
                'content' => $root . '/content',
                'cache' => self::$tmp . '/cache',
                'media' => self::$tmp . '/media',
                'sessions' => self::$tmp . '/sessions',
                'accounts' => self::$tmp . '/accounts',
            ],
            'options' => [
                'debug' => false,
            ],
        ]);

        self::$originalPriceComparison = Site::$methods['priceComparison'] ?? false;
        Site::$methods['priceComparison'] = fn (...$args): array => [];
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$originalPriceComparison === false) {
            unset(Site::$methods['priceComparison']);
        } else {
            Site::$methods['priceComparison'] = self::$originalPriceComparison;
        }

        putenv('STEAM_STATS_DB_PATH');
        Files::removeDir(self::$tmp);
    }

    public function testSearchPageRenders(): void
    {
        $html = self::$kirby->page('search')->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Diario.Games', $html);
    }

    public function testGenrePageRenders(): void
    {
        $games = self::$kirby->site()->find('games')->children()->children()->children()
            ->filterBy('intendedTemplate', 'game');
        $genre = $games->first()->genreList()[0] ?? 'Acción';

        $html = self::$kirby->page('genre')->render(['genreSlug' => $genre]);

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString($genre, $html);
    }

    public function testGamePageRenders(): void
    {
        $game = self::$kirby->site()->index()
            ->filterBy('intendedTemplate', 'game')->first();

        $this->assertNotNull($game);

        $html = $game->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString($game->title()->value(), $html);
    }

    public function testArticlePagesRender(): void
    {
        $articles = self::$kirby->site()->index()->filter(function ($page) {
            return in_array($page->intendedTemplate()->name(), ['post', 'guide', 'news'], true);
        });

        if ($articles->count() === 0) {
            $this->markTestSkipped('No article pages in content');
        }

        $html = $articles->first()->render();

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
    }
}
