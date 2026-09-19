<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\SteamStats\SteamStatsCollector;

class FakeSteamStatsCollector extends SteamStatsCollector
{
    public array $playerCounts = [];
    public array $chartDataResponses = [];
    public array $nodeResponses = [];
    public array $nodeCalls = [];

    protected function fetchCurrentPlayers(int $appid): ?int
    {
        return $this->playerCounts[$appid] ?? null;
    }

    protected function fetchSteamchartsChartData(int $appid): ?string
    {
        return $this->chartDataResponses[$appid] ?? null;
    }

    protected function runNodeScript(string $scriptPath, array $args): ?string
    {
        $this->nodeCalls[] = basename($scriptPath);
        return $this->nodeResponses[basename($scriptPath)] ?? null;
    }
}
