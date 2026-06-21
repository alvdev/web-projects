<?php

namespace Alv\SteamStats;

class SteamStats
{
    private string $apiKey;
    private int $cacheTtl;
    private int $historyTtl;
    private int $historyInterval;

    public function __construct(array $settings)
    {
        $this->apiKey = $settings['api_key'] ?? '';
        $this->cacheTtl = $settings['cache_ttl'] ?? 3600;
        $this->historyTtl = $settings['history_ttl'] ?? 604800;
        $this->historyInterval = $settings['history_interval'] ?? 21600;
    }

    public function getMostPlayed(int $limit = 10): array
    {
        return [];
    }

    public function getTrending(int $limit = 10): array
    {
        return [];
    }
}
