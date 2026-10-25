<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kirby\Cms\App;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;

final class HomePageAffiliateOrderTest extends TestCase
{
    private static App $kirby;
    private static string $tmp;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 3);

        self::$tmp = sys_get_temp_dir() . '/diario-home-aff-' . bin2hex(random_bytes(6));
        foreach (['media', 'sessions', 'accounts', 'cache', 'content'] as $dir) {
            mkdir(self::$tmp . '/' . $dir, 0775, true);
        }

        file_put_contents(self::$tmp . '/content/site.txt', <<<'TXT'
Title: Test Site

----

Alv-aff-banner-enabled: true

----

Alv-aff-programs:

- 
  name: Test Program
  enabled: 'true'
  type: instant-gaming
  affiliate_id: testaff
  banner_label: Ofertas
  banner_sponsor: Patrocinado
  frequency: '1|2|4'
TXT);

        mkdir(self::$tmp . '/content/home', 0775, true);
        file_put_contents(self::$tmp . '/content/home/home.txt', "Title: Inicio\n\n----\n\nTemplate: home\n");

        $gameDir = self::$tmp . '/content/games/2024/03/alpha-quest';
        mkdir($gameDir, 0775, true);
        file_put_contents(
            $gameDir . '/game.txt',
            "Title: Alpha Quest\n\n----\n\nTemplate: game\n\n----\n\nIgdbId: 11\n\n----\n\nGenres: Acción\n"
        );

        $game = fn (int $id, string $name, string $date, int $hypes): array => [
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
            'genres' => ['RPG'],
            'local_url' => null,
        ];

        $fixture = self::$tmp . '/releases.json';
        file_put_contents($fixture, json_encode([
            'recentlyReleased' => [$game(1000, 'Recent One', '2026-09-20', 100)],
            'upcoming' => [$game(2000, 'Upcoming One', '2026-11-01', 50)],
            'anticipated' => [$game(3000, 'Anticipated One', '2027', 500)],
        ]));

        putenv('STEAM_STATS_DB_PATH=' . self::$tmp . '/steam_stats.db');
        putenv('TWITCH_STATS_DB_PATH=' . self::$tmp . '/twitch_stats.db');

        self::$kirby = new App([
            'roots' => [
                'index' => $root,
                'content' => self::$tmp . '/content',
                'cache' => self::$tmp . '/cache',
                'media' => self::$tmp . '/media',
                'sessions' => self::$tmp . '/sessions',
                'accounts' => self::$tmp . '/accounts',
            ],
            'options' => [
                'debug' => false,
                'igdb' => ['client_id' => '', 'client_secret' => ''],
                'alv.releases.fixture-file' => $fixture,
            ],
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        putenv('STEAM_STATS_DB_PATH');
        putenv('TWITCH_STATS_DB_PATH');
        Files::removeDir(self::$tmp);
    }

    protected function setUp(): void
    {
        unset(
            $GLOBALS['alv_aff_banners_init'],
            $GLOBALS['alv_aff_banners_shown'],
            $GLOBALS['alv_aff_scripts_emitted']
        );
    }

    public function testBannerRendersBeforeReleasesModule(): void
    {
        $html = self::$kirby->page('home')->render();

        $bannerPos = strpos($html, 'alv-aff-banner-wrapper');
        $modulePos = strpos($html, 'data-home-releases');

        $this->assertNotFalse($bannerPos, 'Affiliate banner missing from homepage');
        $this->assertNotFalse($modulePos, 'Releases module missing from homepage');
        $this->assertLessThan($modulePos, $bannerPos, 'Affiliate banner should render before the releases module');
    }
}
