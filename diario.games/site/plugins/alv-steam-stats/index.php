<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/SteamStats.php';
@include_once __DIR__ . '/classes/SteamStatsDB.php';
@include_once __DIR__ . '/classes/SteamStatsCollector.php';

App::plugin('alv/steam-stats', [
    'snippets' => [
        'steam-stats-tabs' => __DIR__ . '/snippets/steam-stats-tabs.php',
        'steam-chart' => __DIR__ . '/snippets/steam-chart.php',
    ],
    'options' => [
        'cache' => [
            'type' => 'file',
            'active' => true,
        ],
    ],
    'routes' => [
        [
            'pattern' => 'steam-stats',
            'action' => function () {
                return \Kirby\Cms\Page::factory([
                    'slug' => 'steam-stats',
                    'template' => 'steam-stats',
                    'content' => [
                        'title' => 'Steam Charts',
                    ],
                ])->render();
            }
        ],
        [
            'pattern' => 'steam-stats-update-history',
            'method' => 'POST',
            'action' => function () {
                $stats = site()->steamStats();
                $stats->updatePlayerHistory();
                return ['status' => 'ok'];
            }
        ],
        [
            'pattern' => 'steam-stats-warm',
            'method' => 'POST',
            'action' => function () {
                $key = get('key');
                $expectedKey = option('alv.steam-stats.warm-key');
                if ($expectedKey && $key !== $expectedKey) {
                    return ['error' => 'unauthorized'];
                }

                $stats = site()->steamStats();
                $stats->getMostPlayed(100);
                $stats->getTrending(100);
                $stats->updatePlayerHistory();

                kirby()->cache('alv/steam-stats.cache')
                    ->set('warm-last-run', ['value' => time(), 'timestamp' => time()]);

                return ['status' => 'ok'];
            }
        ],
        [
            'pattern' => 'steam-stats-api/search',
            'method' => 'GET',
            'action' => function () {
                $q = get('q', '');
                if (strlen($q) < 2) {
                    return ['results' => [], 'fromIgdb' => false];
                }

                $db = new \Alv\SteamStats\SteamStatsDB();
                $q = strtolower(trim($q));
                $localResults = [];
                $seen = [];
                $limit = 15;

                $games = site()->index()->filterBy('intendedTemplate', 'game');
                foreach ($games as $game) {
                    if (count($localResults) >= $limit) break;
                    if ($game->content()->get('Screenshots')->isEmpty() && $game->content()->get('Videos')->isEmpty()) continue;
                    $title = $game->title()->value();
                    if (str_contains(strtolower($title), $q)) {
                        $slug = $game->slug();
                        $seen[$slug] = true;
                        $hasSteam = $db->getGameBySlug($slug) !== null;
                        $releaseDate = $game->content()->get('ReleaseDate')->value();
                        $year = '';
                        if (preg_match('/^\d{4}/', $releaseDate, $m)) {
                            $year = $m[0];
                        }
                        $localResults[] = [
                            'slug' => $slug,
                            'name' => $title,
                            'platforms' => \DiarioGames\IGDB\normalizePlatformNames($game->content()->get('Platforms')->value()),
                            'year' => $year,
                            'hasSteam' => $hasSteam,
                            'exists' => true,
                        ];
                    }
                }

                // If local results are few, fall back to IGDB search
                $results = $localResults;
                if (count($localResults) < 5) {
                    try {
                        $igdbConfig = kirby()->option('igdb');
                        if (!empty($igdbConfig['client_id']) && !empty($igdbConfig['client_secret'])) {
                            $root = dirname(__DIR__, 3);
                            require_once $root . '/site/plugins/alv-igdb/classes/helpers.php';
                            require_once $root . '/site/plugins/alv-igdb/classes/IGDBClient.php';
                            require_once $root . '/site/plugins/alv-igdb/classes/GameImporter.php';
                            $client = new \DiarioGames\IGDB\IGDBClient($igdbConfig['client_id'], $igdbConfig['client_secret']);
                            $igdbResults = $client->searchGames($q);
                            foreach ($igdbResults as $ig) {
                                if (count($results) >= $limit) break;
                                $slug = $ig['slug'] ?? '';
                                $normalizedSlug = $slug ? \DiarioGames\IGDB\romanToDigits($slug) : '';
                                if (!$slug || isset($seen[$slug]) || isset($seen[$normalizedSlug])) continue;
                                if (\DiarioGames\IGDB\GameImporter::isExcluded($ig)) continue;
                                $igScreenshots = $ig['screenshots'] ?? [];
                                $igVideos = $ig['videos'] ?? [];
                                if (empty($igScreenshots) && empty($igVideos)) continue;
                                $seen[$normalizedSlug] = true;
                                $platformNames = [];
                                if (!empty($ig['platforms'])) {
                                    foreach ($ig['platforms'] as $p) {
                                        if (is_array($p) && !empty($p['name'])) {
                                            $platformNames[] = $p['name'];
                                        } elseif (is_string($p)) {
                                            $platformNames[] = $p;
                                        }
                                    }
                                }
                                $platformsStr = implode(', ', $platformNames);
                                $lower = mb_strtolower($platformsStr);
                                $allowedKeywords = ['pc', 'xbox', 'playstation', 'nintendo', 'android'];
                                $hasAllowed = false;
                                foreach ($allowedKeywords as $kw) {
                                    if (str_contains($lower, $kw)) { $hasAllowed = true; break; }
                                }
                                if (!$hasAllowed) continue;
                                $igYear = !empty($ig['first_release_date']) ? date('Y', $ig['first_release_date']) : '';
                                $results[] = [
                                    'slug' => $slug,
                                    'name' => $ig['name'] ?? $slug,
                                    'platforms' => \DiarioGames\IGDB\normalizePlatformNames(implode(', ', $platformNames)),
                                    'year' => $igYear,
                                    'hasSteam' => false,
                                    'exists' => false,
                                ];
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log('Steam search IGDB fallback error: ' . $e->getMessage());
                    }
                }

                return ['results' => $results, 'fromIgdb' => count($localResults) < 5];
            }
        ],
        [
            'pattern' => 'steam-stats-api/game/(:any)/data',
            'method' => 'GET',
            'action' => function (string $slug) {
                $db = new \Alv\SteamStats\SteamStatsDB();
                $game = $db->getGameBySlug($slug);
                if (!$game) {
                    return ['error' => 'not found'];
                }

                $appid = $game['appid'];
                $now = time();
                $day = 86400;

                $ranges = ['48h' => 2 * $day, '1w' => 7 * $day, '1m' => 30 * $day, '3m' => 90 * $day, '6m' => 180 * $day, '1y' => 365 * $day, 'max' => 0];

                $data = [
                    'game' => $game,
                    'current' => $db->getCurrentPlayers($appid),
                    'peak_24h' => $db->getPeakPlayers($appid, $now - $day),
                    'peak_3m' => $db->getPeakPlayers($appid, $now - 90 * $day),
                    'ranges' => [],
                ];

                foreach ($ranges as $key => $duration) {
                    $since = $duration > 0 ? $now - $duration : 0;
                    $points = $db->getPlayerCounts($appid, $since);
                    if (in_array($key, ['max', '1y', '6m', '3m'], true)) {
                        $step = max(1, intdiv(count($points), 1000));
                        $filtered = [];
                        foreach ($points as $i => $pt) {
                            if ($i % $step === 0) $filtered[] = $pt;
                        }
                        $points = $filtered;
                    }
                    $data['ranges'][$key] = $points;
                }

                return $data;
            }
        ],
        [
            'pattern' => 'steam-stats-api/collect',
            'method' => 'POST',
            'action' => function () {
                $key = get('key');
                $expectedKey = option('alv.steam-stats.warm-key');
                if ($expectedKey && $key !== $expectedKey) {
                    return ['error' => 'unauthorized'];
                }

                $collector = new \Alv\SteamStats\SteamStatsCollector(option('alv.steam-stats.api-key', ''));
                $stats = $collector->collect();

                kirby()->cache('alv/steam-stats.cache')
                    ->set('warm-last-run', ['value' => time(), 'timestamp' => time()]);

                return ['status' => 'ok', 'stats' => $stats];
            }
        ],
    ],
    'templates' => [
        'steam-stats' => __DIR__ . '/templates/steam-stats.php',
    ],
    'siteMethods' => [
        'steamStatsSettings' => function () {
            return [
                'api_key'          => option('alv.steam-stats.api-key', ''),
                'cache_ttl'        => (int) ($this->steam_stats_cache_ttl()->value() ?: 3600),
                'history_ttl'      => (int) ($this->steam_stats_history_ttl()->value() ?: 604800),
                'history_interval' => (int) ($this->steam_stats_history_interval()->value() ?: 21600),
            ];
        },
        'steamStats' => function () {
            $settings = $this->steamStatsSettings();
            return new Alv\SteamStats\SteamStats($settings);
        },
        'steamChartData' => function (string $slug) {
            $db = new \Alv\SteamStats\SteamStatsDB();
            $game = $db->getGameBySlug($slug);
            if (!$game) return null;

            $appid = $game['appid'];
            $now = time();
            $day = 86400;

            $ranges = ['48h' => 2 * $day, '1w' => 7 * $day, '1m' => 30 * $day, '3m' => 90 * $day, '6m' => 180 * $day, '1y' => 365 * $day, 'max' => 0];

            $data = [
                'game' => $game,
                'current' => $db->getCurrentPlayers($appid),
                'peak_24h' => $db->getPeakPlayers($appid, $now - $day),
                'peak_3m' => $db->getPeakPlayers($appid, $now - 90 * $day),
                'ranges' => [],
            ];

            foreach ($ranges as $key => $duration) {
                $since = $duration > 0 ? $now - $duration : 0;
                $points = $db->getPlayerCounts($appid, $since);
                if (in_array($key, ['max', '1y', '6m', '3m'], true)) {
                    $limit = 1000;
                    $step = max(1, intdiv(count($points), $limit));
                    $filtered = [];
                    foreach ($points as $i => $pt) {
                        if ($i % $step === 0) $filtered[] = $pt;
                    }
                    $points = $filtered;
                }
                $data['ranges'][$key] = $points;
            }

            return $data;
        },
    ],
    'commands' => [
        'steam-stats:collect' => [
            'description' => 'Collect current Steam player counts for all tracked games',
            'args' => [],
            'command' => function () {
                $collector = new \Alv\SteamStats\SteamStatsCollector(option('alv.steam-stats.api-key', ''));
                $stats = $collector->collect();
                echo "Scanned: {$stats['scanned']}, Updated: {$stats['updated']}, Errors: " . count($stats['errors']) . "\n";
                if (!empty($stats['errors'])) {
                    echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
                }
            }
        ],
        'steam-stats:backfill' => [
            'description' => 'Backfill historical player counts from steamcharts.com',
            'args' => [],
            'command' => function () {
                $collector = new \Alv\SteamStats\SteamStatsCollector(option('alv.steam-stats.api-key', ''));
                $stats = $collector->backfill(function ($msg) { echo $msg . "\n"; });
                echo "\nDone. Fetched: {$stats['fetched']}, Inserted: {$stats['inserted']}, Errors: " . count($stats['errors']) . "\n";
                if (!empty($stats['errors'])) {
                    echo "Failed appids: " . implode(', ', $stats['errors']) . "\n";
                }
            }
        ],
    ],
]);
