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
    'routes' => [
        [
            'pattern' => 'newsletter-proxy',
            'method'  => 'POST',
            'action'  => function () {
                $response = Kirby\Http\Remote::post('https://alv.ipzmarketing.com/f/LjnuULUsaB8', [
                    'data' => $_POST,
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    ]
                ]);
                return $response->content();
            }
        ],
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
    // alv.dev: Implement ready function because of dotenv
    'ready' => function () {
        $debug = env('APP_DEBUG', false);
        return [
            'debug' => $debug,
            'fatal' => function ($kirby, $exception) {
                include $kirby->root('templates') . '/fatal.php';
            },
            'date.handler' => 'intl',
            // Caching configuration for maximum performance
            'cache' => [
                'pages' => [
                    'active' => $debug === false,
                    'type'   => 'file',
                ],
                'gallery' => [
                    'active' => true,
                    'type'   => 'file',
                ]
            ],
            'content.cache' => [
                'active' => $debug === false,
                'type'   => 'file',
            ],
            'uuid' => [
                'cache' => [
                    'active' => $debug === false,
                    'type'   => 'file',
                ]
            ],
            'email' => [
                /* 'transport' => [
                    'type' => 'smtp',
                    'host' => 'localhost',
                    'port' => 1025,
                    'security' => false,
                    'username' => 'test@test.com'
                ], */
                'transport' => [
                    'type' => 'smtp',
                    'host' => env('EMAIL_HOST'),
                    'port' => 465,
                    'security' => true,
                    'auth' => true,
                    'username' => env('EMAIL_USERNAME'),
                    'password' => env('EMAIL_PASSWORD'),
                ]
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
                    'jobs' => [
                        'label' => 'Trabajos',
                        'icon' => 'document',
                        'link' => 'pages/trabajos',
                        'current' => function (): bool {
                            $path = App::instance()->path();
                            return Str::contains($path, 'pages/trabajos');
                        }
                    ],
                    'game' => [
                        'label' => 'Juegos',
                        'icon' => 'document',
                        'link' => 'pages/juegos',
                        'current' => function (): bool {
                            $path = App::instance()->path();
                            return Str::contains($path, 'pages/juego');
                        }
                    ],
                    'gallery' => [
                        'label' => 'Galería',
                        'icon' => 'images',
                        'link' => 'pages/galeria',
                        'current' => function (): bool {
                            $path = App::instance()->path();
                            return Str::contains($path, 'pages/galeria');
                        }
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
            'sylvaninjule.embed' => [
                'nocookie' => true,
            ],
            'thumbs' => [
                'srcsets' => [
                    'default' => [
                        '300w'  => ['width' => 300],
                        '400w'  => ['width' => 400],
                        '600w'  => ['width' => 600],
                        '800w'  => ['width' => 800],
                        '900w'  => ['width' => 900],
                        '1200w' => ['width' => 1200],
                        '1800w' => ['width' => 1800]
                    ],
                    'avif' => [
                        '300w'  => ['width' => 300, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '400w'  => ['width' => 400, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '600w'  => ['width' => 600, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '800w'  => ['width' => 800, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '900w'  => ['width' => 900, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '1200w' => ['width' => 1200, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '1800w' => ['width' => 1800, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35]
                    ],
                    'webp' => [
                        '300w'  => ['width' => 300, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '400w'  => ['width' => 400, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '600w'  => ['width' => 600, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '800w'  => ['width' => 800, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '900w'  => ['width' => 900, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '1200w' => ['width' => 1200, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '1800w' => ['width' => 1800, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35]
                    ],
                    'avif-mobile' => [
                        '300w'  => ['width' => 300, 'height' => 500, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '600w'  => ['width' => 600, 'height' => 1000, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '900w'  => ['width' => 900, 'height' => 1500, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
                        '1200w' => ['width' => 1200, 'height' => 2000, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35]
                    ],
                    'webp-mobile' => [
                        '300w'  => ['width' => 300, 'height' => 500, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '600w'  => ['width' => 600, 'height' => 1000, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '900w'  => ['width' => 900, 'height' => 1500, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
                        '1200w' => ['width' => 1200, 'height' => 2000, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35]
                    ],
                    'gallery-avif' => [
                        '300w'  => ['width' => 300, 'format' => 'avif', 'quality' => 60],
                        '400w'  => ['width' => 400, 'format' => 'avif', 'quality' => 60],
                        '600w'  => ['width' => 600, 'format' => 'avif', 'quality' => 60],
                        '800w'  => ['width' => 800, 'format' => 'avif', 'quality' => 60],
                        '900w'  => ['width' => 900, 'format' => 'avif', 'quality' => 60],
                        '1200w' => ['width' => 1200, 'format' => 'avif', 'quality' => 60],
                    ],
                    'gallery-webp' => [
                        '300w'  => ['width' => 300, 'format' => 'webp', 'quality' => 80],
                        '400w'  => ['width' => 400, 'format' => 'webp', 'quality' => 80],
                        '600w'  => ['width' => 600, 'format' => 'webp', 'quality' => 80],
                        '800w'  => ['width' => 800, 'format' => 'webp', 'quality' => 80],
                        '900w'  => ['width' => 900, 'format' => 'webp', 'quality' => 80],
                        '1200w' => ['width' => 1200, 'format' => 'webp', 'quality' => 80],
                    ],
                    'gallery-default' => [
                        '300w'  => ['width' => 300],
                        '400w'  => ['width' => 400],
                        '600w'  => ['width' => 600],
                        '800w'  => ['width' => 800],
                        '900w'  => ['width' => 900],
                        '1200w' => ['width' => 1200],
                    ],
                    'book-avif' => [
                        '300w'  => ['width' => 300, 'height' => 450, 'crop' => true, 'format' => 'avif', 'quality' => 70],
                        '400w'  => ['width' => 400, 'height' => 600, 'crop' => true, 'format' => 'avif', 'quality' => 70],
                        '600w'  => ['width' => 600, 'height' => 900, 'crop' => true, 'format' => 'avif', 'quality' => 70],
                        '800w'  => ['width' => 800, 'height' => 1200, 'crop' => true, 'format' => 'avif', 'quality' => 70],
                        '900w'  => ['width' => 900, 'height' => 1350, 'crop' => true, 'format' => 'avif', 'quality' => 70],
                    ],
                    'book-webp' => [
                        '300w'  => ['width' => 300, 'height' => 450, 'crop' => true, 'format' => 'webp', 'quality' => 80],
                        '400w'  => ['width' => 400, 'height' => 600, 'crop' => true, 'format' => 'webp', 'quality' => 80],
                        '600w'  => ['width' => 600, 'height' => 900, 'crop' => true, 'format' => 'webp', 'quality' => 80],
                        '800w'  => ['width' => 800, 'height' => 1200, 'crop' => true, 'format' => 'webp', 'quality' => 80],
                        '900w'  => ['width' => 900, 'height' => 1350, 'crop' => true, 'format' => 'webp', 'quality' => 80],
                    ],
                    'book-default' => [
                        '300w'  => ['width' => 300, 'height' => 450, 'crop' => true],
                        '400w'  => ['width' => 400, 'height' => 600, 'crop' => true],
                        '600w'  => ['width' => 600, 'height' => 900, 'crop' => true],
                        '800w'  => ['width' => 800, 'height' => 1200, 'crop' => true],
                        '900w'  => ['width' => 900, 'height' => 1350, 'crop' => true],
                    ],
                ]
            ],
        ];
    }
];
