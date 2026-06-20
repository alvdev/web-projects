<?php

namespace DiarioGames\IGDB;

function slugify(string $text): string
{
    return strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($text)), '-'));
}

function igdbImageUrl(string $imageId, string $size = 'cover_big'): string
{
    return "https://images.igdb.com/igdb/image/upload/t_{$size}/{$imageId}.jpg";
}

function downloadImage(string $url, string $destPath): bool
{
    $dir = dirname($destPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $ch = curl_init($url);
    $fp = fopen($destPath, 'wb');
    curl_setopt_array($ch, [
        CURLOPT_FILE => $fp,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'diario.games/1.0',
    ]);
    $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    fclose($fp);
    if (!$success) {
        unlink($destPath);
        return false;
    }
    return true;
}
