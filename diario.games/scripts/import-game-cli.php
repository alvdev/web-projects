#!/usr/bin/env php
<?php
/**
 * Background import worker. Invoked by the steam-stats-api/import-game route.
 * Writes progress phases to Kirby cache (import-progress.{id}).
 *
 * Usage: php scripts/import-game-cli.php <slug> <importId>
 */

$slug = $argv[1] ?? '';
$importId = $argv[2] ?? '';

if (!$slug || !$importId) {
    exit(1);
}

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('STEAM_STATS_WEB_HOST') ?: 'localhost:8888';
}

require __DIR__ . '/../kirby/bootstrap.php';

$kirby = new \Kirby\Cms\App(['cli' => true]);

$igdbConfig = $kirby->option('igdb');
$clientId = $igdbConfig['client_id'] ?? getenv('IGDB_CLIENT_ID');
$clientSecret = $igdbConfig['client_secret'] ?? getenv('IGDB_CLIENT_SECRET');

if (!$clientId || !$clientSecret) {
    exit(1);
}

require_once $kirby->root('index') . '/site/plugins/alv-igdb/classes/helpers.php';
require_once $kirby->root('index') . '/site/plugins/alv-igdb/classes/IGDBClient.php';
require_once $kirby->root('index') . '/site/plugins/alv-igdb/classes/GameImporter.php';

$cache = $kirby->cache('alv/steam-stats.cache');

$phases = [
    'metadata'    => 'Obteniendo información del juego...',
    'platforms'   => 'Verificando plataformas compatibles...',
    'genres'      => 'Categorizando géneros del juego...',
    'description' => 'Traduciendo descripción al español...',
    'screenshots' => 'Descargando capturas de pantalla...',
    'videos'      => 'Obteniendo videos del juego...',
    'saving'      => 'Guardando página del juego...',
    'cover'       => 'Descargando portada...',
    'hero'        => 'Descargando imagen principal...',
    'steam'       => 'Sincronizando datos de Steam...',
];

try {
    $cache->set("import-progress.{$importId}", [
        'phase' => 'start',
        'text'  => 'Conectando con IGDB...',
    ]);

    $client = new \DiarioGames\IGDB\IGDBClient($clientId, $clientSecret);
    $importer = new \DiarioGames\IGDB\GameImporter($client);

    ob_start();
    $result = $importer->importBySlugWithFallback($slug, function ($phase) use ($cache, $importId, $phases) {
        $cache->set("import-progress.{$importId}", [
            'phase' => $phase,
            'text'  => $phases[$phase] ?? $phase,
        ]);
    });
    ob_end_clean();

    if ($result) {
        $cache->set("import-progress.{$importId}", [
            'ready' => true,
            'slug'  => $result,
            'text'  => '¡Listo! Redirigiendo...',
        ]);
    } else {
        $cache->set("import-progress.{$importId}", [
            'error' => true,
            'text'  => 'No se pudo importar el juego',
        ]);
    }
} catch (\Throwable $e) {
    $cache->set("import-progress.{$importId}", [
        'error' => true,
        'text'  => 'Error: ' . $e->getMessage(),
    ]);
}
