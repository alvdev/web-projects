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
        $ranks = $this->getCached('most-played', $this->cacheTtl);

        if ($ranks === null) {
            $ranks = $this->fetchMostPlayedFromSteam();
            $this->setCache('most-played', $ranks);
        }

        $sliced = array_slice($ranks, 0, $limit);
        $appids = array_column($sliced, 'appid');

        $details = $this->fetchGameDetails($appids);

        $games = [];
        foreach ($sliced as $entry) {
            $appid = $entry['appid'];
            $detail = $details[$appid] ?? [];

            $games[] = [
                'rank' => $entry['rank'],
                'appid' => $appid,
                'name' => $detail['name'] ?? '',
                'capsule_image' => $detail['capsule_image'] ?? '',
                'current_players' => $this->getCurrentPlayers($appid),
                'peak_players' => $entry['peak_in_game'] ?? 0,
                'last_week_rank' => $entry['last_week_rank'] ?? -1,
            ];
        }

        return $games;
    }

    public function getTrending(int $limit = 10): array
    {
        $ranks = $this->getCached('most-played', $this->cacheTtl);

        if ($ranks === null) {
            $ranks = $this->fetchMostPlayedFromSteam();
            $this->setCache('most-played', $ranks);
        }

        $withMomentum = [];
        foreach ($ranks as $entry) {
            $lastWeek = $entry['last_week_rank'] ?? -1;
            if ($lastWeek <= 0) {
                continue;
            }
            $momentum = $lastWeek - $entry['rank'];
            $withMomentum[] = array_merge($entry, ['momentum' => $momentum]);
        }

        usort($withMomentum, fn($a, $b) => $b['momentum'] <=> $a['momentum']);

        $sliced = array_slice($withMomentum, 0, $limit);
        $appids = array_column($sliced, 'appid');

        $details = $this->fetchGameDetails($appids);

        $games = [];
        foreach ($sliced as $entry) {
            $appid = $entry['appid'];
            $detail = $details[$appid] ?? [];

            $games[] = [
                'rank' => $entry['rank'],
                'appid' => $appid,
                'name' => $detail['name'] ?? '',
                'capsule_image' => $detail['capsule_image'] ?? '',
                'current_players' => $this->getCurrentPlayers($appid),
                'momentum' => $entry['momentum'],
                'history' => $this->getPlayerHistory($appid),
            ];
        }

        return $games;
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

    private function getCurrentPlayers(int $appid): int
    {
        $cached = $this->getCached('current-players.' . $appid, 900);

        if ($cached !== null) {
            return $cached['count'] ?? 0;
        }

        $count = $this->fetchCurrentPlayers($appid);
        $this->setCache('current-players.' . $appid, ['count' => $count]);

        return $count;
    }

    private function getPlayerHistory(int $appid): array
    {
        $cached = $this->getCached('history.' . $appid, $this->historyTtl);

        if ($cached !== null) {
            return $cached['points'] ?? [];
        }

        return [];
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
