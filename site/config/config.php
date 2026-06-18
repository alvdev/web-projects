<?php

return [
    'debug' => true,
    'url' => $_SERVER['HTTP_HOST'] ?? 'http://localhost:5173',

    'arnoson.kirby-vite' => [
        'entry' => 'assets/src/js/app.js',
        'outDir' => 'public/assets',
    ],
];
