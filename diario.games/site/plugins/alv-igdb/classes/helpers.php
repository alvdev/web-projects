<?php

namespace DiarioGames\IGDB;

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

function translate(string $text, string $to = 'es', string $from = 'en'): string
{
    if (empty(trim($text))) return $text;

    $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? getenv('OPENROUTER_API_KEY');
    if ($apiKey) {
        $systemPrompt = 'Eres un experto en escribir textos de videojuegos en español neutro con un tono conversacional, cálido y divertido, como si hablaras con un amigo. Reglas: usa lenguaje coloquial y natural; evita palabras corporativas; mezcla frases cortas y largas; incluye expresiones idiomáticas españolas neutras y anglicismos de gaming cuando sea natural (ej: pushear, pickear, farmear, grindear, buffear, nerfear). IMPORTANTE: si el texto original es corto (una o dos palabras como "Visual Novel" o "Shooter"), responde con una traducción igual de concisa (ej: "Novela visual" o "Disparos"). Responde SOLO con el texto reescrito en español, sin explicaciones ni introducciones.';
        $body = json_encode([
            'model' => 'openrouter/auto',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Texto original (inglés): {$text}\n\nReescríbelo en español con el tono descrito."]
            ]
        ]);
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            $translated = $data['choices'][0]['message']['content'] ?? '';
            if ($translated) return trim($translated);
        }
    }

    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=' . rawurlencode($from) . '&tl=' . rawurlencode($to) . '&dt=t&q=' . rawurlencode($text);
    $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));
    if ($response === false) return $text;
    $data = json_decode($response, true);
    return $data[0][0][0] ?? $text;
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
