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
                $limit = 15;

                $extractSteamAppId = function ($websites): ?int {
                    if (!is_array($websites)) return null;
                    foreach ($websites as $w) {
                        $url = is_array($w) ? ($w['url'] ?? '') : $w;
                        if (preg_match('/store\.steampowered\.com\/app\/(\d+)/i', $url, $m)) {
                            return (int) $m[1];
                        }
                    }
                    return null;
                };

                // Normalize string for search matching: lowercase, strip punctuation
                $normalize = function ($s) {
                    return preg_replace('/[^a-z0-9\s]/', '', mb_strtolower(trim($s)));
                };
                $nq = $normalize($q);

                // 1. Search local pages
                $localResults = [];
                $games = site()->index()->filterBy('intendedTemplate', 'game');
                foreach ($games as $game) {
                    if (count($localResults) >= $limit) break;
                    if ($game->content()->get('Screenshots')->isEmpty() && $game->content()->get('Videos')->isEmpty()) continue;
                    $title = $game->title()->value();
                    if (!str_contains($normalize($title), $nq)) continue;

                    $slug = $game->slug();
                    $igdbId = (int) $game->content()->get('IgdbId')->value();
                    $hasSteam = $db->getGameBySlug($slug) !== null;
                    // Also check by IgdbId in case slug has --N suffix
                    if (!$hasSteam && $igdbId) {
                        $hasSteam = $db->getGameByIgdbId($igdbId) !== null;
                    }
                    $releaseDate = $game->content()->get('ReleaseDate')->value();
                    $year = '';
                    if (preg_match('/^\d{4}/', $releaseDate, $m)) {
                        $year = $m[0];
                    }
                    $coverUrl = $game->cover() ? $game->cover()->url() : '';
                    $localResults[] = [
                        'slug' => $slug,
                        'name' => $title,
                        'cover' => $coverUrl,
                        'platforms' => \DiarioGames\IGDB\normalizePlatformNames($game->content()->get('Platforms')->value()),
                        'year' => $year,
                        'hasSteam' => $hasSteam,
                        'exists' => true,
                        'igdbId' => $igdbId,
                    ];
                }

                // Deduplicate local results by name: prefer non-duplicate slug, then Steam-verified
                $localByName = [];
                foreach ($localResults as $r) {
                    $key = $normalize($r['name']);
                    $existing = $localByName[$key] ?? null;
                    if (!$existing) {
                        $localByName[$key] = $r;
                    } else {
                        $isOldDup = preg_match('/--\d+$/', $existing['slug']);
                        $isNewDup = preg_match('/--\d+$/', $r['slug']);
                        if ($isOldDup && !$isNewDup) {
                            $localByName[$key] = $r;
                        } elseif ($isOldDup === $isNewDup && !$existing['hasSteam'] && $r['hasSteam']) {
                            $localByName[$key] = $r;
                        }
                    }
                }
                $localResults = array_values($localByName);
                $results = $localResults;

                // Build seen index from local results: slugs + IgdbIds
                $seenSlugs = [];
                $seenIgdbIds = [];
                foreach ($localResults as $r) {
                    $seenSlugs[$r['slug']] = true;
                    if ($r['igdbId']) {
                        $seenIgdbIds[$r['igdbId']] = $r;
                    }
                }

                // 2. Fall back to IGDB if few distinct local results
                if (count($localResults) < 5) {
                    try {
                        $igdbConfig = kirby()->option('igdb');
                        if (!empty($igdbConfig['client_id']) && !empty($igdbConfig['client_secret'])) {
                            $root = dirname(__DIR__, 3);
                            require_once $root . '/site/plugins/alv-igdb/classes/helpers.php';
                            require_once $root . '/site/plugins/alv-igdb/classes/IGDBClient.php';
                            require_once $root . '/site/plugins/alv-igdb/classes/GameImporter.php';
                            $client = new \DiarioGames\IGDB\IGDBClient($igdbConfig['client_id'], $igdbConfig['client_secret']);
                            $igdbRaw = $client->searchGames($q);

                            // Annotate each IGDB result with Steam and local info
                            $annotated = [];
                            foreach ($igdbRaw as $ig) {
                                $slug = $ig['slug'] ?? '';
                                $normalizedSlug = $slug ? \DiarioGames\IGDB\romanToDigits($slug) : '';
                                if (!$slug) continue;
                                if (\DiarioGames\IGDB\GameImporter::isExcluded($ig)) continue;
                                $screenshots = $ig['screenshots'] ?? [];
                                $videos = $ig['videos'] ?? [];
                                if (empty($screenshots) && empty($videos)) continue;

                                $igdbId = $ig['id'] ?? null;
                                $appid = $extractSteamAppId($ig['websites'] ?? []);
                                $steamInDb = $appid !== null && $db->getGameByAppId($appid) !== null;

                                // Check if already imported locally (by IgdbId)
                                $localMatch = $igdbId && isset($seenIgdbIds[$igdbId]) ? $seenIgdbIds[$igdbId] : null;

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
                                $name = $ig['name'] ?? $slug;

                                $annotated[] = [
                                    'ig' => $ig,
                                    'slug' => $slug,
                                    'normalizedSlug' => $normalizedSlug,
                                    'igdbId' => $igdbId,
                                    'appid' => $appid,
                                    'steamInDb' => $steamInDb,
                                    'name' => $name,
                                    'year' => $igYear,
                                    'platforms' => \DiarioGames\IGDB\normalizePlatformNames(implode(', ', $platformNames)),
                                    'localMatch' => $localMatch,
                                ];
                            }

                            // Group by name, dedup: prefer local > steam-in-db > has-steam-link > first
                            $grouped = [];
                            foreach ($annotated as $entry) {
                                $nameKey = $normalize($entry['name']);
                                $grouped[$nameKey][] = $entry;
                            }

                            foreach ($grouped as $nameKey => $group) {
                                // Skip if a local result with a matching name already exists
                                $alreadyLocal = false;
                                foreach ($localResults as $lr) {
                                    if ($normalize($lr['name']) === $nameKey) {
                                        $alreadyLocal = true;
                                        break;
                                    }
                                }
                                if ($alreadyLocal) continue;

                                if (count($group) > 1) {
                                    // Priority: local match > steam in DB > has steam link > first
                                    usort($group, function ($a, $b) {
                                        $prio = function ($e) {
                                            if ($e['localMatch']) return 0;
                                            if ($e['steamInDb']) return 1;
                                            if ($e['appid'] !== null) return 2;
                                            return 3;
                                        };
                                        return $prio($a) <=> $prio($b);
                                    });
                                    $group = [$group[0]];
                                }

                                foreach ($group as $entry) {
                                    if (isset($seenSlugs[$entry['slug']]) || isset($seenSlugs[$entry['normalizedSlug']])) continue;
                                    $seenSlugs[$entry['slug']] = true;
                                    $seenSlugs[$entry['normalizedSlug']] = true;

                                    if ($entry['localMatch']) {
                                        // Already imported — reference the local page directly
                                        $results[] = $entry['localMatch'];
                                    } else {
                                        $hasSteam = $entry['steamInDb'];
                                        $coverUrl = !empty($entry['ig']['cover']['url']) ? str_replace('t_thumb', 't_cover_big', $entry['ig']['cover']['url']) : '';
                                        $results[] = [
                                            'slug' => $entry['slug'],
                                            'name' => $entry['name'],
                                            'cover' => $coverUrl,
                                            'platforms' => $entry['platforms'],
                                            'year' => $entry['year'],
                                            'hasSteam' => $hasSteam,
                                            'exists' => false,
                                        ];
                                    }
                                }
                            }

                            // Sort: Steam-verified first, then existing, then alphabetical
                            usort($results, function ($a, $b) {
                                if ($a['hasSteam'] !== $b['hasSteam']) {
                                    return $b['hasSteam'] <=> $a['hasSteam'];
                                }
                                if ($a['exists'] !== $b['exists']) {
                                    return $b['exists'] <=> $a['exists'];
                                }
                                return strcmp($a['name'], $b['name']);
                            });

                            $results = array_slice($results, 0, $limit);
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
                    $page = page('games/' . $slug);
                    if ($page) {
                        $igdbId = (int) $page->content()->get('IgdbId')->value();
                        if ($igdbId) {
                            $game = $db->getGameByIgdbId($igdbId);
                        }
                    }
                }
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
        [
            'pattern' => 'media/steam-capsule/(:any).jpg',
            'method' => 'GET',
            'action' => function (string $slug) {
                $file = kirby()->root('content') . '/games/' . $slug . '/steam-capsule.jpg';
                if (!file_exists($file)) {
                    return new \Kirby\Http\Response('', 'text/plain', 404);
                }
                $mime = mime_content_type($file) ?: 'image/jpeg';
                return new \Kirby\Http\Response(
                    file_get_contents($file),
                    $mime,
                    200,
                    ['Cache-Control' => 'public, max-age=86400']
                );
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
            if (!$game) {
                // Fallback: try to find by IgdbId for pages with duplicate slugs
                $page = page('games/' . $slug);
                if ($page) {
                    $igdbId = (int) $page->content()->get('IgdbId')->value();
                    if ($igdbId) {
                        $game = $db->getGameByIgdbId($igdbId);
                    }
                }
            }
            if (!$game) return null;

            $appid = $game['appid'];
            $now = time();
            $day = 86400;

            $current = $db->getCurrentPlayers($appid);
            $peak24h = $db->getPeakPlayers($appid, $now - $day);
            $peak3m = $db->getPeakPlayers($appid, $now - 90 * $day);

            // Fall back to scraped cache when DB has no data
            if (($current ?? 0) === 0) {
                try {
                    $scrapedCache = kirby()->cache('alv/steam-stats.cache')->get('stats-most-played');
                    if (is_array($scrapedCache) && isset($scrapedCache['value'])) {
                        foreach ($scrapedCache['value'] as $entry) {
                            if ((int)($entry['appid'] ?? 0) === $appid && ($entry['current_players'] ?? 0) > 0) {
                                $current = (int)$entry['current_players'];
                                $peak24h = (int)($entry['peak_today'] ?? $peak24h);
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // Fall back to live API when DB has no data
            if ($current === null || $peak24h === null) {
                $stats = site()->steamStats();
                $live = $stats->getLivePlayerCount($appid);
                if ($live > 0) {
                    if ($current === null) $current = $live;
                    if ($peak24h === null) $peak24h = $live;
                    if ($peak3m === null) $peak3m = $live;
                }
            }

            $ranges = ['48h' => 2 * $day, '1w' => 7 * $day, '1m' => 30 * $day, '3m' => 90 * $day, '6m' => 180 * $day, '1y' => 365 * $day, 'max' => 0];

            $data = [
                'game' => $game,
                'current' => $current ?? 0,
                'peak_24h' => $peak24h ?? 0,
                'peak_3m' => $peak3m ?? 0,
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
