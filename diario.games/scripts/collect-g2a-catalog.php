<?php

require_once __DIR__ . '/../site/plugins/kirby3-dotenv/global.php';
loadenv(['dir' => __DIR__ . '/..']);

require_once __DIR__ . '/../site/plugins/alv-prices/classes/StorePriceDB.php';

$clientId = env('G2A_CLIENT_ID', '');
$apiKey = env('G2A_API_KEY', '');

if (!$clientId || !$apiKey) {
    echo "G2A credentials not configured. Set G2A_CLIENT_ID and G2A_API_KEY in .env\n";
    exit(1);
}

$token = getAccessToken($clientId, $apiKey);
if (!$token) {
    echo "Failed to get G2A access token\n";
    exit(1);
}

$db = new \Alv\Prices\StorePriceDB();

echo "Warming G2A catalog...\n";

$page = 1;
$total = 0;
$itemsPerPage = 100;

do {
    $data = fetchPage($token, $page, $itemsPerPage);
    if (!$data || empty($data['data'])) {
        break;
    }

    foreach ($data['data'] as $product) {
        $db->upsertG2aProduct($product['id'], $product['name']);
        $total++;
    }

    $page++;
    $hasNext = $data['meta']['hasNext'] ?? false;

    if ($page % 10 === 0) {
        echo "  Page {$page}, {$total} products indexed...\n";
    }
} while ($hasNext);

$db->deleteStaleG2aProducts();
echo "Done. {$total} products indexed.\n";

function getAccessToken(string $clientId, string $apiKey): ?string
{
    $ch = curl_init('https://api.g2a.com/oauth/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $apiKey,
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

function fetchPage(string $token, int $page, int $itemsPerPage): ?array
{
    $url = "https://api.g2a.com/export/v1/product-offers?page={$page}&itemsPerPage={$itemsPerPage}";

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "HTTP {$httpCode} on page {$page}\n";
        return null;
    }

    return json_decode($response, true);
}
