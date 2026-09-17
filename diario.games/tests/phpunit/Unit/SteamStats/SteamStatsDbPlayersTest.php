<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbPlayersTest extends TestCase
{
    use TempDatabase;

    private SteamStatsDB $db;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
        $this->db = new SteamStatsDB($this->tempDatabasePath);
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDatabase();
    }

    public function testInsertPlayerCountUpsertsByAppidAndTimestamp(): void
    {
        $this->db->insertPlayerCount(100, 1700000000, 10);
        $this->db->insertPlayerCount(100, 1700000000, 25);

        $this->assertSame(25, $this->db->getCurrentPlayers(100));
    }

    public function testInsertPlayerCountIfMissingKeepsExistingValue(): void
    {
        $this->db->insertPlayerCountIfMissing(100, 1700000000, 10);
        $this->db->insertPlayerCountIfMissing(100, 1700000000, 99);

        $this->assertSame(10, $this->db->getCurrentPlayers(100));
    }

    public function testGetRecentPlayerCountsReturnsAscendingOrderRespectingLimit(): void
    {
        $this->db->insertPlayerCount(100, 1000, 1);
        $this->db->insertPlayerCount(100, 2000, 2);
        $this->db->insertPlayerCount(100, 3000, 3);

        $this->assertSame(
            [['timestamp' => 2000, 'players' => 2], ['timestamp' => 3000, 'players' => 3]],
            $this->db->getRecentPlayerCounts(100, 2)
        );
    }

    public function testGetRecentPlayerCountsFallsBackToPlayerHistory(): void
    {
        $this->db->upsertPlayerHistory(200, 1000, 5);
        $this->db->upsertPlayerHistory(200, 2000, 8);

        $this->assertSame(
            [['timestamp' => 1000, 'players' => 5], ['timestamp' => 2000, 'players' => 8]],
            $this->db->getRecentPlayerCounts(200)
        );
    }

    public function testDeletePlayerHistoryBefore(): void
    {
        $this->db->upsertPlayerHistory(200, 1000, 5);
        $this->db->upsertPlayerHistory(200, 2000, 8);
        $this->db->deletePlayerHistoryBefore(200, 1500);

        $this->assertSame(
            [['timestamp' => 2000, 'players' => 8]],
            $this->db->getRecentPlayerCounts(200)
        );
    }

    public function testGetPlayerCountsSinceBoundaryIsAscending(): void
    {
        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 20);

        $this->assertSame(
            [['timestamp' => 2000, 'p' => 20]],
            $this->db->getPlayerCounts(100, 1500)
        );
    }

    public function testGetDailyPeakCountsGroupsByUtcDay(): void
    {
        $day1 = 1699920000;
        $day2 = 1700006400;

        $this->db->insertPlayerCount(100, $day1 + 100, 10);
        $this->db->insertPlayerCount(100, $day1 + 200, 50);
        $this->db->insertPlayerCount(100, $day2 + 100, 30);

        $this->assertSame(
            [['timestamp' => $day1, 'p' => 50], ['timestamp' => $day2, 'p' => 30]],
            $this->db->getDailyPeakCounts(100, 0)
        );
    }

    public function testGetWeeklyPeakCountsGroupsByEpochWeek(): void
    {
        $week1 = 1700092800;
        $week2 = 1700697600;

        $this->db->insertPlayerCount(100, $week1 + 100, 10);
        $this->db->insertPlayerCount(100, $week1 + 200, 40);
        $this->db->insertPlayerCount(100, $week2 + 100, 70);

        $this->assertSame(
            [['timestamp' => $week1, 'p' => 40], ['timestamp' => $week2, 'p' => 70]],
            $this->db->getWeeklyPeakCounts(100, 0)
        );
    }

    public function testGetMonthlyPeakCountsGroupsByMonth(): void
    {
        $nov = 1698796800;
        $dec = 1701388800;

        $this->db->insertPlayerCount(100, $nov + 3600, 100);
        $this->db->insertPlayerCount(100, $nov + 7200, 250);
        $this->db->insertPlayerCount(100, $dec + 3600, 400);

        $this->assertSame(
            [
                ['month_key' => '2023-11', 'p' => 250, 'timestamp' => $nov + 3600],
                ['month_key' => '2023-12', 'p' => 400, 'timestamp' => $dec + 3600],
            ],
            $this->db->getMonthlyPeakCounts(100, 0)
        );
    }

    public function testGetWeeklyAveragesSplitsRecentAndPriorWeeks(): void
    {
        $now = time();

        $this->db->insertPlayerCount(100, $now - 86400, 100);
        $this->db->insertPlayerCount(100, $now - 2 * 86400, 200);
        $this->db->insertPlayerCount(100, $now - 8 * 86400, 50);
        $this->db->insertPlayerCount(100, $now - 9 * 86400, 60);

        $result = $this->db->getWeeklyAverages();

        $this->assertEqualsWithDelta(150.0, $result[100]['recent_avg'], 0.001);
        $this->assertEqualsWithDelta(55.0, $result[100]['prior_avg'], 0.001);
        $this->assertSame(2, $result[100]['prior_samples']);
    }

    public function testGetPeakPlayersRespectsSinceBoundary(): void
    {
        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 90);

        $this->assertSame(90, $this->db->getPeakPlayers(100, 1500));
        $this->assertSame(90, $this->db->getPeakPlayers(100, 0));
        $this->assertNull($this->db->getPeakPlayers(100, 5000));
    }

    public function testGetPeakTimestampReturnsHighestCount(): void
    {
        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 90);

        $this->assertSame(
            ['count' => 90, 'timestamp' => 2000],
            $this->db->getPeakTimestamp(100)
        );
    }

    public function testGetLatestTimestamp(): void
    {
        $this->assertNull($this->db->getLatestTimestamp(100));

        $this->db->insertPlayerCount(100, 1000, 10);
        $this->db->insertPlayerCount(100, 2000, 10);

        $this->assertSame(2000, $this->db->getLatestTimestamp(100));
    }

    public function testUpsertGamePeakIsMonotonic(): void
    {
        $this->db->upsertGamePeak(7, 500, 111);
        $this->assertSame(500, $this->db->getGamePeak(7));
        $this->assertSame(111, $this->db->getGamePeakTimestamp(7));

        $this->db->upsertGamePeak(7, 400, 222);
        $this->assertSame(500, $this->db->getGamePeak(7));
        $this->assertSame(111, $this->db->getGamePeakTimestamp(7));

        $this->db->upsertGamePeak(7, 600, 333);
        $this->assertSame(600, $this->db->getGamePeak(7));
        $this->assertSame(333, $this->db->getGamePeakTimestamp(7));
    }

    public function testUpsertGamePeakWithoutTimestampKeepsTimestampNull(): void
    {
        $this->db->upsertGamePeak(8, 100);

        $this->assertSame(100, $this->db->getGamePeak(8));
        $this->assertNull($this->db->getGamePeakTimestamp(8));
    }

    public function testGetAllPlayerDataMergesCountsPeaksAndDefaults(): void
    {
        $now = time();

        $this->db->upsertGame(100, 'played', 'Played');
        $this->db->upsertGame(200, 'unplayed', 'Unplayed');
        $this->db->insertPlayerCount(100, $now - 7200, 10);
        $this->db->insertPlayerCount(100, $now - 3600, 30);
        $this->db->upsertGamePeak(100, 999);

        $data = $this->db->getAllPlayerData();

        $this->assertSame(
            ['current_players' => 30, 'peak_24h' => 30, 'peak_all_time' => 999],
            $data[100]
        );
        $this->assertSame(
            ['current_players' => 0, 'peak_24h' => 0, 'peak_all_time' => 0],
            $data[200]
        );
    }
}
