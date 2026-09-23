<?php

declare(strict_types=1);

namespace Tests\Cli;

use Alv\TwitchStats\TwitchStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\CliRunner;
use Tests\Support\PluginClasses;
use Tests\Support\TempTwitchDatabase;

final class CollectTwitchStatsCliTest extends TestCase
{
    use TempTwitchDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempTwitchDatabase();
        putenv('TWITCH_STATS_WARM_KEY');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempTwitchDatabase();
    }

    private function env(): array
    {
        return [
            'TWITCH_STATS_DB_PATH' => $this->tempTwitchPath,
        ];
    }

    public function testPruneReportsDeletedCount(): void
    {
        $db = new TwitchStatsDB($this->tempTwitchPath);
        $db->insertSnapshot('game', '1', time() - 200000000, 10, 1);
        $db->insertSnapshot('game', '1', time() - 3600, 20, 1);

        $result = CliRunner::runScript('collect-twitch-stats.php', ['prune'], $this->env());

        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('Pruned 1 snapshots.', $result['stdout']);
        $this->assertSame(1, $db->countSnapshots());
    }

    public function testUnknownModePrintsUsage(): void
    {
        $result = CliRunner::runScript('collect-twitch-stats.php', ['bogus'], $this->env());

        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Usage: php collect-twitch-stats.php', $result['stdout']);
    }
}
