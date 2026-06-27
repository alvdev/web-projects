<?php
/**
 * IGDB CLI — Seed, search, import, and auto-fetch games.
 *
 * Usage:
 *   php scripts/igdb.php seed [--limit N]
 *   php scripts/igdb.php search <query>
 *   php scripts/igdb.php import <igdb-id>
 *   php scripts/igdb.php auto-fetch [--max N]
 *   php scripts/igdb.php clean
 */

require_once dirname(__DIR__) . '/kirby/bootstrap.php';
require_once dirname(__DIR__) . '/site/plugins/alv-igdb/classes/helpers.php';
require_once dirname(__DIR__) . '/site/plugins/alv-igdb/classes/IGDBClient.php';
require_once dirname(__DIR__) . '/site/plugins/alv-igdb/classes/GameImporter.php';
require_once dirname(__DIR__) . '/site/plugins/alv-igdb/classes/AutoFetcher.php';

use DiarioGames\IGDB\IGDBClient;
use DiarioGames\IGDB\GameImporter;
use DiarioGames\IGDB\AutoFetcher;

$root = dirname(__DIR__);
$config = require $root . '/site/config/config.php';

$clientId = $config['igdb']['client_id'] ?? getenv('IGDB_CLIENT_ID');
$clientSecret = $config['igdb']['client_secret'] ?? getenv('IGDB_CLIENT_SECRET');

if (!$clientId || !$clientSecret) {
    echo "Error: IGDB_CLIENT_ID and IGDB_CLIENT_SECRET must be set.\n";
    echo "Set them in site/config/config.php or as environment variables.\n";
    exit(1);
}

$command = $argv[1] ?? 'help';

try {
    $client = new IGDBClient($clientId, $clientSecret);

    switch ($command) {
        case 'seed':
            $limit = 0;
            foreach ($argv as $arg) {
                if (str_starts_with($arg, '--limit=')) {
                    $limit = (int) substr($arg, 8);
                }
            }
            echo "Seeding top " . ($limit ?: 'all') . " games...\n";
            $importer = new GameImporter($client);
            $fetcher = new AutoFetcher($client, $importer);
            $result = $fetcher->run($limit);
            echo "Done. Imported: {$result['imported']}, Skipped: {$result['skipped']}\n";
            break;

        case 'search':
            $query = implode(' ', array_slice($argv, 2));
            if (!$query) {
                echo "Usage: php scripts/igdb.php search <query>\n";
                exit(1);
            }
            echo "Searching for \"{$query}\"...\n";
            $results = $client->searchGames($query);
            $results = array_filter($results, fn($g) => $g['slug'] !== null && !GameImporter::isExcluded($g));
            $results = array_values($results);
            if (empty($results)) {
                echo "No results found.\n";
                exit;
            }
            echo "\nResults:\n";
            foreach ($results as $i => $game) {
                $date = !empty($game['first_release_date']) ? date('Y-m-d', $game['first_release_date']) : 'N/A';
                echo "  [{$i}] {$game['name']} ({$date}) — ID: {$game['id']}\n";
            }
            echo "\nEnter number to import, or 'q' to quit: ";
            $input = trim(fgets(STDIN));
            if ($input === 'q') exit;
            $index = (int) $input;
            if (isset($results[$index])) {
                $importer = new GameImporter($client);
                $slug = $importer->import($results[$index]);
                if ($slug) {
                    echo "Imported: {$results[$index]['name']} → /games/{$slug}\n";
                } else {
                    echo "Failed to import.\n";
                }
            }
            break;

        case 'import':
            $id = (int) ($argv[2] ?? 0);
            if (!$id) {
                echo "Usage: php scripts/igdb.php import <igdb-id>\n";
                exit(1);
            }
            echo "Fetching game ID {$id}...\n";
            $gameData = $client->fetchGameById($id);
            if (!$gameData) {
                echo "Game not found.\n";
                exit(1);
            }
            $importer = new GameImporter($client);
            $slug = $importer->import($gameData);
            echo "Imported: {$gameData['name']} → /games/{$slug}\n";
            break;

        case 'auto-fetch':
            $max = 0;
            foreach ($argv as $arg) {
                if (str_starts_with($arg, '--max=')) {
                    $max = (int) substr($arg, 6);
                }
            }
            echo "Auto-fetching " . ($max ? "up to {$max}" : "all") . " games...\n";
            $importer = new GameImporter($client);
            $fetcher = new AutoFetcher($client, $importer);
            $result = $fetcher->run($max);
            echo "Done. Imported: {$result['imported']}, Skipped: {$result['skipped']}\n";
            break;

        case 'clean':
            $gamesDir = $root . '/content/games';
            if (!is_dir($gamesDir)) {
                echo "Nothing to clean.\n";
                exit;
            }
            $dirs = glob($gamesDir . '/*', GLOB_ONLYDIR);
            $count = 0;
            foreach ($dirs as $dir) {
                $basename = basename($dir);
                if ($basename === 'games') continue;
                array_map('unlink', glob("{$dir}/*"));
                rmdir($dir);
                $count++;
                echo "  removed: {$basename}\n";
            }
            if (file_exists($root . '/content/.seed-generated')) {
                unlink($root . '/content/.seed-generated');
            }
            echo "Done. Removed {$count} games.\n";
            break;

        default:
            echo "Usage: php scripts/igdb.php <command> [options]\n\n";
            echo "Commands:\n";
            echo "  seed [--limit=N]      Import top-rated games (default: all)\n";
            echo "  search <query>         Search IGDB and import interactively\n";
            echo "  import <id>            Import a game by IGDB ID\n";
            echo "  auto-fetch [--max=N]   Bulk import all main games\n";
            echo "  clean                  Remove all imported local games\n";
            break;
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
