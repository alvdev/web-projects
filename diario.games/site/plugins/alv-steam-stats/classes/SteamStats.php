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

    private function fetchMostPlayedFromSteam(): array
    {
        $url = 'https://api.steampowered.com/ISteamChartsService/GetMostPlayedGames/v1/';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return [];
        }

        $data = json_decode($response, true);
        return $data['response']['ranks'] ?? [];
    }

    private function fetchCurrentPlayers(int $appid): int
    {
        if (empty($this->apiKey)) {
            return 0;
        }

        $url = 'https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/?' . http_build_query([
            'appid' => $appid,
            'key' => $this->apiKey,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return 0;
        }

        $data = json_decode($response, true);
        return $data['response']['player_count'] ?? 0;
    }

    private function fetchGameDetails(array $appids): array
    {
        if (empty($appids)) {
            return [];
        }

        $url = 'https://store.steampowered.com/api/appdetails?' . http_build_query([
            'appids' => implode(',', $appids),
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return [];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return [];
        }

        $results = [];
        foreach ($data as $appid => $entry) {
            if (!($entry['success'] ?? false)) {
                continue;
            }
            $info = $entry['data'] ?? [];
            $results[$appid] = [
                'name' => $info['name'] ?? '',
                'header_image' => $info['header_image'] ?? '',
                'capsule_image' => $info['capsule_image'] ?? '',
            ];
        }

        return $results;
    }

    private function getCached(string $key, int $ttl)
    {
        $cache = kirby()->cache('alv.steam-stats');
        $entry = $cache->get($key);

        if (!is_array($entry) || !isset($entry['value'], $entry['timestamp'])) {
            return null;
        }

        if ((time() - $entry['timestamp']) > $ttl) {
            return null;
        }

        return $entry['value'];
    }

    private function setCache(string $key, $value): void
    {
        $cache = kirby()->cache('alv.steam-stats');
        $cache->set($key, ['value' => $value, 'timestamp' => time()]);
    }
}
