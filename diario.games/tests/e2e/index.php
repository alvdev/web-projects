<?php

require __DIR__ . '/kirby/bootstrap.php';

$kirby = new \Kirby\Cms\App([
    'roots' => [
        'index' => __DIR__,
        'content' => __DIR__ . '/content',
        'cache' => __DIR__ . '/cache',
        'media' => __DIR__ . '/media',
        'accounts' => __DIR__ . '/accounts',
        'sessions' => __DIR__ . '/sessions',
    ],
    'options' => [
        'debug' => false,
    ],
]);

echo $kirby->render();
