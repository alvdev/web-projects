<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/SteamStats.php';

App::plugin('alv/steam-stats', [
    'snippets' => [
        'steam-stats-tabs' => __DIR__ . '/snippets/steam-stats-tabs.php',
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
        ]
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
    ],
]);
