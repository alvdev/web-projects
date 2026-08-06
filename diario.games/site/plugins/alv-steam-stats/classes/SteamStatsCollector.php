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

        $recursive = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($gamesDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($recursive as $item) {
            if ($item->getFilename() !== 'game.txt') continue;

            $slug = basename(dirname($item->getPathname()));
            $content = file_get_contents($item->getPathname());

            $stats['scanned']++;

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

        // Second pass: enrich with timestamps from steamdb.info
        $totalGames = count(array_keys($appids));
        if ($totalGames <= 20) {
            $enriched = 0;
            foreach (array_keys($appids) as $appid) {
                $sd = $this->collectSteamDBPeak($appid);
                if ($sd) {
                    $this->db->upsertGamePeak($appid, $sd['peak'], $sd['timestamp']);
                    $enriched++;
                }
            }
            if ($enriched > 0) {
                $log && $log('  Enriched ' . $enriched . ' peaks with steamdb timestamps.');
            }
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

    public function collectSteamDBPeak(int $appid): ?array
    {
        $scriptPath = dirname(__DIR__, 4) . '/scripts/fetch-steamdb-peak.mjs';
        if (!file_exists($scriptPath)) return null;

        $nodeBin = $this->findNodeBinary();
        if (!$nodeBin) return null;

        $cmd = escapeshellarg($nodeBin) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg((string)$appid) . ' 2>/dev/null';
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) return null;

        $data = json_decode($output[0], true);
        if (!$data || empty($data['peak']) || empty($data['timestamp'])) return null;

        return ['peak' => (int)$data['peak'], 'timestamp' => (int)$data['timestamp']];
    }

    private function findNodeBinary(): ?string
    {
        $nvmVersions = getenv('HOME') . '/.nvm/versions/node';
        if (is_dir($nvmVersions)) {
            $versions = scandir($nvmVersions, SCANDIR_SORT_DESCENDING);
            foreach ($versions as $v) {
                if ($v === '.' || $v === '..') continue;
                $bin = $nvmVersions . '/' . $v . '/bin/node';
                if (is_executable($bin)) return $bin;
            }
        }
        $system = trim(shell_exec('which node 2>/dev/null') ?? '');
        if ($system && is_executable($system)) {
            $ver = trim(shell_exec($system . ' --version 2>/dev/null') ?? '');
            if ($ver && version_compare(ltrim($ver, 'v'), '20.0.0', '>=')) return $system;
        }
        return null;
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

    public function collectSteamDBHistory(int $appid): ?array
    {
        $lockFile = sys_get_temp_dir() . '/steamdb-backfill-' . $appid . '.lock';
        if (file_exists($lockFile)) return null;

        $scriptPath = dirname(__DIR__, 4) . '/scripts/scrape-steamdb-history.mjs';
        if (!file_exists($scriptPath)) return null;

        $nodeBin = $this->findNodeBinary();
        if (!$nodeBin) return null;

        touch($lockFile);

        $cmd = escapeshellarg($nodeBin) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg((string)$appid) . ' 2>/dev/null';
        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || empty($output)) {
            @unlink($lockFile);
            return null;
        }

        $raw = json_decode(implode('', $output), true);
        if (!is_array($raw) || empty($raw)) {
            @unlink($lockFile);
            return null;
        }

        // Support both old format [[ts,count],...] and new format {points, peak_all_time}
        if (isset($raw['points'])) {
            $data = $raw['points'];
            $domPeak = (int)($raw['peak_all_time'] ?? 0);
        } else {
            $data = $raw;
            $domPeak = 0;
        }

        $inserted = 0;
        $peak = 0;
        $peakTs = null;

        foreach ($data as $point) {
            if (!isset($point[0], $point[1])) continue;
            $ts = (int)($point[0] / 1000);
            $count = (int)$point[1];
            if ($count <= 0) continue;

            $this->db->insertPlayerCount($appid, $ts, $count);
            $inserted++;

            if ($count > $peak) {
                $peak = $count;
                $peakTs = $ts;
            }
        }

        // DOM peak takes priority if higher than the daily-bucket max
        if ($domPeak > $peak) {
            $peak = $domPeak;
            $peakTs = 0;
        }

        if ($peak > 0) {
            $this->db->upsertGamePeak($appid, $peak, $peakTs);
        }

        @unlink($lockFile);
        return ['points' => $inserted, 'peak' => $peak];
    }

    public function backfillSteamDBHistory(?callable $log = null, int $limit = 20): array
    {
        $appids = $this->db->getAllAppids();
        $stats = ['scanned' => 0, 'backfilled' => 0, 'skipped' => 0, 'errors' => []];

        $sevenDaysAgo = time() - 7 * 86400;

        $lockDir = sys_get_temp_dir();
        $processed = 0;

        foreach ($appids as $appid) {
            if ($processed >= $limit) break;

            $lockFile = $lockDir . '/steamdb-backfill-' . $appid . '.lock';
            if (file_exists($lockFile)) {
                $stats['skipped']++;
                continue;
            }

            $latestTs = $this->db->getLatestTimestamp($appid);
            if ($latestTs !== null && $latestTs >= $sevenDaysAgo) {
                $stats['skipped']++;
                continue;
            }

            $stats['scanned']++;
            $processed++;

            $log && $log("Backfilling SteamDB history for app $appid...");
            $result = $this->collectSteamDBHistory($appid);

            if ($result) {
                $stats['backfilled']++;
                $log && $log("  Inserted {$result['points']} points, peak {$result['peak']}");
            } else {
                $stats['errors'][] = $appid;
                $log && $log("  Failed to fetch data");
            }

            if ($processed < $limit && $processed < count($appids)) {
                usleep(500000);
            }
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

        $yearMonth = \DiarioGames\IGDB\resolveGamePath($slug);
        if (!$yearMonth) return null;
        $destDir = dirname(__DIR__, 4) . '/content/games/' . $yearMonth . '/' . $slug;
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $destFile = $destDir . '/steam-capsule.jpg';

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
                $this->resizeCapsule($destFile, 100);
                return '/media/steam-capsule/' . $slug . '.jpg';
            }
        }

        @unlink($destFile);
        return null;
    }

    private function resizeCapsule(string $path, int $maxHeight): void
    {
        $src = @imagecreatefromjpeg($path);
        if (!$src) return;

        $origW = imagesx($src);
        $origH = imagesy($src);
        if ($origH <= $maxHeight) {
            imagedestroy($src);
            return;
        }

        $newW = (int) round($origW * $maxHeight / $origH);
        $newH = $maxHeight;

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagejpeg($dst, $path, 85);
        imagedestroy($dst);
        imagedestroy($src);
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
            // Steam returns 404 with a valid JSON body for unreleased/beta games.
            // Check the response body for "no data" before giving up.
            if ($response) {
                $data = json_decode($response, true);
                if (is_array($data)) {
                    if (isset($data['response']['player_count'])) {
                        return (int)$data['response']['player_count'];
                    }
                    // Valid Steam response without player_count — game has no data
                    return 0;
                }
            }
            return null;
        }

        $data = json_decode($response, true);
        if (isset($data['response']['player_count'])) {
            return (int)$data['response']['player_count'];
        }
        return 0;
    }
}
