<?php

return [
    'debug' => true,
    'fatal' => function ($kirby, $exception) {
        include $kirby->root('templates') . '/fatal.php';
    },
    'panel' => [
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
