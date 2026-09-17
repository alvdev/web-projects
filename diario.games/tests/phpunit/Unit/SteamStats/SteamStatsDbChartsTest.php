<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;
use Tests\Support\TempDatabase;

final class SteamStatsDbChartsTest extends TestCase
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

    public function testReplaceChartEntriesChunksAndSkipsInvalidRows(): void
    {
        $rows = [
            ['rank' => 1, 'appid' => 10, 'name' => 'A', 'current' => 5, 'peak_24h' => 6, 'peak_all_time' => 7],
            ['rank' => 101, 'appid' => 20, 'name' => 'B', 'current' => 8, 'peak_24h' => 9, 'peak_all_time' => 10],
            ['rank' => 0, 'appid' => 30, 'name' => 'InvalidRank'],
            ['rank' => 3, 'appid' => 0, 'name' => 'InvalidAppid'],
        ];

        $inserted = $this->db->replaceChartEntries($rows, 1699999999);

        $this->assertSame(2, $inserted);
        $this->assertSame(2, $this->db->countChartEntries());
        $this->assertSame([10], array_column($this->db->getChartEntriesByChunk(0), 'appid'));
        $this->assertSame([20], array_column($this->db->getChartEntriesByChunk(1), 'appid'));
    }

    public function testReplaceChartEntriesReplacesPreviousSnapshot(): void
    {
        $this->db->replaceChartEntries(
            [['rank' => 1, 'appid' => 10, 'name' => 'A']],
            1000
        );
        $this->db->replaceChartEntries(
            [['rank' => 1, 'appid' => 11, 'name' => 'B']],
            2000
        );

        $this->assertSame(1, $this->db->countChartEntries());
        $this->assertSame([11], array_column($this->db->getChartEntriesByChunk(0), 'appid'));
    }

    public function testMarkChartChunksFreshCoversSnapshot(): void
    {
        $this->db->markChartChunksFresh(250, 1699999999);

        $chunk0 = $this->db->getChartChunk(0);
        $chunk1 = $this->db->getChartChunk(1);
        $chunk2 = $this->db->getChartChunk(2);

        $this->assertSame('fresh', $chunk0['status']);
        $this->assertSame(100, (int)$chunk0['entry_count']);
        $this->assertSame(100, (int)$chunk1['entry_count']);
        $this->assertSame(50, (int)$chunk2['entry_count']);
        $this->assertSame(1699999999, (int)$chunk0['fetched_at']);
        $this->assertNull($this->db->getChartChunk(3));
    }

    public function testMarkChartChunksFreshWithZeroEntriesCreatesNoChunks(): void
    {
        $this->db->markChartChunksFresh(0, 1699999999);

        $this->assertNull($this->db->getChartChunk(0));
    }

    public function testMarkChartChunkErrorPreservesFetchedAt(): void
    {
        $this->db->markChartChunksFresh(100, 1699999999);
        $this->db->markChartChunkError(0, 'scrape failed');

        $chunk = $this->db->getChartChunk(0);

        $this->assertSame('error', $chunk['status']);
        $this->assertSame('scrape failed', $chunk['last_error']);
        $this->assertSame(1699999999, (int)$chunk['fetched_at']);
    }

    public function testSearchChartEntriesOrdersByRankAndClampsLimit(): void
    {
        $rows = [];
        for ($i = 1; $i <= 20; $i++) {
            $rows[] = ['rank' => $i, 'appid' => 1000 + $i, 'name' => 'Test Game ' . $i];
        }
        $this->db->replaceChartEntries($rows, 1000);

        $hits = $this->db->searchChartEntries('Test Game', 50);

        $this->assertCount(15, $hits);
        $this->assertSame(1, (int)$hits[0]['rank']);
        $this->assertSame(15, (int)$hits[14]['rank']);
    }
}
