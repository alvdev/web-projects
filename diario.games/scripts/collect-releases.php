<?php
/**
 * Warm the upcoming-releases cache.
 *
 * Usage:
 *   php scripts/collect-releases.php
 *
 * Cron: run every 6 hours.
 */

require __DIR__ . '/../kirby/bootstrap.php';

if (empty($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = getenv('RELEASES_WEB_HOST') ?: getenv('STEAM_STATS_WEB_HOST') ?: 'localhost:8888';
}

$kirby = new \Kirby\Cms\App([
    'cli' => true,
]);

$igdb = option('igdb') ?? [];
$releases = new \Alv\Releases\Releases([
    'client_id' => $igdb['client_id'] ?? '',
    'client_secret' => $igdb['client_secret'] ?? '',
    'cache_ttl' => (int) option('alv.releases.cache-ttl', 21600),
    'fixture_file' => (string) option('alv.releases.fixture-file', ''),
]);

$counts = $releases->warm();
echo "Releases warmed: {$counts['recentlyReleased']} recently released, {$counts['upcoming']} upcoming, {$counts['anticipated']} anticipated.\n";
