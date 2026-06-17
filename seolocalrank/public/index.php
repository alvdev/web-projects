<?php

declare(strict_types=1);

/**
 * Front controller for the seolocalrank app.
 *
 * Routing rules:
 *   /                  -> renders the home view
 *   /assets/<js|css>/<safe-path>  -> static file (realpath-confined)
 *   anything else      -> 404
 */

require __DIR__ . '/../vendor/autoload.php';

use SeoLocalRank\Env;
use SeoLocalRank\Regions;

Env::load(dirname(__DIR__) . '/.env');

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch (true) {
    case $uri === '/' && $method === 'GET':
        render_home();
        return;

    case (bool) preg_match('#^/assets/(?:js|css)/[\w\-.]+(?:\.[\w\-]+)*$#', $uri):
        serve_static($uri);
        return;

    default:
        http_response_code(404);
        echo 'Not found';
        return;
}

// ---------- helpers ----------------------------------------------

function render_home(): void
{
    $regions = Regions::all();
    $mapsKey = Env::get('GOOGLE_MAPS_API_KEY') ?? '';
    $appUrl  = Env::get('APP_URL') ?? 'http://localhost:8000';

    require __DIR__ . '/../src/Views/layout.php';
}

/**
 * Serve a static asset only if its resolved real path still lives
 * inside the public/assets/ directory. Guards against path traversal.
 */
function serve_static(string $uri): void
{
    $assetsRoot = realpath(__DIR__ . '/assets');
    if ($assetsRoot === false) {
        http_response_code(500);
        return;
    }

    $absolute = realpath(__DIR__ . $uri);
    if ($absolute === false || !is_file($absolute)) {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    // Defence in depth: confirm the resolved file is under assets/.
    $prefix = $assetsRoot . DIRECTORY_SEPARATOR;
    if (!str_starts_with($absolute, $prefix)) {
        http_response_code(404);
        echo 'Not found';
        return;
    }

    $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'css' => 'text/css; charset=utf-8',
        'js'  => 'application/javascript; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
        default => 'application/octet-stream',
    };
    header("Content-Type: {$mime}");
    // Force the browser to revalidate cached assets on every load so
    // changes to app.js / app.css take effect immediately.
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($absolute);
}
