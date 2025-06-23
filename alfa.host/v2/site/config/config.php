<?php

return [
    'debug' => true,
    'fatal' => function ($kirby, $exception) {
        include $kirby->root('templates') . '/fatal.php';
    },
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
