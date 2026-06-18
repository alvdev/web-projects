<?php

return [
    'debug' => true,
    'url' => $_SERVER['HTTP_HOST'] ?? 'http://localhost:5173',

    'arnoson.kirby-vite' => [
        'entry' => 'assets/src/js/app.js',
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
        'ai.enabled' => false,
        'review.enabled' => false,
        'sitemap.exclude' => ['error'],
        'excludeTemplates' => ['default'],
        'excludeStatus' => ['draft'],
    ],

    'routes' => [
        [
            'pattern' => 'genre/(:any)',
            'action'  => function ($genre) {
                return page('games')->render(['genre' => $genre]);
            },
        ],
    ],
];
