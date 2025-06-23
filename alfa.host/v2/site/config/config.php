<?php

return [
    'debug' => true,
    'fatal' => function ($kirby, $exception) {
        include $kirby->root('templates') . '/fatal.php';
    },
    'thumbs' => [
        'presets' => [
            'default' => [
                'format' => 'avif',
                'quality' => 60,
            ],
        ],
        'srcsets' => [
            'default' => [
                '800w' => ['width' => 800, 'format' => 'avif', 'quality' => 60],
                '1024w' => ['width' => 1024, 'format' => 'avif', 'quality' => 60],
                '1440w' => ['width' => 1440, 'format' => 'avif', 'quality' => 60],
                '2048w' => ['width' => 2048, 'format' => 'avif', 'quality' => 60]
            ],
        ]
    ],
    'panel' => [
        'css' => 'assets/css/panel.css',
        'menu' => [
            'viewSite' => [
                'label' => [
                    'en' => 'View site',
                    'es' => 'Ver sitio'
                ],
                'icon' => 'open',
                'link' => '../',
                'target' => '_blank'
            ],
            '-',
            'site' => [
                'label' => 'Dashboard'
            ],
            'landings' => [
                'label' => 'Landings',
                'icon' => 'page',
                'link' => 'pages/blog',
            ],
            'blog' => [
                'label' => 'Blog',
                'icon' => 'blog',
                'link' => 'pages/blog',
            ],
            '-',
            'siteSettings' => [
                'label' => 'Site',
                'icon' => 'global'
            ],
            'forms' => [
                'label' => 'Forms',
                'icon' => 'forms',
            ],
            '-',
            'users',
            'languages',
            'system',
        ]
    ]
];
