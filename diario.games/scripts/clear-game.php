#!/usr/bin/env php
<?php
/**
 * Remove a game completely: Kirby content + SQLite data + cache.
 *
 * Usage: php scripts/clear-game.php <slug>
 *
 * After running this, visiting /<slug> will trigger a fresh import from IGDB
 * with all SteamDB history backfill applied automatically.
 */

require __DIR__ . '/../kirby/bootstrap.php';

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('STEAM_STATS_WEB_HOST') ?: 'localhost:8888';
}

$kirby = new \Kirby\Cms\App(['cli' => true]);

$arg = $argv[1] ?? '';

if (!$arg) {
    echo "Usage: php scripts/clear-game.php <slug|appid>\n";
    echo "  php scripts/clear-game.php counter-strike-2\n";
    echo "  php scripts/clear-game.php 730\n";
    exit(1);
}

$db = new \Alv\SteamStats\SteamStatsDB();

if (ctype_digit($arg)) {
    $game = $db->getGameByAppId((int)$arg);
} else {
    $game = $db->getGameBySlug($arg);
}

$slug = $game['slug'] ?? $arg;
$appid = $game['appid'] ?? null;
$name = $game['name'] ?? $slug;

if ($game) {
    echo "Clearing: $name (slug=$slug, appid=$appid)\n";
} else {
    echo "No DB record found for: $arg — clearing content only\n";
}

// 1. Delete Kirby content directory (search across all year/month dirs)
$gamesRoot = dirname(__DIR__) . '/content/games';
$foundContent = false;
if (is_dir($gamesRoot)) {
    $it = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($gamesRoot, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        if ($item->isDir() && $item->getFilename() === $slug) {
            $contentDir = $item->getPathname();
            deleteDir($contentDir);
            $foundContent = true;
            echo "  Removed content: $contentDir\n";

            // Remove empty year/month parent dirs
            $ymDir = $item->getPath();
            if (is_dir($ymDir) && count(scandir($ymDir)) <= 2) {
                rmdir($ymDir);
                $yyDir = dirname($ymDir);
                if (is_dir($yyDir) && count(scandir($yyDir)) <= 2) {
                    @rmdir($yyDir);
                }
            }
            break;
        }
    }
}
if (!$foundContent) {
    echo "  No content directory found for slug\n";
}

// 2. Delete SQLite game data
if ($appid) {
    $sqlitePath = dirname(__DIR__) . '/sqlite/steam_stats.db';
    if (!file_exists($sqlitePath)) {
        echo "  No SQLite DB found\n";
        exit(1);
    }
    $pdo = new \PDO('sqlite:' . $sqlitePath);

    $pdo->exec('DELETE FROM player_counts WHERE appid = ' . (int)$appid);
    $pdo->exec('DELETE FROM game_peaks WHERE appid = ' . (int)$appid);
    echo "  Cleared player_counts + game_peaks for appid $appid\n";
}

// 3. Keep steam_games row (slug→appid mapping) so links + reimport work
// 4. Clear Kirby caches
try {
    $kirby->cache('alv/steam-stats.cache')->remove('player-data-summary');
    $kirby->cache('alv/steam-stats.cache')->remove('trending-growth');
    $kirby->cache('alv/steam-stats.cache')->remove('stats-most-played');
    echo "  Cleared stats caches\n";
} catch (\Throwable $e) {}

// 5. Clean up any stale lock files
@unlink(sys_get_temp_dir() . '/steamdb-backfill-' . $appid . '.lock');

echo "\nDone. Visit /$slug to trigger fresh import.\n";

function deleteDir(string $dir): void
{
    if (!is_dir($dir)) return;
    $items = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}
