<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/plugins/taxonomylist/blueprints.yaml',
    'modified' => 1665250844,
    'size' => 948,
    'data' => [
        'name' => 'Taxonomy List',
        'type' => 'plugin',
        'slug' => 'taxonomylist',
        'version' => '1.3.5',
        'description' => 'With the **TaxonomyList plugin** you can easily create list of **taxonomy** items such as **tags**, **categories**, etc.',
        'icon' => 'tag',
        'author' => [
            'name' => 'Team Grav',
            'email' => 'devs@getgrav.org',
            'url' => 'http://getgrav.org'
        ],
        'homepage' => 'https://github.com/getgrav/grav-plugin-taxonomylist',
        'demo' => 'http://demo.getgrav.org/blog-skeleton',
        'keywords' => 'taxonomylist, plugin, taxonomy, list, tags, categories',
        'bugs' => 'https://github.com/getgrav/grav-plugin-taxonomylist/issues',
        'license' => 'MIT',
        'dependencies' => [
            0 => [
                'name' => 'grav',
                'version' => '>=1.6.0'
            ]
        ],
        'form' => [
            'validation' => 'strict',
            'fields' => [
                'enabled' => [
                    'type' => 'toggle',
                    'label' => 'Plugin status',
                    'highlight' => 1,
                    'default' => 0,
                    'options' => [
                        1 => 'Enabled',
                        0 => 'Disabled'
                    ],
                    'validate' => [
                        'type' => 'bool'
                    ]
                ],
                'route' => [
                    'type' => 'text',
                    'label' => 'Route to blog',
                    'placeholder' => '/blog',
                    'validate' => [
                        'pattern' => '/([a-z\\-_]+/?)+'
                    ]
                ]
            ]
        ]
    ]
];
