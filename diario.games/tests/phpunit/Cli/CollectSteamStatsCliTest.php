<?php

declare(strict_types=1);

namespace Tests\Cli;

use PHPUnit\Framework\TestCase;
use Tests\Support\CliRunner;
use Tests\Support\Files;
use Tests\Support\TempDatabase;

final class CollectSteamStatsCliTest extends TestCase
{
    use TempDatabase;

    private const CHARTS_LOCK = '/tmp/steamdb-charts-browser.lock';

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        @unlink(self::CHARTS_LOCK);
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    private function fixtureEnv(): array
    {
        $gamesDir = $this->tempDatabaseDir . '/games';
        mkdir($gamesDir . '/2024/03/alpha', 0775, true);
        file_put_contents(
            $gamesDir . '/2024/03/alpha/game.txt',
            "Title: Alpha Game\n\n----\n\nTemplate: game\n"
        );
        file_put_contents($gamesDir . '/2024/03/alpha/screenshot-0.jpg.txt', "Template: screenshot\n");

        return [
            'STEAM_STATS_DB_PATH' => $this->tempDatabasePath,
            'STEAM_STATS_CONTENT_DIR' => $gamesDir,
            'STEAM_STATS_SKIP_EXTRAS' => '1',
        ];
    }

    public function testCollectModeScansFixtureContentAndSkipsExtras(): void
    {
        $result = CliRunner::run(['collect'], $this->fixtureEnv());

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('Scanned: 1', $result['stdout']);
        $this->assertStringContainsString('Updated: 0', $result['stdout']);
        $this->assertStringNotContainsString('Caches warmed', $result['stdout']);
        $this->assertStringNotContainsString('All-time peaks', $result['stdout']);
        $this->assertStringNotContainsString('Imported ', $result['stdout']);
    }

    public function testChartsModeSkipsWhenLockIsFresh(): void
    {
        @unlink(self::CHARTS_LOCK);
        touch(self::CHARTS_LOCK);

        $result = CliRunner::run(['charts', '0'], $this->fixtureEnv());

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('already running', $result['stdout']);
        $this->assertFileExists(self::CHARTS_LOCK);
    }

    public function testHistoryBySlugRequiresSlug(): void
    {
        $result = CliRunner::run(['steamdb-history-by-slug'], $this->fixtureEnv());

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Usage: php collect-steam-stats.php steamdb-history-by-slug', $result['stdout']);
    }

    public function testHistoryBySlugUnknownGameExitsWithError(): void
    {
        $result = CliRunner::run(['steamdb-history-by-slug', 'unknown-game'], $this->fixtureEnv());

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Game not found for slug: unknown-game', $result['stdout']);
    }

    public function testSteamDbPeakWithoutAppidPrintsUsage(): void
    {
        $result = CliRunner::run(['steamdb-peak', '0'], $this->fixtureEnv());

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('Usage: php collect-steam-stats.php steamdb-peak <appid>', $result['stdout']);
    }
}
