<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use Kirby\Cms\App;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;
use Tests\Support\TestableSteamStats;

final class SteamStatsTrendingTest extends TestCase
{
    use TempDatabase;

    private static App $kirby;
    private static string $tmp;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();

        self::$tmp = sys_get_temp_dir() . '/diario-trending-' . bin2hex(random_bytes(6));
        foreach (['cache', 'media', 'sessions', 'accounts'] as $dir) {
            mkdir(self::$tmp . '/' . $dir, 0775, true);
        }

        self::$kirby = new App([
            'roots' => [
                'index' => dirname(__DIR__, 4),
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
        Files::removeDir(self::$tmp);
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    private function stats(): TestableSteamStats
    {
        return new TestableSteamStats(['api_key' => '', 'cache_ttl' => 3600]);
    }

    private function seedTrendingData(): void
    {
        $db = new SteamStatsDB($this->tempDatabasePath);
        $now = time();

        $db->upsertGame(100, 'fast', 'Fast Game');
        $db->upsertGame(200, 'slow', 'Slow Game');
        $db->upsertGame(300, 'new', 'New Game');

        $db->insertPlayerCount(100, $now - 8 * 86400, 100);
        $db->insertPlayerCount(100, $now - 86400, 150);
        $db->insertPlayerCount(200, $now - 8 * 86400, 300);
        $db->insertPlayerCount(200, $now - 86400, 300);
        $db->insertPlayerCount(300, $now - 86400, 1000);
    }

    public function testGetTrendingComputesGrowthAndSortsDescending(): void
    {
        $this->seedTrendingData();

        $stats = $this->stats();
        $stats->detailsById = [
            100 => ['name' => 'Fast Game', 'capsule_image' => 'https://cdn/100.jpg'],
            200 => ['name' => 'Slow Game', 'capsule_image' => 'https://cdn/200.jpg'],
            300 => ['name' => 'New Game', 'capsule_image' => 'https://cdn/300.jpg'],
        ];
        $stats->histories = [100 => [['timestamp' => 1, 'players' => 2]]];

        $result = $stats->getTrending(10, 0);

        $this->assertSame([300, 100, 200], array_column($result, 'appid'));

        $this->assertSame(1, $result[0]['rank']);
        $this->assertTrue($result[0]['is_new']);
        $this->assertEqualsWithDelta(1000.0, $result[0]['growth_pct'], 0.001);

        $this->assertFalse($result[1]['is_new']);
        $this->assertEqualsWithDelta(50.0, $result[1]['growth_pct'], 0.001);
        $this->assertSame('Fast Game', $result[1]['name']);
        $this->assertSame([['timestamp' => 1, 'players' => 2]], $result[1]['history']);

        $this->assertEqualsWithDelta(0.0, $result[2]['growth_pct'], 0.001);
    }

    public function testGetTrendingServesCachedResultsWithResetRanks(): void
    {
        $stats = $this->stats();
        $stats->cacheStore['trending-growth'] = [
            ['appid' => 5, 'name' => 'Cached A', 'growth_pct' => 10],
            ['appid' => 6, 'name' => 'Cached B', 'growth_pct' => 5],
        ];

        $result = $stats->getTrending(1, 0);

        $this->assertCount(1, $result);
        $this->assertSame(5, $result[0]['appid']);
        $this->assertSame(1, $result[0]['rank']);
    }

    public function testGetMostPlayedUsesScrapedListAndDetails(): void
    {
        $stats = $this->stats();
        $stats->cacheStore['stats-most-played'] = [
            ['appid' => 570, 'name' => 'Scraped Name', 'current_players' => 10, 'peak_today' => 20],
            ['appid' => 730, 'name' => 'Second', 'current_players' => 5, 'peak_today' => 6],
        ];
        $stats->detailsById = [
            570 => ['name' => 'Dota 2', 'capsule_image' => 'https://cdn/570.jpg'],
        ];

        $result = $stats->getMostPlayed(1);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['rank']);
        $this->assertSame(570, $result[0]['appid']);
        $this->assertSame('Scraped Name', $result[0]['name']);
        $this->assertSame('https://cdn/570.jpg', $result[0]['capsule_image']);
        $this->assertSame(10, $result[0]['current_players']);
        $this->assertSame(20, $result[0]['peak_players']);
    }
}
