<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kirby\Cms\App;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;

final class AffiliateBannerRenderTest extends TestCase
{
    private static App $kirby;
    private static string $tmp;

    public static function setUpBeforeClass(): void
    {
        $root = dirname(__DIR__, 3);

        self::$tmp = sys_get_temp_dir() . '/diario-banner-' . bin2hex(random_bytes(6));
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

        putenv('STEAM_STATS_DB_PATH=' . self::$tmp . '/steam_stats.db');

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
            ],
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        putenv('STEAM_STATS_DB_PATH');
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

    public function testBannerRendersForMatchingItemCount(): void
    {
        $html = snippet('affiliate-banner', ['grid' => true, 'itemCount' => 2], true);

        $this->assertStringContainsString('ig-aff-banner-test-program-md', $html);
        $this->assertStringContainsString("igr: 'testaff'", $html);
        $this->assertStringContainsString('loader.js', $html);
    }

    public function testBannerRendersNothingForNonMatchingItemCount(): void
    {
        $html = snippet('affiliate-banner', ['grid' => true, 'itemCount' => 3], true);

        $this->assertStringNotContainsString('ig-aff-banner-test-program', $html);
        $this->assertStringNotContainsString('loader.js', $html);
    }
}
