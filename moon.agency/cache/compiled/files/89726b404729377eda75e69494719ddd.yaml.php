<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/accounts/test.yaml',
    'modified' => 1667252844,
    'size' => 649,
    'data' => [
        'state' => 'enabled',
        'email' => 'test@test.com',
        'fullname' => 'test',
        'title' => 'test',
        'language' => 'es',
        'content_editor' => 'default',
        'twofa_enabled' => false,
        'twofa_secret' => 'DNOSUWIDQGB6GXOIHKAXMTIW2YTDSWY2',
        'avatar' => [
            
        ],
        'hashed_password' => '$2y$10$GItteZnnYB4UbrUGTZYb..nM8yKD2gviuEhMvqwU6dfFux3Tq8YI.',
        'access' => [
            'site' => [
                'login' => true
            ],
            'admin' => [
                'login' => true,
                'configuration' => [
                    'system' => false,
                    'site' => false,
                    'media' => false,
                    'security' => false,
                    'info' => false,
                    'pages' => true,
                    'users' => false
                ],
                'pages' => true,
                'maintenance' => true,
                'statistics' => true,
                'plugins' => true,
                'users' => [
                    'create' => true,
                    'read' => true,
                    'update' => true,
                    'delete' => false,
                    'list' => true
                ]
            ]
        ]
    ]
];
