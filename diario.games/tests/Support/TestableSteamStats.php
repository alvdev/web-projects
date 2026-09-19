<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\SteamStats\SteamStats;

class TestableSteamStats extends SteamStats
{
    public array $cacheStore = [];
    public array $detailsById = [];
    public array $histories = [];

    protected function getCached(string $key, int $ttl)
    {
        return $this->cacheStore[$key] ?? null;
    }

    protected function setCache(string $key, $value): void
    {
        $this->cacheStore[$key] = $value;
    }

    protected function fetchGameDetails(array $appids): array
    {
        $result = [];
        foreach ($appids as $appid) {
            if (isset($this->detailsById[$appid])) {
                $result[$appid] = $this->detailsById[$appid];
            }
        }
        return $result;
    }

    protected function getPlayerHistory(int $appid): array
    {
        return $this->histories[$appid] ?? [];
    }
}
