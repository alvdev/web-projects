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
        $this->cacheTtl = $settings['cache_ttl'] ?? 7200;
        $this->historyTtl = $settings['history_ttl'] ?? 604800;
        $this->historyInterval = $settings['history_interval'] ?? 21600;
    }

    public function getMostPlayed(int $limit = 10): array
    {
        $games = $this->getCached('stats-most-played', $this->cacheTtl);

        if ($games === null) {
            $games = $this->fetchGameListFromStats();
            if (!empty($games)) {
                $this->setCache('stats-most-played', $games);
            }
        }

        if (empty($games)) {
            return $this->getMostPlayedFallback($limit);
        }

        $sliced = array_slice($games, 0, $limit);
        $appids = array_column($sliced, 'appid');

        $details = $this->fetchGameDetails($appids);

        $result = [];
        foreach ($sliced as $index => $entry) {
            $appid = $entry['appid'];
            $detail = $details[$appid] ?? [];

            $result[] = [
                'rank' => $index + 1,
                'appid' => $appid,
                'name' => $entry['name'] ?? $detail['name'] ?? '',
                'capsule_image' => $detail['capsule_image'] ?? '',
                'current_players' => (int)($entry['current_players'] ?? 0),
                'peak_players' => (int)($entry['peak_today'] ?? 0),
            ];
        }

        return $result;
    }

    public function getTrending(int $limit = 10): array
    {
        $ranks = $this->getCached('peak-ranks', $this->cacheTtl);

        if ($ranks === null) {
            $ranks = $this->fetchMostPlayedFromSteam();
            if (!empty($ranks)) {
                $this->setCache('peak-ranks', $ranks);
            }
        }

        if (empty($ranks)) {
            return [];
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

    private function getMostPlayedFallback(int $limit): array
    {
        $ranks = $this->fetchMostPlayedFromSteam();
        if (empty($ranks)) {
            return [];
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
            ];
        }

        usort($games, fn($a, $b) => $b['current_players'] <=> $a['current_players']);
        foreach ($games as $index => &$game) {
            $game['rank'] = $index + 1;
        }

        return $games;
    }

    private function fetchGameListFromStats(): array
    {
        $url = 'https://store.steampowered.com/stats/stats';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return [];
        }

        $games = [];
        $pattern = '/<tr class="player_count_row[^>]*">\s*<td[^>]*>\s*<span[^>]*>([\d,]+)<\/span>\s*<\/td>\s*<td[^>]*>\s*<span[^>]*>([\d,]+)<\/span>\s*<\/td>\s*<td[^>]*>[^<]*<\/td>\s*<td[^>]*>\s*<a[^>]*href="[^"]*app\/(\d+)\/[^"]*"[^>]*>([^<]+)<\/a>/s';

        if (preg_match_all($pattern, $response, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $games[] = [
                    'appid' => (int)$match[3],
                    'name' => html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'current_players' => (int)str_replace(',', '', $match[1]),
                    'peak_today' => (int)str_replace(',', '', $match[2]),
                ];
            }
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

        $results = [];
        $uncachedAppids = [];

        // Check cache for each app
        foreach ($appids as $appid) {
            $cached = $this->getCached('game-details.' . $appid, 86400); // Cache for 24 hours
            if ($cached !== null) {
                $results[$appid] = $cached;
            } else {
                $uncachedAppids[] = $appid;
            }
        }

        // Fetch uncached apps
        foreach ($uncachedAppids as $appid) {
            $url = 'https://store.steampowered.com/api/appdetails?appids=' . $appid;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                continue;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || !isset($data[$appid])) {
                continue;
            }

            $entry = $data[$appid];
            if (!($entry['success'] ?? false)) {
                continue;
            }

            $info = $entry['data'] ?? [];
            $gameData = [
                'name' => $info['name'] ?? '',
                'header_image' => $info['header_image'] ?? '',
                'capsule_image' => $info['capsule_image'] ?? '',
            ];

            $results[$appid] = $gameData;
            $this->setCache('game-details.' . $appid, $gameData);
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

    public function updatePlayerHistory(): void
    {
        $tracked = [];

        // Track top 20 most played
        $mostPlayed = $this->getMostPlayed(20);
        foreach ($mostPlayed as $game) {
            $tracked[$game['appid']] = $game['current_players'];
        }

        // Also track top 20 trending
        $trending = $this->getTrending(20);
        foreach ($trending as $game) {
            $tracked[$game['appid']] = $game['current_players'];
        }

        foreach ($tracked as $appid => $currentPlayers) {
            $history = $this->getPlayerHistory($appid);
            $history[] = [
                'timestamp' => time(),
                'players' => $currentPlayers,
            ];

            if (count($history) > 28) {
                $history = array_slice($history, -28);
            }

            $this->setCache('history.' . $appid, ['points' => $history]);
        }
    }

    private function getCached(string $key, int $ttl)
    {
        $cache = kirby()->cache('alv/steam-stats.cache');
        $entry = $cache->get($key);

        // Kirby cache returns the value directly, not wrapped
        if ($entry === null) {
            return null;
        }

        // Check if it's our wrapped format
        if (is_array($entry) && isset($entry['value'], $entry['timestamp'])) {
            if ((time() - $entry['timestamp']) > $ttl) {
                return null;
            }
            return $entry['value'];
        }

        // If it's not wrapped, return as-is (for backwards compatibility)
        return $entry;
    }

    private function setCache(string $key, $value): void
    {
        $cache = kirby()->cache('alv/steam-stats.cache');
        $cache->set($key, ['value' => $value, 'timestamp' => time()]);
    }
}
