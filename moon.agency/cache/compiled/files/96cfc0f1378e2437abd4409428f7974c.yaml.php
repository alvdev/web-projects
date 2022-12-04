<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/config/plugins/form.yaml',
    'modified' => 1668793387,
    'size' => 714,
    'data' => [
        'enabled' => true,
        'built_in_css' => true,
        'inline_css' => true,
        'refresh_prevention' => true,
        'client_side_validation' => true,
        'inline_errors' => true,
        'files' => [
            'multiple' => false,
            'limit' => 10,
            'destination' => 'self@',
            'avoid_overwriting' => false,
            'random_name' => false,
            'filesize' => 0,
            'accept' => [
                0 => 'image/*'
            ]
        ],
        'recaptcha' => [
            'version' => '2-checkbox',
            'theme' => 'dark',
            'site_key' => NULL,
            'secret_key' => NULL
        ],
        'turnstile' => [
            'widget' => 'managed',
            'theme' => 'light',
            'site_key' => NULL,
            'secret_key' => NULL
        ],
        'basic_captcha' => [
            'type' => 'characters',
            'chars' => [
                'length' => 6,
                'font' => 'zxx-noise.ttf',
                'bg' => '#cccccc',
                'text' => '#333333',
                'size' => 24,
                'start_x' => 5,
                'start_y' => 30,
                'box_width' => 135,
                'box_height' => 40
            ],
            'math' => [
                'min' => 1,
                'max' => 12,
                'operators' => [
                    0 => '+',
                    1 => '-',
                    2 => '*'
                ]
            ]
        ]
    ]
];
