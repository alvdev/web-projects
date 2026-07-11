<?php

require_once __DIR__ . '/../plugins/kirby3-dotenv/global.php';
loadenv(['dir' => dirname(__DIR__, 2)]);

return [
    'debug' => true,
    'url' => $_SERVER['HTTP_HOST'] ?? 'http://localhost:5173',

    'igdb' => [
        'client_id' => env('IGDB_CLIENT_ID', ''),
        'client_secret' => env('IGDB_CLIENT_SECRET', ''),
    ],

    'arnoson.kirby-vite' => [
        'outDir' => 'public/assets',
    ],

    'arnoson.kirby-loupe' => [
        'pages' => fn($page) => $page->intendedTemplate()->name() === 'game' || $page->intendedTemplate()->name() === 'post',
        'fields' => [
            'title',
            'summary',
            'text' => fn($page) => strip_tags($page->text()),
        ],
        'searchable' => ['title', 'summary', 'text'],
    ],

    'tearoom1.meta-kit' => [
        'api.key' => env('OPENROUTER_API_KEY'),
        'api.model' => 'openai/gpt-oss-20b:free',
    ],

    'alv.steam-stats.api-key' => env('STEAM_STATS_API_KEY', ''),
    'alv.steam-stats.warm-key' => env('STEAM_STATS_WARM_KEY', ''),

    'cache.alv/steam-stats.cache' => [
        'type' => 'file',
        'active' => true,
    ],

    'routes' => [
        [
            'pattern' => 'genre/(:any)',
            'action'  => function ($genre) {
                return page('genre')->render(['genreSlug' => $genre]);
            },
        ],
        [
            'pattern' => 'games/by-appid/(:num)',
            'method' => 'GET',
            'action' => function (string $appidStr) {
                $appid = (int) $appidStr;

                // 1. Already imported → permanent redirect to the game page.
                try {
                    $db = new \Alv\SteamStats\SteamStatsDB();
                    $game = $db->getGameByAppId($appid);
                    if ($game && page('games/' . $game['slug'])) {
                        go('/games/' . $game['slug'], 301);
                    }
                } catch (\Throwable $e) {}

                // 2. Resolve the IGDB slug for this Steam appid, then hand off to the
                //    existing /games/(:any) route which already handles on-the-fly import.
                $config = kirby()->option('igdb');
                if (!empty($config['client_id']) && !empty($config['client_secret'])) {
                    try {
                        require_once dirname(__DIR__, 2) . '/site/plugins/alv-igdb/classes/IGDBClient.php';
                        $client = new \DiarioGames\IGDB\IGDBClient($config['client_id'], $config['client_secret']);
                        $gameData = $client->fetchGameBySteamAppId($appid);
                        if (!empty($gameData['slug'])) {
                            go('/games/' . $gameData['slug'], 302);
                        }
                    } catch (\Throwable $e) {
                        error_log('by-appid slug lookup failed for ' . $appid . ': ' . $e->getMessage());
                    }
                }

                // 3. Could not resolve a slug — fall back to the Steam store page.
                go('https://store.steampowered.com/app/' . $appid, 302);
            }
        ],
        [
            'pattern' => 'games/(:any)',
            'method' => 'GET',
            'action' => function (string $slug) {
                $igdbRoot = dirname(__DIR__, 2);
                require_once $igdbRoot . '/site/plugins/alv-igdb/classes/helpers.php';

                $page = page('games/' . $slug);
                if ($page) {
                    $canonical = \DiarioGames\IGDB\romanToDigits($slug);
                    if ($canonical !== $slug) {
                        $oldDir = $igdbRoot . '/content/games/' . $slug;
                        $newDir = $igdbRoot . '/content/games/' . $canonical;
                        if (is_dir($oldDir) && !is_dir($newDir)) {
                            rename($oldDir, $newDir);
                        }
                        go('/games/' . $canonical, 301);
                    }
                    return $page;
                }

                $config = kirby()->option('igdb');
                if (empty($config['client_id']) || empty($config['client_secret'])) {
                    return page('error');
                }

                try {
                    require_once $igdbRoot . '/site/plugins/alv-igdb/classes/IGDBClient.php';
                    require_once $igdbRoot . '/site/plugins/alv-igdb/classes/GameImporter.php';

                    $client = new \DiarioGames\IGDB\IGDBClient($config['client_id'], $config['client_secret']);
                    $importer = new \DiarioGames\IGDB\GameImporter($client);
                    $result = $importer->importBySlug($slug);

                    if ($result) {
                        go('/games/' . $result);
                    }

                    // Fallback: search IGDB when direct slug lookup fails
                    // Replace dashes with spaces — IGDB search expects natural-language queries, not URL slugs.
                    $searchQuery = str_replace('-', ' ', $slug);
                    $igdbResults = $client->searchGames($searchQuery);

                    // If no results found, retry with a broader query (drop last word)
                    // e.g. "grand theft auto 5 legacy" → "grand theft auto 5"
                    if (empty($igdbResults)) {
                        $words = explode(' ', $searchQuery);
                        if (count($words) > 2) {
                            array_pop($words);
                            $broaderQuery = implode(' ', $words);
                            $igdbResults = $client->searchGames($broaderQuery);
                        }
                    }

                    $imported = false;
                    foreach ($igdbResults as $ig) {
                        $igdbSlug = $ig['slug'] ?? '';
                        if (!$igdbSlug) continue;
                        // Strip IGDB duplicate suffix (--N) before normalizing roman numerals
                        $canonicalSlug = preg_replace('/--\d+$/', '', $igdbSlug);
                        if (\DiarioGames\IGDB\romanToDigits($canonicalSlug) !== $slug) continue;
                        if (\DiarioGames\IGDB\GameImporter::isExcluded($ig)) continue;

                        // If a local page with this IgdbId already exists, redirect to it
                        $existingPage = site()->index()->filterBy('intendedTemplate', 'game')->filter(function ($p) use ($ig) {
                            return (int) $p->content()->get('IgdbId')->value() === ($ig['id'] ?? 0);
                        })->first();
                        if ($existingPage) {
                            go($existingPage->url());
                        }

                        // Use the canonical slug (without --N suffix) so the content
                        // directory matches the requested URL path.
                        $ig['slug'] = $canonicalSlug;
                        $result = $importer->import($ig);
                        if ($result) {
                            go('/games/' . $result);
                        }
                        $imported = true;
                    }

                    // If no exact slug match was found, fall back to importing the
                    // closest IGDB result under the requested slug.  This handles
                    // Steam-specific sub-editions (e.g. "Legacy") that don't have a
                    // dedicated IGDB entry — the base game data is imported instead.
                    if (!$imported && !empty($igdbResults)) {
                        foreach ($igdbResults as $ig) {
                            if (\DiarioGames\IGDB\GameImporter::isExcluded($ig)) continue;
                            $ig['slug'] = $slug;
                            $result = $importer->import($ig);
                            if ($result) {
                                go('/games/' . $result);
                            }
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('IGDB import error for ' . $slug . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                }

                return page('error');
            }
        ],
    ],
];
