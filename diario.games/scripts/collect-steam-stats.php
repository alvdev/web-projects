<?php
/**
 * Collect current Steam player counts for all tracked games.
 * Run via cron: 0 * * * * php /path/to/scripts/collect-steam-stats.php
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

if ($mode === 'backfill') {
    echo "Backfilling historical data from steamcharts.com...\n";
    $stats = $collector->backfill(function ($msg) { echo "  $msg\n"; });
    echo "Fetched: {$stats['fetched']}, Inserted: {$stats['inserted']}, Errors: " . count($stats['errors']) . "\n";
} elseif ($mode === 'peaks') {
    echo "Collecting all-time peaks from steamcharts.com...\n";
    $limit = (int)($argv[2] ?? 100);
    $stats = $collector->collectAllTimePeaks(function ($msg) { echo "  $msg\n"; }, $limit);
    echo "Fetched: {$stats['fetched']}, Errors: " . count($stats['errors']) . "\n";
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
