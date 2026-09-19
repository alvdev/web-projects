<?php

declare(strict_types=1);

$contentDir = $argv[1] ?? '';
if ($contentDir === '') {
    fwrite(STDERR, "usage: php seed-content.php <content-dir>\n");
    exit(1);
}

@mkdir($contentDir, 0775, true);
file_put_contents($contentDir . '/site.txt', "Title: E2E Site\n");

$games = [
    '2024/03/e2e-game' => [
        'Title' => 'E2E Game',
        'ReleaseDate' => '2024-03-15',
        'IgdbId' => '1',
        'Platforms' => 'PC (Microsoft Windows)',
        'Genres' => 'RPG',
        'Tags' => 'Acción',
        'Screenshots' => 'shot_1',
        'Websites' => '1:https://store.steampowered.com/app/990001/',
    ],
    '2024/04/e2e-second' => [
        'Title' => 'E2E Second',
        'ReleaseDate' => '2024-04-01',
        'IgdbId' => '2',
        'Screenshots' => 'shot_1',
    ],
];

foreach ($games as $path => $fields) {
    $dir = $contentDir . '/games/' . $path;
    @mkdir($dir, 0775, true);

    $parts = ['Title: ' . $fields['Title'], 'Template: game'];
    foreach ($fields as $key => $value) {
        if ($key === 'Title') continue;
        $parts[] = $key . ': ' . $value;
    }

    file_put_contents($dir . '/game.txt', implode("\n\n----\n\n", $parts) . "\n");
}
