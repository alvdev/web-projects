<?php

/**
 * The config file is optional. It accepts a return array with config options
 * Note: Never include more than one return statement, all options go within this single return array
 * In this example, we set debugging to true, so that errors are displayed onscreen. 
 * This setting must be set to false in production.
 * All config options: https://getkirby.com/docs/reference/system/options
 */

use Kirby\Cms\App;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

return [
    // alv.dev: Implement ready function because of dotenv
    'ready' => function () {
        return [
            'debug' => env('APP_DEBUG', true),
            'fatal' => function ($kirby, $exception) {
                include $kirby->root('templates') . '/fatal.php';
            },
            'date.handler' => 'intl',
            'email' => [
                'transport' => [
                    'type' => 'smtp',
                    'host' => 'localhost',
                    'port' => 1025,
                    'security' => false,
                    'username' => 'test@test.com'
                ],
                /* 'transport' => [
                    'type' => 'smtp',
                    'host' => env('EMAIL_HOST'),
                    'port' => 465,
                    'security' => true,
                    'auth' => true,
                    'username' => env('EMAIL_USERNAME'),
                    'password' => env('EMAIL_PASSWORD'),
                ] */
            ],
            'panel' => [
                'favicon' => 'assets/favicon.png',
                'menu' => [
                    'site' => [
                        'current' => function (): bool {
                            $links = ['site'];

                            $path  = App::instance()->path();
                            return A::every($links, fn($link) => Str::contains($path, $link));
                        },
                    ],
                    'blog' => [
                        'label' => 'Blog',
                        'icon' => 'document',
                        'link' => 'pages/blog',
                        'current' => function (): bool {
                            $path = App::instance()->path();
                            return Str::contains($path, 'pages/blog');
                        }
                    ],
                    'pictures' => [
                        'label' => 'Galería',
                        'icon' => 'images',
                        'link' => 'pages/gallery',
                        'current' => function (): bool {
                            $path = App::instance()->path();
                            return Str::contains($path, 'pages/gallery');
                        }
                    ],
                    '-',
                    'users',
                    'forms' => [
                        'label' => 'Forms',
                        'icon' => 'form',
                        'link' => 'pages/forms',
                        'current' => function (): bool {
                            $path = App::instance()->path();
                            return Str::contains($path, 'pages/forms');
                        }
                    ],
                    'system',
                ]
            ],
            'mauricerenck.PexelsImageField' => [
                'apiKey' => env('PEXELS_APIKEY'),
                'downloadSize' => 'landscape',
                'images' => 7
            ],
            'tobimori.dreamform' => [
                'guards' => [
                    'available' => ['csrf', 'honeypot', 'turnstile', /* 'ratelimit' */],
                    'honeypot.availableFields' => ['website', 'url', 'birthdate'],
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
            ],
            'thumbs' => [
                'srcsets' => [
                    'default' => [
                        '300w'  => ['width' => 300],
                        '600w'  => ['width' => 600],
                        '900w'  => ['width' => 900],
                        '1200w' => ['width' => 1200],
                        '1800w' => ['width' => 1800]
                    ],
                    'avif' => [
                        '300w'  => ['width' => 300, 'format' => 'avif'],
                        '600w'  => ['width' => 600, 'format' => 'avif'],
                        '900w'  => ['width' => 900, 'format' => 'avif'],
                        '1200w' => ['width' => 1200, 'format' => 'avif'],
                        '1800w' => ['width' => 1800, 'format' => 'avif']
                    ],
                    'webp' => [
                        '300w'  => ['width' => 300, 'format' => 'webp'],
                        '600w'  => ['width' => 600, 'format' => 'webp'],
                        '900w'  => ['width' => 900, 'format' => 'webp'],
                        '1200w' => ['width' => 1200, 'format' => 'webp'],
                        '1800w' => ['width' => 1800, 'format' => 'webp']
                    ],
                ]
            ],
        ];
    }
];
