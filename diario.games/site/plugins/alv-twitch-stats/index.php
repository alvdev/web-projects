<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/TwitchClient.php';
@include_once __DIR__ . '/classes/TwitchTrackerClient.php';
@include_once __DIR__ . '/classes/TwitchStatsDB.php';
@include_once __DIR__ . '/classes/TwitchStatsCollector.php';
@include_once __DIR__ . '/classes/TwitchStats.php';

App::plugin('alv/twitch-stats', [
    'snippets' => [
        'twitch-stats-tabs' => __DIR__ . '/snippets/twitch-stats-tabs.php',
    ],
    'templates' => [
        'twitch-stats' => __DIR__ . '/templates/twitch-stats.php',
    ],
    'blueprints' => [
        'twitch-stats' => __DIR__ . '/blueprints/twitch-stats.yml',
    ],
    'options' => [
        'cache-ttl' => 300,
        'tracker-ttl' => 21600,
        'history-ttl' => 7776000,
        'warm-key' => '',
        'fixture-file' => '',
    ],
    'routes' => [
        [
            'pattern' => 'twitch-stats',
            'method' => 'GET',
            'action' => function () {
                return \Kirby\Cms\Page::factory([
                    'slug' => 'twitch-stats',
                    'template' => 'twitch-stats',
                    'content' => [
                        'title' => 'Twitch Charts',
                    ],
                ])->render();
            }
        ],
        [
            'pattern' => 'twitch-stats-warm',
            'method' => 'POST',
            'action' => function () {
                $key = get('key');
                $expectedKey = option('alv.twitch-stats.warm-key');
                if ($expectedKey && $key !== $expectedKey) {
                    return ['error' => 'unauthorized'];
                }

                $stats = site()->twitchStats();
                $stats->getTopGames(100);
                $stats->getTopStreamers(100);
                $stats->getTopSpanish(100);

                $igdb = option('igdb') ?? [];
                $collector = new \Alv\TwitchStats\TwitchStatsCollector(
                    new \Alv\TwitchStats\TwitchClient($igdb['client_id'] ?? '', $igdb['client_secret'] ?? ''),
                    new \Alv\TwitchStats\TwitchStatsDB(),
                    (int) option('alv.twitch-stats.history-ttl', 7776000)
                );

                $snapshot = $collector->snapshot();
                $pruned = $collector->prune();

                kirby()->cache('alv/twitch-stats.cache')->set('warm-last-run', time(), 30);

                return ['status' => 'ok', 'snapshot' => $snapshot, 'pruned' => $pruned];
            }
        ],
    ],
    'siteMethods' => [
        'twitchStatsSettings' => function () {
            $igdb = option('igdb') ?? [];

            return [
                'client_id' => $igdb['client_id'] ?? '',
                'client_secret' => $igdb['client_secret'] ?? '',
                'cache_ttl' => (int) ($this->twitch_stats_cache_ttl()->value() ?: option('alv.twitch-stats.cache-ttl', 300)),
                'tracker_ttl' => (int) ($this->twitch_stats_tracker_ttl()->value() ?: option('alv.twitch-stats.tracker-ttl', 21600)),
                'history_ttl' => (int) ($this->twitch_stats_history_ttl()->value() ?: option('alv.twitch-stats.history-ttl', 7776000)),
                'tracker_limit' => (int) option('alv.twitch-stats.tracker-limit', 20),
                'fixture_file' => (string) option('alv.twitch-stats.fixture-file', ''),
            ];
        },
        'twitchStats' => function () {
            return new \Alv\TwitchStats\TwitchStats($this->twitchStatsSettings());
        },
    ],
]);
