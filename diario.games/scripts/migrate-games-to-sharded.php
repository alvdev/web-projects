#!/usr/bin/env php
<?php
/**
 * Migrate existing flat game directories to sharded year/month/slug structure.
 *
 * Usage: php scripts/migrate-games-to-sharded.php
 */

$gamesDir = __DIR__ . '/../content/games';

if (!is_dir($gamesDir)) {
    echo "Error: content/games directory not found.\n";
    exit(1);
}

$entries = scandir($gamesDir);
$index = [];
$igdbIndex = [];
$moved = 0;
$skipped = 0;

foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') continue;

    $oldPath = "{$gamesDir}/{$entry}";

    // Skip non-directories (e.g. games.txt, any loose files)
    if (!is_dir($oldPath)) continue;

    // Skip directories that are already sharded (year-named dirs)
    if (preg_match('/^\d{4}$/', $entry)) continue;

    $gameFile = "{$oldPath}/game.txt";
    if (!file_exists($gameFile)) {
        echo "  skip: {$entry} (no game.txt)\n";
        $skipped++;
        continue;
    }

    $content = file_get_contents($gameFile);
    $releaseDate = '';
    if (preg_match('/^ReleaseDate:\s*(.+)$/m', $content, $m)) {
        $releaseDate = trim($m[1]);
    }

    $igdbId = 0;
    if (preg_match('/^IgdbId:\s*(\d+)/m', $content, $m)) {
        $igdbId = (int) $m[1];
    }

    // Derive year/month
    $year = '00';
    $month = '00';
    if (preg_match('/^(\d{4})-(\d{2})/', $releaseDate, $m)) {
        $year = $m[1];
        $month = $m[2];
    } elseif (preg_match('/^(\d{4})/', $releaseDate, $m)) {
        $year = $m[1];
    }

    $newDir = "{$gamesDir}/{$year}/{$month}/{$entry}";
    $newParent = "{$gamesDir}/{$year}/{$month}";

    if (!is_dir($newParent)) {
        mkdir($newParent, 0755, true);
    }

    echo "  move: {$entry} -> games/{$year}/{$month}/{$entry}\n";
    rename($oldPath, $newDir);
    $moved++;

    $index[$entry] = "{$year}/{$month}";
    if ($igdbId > 0) {
        $igdbIndex[$igdbId] = $entry;
    }
}

// Write index files
$indexDir = __DIR__ . '/../data/games';
if (!is_dir($indexDir)) {
    mkdir($indexDir, 0755, true);
}

$indexPath = "{$indexDir}/index.php";
file_put_contents($indexPath, '<?php return ' . var_export($index, true) . ';' . "\n");
echo "\nWrote {$indexPath} (" . count($index) . " entries)\n";

$igdbPath = "{$indexDir}/index-igdb.php";
file_put_contents($igdbPath, '<?php return ' . var_export($igdbIndex, true) . ';' . "\n");
echo "Wrote {$igdbPath} (" . count($igdbIndex) . " entries)\n";

echo "\nDone: {$moved} moved, {$skipped} skipped.\n";
