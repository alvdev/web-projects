<?php
/**
 * Collect Twitch viewer snapshots and prune old history.
 *
 * Usage:
 *   php collect-twitch-stats.php              # snapshot (default)
 *   php collect-twitch-stats.php snapshot     # top 100 games, streamers and Spanish streamers
 *   php collect-twitch-stats.php prune        # remove snapshots older than the retention option
 *
 * Cron: run every 5 minutes (snapshots are only written once per hour):
 *   every-5-min cron -> php scripts/collect-twitch-stats.php >/dev/null 2>&1
 */

require __DIR__ . '/../kirby/bootstrap.php';

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('TWITCH_STATS_WEB_HOST') ?: getenv('STEAM_STATS_WEB_HOST') ?: 'localhost:8888';
}

$kirby = new \Kirby\Cms\App([
    'cli' => true,
]);

$mode = $argv[1] ?? 'snapshot';

$igdb = option('igdb') ?? [];
$client = new \Alv\TwitchStats\TwitchClient($igdb['client_id'] ?? '', $igdb['client_secret'] ?? '');
$collector = new \Alv\TwitchStats\TwitchStatsCollector(
    $client,
    new \Alv\TwitchStats\TwitchStatsDB(),
    (int) option('alv.twitch-stats.history-ttl', 7776000)
);

if ($mode === 'snapshot') {
    if (!$client->isConfigured()) {
        echo "Snapshot skipped (Twitch credentials not configured).\n";
        exit(0);
    }

    $result = $collector->snapshot();
    if ($result['skipped']) {
        echo "Snapshot skipped (already collected this hour).\n";
        exit(0);
    }

    echo "Snapshot written: {$result['games']} games, {$result['streamers']} streamers, {$result['spanish']} Spanish streamers.\n";
    exit(0);
}

if ($mode === 'prune') {
    $pruned = $collector->prune();
    echo "Pruned $pruned snapshots.\n";
    exit(0);
}

echo "Usage: php collect-twitch-stats.php [snapshot|prune]\n";
exit(1);
