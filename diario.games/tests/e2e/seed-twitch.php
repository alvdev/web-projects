<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/site/plugins/alv-twitch-stats/classes/TwitchStatsDB.php';

$dbPath = getenv('TWITCH_STATS_DB_PATH');
if (!$dbPath) {
    fwrite(STDERR, "TWITCH_STATS_DB_PATH is required\n");
    exit(1);
}

$db = new \Alv\TwitchStats\TwitchStatsDB($dbPath);
$now = time();

$db->upsertGame('1', 'E2E Game', 1, 'https://static-cdn.jtvnw.net/ttv-boxart/1-144x192.jpg');

$db->upsertStreamer('u1', 'e2e-streamer', 'E2E Streamer', '/assets/images/diario-games-logo.webp', '');
$db->upsertStreamer('u4', 'spanish-one', 'Spanish One', '/assets/images/diario-games-logo.webp', 'es');

$db->insertSnapshot('game', '1', $now - 7200, 800, 1);
$db->insertSnapshot('game', '1', $now - 3600, 900, 1);
$db->insertSnapshot('streamer', 'u1', $now - 7200, 600, 2, 'E2E Game');
$db->insertSnapshot('streamer', 'u1', $now - 3600, 1000, 1, 'E2E Game');
$db->insertSnapshot('streamer_es', 'u4', $now - 7200, 400, 1, 'E2E Game');
$db->insertSnapshot('streamer_es', 'u4', $now - 3600, 700, 1, 'E2E Game');
