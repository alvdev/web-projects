<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/SteamStats.php';

App::plugin('alv/steam-stats', [
    'siteMethods' => [
        'steamStatsSettings' => function () {
            $settings = $this->steam_stats_settings();
            if ($settings->isEmpty()) {
                return [];
            }
            return $settings->toObject()->toArray();
        },
        'steamStats' => function () {
            $settings = $this->steamStatsSettings();
            return new Alv\SteamStats\SteamStats($settings);
        },
    ],
]);
