<?php

namespace Alv\Prices\Adapters;

use Alv\Prices\StoreAdapter;
use Alv\Prices\StorePriceDB;

class G2AAdapter extends StoreAdapter
{
    protected string $name = 'G2A';
    protected string $logo = 'g2a';
    private string $clientId;
    private string $apiKey;

    public function __construct(string $clientId, string $apiKey)
    {
        $this->clientId = $clientId;
        $this->apiKey = $apiKey;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function searchGame(string $gameName): ?string
    {
        return null;
    }

    public function scrapePrice(string $url): ?array
    {
        return null;
    }

    public function getAffiliateUrl(string $gameName): string
    {
        return 'https://www.g2a.com/search?query=' . urlencode($gameName);
    }

    public function fetchPrice(string $gameName, ?int $appid = null): ?array
    {
        $db = new StorePriceDB();
        $productId = $db->findG2aProductId($gameName);

        if ($productId === null) {
            return null;
        }

        $token = $this->getToken();
        if ($token === null) {
            return null;
        }

        $offers = $this->getOffers($token, $productId);
        if ($offers === null || empty($offers['offers'])) {
            return null;
        }

        $cheapest = $offers['offers'][0];
        $price = (float)($cheapest['price'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        $slug = $this->slugify($offers['name'] ?? $gameName);
        $url = 'https://www.g2a.com/' . $slug . '/p/' . $productId;

        return [
            'price'        => $price,
            'initialPrice' => null,
            'discount'     => null,
            'currency'     => $offers['currency'] ?? 'EUR',
            'url'          => $url,
        ];
    }

    private function getToken(): ?string
    {
        $ch = curl_init('https://api.g2a.com/oauth/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->apiKey,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    private function getOffers(string $token, string $productId): ?array
    {
        $url = "https://api.g2a.com/export/v1/product-offers/{$productId}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['data'] ?? null;
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}
