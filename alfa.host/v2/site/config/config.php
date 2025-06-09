<?php

return [
    'debug' => true,
    'panel' => [
        'menu' => [
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
