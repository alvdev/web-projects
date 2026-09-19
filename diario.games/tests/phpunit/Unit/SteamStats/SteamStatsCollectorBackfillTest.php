<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsCollector;
use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSteamStatsCollector;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsCollectorBackfillTest extends TestCase
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

    public function testMapSteamchartsPointsFiltersAndConvertsMilliseconds(): void
    {
        $mapped = SteamStatsCollector::mapSteamchartsPoints([
            [1700000000000, 10],
            [1700003600000, '20'],
            [1700007200000],
            'invalid',
        ]);

        $this->assertSame([
            ['timestamp' => 1700000000, 'count' => 10],
            ['timestamp' => 1700003600, 'count' => 20],
        ], $mapped);
    }

    public function testBackfillInsertsPointsAndRecordsErrors(): void
    {
        $db = new SteamStatsDB($this->tempDatabasePath);
        $db->upsertGame(100, 'one', 'One');
        $db->upsertGame(200, 'two', 'Two');

        $collector = new FakeSteamStatsCollector('test-key');
        $collector->chartDataResponses = [
            100 => json_encode([[1700000000000, 10], [1700003600000, 20]]),
            200 => null,
        ];

        $stats = $collector->backfill();

        $this->assertSame(1, $stats['fetched']);
        $this->assertSame(2, $stats['inserted']);
        $this->assertSame([200], $stats['errors']);

        $this->assertSame(
            [['timestamp' => 1700000000, 'p' => 10], ['timestamp' => 1700003600, 'p' => 20]],
            $db->getPlayerCounts(100, 0)
        );
    }
}
