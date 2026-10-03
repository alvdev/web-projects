<?php

use Kirby\Cms\App;

@include_once __DIR__ . '/classes/Releases.php';

App::plugin('alv/releases', [
    'snippets' => [
        'home-upcoming-releases' => __DIR__ . '/snippets/home-upcoming-releases.php',
    ],
    'templates' => [
        'lanzamientos' => __DIR__ . '/templates/lanzamientos.php',
    ],
    'routes' => [
        [
            'pattern' => 'lanzamientos',
            'method' => 'GET',
            'action' => function () {
                return \Kirby\Cms\Page::factory([
                    'slug' => 'lanzamientos',
                    'template' => 'lanzamientos',
                    'content' => [
                        'title' => 'Lanzamientos',
                        'metaDescription' => 'Calendario de próximos lanzamientos de videojuegos en PC y consolas: fechas confirmadas y los títulos más esperados.',
                    ],
                ])->render();
            },
        ],
        [
            'pattern' => 'lanzamientos-warm',
            'method' => 'POST',
            'action' => function () {
                $key = get('key');
                $expectedKey = option('alv.releases.warm-key');
                if ($expectedKey && $key !== $expectedKey) {
                    return ['error' => 'unauthorized'];
                }

                $counts = site()->releases()->warm();

                return ['status' => 'ok', 'counts' => $counts];
            },
        ],
    ],
    'siteMethods' => [
        'releases' => function () {
            $igdb = option('igdb') ?? [];

            return new \Alv\Releases\Releases([
                'client_id' => $igdb['client_id'] ?? '',
                'client_secret' => $igdb['client_secret'] ?? '',
                'cache_ttl' => (int) option('alv.releases.cache-ttl', 21600),
                'notable_hypes' => (int) option('alv.releases.notable-hypes', 20),
                'fixture_file' => (string) option('alv.releases.fixture-file', ''),
            ]);
        },
    ],
]);
