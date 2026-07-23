<?php

namespace DiarioGames\AI;

class AIClient
{
    public static function translate(string $text, string $backend = 'opencode'): string
    {
        if (empty(trim($text))) return $text;

        $systemPrompt = self::getSystemPrompt();
        $userMsg = "Texto original (inglés): {$text}\n\nReescríbelo en español neutro. NO traduzcas literal: reformula con tus propias palabras. Usa SIEMPRE 'tú' (eres, puedes, tienes) — NUNCA 'vos' (sos, podés, tenés).";

        $result = self::call($systemPrompt, $userMsg, $backend);
        if ($result !== null) return $result;

        $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=es&dt=t&q=' . rawurlencode($text);
        $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 5]]));
        if ($response === false) return $text;
        $data = json_decode($response, true);
        return $data[0][0][0] ?? $text;
    }

    public static function rewrite(string $text, string $backend = 'opencode'): string
    {
        if (empty(trim($text))) return $text;

        $systemPrompt = self::getSystemPrompt();
        $userMsg = "Texto original (español): {$text}\n\nReescríbelo en español neutro internacional. Elimina cualquier regionalismo o expresión local. Reformula con tus propias palabras, cambiando estructura y vocabulario. Usa SIEMPRE 'tú' (eres, puedes, tienes) — NUNCA 'vos' (sos, podés, tenés).";

        return self::call($systemPrompt, $userMsg, $backend) ?? $text;
    }

    public static function generate(string $prompt, string $backend = 'opencode'): string
    {
        if (empty(trim($prompt))) return '';

        $systemPrompt = self::getWritingPrompt();
        return self::call($systemPrompt, $prompt, $backend) ?? '';
    }

    private static function call(string $systemPrompt, string $userMsg, string $backend): ?string
    {
        $config = ['opencode', 'openrouter'];

        if ($backend === 'opencode') {
            $result = self::callOpenCode($systemPrompt, $userMsg);
            if ($result !== null) return $result;
        }

        if ($backend === 'openrouter') {
            $result = self::callOpenRouter($systemPrompt, $userMsg);
            if ($result !== null) return $result;
        }

        return null;
    }

    private static function callOpenCode(string $systemPrompt, string $userMsg): ?string
    {
        $apiKey = $_ENV['OPENCODE_API_KEY'] ?? $_SERVER['OPENCODE_API_KEY'] ?? getenv('OPENCODE_API_KEY');
        if (!$apiKey) return null;

        $body = json_encode([
            'model' => 'deepseek-v4-flash',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMsg],
            ],
        ]);

        $ch = curl_init('https://opencode.ai/zen/go/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            $content = $data['choices'][0]['message']['content'] ?? '';
            if ($content) return trim($content);
        }

        return null;
    }

    private static function callOpenRouter(string $systemPrompt, string $userMsg): ?string
    {
        $apiKey = $_ENV['OPENROUTER_API_KEY'] ?? $_SERVER['OPENROUTER_API_KEY'] ?? getenv('OPENROUTER_API_KEY');
        if (!$apiKey) return null;

        $body = json_encode([
            'model' => 'openrouter/auto',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMsg],
            ],
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
            $content = $data['choices'][0]['message']['content'] ?? '';
            if ($content) return trim($content);
        }

        return null;
    }

    private static function getSystemPrompt(): string
    {
        return 'Eres un experto en escribir textos de videojuegos en español internacional neutro, comprensible para hispanohablantes de cualquier país. Tono conversacional, cálido y divertido, como si hablaras con un amigo. REGLA #1 — CONJUGACIONES: Usa EXCLUSIVAMENTE las formas estándar de "tú": puedes, tienes, quieres, eres, haces, estás, vas, dices, ves, sabes, sales, pones, vienes, etc. JAMÁS uses "vos" (sos, podés, tenés, querés, hacés) ni "vosotros" (podéis, tenéis, sois). Esta es la regla más importante. REGLA #2 — SIN REGIONALISMOS: No uses expresiones de ningún país específico. Prohibido: españolismos ("ostras", "colega", "molar", "flipar", "chungo", "pasta", "movida", "liarla", "pringar", "pajolera", "pasada", "pipa", "chachi", "cañero", "pirarse", "petarlo", "ni de coña", "que no veas", "dar caña", "tío"), mexicanismos ("chido", "padre", "güey", "chingón", "neta"), chilenismos ("po", "cachai", "bacán"), colombianismos ("parce", "berraco"). REGLA #3 — REWRITE, NO TRADUZCAS: No hagas traducción literal. Reescribe con tus propias palabras, cambia estructura y orden de las ideas. Puedes usar anglicismos de gaming universales (pushear, pickear, farmear, grindear, buffear, nerfear). Si el texto original es muy corto (1-2 palabras como "Visual Novel"), responde igual de conciso ("Novela visual" o "Disparos"). Responde SOLO con el texto final en español, sin explicaciones.';
    }

    private static function getWritingPrompt(): string
    {
        return 'Eres un redactor experto en videojuegos que escribe en español internacional neutro, comprensible para hispanohablantes de cualquier país. Tono conversacional, cálido y divertido, como si hablaras con un amigo. REGLA #1 — CONJUGACIONES: Usa EXCLUSIVAMENTE las formas estándar de "tú" (puedes, tienes, quieres, eres, haces). JAMÁS uses "vos" (sos, podés, tenés) ni "vosotros". REGLA #2 — SIN REGIONALISMOS: No uses expresiones de ningún país. REGLA #3 — ORIGINALIDAD: Crea contenido original, fresco y con personalidad. Responde SOLO con el texto final en español, sin explicaciones.';
    }
}
