<?php

namespace Alv\Prices\Adapters;

use Alv\Prices\StoreAdapter;

class InstantGamingAdapter extends StoreAdapter
{
    protected string $name = 'Instant Gaming';
    protected string $logo = 'instant-gaming';
    private string $affId;

    private const ALGOLIA_APP_ID = 'QKNHP8TC3Y';
    private const ALGOLIA_API_KEY = '93946b91c013211f842ddf1819ea880b';
    private const ALGOLIA_INDEX = 'produits_en';

    public function __construct(string $affId = '')
    {
        $this->affId = $affId;
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
        $url = 'https://www.instant-gaming.com/en/search/?q=' . urlencode($gameName);
        if ($this->affId) {
            $url .= '&igr=' . urlencode($this->affId);
        }
        return $url;
    }

    public function fetchPrice(string $gameName, ?int $appid = null): ?array
    {
        $results = $this->searchAlgolia($gameName);
        if (empty($results)) {
            return null;
        }

        $best = $this->pickBestHit($results, $gameName);
        if ($best === null) {
            return null;
        }

        $price = (float)($best['price_eur'] ?? $best['price'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        $retail = (float)($best['default_retail'] ?? $best['retail'] ?? $price);
        $discount = (int)($best['discount'] ?? 0);

        $seoName = $best['seo_name'] ?? '';
        $prodId = $best['prod_id'] ?? '';

        $url = 'https://www.instant-gaming.com/en/' . $prodId . '-' . $seoName . '/';
        if ($this->affId) {
            $url .= '?igr=' . urlencode($this->affId);
        }

        return [
            'price'        => $price,
            'initialPrice' => $retail > $price ? $retail : null,
            'discount'     => $discount > 0 ? $discount : null,
            'currency'     => 'EUR',
            'url'          => $url,
        ];
    }

    private function searchAlgolia(string $query): array
    {
        $url = 'https://' . self::ALGOLIA_APP_ID . '-dsn.algolia.net/1/indexes/' . self::ALGOLIA_INDEX . '/query';

        $body = json_encode([
            'query'       => $query,
            'hitsPerPage' => 10,
            'filters'     => '(country_whitelist:"ES" OR country_whitelist:"worldwide" OR country_whitelist:"WW") AND (NOT country_blacklist:"ES")',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Algolia-API-Key: ' . self::ALGOLIA_API_KEY,
                'X-Algolia-Application-Id: ' . self::ALGOLIA_APP_ID,
                'Referer: https://www.instant-gaming.com/',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return [];
        }

        $data = json_decode($response, true);
        return $data['hits'] ?? [];
    }

    private function pickBestHit(array $hits, string $gameName): ?array
    {
        $normalized = mb_strtolower(trim($gameName));
        $best = null;
        $bestPrice = PHP_FLOAT_MAX;

        foreach ($hits as $hit) {
            $name = $hit['name'] ?? '';
            if (empty($name)) continue;

            $hitName = mb_strtolower(trim($name));

            if ($hitName !== $normalized) {
                continue;
            }

            $price = (float)($hit['price_eur'] ?? $hit['price'] ?? 0);
            if ($price <= 0) continue;

            $hasStock = $hit['has_stock'] ?? true;
            if (!$hasStock) continue;

            if ($price < $bestPrice) {
                $best = $hit;
                $bestPrice = $price;
            }
        }

        return $best;
    }
}
