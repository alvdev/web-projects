<?php

namespace DiarioGames\IGDB;

@include_once __DIR__ . '/../../alv-ai/classes/AIClient.php';

function slugify(string $text): string
{
    return strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($text)), '-'));
}

function romanToDigits(string $slug): string
{
    $replacements = [
        '/\bxvi\b/i' => '16',
        '/\bxv\b/i'  => '15',
        '/\bxiv\b/i' => '14',
        '/\bxiii\b/i'=> '13',
        '/\bxii\b/i' => '12',
        '/\bxi\b/i'  => '11',
        '/\bix\b/i'  => '9',
        '/\bviii\b/i'=> '8',
        '/\bvii\b/i' => '7',
        '/\bvi\b/i'  => '6',
        '/\biv\b/i'  => '4',
        '/\biii\b/i' => '3',
        '/\bii\b/i'  => '2',
        '/\bx\b/i'   => '10',
        '/\bv\b/i'   => '5',
        '/\bi\b/i'   => '1',
    ];

    return preg_replace(array_keys($replacements), array_values($replacements), $slug);
}

function igdbImageUrl(string $imageId, string $size = 'cover_big'): string
{
    return "https://images.igdb.com/igdb/image/upload/t_{$size}/{$imageId}.jpg";
}

function translate(string $text, string $to = 'es', string $from = 'en', string $backend = 'opencode'): string
{
    return \DiarioGames\AI\AIClient::translate($text, $backend);
}

function normalizePlatformNames(string $platformStr): string
{
    if (empty(trim($platformStr))) return '';

    if (str_contains($platformStr, 'Xbox (')) {
        return $platformStr;
    }

    $excluded = ['Legacy Mobile Device', 'Windows Mobile', 'N-Gage'];

    $names = array_map('trim', explode(',', $platformStr));
    $result = [];
    $xboxVariants = [];

    foreach ($names as $name) {
        if (in_array($name, $excluded)) continue;

        $name = str_replace(' (Microsoft Windows)', '', $name);
        $name = str_ireplace('playstation', 'PS', $name);
        $name = preg_replace('/\bPS\s+(\d)/', 'PS$1', $name);

        if (preg_match('/^Xbox\s+(.+)$/i', $name, $m)) {
            $variant = preg_replace('/^Series\s+/i', '', $m[1]);
            $xboxVariants[] = $variant;
        } else {
            $result[] = $name;
        }
    }

    if (!empty($xboxVariants)) {
        $xboxStr = count($xboxVariants) === 1
            ? 'Xbox ' . $xboxVariants[0]
            : 'Xbox (' . implode(', ', $xboxVariants) . ')';
        $result[] = $xboxStr;
    }

    usort($result, function ($a, $b) {
        if ($a === 'PC') return -1;
        if ($b === 'PC') return 1;
        return 0;
    });

    return implode(', ', $result);
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
