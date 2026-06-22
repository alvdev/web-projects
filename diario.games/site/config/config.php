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

    'cache' => [
        'alv.steam-stats' => [
            'type' => 'file',
            'active' => true,
        ],
    ],

    'routes' => [
        [
            'pattern' => 'genre/(:any)',
            'action'  => function ($genre) {
                return page('genre')->render(['genreSlug' => $genre]);
            },
        ],
        [
            'pattern' => 'games/(:any)',
            'method' => 'GET',
            'action' => function (string $slug) {
                $page = page('games/' . $slug);
                if ($page) return $page;

                $config = kirby()->option('igdb');
                if (empty($config['client_id']) || empty($config['client_secret'])) {
                    return page('error');
                }

                try {
                    require_once kirby()->root('base') . '/site/igdb/helpers.php';
                    require_once kirby()->root('base') . '/site/igdb/IGDBClient.php';
                    require_once kirby()->root('base') . '/site/igdb/GameImporter.php';

                    $client = new \DiarioGames\IGDB\IGDBClient($config['client_id'], $config['client_secret']);
                    $importer = new \DiarioGames\IGDB\GameImporter($client);
                    $result = $importer->importBySlug($slug);

                    if ($result) {
                        go('/games/' . $slug);
                    }
                } catch (\Throwable $e) {
                    // fall through to 404
                }

                return page('error');
            }
        ],
    ],
];
