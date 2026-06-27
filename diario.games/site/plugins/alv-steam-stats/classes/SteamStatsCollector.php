<?php

namespace Alv\SteamStats;

class SteamStatsCollector
{
    private string $apiKey;
    private SteamStatsDB $db;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->db = new SteamStatsDB();
    }

    public function collect(): array
    {
        $gamesDir = dirname(__DIR__, 4) . '/content/games';
        $stats = ['scanned' => 0, 'updated' => 0, 'errors' => []];

        $dirs = new \DirectoryIterator($gamesDir);
        foreach ($dirs as $dir) {
            if (!$dir->isDir() || $dir->isDot()) continue;

            $slug = $dir->getFilename();
            $gameFile = $dir->getPathname() . '/game.txt';
            if (!file_exists($gameFile)) continue;

            $stats['scanned']++;
            $content = file_get_contents($gameFile);

            // Extract Steam app ID from Websites field
            if (preg_match('/store\.steampowered\.com\/app\/(\d+)/i', $content, $m)) {
                $appid = (int) $m[1];

                // Extract title
                preg_match('/^Title:\s*(.+)/m', $content, $tm);
                $name = trim($tm[1] ?? $slug);

                // Extract IGDB ID
                $igdbId = null;
                if (preg_match('/^IgdbId:\s*(\d+)/m', $content, $im)) {
                    $igdbId = (int) $im[1];
                }

                $this->db->upsertGame($appid, $slug, $name, $igdbId);
            }
        }

        // Fetch current players for all known appids
        $appids = $this->db->getAllAppids();
        $now = time();
        $hourSlot = $now - ($now % 3600); // round to current hour

        foreach ($appids as $appid) {
            $count = $this->fetchCurrentPlayers($appid);
            if ($count !== null) {
                $this->db->insertPlayerCount($appid, $hourSlot, $count);
                $stats['updated']++;
            } else {
                $stats['errors'][] = $appid;
            }
        }

        return $stats;
    }

    public function backfill(?callable $log = null): array
    {
        $appids = $this->db->getAllAppids();
        $stats = ['fetched' => 0, 'inserted' => 0, 'errors' => []];

        foreach ($appids as $appid) {
            $log && $log("Backfilling app $appid...");

            $url = "https://steamcharts.com/app/{$appid}/chart-data.json";

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                $stats['errors'][] = $appid;
                $log && $log("  HTTP $httpCode, skipping");
                continue;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || empty($data)) {
                $stats['errors'][] = $appid;
                continue;
            }

            $stats['fetched']++;
            $inserted = 0;

            foreach ($data as $point) {
                if (!isset($point[0], $point[1])) continue;
                $ts = (int)($point[0] / 1000); // ms to seconds
                $count = (int)$point[1];

                // INSERT OR IGNORE handles duplicates
                $this->db->insertPlayerCount($appid, $ts, $count);
                $inserted++;
            }

            $stats['inserted'] += $inserted;
            $log && $log("  Inserted $inserted points");

            // Be respectful — delay between requests
            usleep(500000);
        }

        return $stats;
    }

    private function fetchCurrentPlayers(int $appid): ?int
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $url = 'https://api.steampowered.com/ISteamUserStats/GetNumberOfCurrentPlayers/v1/?' . http_build_query([
            'appid' => $appid,
            'key' => $this->apiKey,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['response']['player_count'] ?? null;
    }
}
