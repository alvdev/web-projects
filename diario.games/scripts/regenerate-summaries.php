<?php
/**
 * Regenerate game summaries with neutral International Spanish via DeepSeek.
 *
 * Reads each game.txt, fetches the original English summary from IGDB,
 * translates via DeepSeek with the neutral-Spanish prompt, and updates the file.
 *
 * Usage:
 *   php scripts/regenerate-summaries.php                        # all games
 *   php scripts/regenerate-summaries.php --dry-run              # preview only
 *   php scripts/regenerate-summaries.php --slug=chrono-trigger  # single game
 *   php scripts/regenerate-summaries.php --limit=10             # first N games
 *   php scripts/regenerate-summaries.php --force                # skip confirmation prompt
 */

$root = dirname(__DIR__);

$dryRun = in_array('--dry-run', $argv);
$force = in_array('--force', $argv);
$singleSlug = null;
$limit = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--slug=')) {
        $singleSlug = substr($arg, 7);
    }
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int) substr($arg, 8);
    }
}

require_once $root . '/kirby/bootstrap.php';
require_once $root . '/site/plugins/alv-ai/classes/AIClient.php';
require_once $root . '/site/plugins/alv-igdb/classes/IGDBClient.php';

$config = require $root . '/site/config/config.php';
$clientId = $config['igdb']['client_id'] ?? getenv('IGDB_CLIENT_ID');
$clientSecret = $config['igdb']['client_secret'] ?? getenv('IGDB_CLIENT_SECRET');

if (!$clientId || !$clientSecret) {
    echo "Error: IGDB_CLIENT_ID and IGDB_CLIENT_SECRET must be set.\n";
    exit(1);
}

$opencodeKey = $_ENV['OPENCODE_API_KEY'] ?? $_SERVER['OPENCODE_API_KEY'] ?? getenv('OPENCODE_API_KEY');
if (!$opencodeKey) {
    echo "Error: OPENCODE_API_KEY must be set.\n";
    exit(1);
}

$gamesDir = $root . '/content/games';
if (!is_dir($gamesDir)) {
    echo "Error: content/games directory not found.\n";
    exit(1);
}

$dirs = glob($gamesDir . '/*', GLOB_ONLYDIR);
$dirs = array_filter($dirs, fn($d) => basename($d) !== 'games');

if ($singleSlug) {
    $dirs = array_values(array_filter($dirs, fn($d) => basename($d) === $singleSlug));
    if (empty($dirs)) {
        echo "Error: game '{$singleSlug}' not found.\n";
        exit(1);
    }
}

$total = count($dirs);
if ($limit && $limit < $total) {
    $dirs = array_slice($dirs, 0, $limit);
    $total = count($dirs);
}

echo "\nRegenerating summaries for {$total} games" . ($dryRun ? " [DRY RUN]" : "") . "\n";
echo "Backend: OpenCode (deepseek-v4-flash) — neutral International Spanish\n\n";

if (!$dryRun && !$force) {
    echo "This will overwrite {$total} game.txt files. Continue? [y/N] ";
    $answer = trim(fgets(STDIN));
    if (strtolower($answer) !== 'y') {
        echo "Aborted.\n";
        exit(0);
    }
}

try {
    $client = new \DiarioGames\IGDB\IGDBClient($clientId, $clientSecret);
} catch (\Throwable $e) {
    echo "Error creating IGDB client: " . $e->getMessage() . "\n";
    exit(1);
}

$updated = 0;
$skipped = 0;
$failed = 0;
$errors = [];
$igdbCache = [];
$startTime = time();

foreach ($dirs as $i => $dir) {
    $slug = basename($dir);
    $txtPath = "{$dir}/game.txt";

    if (!file_exists($txtPath)) {
        $skipped++;
        continue;
    }

    $content = file_get_contents($txtPath);
    if ($content === false) {
        $skipped++;
        continue;
    }

    if (!preg_match('/^Igdbid:\s*(\d+)/mi', $content, $m)) {
        echo "  [SKIP] {$slug} — no IgdbId found\n";
        $skipped++;
        continue;
    }

    $igdbId = (int) $m[1];

    $currentSummary = '';
    if (preg_match('/^Summary:\s*(.+?)(?:\n----|\n\z)/ms', $content, $sm)) {
        $currentSummary = trim($sm[1]);
    }

    $status = "[{$slug}]";

    try {
        if (isset($igdbCache[$igdbId])) {
            $englishSummary = $igdbCache[$igdbId];
        } else {
            $gameData = $client->fetchGameById($igdbId);
            $englishSummary = $gameData['summary'] ?? '';
            $igdbCache[$igdbId] = $englishSummary;
        }

        if (empty(trim($englishSummary))) {
            echo "  {$status} SKIP — no English summary from IGDB\n";
            $skipped++;
            continue;
        }

        $neutralSummary = \DiarioGames\AI\AIClient::translate($englishSummary, 'opencode');

        if ($neutralSummary === $englishSummary || empty(trim($neutralSummary))) {
            echo "  {$status} FAIL — translation returned empty/unchanged\n";
            $failed++;
            $errors[] = ['slug' => $slug, 'error' => 'Empty translation'];
            continue;
        }

        if (trim($neutralSummary) === $currentSummary) {
            echo "  {$status} SKIP — already has neutral summary\n";
            $skipped++;
            continue;
        }

        if ($dryRun) {
            $preview = mb_substr($neutralSummary, 0, 100);
            echo "  {$status} DRY-RUN — would update to: {$preview}...\n";
            $updated++;
            continue;
        }

        $newContent = preg_replace(
            '/^Summary:\s*.+?(?=\n----|\n\z)/ms',
            'Summary: ' . $neutralSummary,
            $content,
            1
        );

        if ($newContent === null || $newContent === $content) {
            echo "  {$status} FAIL — could not replace Summary in file\n";
            $failed++;
            $errors[] = ['slug' => $slug, 'error' => 'File replace failed'];
            continue;
        }

        file_put_contents($txtPath, $newContent);
        $preview = mb_substr($neutralSummary, 0, 80);
        echo "  {$status} OK — {$preview}...\n";
        $updated++;
    } catch (\Throwable $e) {
        echo "  {$status} ERROR — " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = ['slug' => $slug, 'error' => $e->getMessage()];
    }

    $progress = $i + 1;
    if ($progress % 10 === 0) {
        $elapsed = time() - $startTime;
        $rate = $elapsed > 0 ? round($progress / $elapsed, 1) : 0;
        echo "  --- Progress: {$progress}/{$total} ({$rate} games/sec) ---\n";
    }
}

$elapsed = time() - $startTime;
echo "\n========================================\n";
echo "Completed in {$elapsed}s\n";
echo "  Updated: {$updated}\n";
echo "  Skipped: {$skipped}\n";
echo "  Failed:  {$failed}\n";

if (!empty($errors)) {
    $logFile = $root . '/storage/regenerate-errors.json';
    file_put_contents($logFile, json_encode($errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\nErrors logged to: {$logFile}\n";
}
