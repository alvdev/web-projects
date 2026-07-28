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

    $excluded = ['Legacy Mobile Device', 'Windows Mobile', 'N-Gage', 'Google Stadia', 'Windows Phone'];

    // Normalize Xbox "(X|S, One)" before comma splitting
    $platformStr = preg_replace_callback('/Xbox\s*\(([^)]+)\)/i', function ($m) {
        return 'Xbox ' . str_replace(',', ' ', $m[1]);
    }, $platformStr);

    $names = array_map('trim', explode(',', $platformStr));

    $psVariants      = [];
    $switchVariants  = [];
    $xboxVariants    = [];
    $entries         = [];

    foreach ($names as $name) {
        if ($name === '' || in_array($name, $excluded)) continue;

        $name = str_replace(' (Microsoft Windows)', '', $name);

        if (preg_match('/^(?:PlayStation|PS)\s*(.+)$/i', $name, $m)) {
            $psVariants[] = $m[1];
            continue;
        }

        if (preg_match('/^Nintendo Switch(?:\s+(\d+))?$/i', $name, $m)) {
            $switchVariants[] = isset($m[1]) ? $m[1] : '1';
            continue;
        }

        if (preg_match('/^Xbox\s+(.+)$/i', $name, $m)) {
            $variant = trim($m[1]);
            // "Series X|S" → "X|S"
            $variant = preg_replace('/^Series\s+([XS]\|?[XS])$/i', '$1', $variant);
            foreach (preg_split('/\s+/', $variant) as $part) {
                $part = trim($part);
                if ($part !== '') $xboxVariants[] = $part;
            }
            continue;
        }

        $entries[] = $name;
    }

    // Group PlayStation
    if (!empty($psVariants)) {
        $psVariants = array_unique($psVariants);
        usort($psVariants, function ($a, $b) {
            $aNum = is_numeric($a);
            $bNum = is_numeric($b);
            if ($aNum && $bNum) return (int)$a - (int)$b;
            if ($aNum) return -1;
            if ($bNum) return 1;
            return strcasecmp($a, $b);
        });
        $entries[] = 'PS ' . implode('|', $psVariants);
    }

    // Group Switch
    if (!empty($switchVariants)) {
        $switchVariants = array_unique($switchVariants);
        sort($switchVariants, SORT_NUMERIC);
        if ($switchVariants === ['1']) {
            $entries[] = 'Switch';
        } else {
            $entries[] = 'Switch ' . implode('|', $switchVariants);
        }
    }

    // Group Xbox
    if (!empty($xboxVariants)) {
        $xboxVariants = array_unique($xboxVariants);
        usort($xboxVariants, function ($a, $b) {
            $aOrder = in_array(strtoupper($a), ['X|S', 'S|X']) ? 0 : 1;
            $bOrder = in_array(strtoupper($b), ['X|S', 'S|X']) ? 0 : 1;
            if ($aOrder !== $bOrder) return $aOrder - $bOrder;
            return strcasecmp($a, $b);
        });
        $entries[] = 'Xbox ' . implode('|', $xboxVariants);
    }

    // Sort: OS first → consoles → phones last
    usort($entries, function ($a, $b) {
        $catA = platformCategory($a);
        $catB = platformCategory($b);
        if ($catA !== $catB) return $catA - $catB;
        if ($a === 'PC') return -1;
        if ($b === 'PC') return 1;
        return strcasecmp($a, $b);
    });

    return implode(', ', $entries);
}

function platformCategory(string $name): int
{
    $lower = strtolower($name);
    if (in_array($lower, ['pc', 'linux', 'mac'])) return 0;
    if (preg_match('/^(iOS|Android|Windows Phone)/i', $name)) return 2;
    return 1;
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

function _db(): ?\Alv\SteamStats\SteamStatsDB
{
    static $instance = null;
    if ($instance === null) {
        try {
            $instance = new \Alv\SteamStats\SteamStatsDB();
        } catch (\Throwable $e) {
            return null;
        }
    }
    return $instance;
}

function resolveGamePath(string $slug): ?string
{
    try {
        $db = _db();
        if (!$db) return null;
        return $db->getYearMonth($slug);
    } catch (\Throwable $e) {
        return null;
    }
}

function resolveGameByIgdbId(int $igdbId): ?string
{
    try {
        $db = _db();
        if (!$db) return null;
        $game = $db->getGameByIgdbId($igdbId);
        return $game['slug'] ?? null;
    } catch (\Throwable $e) {
        return null;
    }
}

function addGameToIndex(string $slug, string $yearMonth, int $igdbId): void
{
    try {
        $db = _db();
        if (!$db) return;
        $db->setYearMonth($slug, $yearMonth, $igdbId > 0 ? $igdbId : null);
    } catch (\Throwable $e) {}
}

function deriveYearMonth(string $releaseDate): array
{
    if (preg_match('/^(\d{4})-(\d{2})/', $releaseDate, $m)) {
        return [$m[1], $m[2]];
    }
    if (preg_match('/^(\d{4})/', $releaseDate, $m)) {
        return [$m[1], '00'];
    }
    return ['00', '00'];
}

function fetchThesvgIcon(string $url): ?array
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host) return null;

    $host = preg_replace('/^www\./', '', $host);
    $parts = explode('.', $host);
    $name = strtolower($parts[0]);

    $svgDir  = dirname(__DIR__, 4) . '/assets/svgs/';
    $mapFile = dirname(__DIR__, 4) . '/data/website-icons.json';

    // Existing local or explicit icons take priority
    if (file_exists("{$svgDir}{$name}.svg")) return null;

    $slugs = [$name];
    $hyphenated = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
    if ($hyphenated !== $name) $slugs[] = $hyphenated;

    foreach ($slugs as $slug) {
        $cdnUrl = "https://cdn.jsdelivr.net/gh/glincker/thesvg@main/public/icons/{$slug}/default.svg";

        $ch = curl_init($cdnUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'diario.games/1.0',
        ]);
        $svg = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$svg) continue;

        $svg = preg_replace('/fill="[^"]*"/', 'fill="currentColor"', $svg, 1);
        file_put_contents("{$svgDir}{$slug}.svg", $svg);

        $mappings = file_exists($mapFile) ? json_decode(file_get_contents($mapFile), true) : [];
        $label = ucfirst(str_replace('-', ' ', $slug));
        $mappings[$host]         = ['label' => $label, 'icon' => $slug];
        $mappings["www.{$host}"] = ['label' => $label, 'icon' => $slug];
        file_put_contents($mapFile, json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return ['label' => $label, 'icon' => $slug];
    }

    return null;
}
