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

    public function collectAllTimePeaks(?callable $log = null, int $limit = 100): array
    {
        $appids = [];

        // Priority 1: top games from most-played
        try {
            $stats = site()->steamStats()->getMostPlayed(100);
            foreach ($stats as $g) {
                $appids[$g['appid']] = true;
            }
        } catch (\Throwable $e) {}

        // Priority 2: local site games
        try {
            foreach ($this->db->getAllGames() as $g) {
                $appids[$g['appid']] = true;
            }
        } catch (\Throwable $e) {}

        $allAppids = array_keys($appids);

        // Filter to games not yet in game_peaks table
        $uncached = [];
        foreach ($allAppids as $appid) {
            $existing = $this->db->getGamePeak($appid);
            if ($existing === null || $existing <= 0) {
                $uncached[] = $appid;
                if (count($uncached) >= $limit) break;
            }
        }

        if (empty($uncached)) {
            $log && $log('All games already have peaks recorded.');
            return ['fetched' => 0, 'errors' => []];
        }

        $log && $log('Fetching all-time peaks for ' . count($uncached) . ' games...');

        // Fetch in parallel batches of 5 to avoid rate limiting
        $peaks = [];
        $chunks = array_chunk($uncached, 5);
        foreach ($chunks as $i => $chunk) {
            $log && $log('  Batch ' . ($i + 1) . '/' . count($chunks) . '...');
            $batch = $this->batchFetchPeaks($chunk);
            foreach ($batch as $appid => $peak) {
                $peaks[$appid] = $peak;
            }
            if ($i < count($chunks) - 1) {
                usleep(200000);
            }
        }

        // Store in DB
        $stored = 0;
        foreach ($peaks as $appid => $peak) {
            $this->db->upsertGamePeak($appid, $peak);
            $stored++;
        }

        return ['fetched' => $stored, 'errors' => []];
    }

    private function batchFetchPeaks(array $appids): array
    {
        $results = [];
        $handles = [];
        $mh = curl_multi_init();

        foreach ($appids as $appid) {
            $ch = curl_init('https://steamcharts.com/app/' . $appid . '/chart-data.json');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$appid] = $ch;
        }

        if (!empty($handles)) {
            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);

            foreach ($handles as $appid => $ch) {
                $response = curl_multi_getcontent($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);
                    if (is_array($data)) {
                        $peak = 0;
                        foreach ($data as $point) {
                            if (isset($point[1]) && $point[1] > $peak) {
                                $peak = (int)$point[1];
                            }
                        }
                        if ($peak > 0) {
                            $results[$appid] = $peak;
                        }
                    }
                }
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
        }

        curl_multi_close($mh);
        return $results;
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

    public function downloadCapsule(int $appid, ?string $slug = null): ?string
    {
        if (!$slug) {
            $game = $this->db->getGameByAppId($appid);
            if (!$game) return null;
            $slug = $game['slug'];
        }

        // Get correct capsule URL from Steam store API
        $apiUrl = "https://store.steampowered.com/api/appdetails?appids={$appid}";
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data[$appid]['success']) || !$data[$appid]['success']) return null;

        // Prefer header_image (largest), fall back to capsule_image
        $info = $data[$appid]['data'] ?? [];
        $imageUrl = $info['header_image'] ?? $info['capsule_image'] ?? '';
        if (empty($imageUrl)) return null;

        $destFile = dirname(__DIR__, 4) . '/content/games/' . $slug . '/steam-capsule.jpg';

        $ch = curl_init($imageUrl);
        $fp = fopen($destFile, 'wb');
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SteamStats/1.0)',
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode === 200) {
            $size = filesize($destFile);
            if ($size > 1000) {
                return '/media/steam-capsule/' . $slug . '.jpg';
            }
        }

        @unlink($destFile);
        return null;
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
