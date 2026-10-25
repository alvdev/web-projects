<?php

declare(strict_types=1);

$fixturePath = $argv[1] ?? '';
if ($fixturePath === '') {
    fwrite(STDERR, "usage: php seed-releases.php <fixture-path>\n");
    exit(1);
}

function releaseGame(int $id, string $name, array $genres, string $date, ?string $localUrl = null, int $hypes = 50): array
{
    return [
        'igdb_id' => $id,
        'slug' => 'game-' . $id,
        'name' => $name,
        'release_date' => $date,
        'display_date' => $date,
        'month_key' => substr($date, 0, 7),
        'month_label' => $date,
        'hypes' => $hypes,
        'cover_url' => null,
        'platforms' => ['PC'],
        'genres' => $genres,
        'local_url' => $localUrl,
    ];
}

$recent = [];
for ($i = 0; $i < 35; $i++) {
    $recent[] = releaseGame(
        1000 + $i,
        'Recent ' . ($i + 1),
        $i % 2 === 0 ? ['RPG'] : ['Acción'],
        '2026-09-' . str_pad((string) (1 + $i % 28), 2, '0', STR_PAD_LEFT),
        null,
        100 - $i
    );
}

$upcoming = [];
for ($i = 0; $i < 5; $i++) {
    $upcoming[] = releaseGame(2000 + $i, 'Upcoming ' . ($i + 1), ['RPG'], '2026-11-' . str_pad((string) (1 + $i), 2, '0', STR_PAD_LEFT), null, 50);
}

$anticipated = [];
for ($i = 0; $i < 5; $i++) {
    $anticipated[] = releaseGame(3000 + $i, 'Anticipated ' . ($i + 1), ['Aventura'], '2027', null, 500 - $i);
}

@mkdir(dirname($fixturePath), 0775, true);
file_put_contents($fixturePath, json_encode([
    'recentlyReleased' => $recent,
    'upcoming' => $upcoming,
    'anticipated' => $anticipated,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
