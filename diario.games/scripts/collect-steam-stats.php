<?php
/**
 * Collect current Steam player counts for all tracked games.
 * Run via cron: 0 * * * * php /path/to/scripts/collect-steam-stats.php
 *
 * Requires the Kirby autoloader for the plugin classes.
 */

require __DIR__ . '/../kirby/bootstrap.php';

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
} else {
    $stats = $collector->collect();
    echo "Scanned: {$stats['scanned']}, Updated: {$stats['updated']}, Errors: " . count($stats['errors']) . "\n";
}

if (!empty($stats['errors'])) {
    echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
}
