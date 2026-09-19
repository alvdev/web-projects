<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsCollector;
use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSteamStatsCollector;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsCollectorSteamDbTest extends TestCase
{
    use TempDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    public function testMapSteamDbHistoryHandlesOldFormat(): void
    {
        $mapped = SteamStatsCollector::mapSteamDbHistory([
            [1700000000000, 10],
            [1700003600000, 0],
            [1700007200000, 25],
        ]);

        $this->assertSame([
            ['timestamp' => 1700000000, 'count' => 10],
            ['timestamp' => 1700007200, 'count' => 25],
        ], $mapped['points']);
        $this->assertSame(25, $mapped['peak']);
        $this->assertSame(1700007200, $mapped['peak_timestamp']);
    }

    public function testMapSteamDbHistoryPrefersHigherDomPeak(): void
    {
        $mapped = SteamStatsCollector::mapSteamDbHistory([
            'points' => [[1700000000000, 10]],
            'peak_all_time' => 999,
        ]);

        $this->assertSame(999, $mapped['peak']);
        $this->assertSame(0, $mapped['peak_timestamp']);
    }

    public function testMapSteamDbHistoryKeepsPointPeakWhenDomPeakLower(): void
    {
        $mapped = SteamStatsCollector::mapSteamDbHistory([
            'points' => [[1700000000000, 50]],
            'peak_all_time' => 20,
        ]);

        $this->assertSame(50, $mapped['peak']);
        $this->assertSame(1700000000, $mapped['peak_timestamp']);
    }

    public function testCollectSteamDbPeakParsesNodeOutput(): void
    {
        $collector = new FakeSteamStatsCollector('test-key');
        $collector->nodeResponses = [
            'fetch-steamdb-peak.mjs' => '{"peak":123,"timestamp":456}',
        ];

        $this->assertSame(
            ['peak' => 123, 'timestamp' => 456],
            $collector->collectSteamDBPeak(570)
        );
        $this->assertSame(['fetch-steamdb-peak.mjs'], $collector->nodeCalls);
    }

    public function testCollectSteamDbPeakReturnsNullForInvalidOutput(): void
    {
        $collector = new FakeSteamStatsCollector('test-key');
        $collector->nodeResponses = ['fetch-steamdb-peak.mjs' => 'not-json'];

        $this->assertNull($collector->collectSteamDBPeak(570));
    }

    public function testCollectSteamDbHistoryInsertsPointsAndCleansLock(): void
    {
        $appid = 999000001;
        $lock = sys_get_temp_dir() . '/steamdb-backfill-' . $appid . '.lock';
        @unlink($lock);

        $collector = new FakeSteamStatsCollector('test-key');
        $collector->nodeResponses = [
            'scrape-steamdb-history.mjs' => '{"points":[[1700000000000,10],[1700003600000,25]],"peak_all_time":0}',
        ];

        $result = $collector->collectSteamDBHistory($appid);

        $this->assertSame(['points' => 2, 'peak' => 25], $result);
        $this->assertFileDoesNotExist($lock);

        $db = new SteamStatsDB($this->tempDatabasePath);
        $this->assertSame(25, $db->getGamePeak($appid));
    }

    public function testCollectSteamDbHistorySkipsWhenLockExists(): void
    {
        $appid = 999000002;
        $lock = sys_get_temp_dir() . '/steamdb-backfill-' . $appid . '.lock';
        @unlink($lock);
        touch($lock);

        try {
            $collector = new FakeSteamStatsCollector('test-key');
            $collector->nodeResponses = [
                'scrape-steamdb-history.mjs' => '{"points":[[1700000000000,10]],"peak_all_time":0}',
            ];

            $this->assertNull($collector->collectSteamDBHistory($appid));
            $this->assertSame([], $collector->nodeCalls);
        } finally {
            @unlink($lock);
        }
    }
}
