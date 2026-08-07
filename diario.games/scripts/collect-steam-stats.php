<?php
/**
 * Collect current Steam player counts for all tracked games.
 *
 * Usage:
 *   php collect-steam-stats.php collect                     # default: hourly player count snapshot
 *   php collect-steam-stats.php backfill                    # backfill from steamcharts.com
 *   php collect-steam-stats.php player-update               # collect + clear cache
 *   php collect-steam-stats.php peaks [limit]               # collect all-time peaks from steamcharts
 *   php collect-steam-stats.php steamdb-peak <appid>        # fetch all-time peak from steamdb.info
 *   php collect-steam-stats.php steamdb-history <appid>     # backfill history for one game (by Steam appid)
 *   php collect-steam-stats.php steamdb-history-by-slug <slug>  # backfill history for one game (by diario.games slug)
 *   php collect-steam-stats.php steamdb-backfill [limit]    # batch backfill games with stale data
 *   php collect-steam-stats.php steamdb-catchup [limit]     # catch up newly-imported games (≤ 1 data point)
 *
 * Cron: run every hour for snapshots, every 30 min for catch-up.
 *
 * Requires the Kirby autoloader for the plugin classes.
 */

require __DIR__ . '/../kirby/bootstrap.php';

// Match web server's cache namespace by setting HTTP_HOST
// (config uses $_SERVER['HTTP_HOST'] for URL-based cache prefix)
if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('STEAM_STATS_WEB_HOST') ?: 'localhost:8888';
}

$kirby = new \Kirby\Cms\App([
    'cli' => true,
]);

$key = option('alv.steam-stats.api-key', '');
if (empty($key)) {
    echo "Error: STEAM_STATS_API_KEY not configured\n";
    exit(1);
}

$mode = $argv[1] ?? 'collect';

$collector = new \Alv\SteamStats\SteamStatsCollector($key);

if ($mode === 'player-update') {
    $stats = $collector->collect();
    echo "Scanned: {$stats['scanned']}, Updated: {$stats['updated']}, Errors: " . count($stats['errors']) . "\n";
    if (!empty($stats['errors'])) {
        echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
    }
    try {
        kirby()->cache('alv/steam-stats.cache')->remove('player-data-summary');
        (new \Alv\SteamStats\SteamStatsDB())->getAllPlayerDataCached();
        kirby()->cache('alv/steam-stats.cache')
            ->set('warm-last-run', ['value' => time(), 'timestamp' => time()]);
    } catch (\Throwable $e) {}
    exit(0);
}

if ($mode === 'backfill') {
    echo "Backfilling historical data from steamcharts.com...\n";
    $stats = $collector->backfill(function ($msg) { echo "  $msg\n"; });
    echo "Fetched: {$stats['fetched']}, Inserted: {$stats['inserted']}, Errors: " . count($stats['errors']) . "\n";
} elseif ($mode === 'peaks') {
    echo "Collecting all-time peaks from steamcharts.com...\n";
    $limit = (int)($argv[2] ?? 100);
    $stats = $collector->collectAllTimePeaks(function ($msg) { echo "  $msg\n"; }, $limit);
    echo "Fetched: {$stats['fetched']}, Errors: " . count($stats['errors']) . "\n";
} elseif ($mode === 'steamdb-peak') {
    $appid = (int)($argv[2] ?? 0);
    if ($appid > 0) {
        echo "Fetching all-time peak from steamdb.info for appid $appid...\n";
        $sd = $collector->collectSteamDBPeak($appid);
        if ($sd) {
            (new \Alv\SteamStats\SteamStatsDB())->upsertGamePeak($appid, $sd['peak'], $sd['timestamp']);
            echo "Peak: {$sd['peak']} at " . date('Y-m-d', $sd['timestamp']) . "\n";
            try {
                kirby()->cache('alv/steam-stats.cache')->remove('player-data-summary');
                kirby()->cache('alv/steam-stats.cache')->remove('trending-growth');
                echo "Caches cleared.\n";
            } catch (\Throwable $e) {}
        } else {
            echo "No peak data found from steamdb.info\n";
        }
    } else {
        echo "Usage: php collect-steam-stats.php steamdb-peak <appid>\n";
    }
    exit(0);
} elseif ($mode === 'steamdb-history') {
    $appid = (int)($argv[2] ?? 0);
    if ($appid > 0) {
        echo "Fetching historical player counts from steamdb.info for appid $appid...\n";
        $result = $collector->collectSteamDBHistory($appid);
        if ($result) {
            echo "Inserted {$result['points']} data points, peak: {$result['peak']}\n";
            try {
                kirby()->cache('alv/steam-stats.cache')->remove('player-data-summary');
                echo "Caches cleared.\n";
            } catch (\Throwable $e) {}
        } else {
            echo "No data found from steamdb.info for appid $appid\n";
        }
    } else {
        echo "Usage: php collect-steam-stats.php steamdb-history <appid>\n";
    }
    exit(0);
} elseif ($mode === 'steamdb-history-by-slug') {
    $slug = $argv[2] ?? '';
    if ($slug === '') {
        echo "Usage: php collect-steam-stats.php steamdb-history-by-slug <slug>\n";
        exit(1);
    }
    $db = new \Alv\SteamStats\SteamStatsDB();
    $game = $db->getGameBySlug($slug);
    if (!$game) {
        echo "Game not found for slug: $slug\n";
        exit(1);
    }
    $appid = $game['appid'];
    echo "Game: {$game['name']} (appid $appid)\n";
    echo "Fetching historical player counts from steamdb.info...\n";
    $result = $collector->collectSteamDBHistory($appid);
    if ($result) {
        echo "Inserted {$result['points']} data points, peak: {$result['peak']}\n";
        try {
            kirby()->cache('alv/steam-stats.cache')->remove('player-data-summary');
            echo "Caches cleared.\n";
        } catch (\Throwable $e) {}
    } else {
        echo "No data found from steamdb.info for $slug\n";
    }
    exit(0);
} elseif ($mode === 'steamdb-backfill') {
    $limit = (int)($argv[2] ?? 20);
    echo "Backfilling SteamDB history for up to $limit games...\n";
    $stats = $collector->backfillSteamDBHistory(function ($msg) { echo "  $msg\n"; }, $limit);
    echo "Scanned: {$stats['scanned']}, Backfilled: {$stats['backfilled']}, Skipped: {$stats['skipped']}, Errors: " . count($stats['errors']) . "\n";
    if (!empty($stats['errors'])) {
        echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
    }
    exit(0);
} elseif ($mode === 'steamdb-catchup') {
    $limit = (int)($argv[2] ?? 10);
    echo "Catching up SteamDB history for up to $limit newly-imported games...\n";

    $db = new \Alv\SteamStats\SteamStatsDB();
    $appids = $db->getAllAppids();
    $stats = ['scanned' => 0, 'backfilled' => 0, 'skipped' => 0, 'processed' => 0, 'errors' => []];

    foreach ($appids as $appid) {
        if ($stats['processed'] >= $limit) break;

        // Only catch up games with ≤ 1 data point (the immediate import snapshot)
        $recentPoints = $db->getRecentPlayerCounts($appid, 1);
        if (count($recentPoints) > 1) {
            $stats['skipped']++;
            continue;
        }

        $lockFile = sys_get_temp_dir() . '/steamdb-backfill-' . $appid . '.lock';
        if (file_exists($lockFile)) {
            $stats['skipped']++;
            continue;
        }

        $stats['scanned']++;
        $stats['processed']++;
        echo "  Backfilling appid $appid...\n";
        $result = $collector->collectSteamDBHistory($appid);
        if ($result) {
            $stats['backfilled']++;
            echo "    Inserted {$result['points']} points, peak {$result['peak']}\n";
        } else {
            $stats['errors'][] = $appid;
            echo "    Failed\n";
        }
    }

    echo "Scanned: {$stats['scanned']}, Backfilled: {$stats['backfilled']}, Skipped: {$stats['skipped']}, Errors: " . count($stats['errors']) . "\n";
    if (!empty($stats['errors'])) {
        echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
    }
    exit(0);
} else {
    $stats = $collector->collect();
    echo "Scanned: {$stats['scanned']}, Updated: {$stats['updated']}, Errors: " . count($stats['errors']) . "\n";
}

if (!empty($stats['errors'])) {
    echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
}

// Import scraped top-100 games into collector tracking so they get SQLite data
try {
    $scraped = site()->steamStats()->getMostPlayed(100);
    $db = new \Alv\SteamStats\SteamStatsDB();
    $newGames = 0;
    foreach ($scraped as $g) {
        $existing = $db->getGameByAppId($g['appid']);
        if ($existing) continue;
        // Generate a unique slug from the game name
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($g['name']));
        $slug = trim($slug, '-');
        $base = $slug;
        $suffix = 1;
        while ($db->getGameBySlug($slug)) {
            $slug = $base . '--' . $suffix++;
        }
        $db->upsertGame($g['appid'], $slug, $g['name']);
        $newGames++;
    }
    if ($newGames > 0) {
        echo "Imported $newGames scraped games into collector tracking.\n";
        // Re-run collect to fetch player counts for new games
        $collector->collect();
    }
} catch (\Throwable $e) {
    echo "Import scraped games failed: " . $e->getMessage() . "\n";
}

// Warm caches so page loads never trigger synchronous scraping or API calls
try {
    $stats = site()->steamStats();

    // Warm most-played scraped data + game details
    $mostPlayed = $stats->getMostPlayed(100);

    // Warm trending data + current-players cache for trending games
    $stats->getTrending(100);

    // Also warm current-players cache for the full top-100 scraped list
    // (covers games not in trending list like Apex Legends)
    foreach ($mostPlayed as $g) {
        $stats->getLivePlayerCount($g['appid']);
    }

    $stats->updatePlayerHistory();
    kirby()->cache('alv/steam-stats.cache')->remove('player-data-summary');
    (new \Alv\SteamStats\SteamStatsDB())->getAllPlayerDataCached();
    kirby()->cache('alv/steam-stats.cache')
        ->set('warm-last-run', ['value' => time(), 'timestamp' => time()]);
    echo "Caches warmed.\n";
} catch (\Throwable $e) {
    echo "Cache warm failed: " . $e->getMessage() . "\n";
}

// Download capsule images locally for all Steam games sitewide
try {
    $gamesDir = dirname(__DIR__) . '/content/games';
    $downloaded = 0;

    $recursive = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($gamesDir, \FilesystemIterator::SKIP_DOTS)
    );
    foreach ($recursive as $item) {
        if ($item->getFilename() !== 'game.txt') continue;

        $gameDir = dirname($item->getPathname());
        $slug = basename($gameDir);
        $content = file_get_contents($item->getPathname());

        if (!preg_match('/store\.steampowered\.com\/app\/(\d+)/i', $content, $m)) continue;

        $appid = (int) $m[1];
        $localPath = $gameDir . '/steam-capsule.jpg';
        if (file_exists($localPath)) continue;

        $result = $collector->downloadCapsule($appid, $slug);
        if ($result !== null) {
            $downloaded++;
        }
        kirby()->cache('alv/steam-stats.cache')->remove('game-details.' . $appid);
        usleep(100000);
    }

    site()->steamStats()->getMostPlayed(100);
    site()->steamStats()->getTrending(100);

    if ($downloaded > 0) echo "Downloaded $downloaded capsule images.\n";
} catch (\Throwable $e) {
    echo "Capsule download failed: " . $e->getMessage() . "\n";
}

// Collect all-time peaks from steamcharts (100 uncached games per run)
if ($mode !== 'backfill') {
    try {
        $peakStats = $collector->collectAllTimePeaks(null, 100);
        if ($peakStats['fetched'] > 0) {
            echo "All-time peaks fetched: {$peakStats['fetched']}\n";
        }
    } catch (\Throwable $e) {
        echo "All-time peaks failed: " . $e->getMessage() . "\n";
    }
}
