<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/site/plugins/alv-steam-stats/classes/SteamStatsDB.php';

$dbPath = getenv('STEAM_STATS_DB_PATH');
if (!$dbPath) {
    fwrite(STDERR, "STEAM_STATS_DB_PATH is required\n");
    exit(1);
}

$db = new \Alv\SteamStats\SteamStatsDB($dbPath);
$now = time();

$db->upsertGame(990001, 'e2e-game', 'E2E Game', 1);
$db->upsertGame(990002, 'e2e-second', 'E2E Second', 2);
$db->setYearMonth('e2e-game', '2024/03', 1);
$db->setYearMonth('e2e-second', '2024/04', 2);

$db->insertPlayerCount(990001, $now - 2 * 86400, 500);
$db->insertPlayerCount(990001, $now - 86400, 1200);
$db->insertPlayerCount(990001, $now - 3600, 1500);
$db->upsertGamePeak(990001, 1800, $now - 10 * 86400);

$db->insertPlayerCount(990002, $now - 3600, 100);
