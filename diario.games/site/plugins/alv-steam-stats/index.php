<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/SteamStats.php';

App::plugin('alv/steam-stats', [
    'snippets' => [
        'steam-stats-tabs' => __DIR__ . '/snippets/steam-stats-tabs.php',
    ],
    'routes' => [
        [
            'pattern' => 'steam-stats',
            'action' => function () {
                return [
                    'template' => 'steam-stats',
                    'model' => [],
                ];
            }
        ]
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
