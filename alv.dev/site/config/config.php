<?php

/**
 * The config file is optional. It accepts a return array with config options
 * Note: Never include more than one return statement, all options go within this single return array
 * In this example, we set debugging to true, so that errors are displayed onscreen. 
 * This setting must be set to false in production.
 * All config options: https://getkirby.com/docs/reference/system/options
 */
return [
    'debug' => true,
    'author.seo-audit' => [
        'option' => 'alv dev'
    ],
    'languages' => true,
    'panel' => [
        'favicon' => 'assets/favicon.png',
        'menu' => [
            'site',
            'blog' => [
                'label' => 'Blog',
                'icon' => 'blog',
                'link' => 'pages/blog'
            ],
            '-',
            'users',
            'forms' => [
                'label' => 'Forms',
                'icon' => 'form',
                'link' => 'pages/forms'
            ],
            'languages',
            'system',
        ]
    ],
    'tobimori.dreamform' => [
        'guards' => [
            'available' => ['csrf', 'honeypot', 'turnstile', /* 'ratelimit' */],
            'honeypot.availableFields' => ['website', 'email', 'name', 'url', 'birthdate'],
        ],
    ],
    'routes' => [
        [
            'pattern' => 'blog/(:any)',
            'action' => function ($cat) {
                if ($page = page('blog/' . $cat)) {
                    return $page;
                } else {
                    return page('blog')->render([
                        'cat' => $cat
                    ]);
                }
            }
        ],
        [
            'pattern' => 'blog/tag/(:any)',
            'action' => function ($tag) {
                if ($page = page('blog/' . $tag)) {
                    return $page;
                } else {
                    return page('blog')->render([
                        'tag' => $tag
                    ]);
                }
            }
        ]
    ]
];