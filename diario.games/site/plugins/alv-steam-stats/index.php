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
            'pattern' => 'api/steam-stats/update-history',
            'method' => 'POST',
            'action' => function () {
                $stats = site()->steamStats();
                $stats->updatePlayerHistory();
                return ['status' => 'ok'];
            }
        ]
    ],
    'hooks' => [
        'route:before' => function () {
            // Update history every 6 hours on page load
            $cache = kirby()->cache('alv/steam-stats.cache');
            $lastUpdate = $cache->get('history-last-update');
            $now = time();
            
            if ($lastUpdate === null || ($now - $lastUpdate['timestamp']) > 21600) {
                $stats = site()->steamStats();
                $stats->updatePlayerHistory();
                $cache->set('history-last-update', ['timestamp' => $now]);
            }
        }
    ],
    'templates' => [
        'steam-stats' => __DIR__ . '/templates/steam-stats.php',
    ],
    'siteMethods' => [
        'steamStatsSettings' => function () {
            return [
                'api_key'          => $this->steam_stats_api_key()->value() ?? '',
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
