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
            'pattern' => 'steam-stats-api/rankings',
            'method' => 'GET',
            'action' => function () {
                $chunk = max(0, (int) get('chunk', 0));
                $ttl = (int) option('alv.steam-stats.charts-ttl', 900);
                $db = new \Alv\SteamStats\SteamStatsDB();

                $chunkRow = $db->getChartChunk($chunk);
                $fetchedAt = (int) ($chunkRow['fetched_at'] ?? 0);
                $status = $chunkRow['status'] ?? 'missing';
                $fresh = $status === 'fresh' && $fetchedAt > 0 && (time() - $fetchedAt) <= $ttl;

                $totalEntries = $db->countChartEntries();
                $maxChunk = $totalEntries > 0 ? (int) ceil($totalEntries / \Alv\SteamStats\SteamStatsDB::CHART_CHUNK_SIZE) - 1 : -1;

                $refreshPending = false;
                if (!$fresh && ($maxChunk < 0 || $chunk <= $maxChunk)) {
                    $lockFile = sys_get_temp_dir() . '/steamdb-charts-browser.lock';
                    $locked = file_exists($lockFile) && (time() - filemtime($lockFile)) < 300;

                    $cooldownOk = true;
                    try {
                        $cache = kirby()->cache('alv/steam-stats.cache');
                        $lastSpawn = $cache->get('charts-spawn');
                        $cooldownOk = !(is_array($lastSpawn) && (time() - ($lastSpawn['value'] ?? 0)) < 60);
                    } catch (\Throwable $e) {}

                    if ($locked) {
                        $refreshPending = true;
                    } elseif ($cooldownOk) {
                        $spawner = option('alv.steam-stats.charts-spawner');

                        if (is_callable($spawner)) {
                            $spawner($chunk);
                        } else {
                            $php = PHP_BINDIR . '/php';
                            if (!is_executable($php)) $php = PHP_BINARY;
                            if (!is_executable($php)) $php = 'php';

                            $script = dirname(__DIR__, 3) . '/scripts/collect-steam-stats.php';
                            $cmd = 'nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($script)
                                . ' charts ' . $chunk . ' > /dev/null 2>&1 &';
                            @exec($cmd);
                        }

                        try {
                            kirby()->cache('alv/steam-stats.cache')
                                ->set('charts-spawn', ['value' => time(), 'timestamp' => time()]);
                        } catch (\Throwable $e) {}

                        $refreshPending = true;
                    }
                }

                $rows = $db->getChartEntriesByChunk($chunk);

                return [
                    'chunk'           => $chunk,
                    'status'          => $status,
                    'fresh'           => $fresh,
                    'fetched_at'      => $fetchedAt,
                    'ttl'             => $ttl,
                    'refresh_pending' => $refreshPending,
                    'rows'            => $rows,
                ];
            }
        ],
        [
            'pattern' => 'steam-stats-api/search',
            'method' => 'GET',
            'action' => function () {
                $q = strtolower(trim(get('q', '')));
                $source = get('source', '');
                if ($q === '') {
                    return ['results' => [], 'fromIgdb' => false];
                }

                $cache = kirby()->cache('alv/steam-stats.cache');
                $db = new \Alv\SteamStats\SteamStatsDB();
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

                // 2. IGDB long-tail fallback: separate request so the first paint stays fast
                if ($source === 'igdb') {
                    if (mb_strlen($nq) < 3) {
                        return ['results' => [], 'fromIgdb' => false];
                    }

                    $cacheKey = 'search-igdb-' . md5($nq);
                    $cached = $cache->get($cacheKey);
                    if ($cached !== null) {
                        return $cached;
                    }

                    $results = [];
                    try {
                        $igdbConfig = kirby()->option('igdb');
                        if (!empty($igdbConfig['client_id']) && !empty($igdbConfig['client_secret'])) {
                            $client = new \DiarioGames\IGDB\IGDBClient($igdbConfig['client_id'], $igdbConfig['client_secret']);
                            $igdbRaw = $client->searchGames($q);

                            $localIgdbIds = [];
                            foreach (site()->index()->filterBy('intendedTemplate', 'game') as $game) {
                                $id = (int) $game->content()->get('IgdbId')->value();
                                if ($id) $localIgdbIds[$id] = true;
                            }

                            // Annotate each IGDB result with Steam and local info
                            $annotated = [];
                            foreach ($igdbRaw as $ig) {
                                $slug = $ig['slug'] ?? '';
                                $normalizedSlug = $slug ? \DiarioGames\IGDB\romanToDigits($slug) : '';
                                if (!$slug) continue;
                                if (\DiarioGames\IGDB\GameImporter::isExcluded($ig)) continue;
                                if (isset($localIgdbIds[$ig['id'] ?? 0])) continue;

                                $screenshots = $ig['screenshots'] ?? [];
                                $videos = $ig['videos'] ?? [];
                                if (empty($screenshots) && empty($videos)) continue;

                                $igdbId = $ig['id'] ?? null;
                                $appid = $extractSteamAppId($ig['websites'] ?? []);
                                $steamInDb = $appid !== null && $db->getGameByAppId($appid) !== null;

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
                                    'slug' => $slug,
                                    'normalizedSlug' => $normalizedSlug,
                                    'igdbId' => $igdbId,
                                    'appid' => $appid,
                                    'steamInDb' => $steamInDb,
                                    'name' => $name,
                                    'year' => $igYear,
                                    'platforms' => \DiarioGames\IGDB\normalizePlatformNames(implode(', ', $platformNames)),
                                ];
                            }

                            // Fetch cover image IDs for IGDB entries
                            $coverByGameId = [];
                            $needCovers = [];
                            foreach ($annotated as $entry) {
                                if (!empty($entry['igdbId'])) $needCovers[] = $entry['igdbId'];
                            }
                            if (!empty($needCovers)) {
                                $needCovers = array_values(array_unique($needCovers));
                                $coversData = $client->fetchCovers($needCovers);
                                foreach ($coversData as $c) {
                                    if (!empty($c['game']) && !empty($c['image_id'])) {
                                        $coverByGameId[$c['game']] = $c['image_id'];
                                    }
                                }
                            }

                            // Group by name, dedup: prefer steam-in-db > has-steam-link > first
                            $grouped = [];
                            foreach ($annotated as $entry) {
                                $nameKey = $normalize($entry['name']);
                                $grouped[$nameKey][] = $entry;
                            }

                            $seenSlugs = [];
                            foreach ($grouped as $group) {
                                if (count($group) > 1) {
                                    usort($group, function ($a, $b) {
                                        $prio = function ($e) {
                                            if ($e['steamInDb']) return 0;
                                            if ($e['appid'] !== null) return 1;
                                            return 2;
                                        };
                                        return $prio($a) <=> $prio($b);
                                    });
                                    $group = [$group[0]];
                                }

                                foreach ($group as $entry) {
                                    if (isset($seenSlugs[$entry['slug']]) || isset($seenSlugs[$entry['normalizedSlug']])) continue;
                                    $seenSlugs[$entry['slug']] = true;
                                    $seenSlugs[$entry['normalizedSlug']] = true;

                                    $coverImgId = $coverByGameId[$entry['igdbId']] ?? null;
                                    $coverUrl = $coverImgId ? \DiarioGames\IGDB\igdbImageUrl($coverImgId, 'cover_big') : '';
                                    $results[] = [
                                        'slug' => $entry['slug'],
                                        'name' => $entry['name'],
                                        'cover' => $coverUrl,
                                        'platforms' => $entry['platforms'],
                                        'year' => $entry['year'],
                                        'hasSteam' => $entry['steamInDb'],
                                        'exists' => false,
                                        'igdbId' => $entry['igdbId'],
                                    ];
                                }
                            }

                            // Sort: Steam-verified first, then alphabetical
                            usort($results, function ($a, $b) {
                                if ($a['hasSteam'] !== $b['hasSteam']) {
                                    return $b['hasSteam'] <=> $a['hasSteam'];
                                }
                                return strcmp($a['name'], $b['name']);
                            });

                            $results = array_slice($results, 0, $limit);
                        }
                    } catch (\Throwable $e) {
                        error_log('Steam search IGDB fallback error: ' . $e->getMessage());
                    }

                    $response = ['results' => $results, 'fromIgdb' => true];
                    $cache->set($cacheKey, $response, 21600);
                    return $response;
                }

                $cacheKey = 'search-v2-' . md5($nq);
                $cached = $cache->get($cacheKey);
                if ($cached !== null) {
                    return $cached;
                }

                // 1. Search local pages
                $localResults = [];
                $localBySlug = [];
                $localByIgdbId = [];
                $games = site()->index()->filterBy('intendedTemplate', 'game');
                foreach ($games as $game) {
                    $title = $game->title()->value();
                    $slug = $game->slug();
                    $igdbId = (int) $game->content()->get('IgdbId')->value();
                    $releaseDate = $game->content()->get('ReleaseDate')->value();
                    $year = '';
                    if (preg_match('/^\d{4}/', $releaseDate, $m)) {
                        $year = $m[0];
                    }
                    $entry = [
                        'slug' => $slug,
                        'name' => $title,
                        'cover' => $game->cover() ? $game->cover()->url() : '',
                        'platforms' => \DiarioGames\IGDB\normalizePlatformNames($game->content()->get('Platforms')->value()),
                        'year' => $year,
                        'hasSteam' => false,
                        'exists' => true,
                        'igdbId' => $igdbId,
                    ];

                    // Keep an index for dedupe even when the page is not searchable
                    $localBySlug[$slug] = $entry;
                    if ($igdbId) $localByIgdbId[$igdbId] = $entry;

                    if (count($localResults) >= $limit) continue;
                    if ($game->content()->get('Screenshots')->isEmpty() && $game->content()->get('Videos')->isEmpty()) continue;
                    if (!str_contains($normalize($title), $nq)) continue;

                    $entry['hasSteam'] = $db->getGameBySlug($slug) !== null;
                    // Also check by IgdbId in case slug has --N suffix
                    if (!$entry['hasSteam'] && $igdbId) {
                        $entry['hasSteam'] = $db->getGameByIgdbId($igdbId) !== null;
                    }
                    $localResults[] = $entry;
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
                $results = array_values($localByName);

                $seenNames = [];
                $seenSlugs = [];
                $seenAppids = [];
                foreach ($results as $r) {
                    $seenNames[$normalize($r['name'])] = true;
                    $seenSlugs[$r['slug']] = true;
                }

                $addResult = function (array $entry, ?int $appid = null) use (&$results, &$seenNames, &$seenSlugs, &$seenAppids, $normalize, $limit) {
                    if (count($results) >= $limit) return;
                    $nameKey = $normalize($entry['name']);
                    if (isset($seenNames[$nameKey]) || isset($seenSlugs[$entry['slug']])) return;
                    if ($appid !== null && isset($seenAppids[$appid])) return;
                    $seenNames[$nameKey] = true;
                    $seenSlugs[$entry['slug']] = true;
                    if ($appid !== null) $seenAppids[$appid] = true;
                    $results[] = $entry;
                };

                $trackedAppids = array_flip(array_filter(array_map('intval', $db->getAllAppids())));

                // 2. Tracked Steam games from the local DB (instant)
                if (count($results) < $limit) {
                    foreach ($db->searchGames($q) as $row) {
                        $appid = (int) $row['appid'];
                        $slug = (string) $row['slug'];

                        if (isset($localBySlug[$slug])) {
                            $local = $localBySlug[$slug];
                            $local['hasSteam'] = $appid > 0;
                            $addResult($local, $appid > 0 ? $appid : null);
                            continue;
                        }
                        $igdbId = (int) ($row['igdb_id'] ?? 0);
                        if ($igdbId && isset($localByIgdbId[$igdbId])) {
                            $local = $localByIgdbId[$igdbId];
                            $local['hasSteam'] = $appid > 0;
                            $addResult($local, $appid > 0 ? $appid : null);
                            continue;
                        }

                        $year = '';
                        if (!empty($row['year_month']) && preg_match('/^(\d{4})/', $row['year_month'], $m)) {
                            $year = $m[0];
                        }
                        $addResult([
                            'slug' => $slug,
                            'name' => (string) $row['name'],
                            'cover' => $appid > 0 ? 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/' . $appid . '/library_600x900.jpg' : '',
                            'platforms' => '',
                            'year' => $year,
                            'hasSteam' => true,
                            'exists' => false,
                            'igdbId' => $igdbId ?: null,
                        ], $appid > 0 ? $appid : null);
                    }
                }

                // 3. SteamDB chart entries: popular games not necessarily tracked yet (instant)
                if (count($results) < $limit) {
                    foreach ($db->searchChartEntries($q, $limit) as $row) {
                        $appid = (int) $row['appid'];
                        if ($appid <= 0) continue;
                        $name = (string) $row['name'];
                        $slug = \DiarioGames\IGDB\romanToDigits(\DiarioGames\IGDB\slugify($name));

                        if (isset($localBySlug[$slug])) {
                            $local = $localBySlug[$slug];
                            $local['hasSteam'] = isset($trackedAppids[$appid]);
                            $addResult($local, $appid);
                            continue;
                        }
                        $addResult([
                            'slug' => $slug,
                            'name' => $name,
                            'cover' => 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/' . $appid . '/library_600x900.jpg',
                            'platforms' => '',
                            'year' => '',
                            'hasSteam' => isset($trackedAppids[$appid]),
                            'exists' => false,
                            'igdbId' => null,
                        ], $appid);
                    }
                }

                $response = ['results' => $results, 'fromIgdb' => false];
                $cache->set($cacheKey, $response, 300);
                return $response;
            }
        ],
        [
            'pattern' => 'steam-stats-api/game/(:any)/data',
            'method' => 'GET',
            'action' => function (string $slug) {
                $db = new \Alv\SteamStats\SteamStatsDB();
                $game = $db->getGameBySlug($slug);
                if (!$game) {
                    $yearMonth = \DiarioGames\IGDB\resolveGamePath($slug);
                $page = $yearMonth ? page('games/' . $yearMonth . '/' . $slug) : null;
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

            $ranges = ['48h' => 'hourly', '1w' => 'hourly', '1m' => 'daily', '3m' => 'daily', '6m' => 'daily', '1y' => 'weekly', '3y' => 'monthly', '6y' => 'monthly', '9y' => 'monthly', '12y' => 'monthly', 'max' => 'auto'];

                $data = [
                    'game' => $game,
                    'current' => $db->getCurrentPlayers($appid),
                    'peak_24h' => $db->getPeakPlayers($appid, $now - $day),
                    'peak_3m' => $db->getPeakPlayers($appid, $now - 90 * $day),
                    'peak_all_time' => $db->getGamePeak($appid) ?? 0,
                    'ranges' => [],
                ];

                $allPoints = $db->getPlayerCounts($appid, 0);
                $dataAgeSeconds = !empty($allPoints) ? $now - $allPoints[0]['timestamp'] : 0;
                $durations = ['48h' => 2, '1w' => 7, '1m' => 30, '3m' => 90, '6m' => 180, '1y' => 365, '3y' => 3*365, '6y' => 6*365, '9y' => 9*365, '12y' => 12*365, 'max' => 0];

                foreach ($ranges as $key => $method) {
                    $since = $durations[$key] > 0 ? $now - $durations[$key] * $day : 0;

                    if ($method === 'hourly') {
                        $points = $db->getPlayerCounts($appid, $since);
                    } elseif ($method === 'daily') {
                        $points = $db->getDailyPeakCounts($appid, $since);
                        $currentBoundary = strtotime('today 00:00:00');
                    } elseif ($method === 'weekly') {
                        $points = $db->getWeeklyPeakCounts($appid, $since);
                        $currentBoundary = $now - ($now % 604800);
                    } elseif ($method === 'monthly') {
                        $points = $db->getMonthlyPeakCounts($appid, $since);
                        $currentBoundary = strtotime('first day of this month 00:00:00');
                    } else {
                        if ($dataAgeSeconds <= 7 * $day) {
                            $points = $db->getPlayerCounts($appid, 0);
                        } elseif ($dataAgeSeconds <= 30 * $day) {
                            $points = $db->getDailyPeakCounts($appid, 0);
                            $currentBoundary = strtotime('today 00:00:00');
                        } elseif ($dataAgeSeconds <= 365 * $day) {
                            $points = $db->getWeeklyPeakCounts($appid, 0);
                            $currentBoundary = $now - ($now % 604800);
                        } else {
                            $points = $db->getMonthlyPeakCounts($appid, 0);
                            $currentBoundary = strtotime('first day of this month 00:00:00');
                        }
                    }

                    if (isset($currentBoundary) && count($points) > 1) {
                        $last = end($points);
                        if ($last['timestamp'] >= $currentBoundary) {
                            array_pop($points);
                        }
                        unset($currentBoundary);
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
            'pattern' => 'steam-stats-api/import-game',
            'method' => 'GET|POST',
            'action' => function () {
                $slug = get('slug', '');
                if (!$slug) {
                    return ['error' => 'slug required'];
                }

                $importId = bin2hex(random_bytes(8));
                $cache = kirby()->cache('alv/steam-stats.cache');
                $cache->set("import-progress.{$importId}", ['phase' => 'start', 'text' => 'Iniciando importación...']);

                $igdbConfig = kirby()->option('igdb');
                $clientId = $igdbConfig['client_id'] ?? getenv('IGDB_CLIENT_ID');
                $clientSecret = $igdbConfig['client_secret'] ?? getenv('IGDB_CLIENT_SECRET');

                if (!$clientId || !$clientSecret) {
                    $cache->set("import-progress.{$importId}", [
                        'error' => true,
                        'text' => 'Error: credenciales de IGDB no configuradas',
                    ]);
                    return ['id' => $importId, 'error' => 'igdb_credentials'];
                }

                try {
                    $igdbConfig = kirby()->option('igdb');
                    $clientId = $igdbConfig['client_id'] ?? getenv('IGDB_CLIENT_ID');
                    $clientSecret = $igdbConfig['client_secret'] ?? getenv('IGDB_CLIENT_SECRET');

                    if (!$clientId || !$clientSecret) {
                        $cache->set("import-progress.{$importId}", [
                            'error' => true,
                            'text' => 'Error: credenciales de IGDB no configuradas',
                        ]);
                        return ['id' => $importId, 'error' => 'igdb_credentials'];
                    }

                    $cache->set("import-progress.{$importId}", [
                        'phase' => 'start',
                        'text'  => 'Conectando con IGDB...',
                    ]);

                    $script = kirby()->root('index') . '/scripts/import-game-cli.php';
                    exec(sprintf(
                        'php %s %s %s > /dev/null 2>&1 &',
                        escapeshellarg($script),
                        escapeshellarg($slug),
                        escapeshellarg($importId)
                    ));

                    return ['id' => $importId, 'ok' => true];
                } catch (\Throwable $e) {
                    $cache->set("import-progress.{$importId}", [
                        'error' => true,
                        'text'  => 'Error: ' . $e->getMessage(),
                    ]);
                    return ['id' => $importId, 'error' => 'exception'];
                }
            }
        ],
        [
            'pattern' => 'steam-stats-api/import-progress/(:any)',
            'method' => 'GET',
            'action' => function (string $importId) {
                $cache = kirby()->cache('alv/steam-stats.cache');
                $progress = $cache->get("import-progress.{$importId}");
                if (!$progress) {
                    return ['phase' => 'unknown', 'text' => 'Esperando...'];
                }
                return $progress;
            }
        ],
        [
            'pattern' => 'media/steam-capsule/(:any).jpg',
            'method' => 'GET',
            'action' => function (string $slug) {
                $yearMonth = \DiarioGames\IGDB\resolveGamePath($slug);
                $file = $yearMonth ? kirby()->root('content') . '/games/' . $yearMonth . '/' . $slug . '/steam-capsule.jpg' : null;
                if (!$file || !file_exists($file)) {
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
                $yearMonth = \DiarioGames\IGDB\resolveGamePath($slug);
                $page = $yearMonth ? page('games/' . $yearMonth . '/' . $slug) : null;
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

            $ranges = ['48h' => 'hourly', '1w' => 'hourly', '1m' => 'daily', '3m' => 'daily', '6m' => 'daily', '1y' => 'weekly', '3y' => 'monthly', '6y' => 'monthly', '9y' => 'monthly', '12y' => 'monthly', 'max' => 'auto'];

            $peakAllTime = $db->getGamePeak($appid);
            $peakTimestamp = $db->getGamePeakTimestamp($appid);
            $peakInfo = $db->getPeakTimestamp($appid);
            if ($peakInfo && $peakInfo['count'] > ($peakAllTime ?? 0)) {
                $peakAllTime = $peakInfo['count'];
            }
            $peakAgeLabel = 'Max. historico';
            $peakTs = $peakTimestamp ?? ($peakInfo['timestamp'] ?? null);
            if ($peakTs !== null && $peakTs > 0) {
                $diffDays = (int)(($now - $peakTs) / 86400);

                if ($diffDays === 1) {
                    $peakAgeLabel = 'Max. hace 1 dia';
                } elseif ($diffDays < 7) {
                    $peakAgeLabel = 'Max. hace ' . $diffDays . ' dias';
                } elseif ($diffDays < 14) {
                    $peakAgeLabel = 'Max. hace 1 semana';
                } elseif ($diffDays < 30) {
                    $weeks = (int)($diffDays / 7);
                    $peakAgeLabel = 'Max. hace ' . $weeks . ' semanas';
                } elseif ($diffDays < 60) {
                    $peakAgeLabel = 'Max. hace 1 mes';
                } elseif ($diffDays < 365) {
                    $months = (int)($diffDays / 30);
                    $peakAgeLabel = 'Max. hace ' . $months . ' meses';
                } else {
                    $totalMonths = (int)($diffDays / 30);
                    $years = (int)($totalMonths / 12);
                    $remainder = $totalMonths % 12;

                    $yearText = $years === 1 ? '1 año' : $years . ' años';

                    if ($remainder === 0) {
                        $peakAgeLabel = 'Max. hace ' . $yearText;
                    } elseif ($remainder === 6) {
                        $peakAgeLabel = 'Max. hace ' . $yearText . ' y medio';
                    } elseif ($remainder === 1) {
                        $peakAgeLabel = 'Max. hace ' . $yearText . ' y 1 mes';
                    } else {
                        $peakAgeLabel = 'Max. hace ' . $yearText . ' y ' . $remainder . ' meses';
                    }
                }
            }

            $data = [
                'game' => $game,
                'current' => $current ?? 0,
                'peak_24h' => $peak24h ?? 0,
                'peak_3m' => $peak3m ?? 0,
                'peak_all_time' => $peakAllTime ?? 0,
                'peak_all_time_age' => $peakAgeLabel,
                'ranges' => [],
            ];

            // Pre-fetch all raw points for max-range adaptive selection
            $allPoints = $db->getPlayerCounts($appid, 0);
            $earliestTs = !empty($allPoints) ? $allPoints[0]['timestamp'] : null;
            $dataAgeSeconds = $earliestTs !== null ? $now - $earliestTs : 0;

            $durations = ['48h' => 2, '1w' => 7, '1m' => 30, '3m' => 90, '6m' => 180, '1y' => 365, '3y' => 3*365, '6y' => 6*365, '9y' => 9*365, '12y' => 12*365, 'max' => 0];

            // Build each range with the appropriate aggregation method
            foreach ($ranges as $key => $method) {
                $since = $durations[$key] > 0 ? $now - $durations[$key] * $day : 0;

                if ($method === 'hourly') {
                    $points = $db->getPlayerCounts($appid, $since);
                } elseif ($method === 'daily') {
                    $points = $db->getDailyPeakCounts($appid, $since);
                    $currentBoundary = strtotime('today 00:00:00');
                } elseif ($method === 'weekly') {
                    $points = $db->getWeeklyPeakCounts($appid, $since);
                    $currentBoundary = $now - ($now % 604800);
                } elseif ($method === 'monthly') {
                    $points = $db->getMonthlyPeakCounts($appid, $since);
                    $currentBoundary = strtotime('first day of this month 00:00:00');
                } else {
                    // auto: pick aggregation based on data age
                    $maxSince = 0;
                    if ($dataAgeSeconds <= 7 * $day) {
                        $points = $db->getPlayerCounts($appid, $maxSince);
                    } elseif ($dataAgeSeconds <= 30 * $day) {
                        $points = $db->getDailyPeakCounts($appid, $maxSince);
                        $currentBoundary = strtotime('today 00:00:00');
                    } elseif ($dataAgeSeconds <= 365 * $day) {
                        $points = $db->getWeeklyPeakCounts($appid, $maxSince);
                        $currentBoundary = $now - ($now % 604800);
                    } else {
                        $points = $db->getMonthlyPeakCounts($appid, $maxSince);
                        $currentBoundary = strtotime('first day of this month 00:00:00');
                    }
                }

                // Drop the last point if it falls in the current incomplete period
                if (isset($currentBoundary) && count($points) > 1) {
                    $last = end($points);
                    if ($last['timestamp'] >= $currentBoundary) {
                        array_pop($points);
                    }
                    unset($currentBoundary);
                }

                $data['ranges'][$key] = $points;
            }

            // Determine which tabs to show based on game release date (or earliest data)
            $releaseDateStr = null;
            $yearMonth = $game['year_month'] ?? \DiarioGames\IGDB\resolveGamePath($slug);
            $kirbyPage = $yearMonth ? page('games/' . $yearMonth . '/' . $slug) : null;
            if ($kirbyPage) {
                $releaseDateStr = $kirbyPage->content()->get('ReleaseDate')->value();
            }

            $gameAgeDays = 0;
            if ($releaseDateStr && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $releaseDateStr, $rm)) {
                $releaseTs = strtotime("{$rm[1]}-{$rm[2]}-{$rm[3]}");
                if ($releaseTs && $releaseTs < $now) {
                    $gameAgeDays = (int)(($now - $releaseTs) / $day);
                }
            }

            $dataAgeDays = $earliestTs !== null ? (int)(($now - $earliestTs) / $day) : 0;
            if ($gameAgeDays <= 0) {
                $gameAgeDays = $dataAgeDays;
            }
            // Use whichever goes further back: release date or actual data
            $effectiveAgeDays = max($gameAgeDays, $dataAgeDays);

            // Tab display rules: hide tabs that span longer than the game has existed
            $tabMinAges = ['48h' => 2, '1w' => 7, '1m' => 30, '3m' => 90, '6m' => 180, '1y' => 365, '3y' => 3*365, '6y' => 6*365, '9y' => 9*365, '12y' => 12*365, 'max' => 0];
            $data['available_tabs'] = [];
            foreach ($tabMinAges as $label => $minDays) {
                if ($effectiveAgeDays >= $minDays) {
                    $data['available_tabs'][] = $label;
                }
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
