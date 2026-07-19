<?php

namespace Alv\Prices\Adapters;

use Alv\Prices\StoreAdapter;

class ItadAdapter extends StoreAdapter
{
    protected string $name = 'ITAD';
    protected string $logo = 'itad';
    private string $apiKey;
    private string $country;
    private array $affiliateIds;

    private const BASE = 'https://api.isthereanydeal.com';

    private array $storeMap = [
        'Steam'            => ['logo' => 'steam',            'domain' => 'store.steampowered.com'],
        'GOG'              => ['logo' => 'gogdotcom',        'domain' => 'gog.com'],
        'Epic Games Store' => ['logo' => 'epicgames',        'domain' => 'store.epicgames.com'],
        'GreenManGaming'   => ['logo' => 'greenmangaming',   'domain' => 'greenmangaming.com'],
        'Fanatical'        => ['logo' => 'fanatical',        'domain' => 'fanatical.com'],
        'Humble Store'     => ['logo' => 'humble',           'domain' => 'humblebundle.com'],
        'GameBillet'       => ['logo' => 'gamebillet',       'domain' => 'gamebillet.com'],
        'GamersGate'       => ['logo' => 'gamersgate',       'domain' => 'gamersgate.com'],
        'AllYouPlay'       => ['logo' => 'allyouplay',       'domain' => 'allyouplay.com'],
        'IndieGala Store'  => ['logo' => 'indiegala',        'domain' => 'indiegala.com'],
        'WinGameStore'     => ['logo' => 'wingamestore',     'domain' => 'wingamestore.com'],
        'JoyBuggy'         => ['logo' => 'joybuggy',         'domain' => 'joybuggy.com'],
        'eTail.Market'     => ['logo' => 'etail',            'domain' => 'etail.market'],
    ];

    public function __construct(string $apiKey, array $affiliateIds = [], string $country = 'ES')
    {
        $this->apiKey = $apiKey;
        $this->affiliateIds = $affiliateIds;
        $this->country = $country;
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
        return 'https://isthereanydeal.com/game/' . urlencode($gameName) . '/';
    }

    public function fetchAllPrices(string $gameName, ?int $appid = null): array
    {
        $gameId = $this->lookupGame($gameName, $appid);
        if ($gameId === null) {
            return [];
        }

        return $this->fetchPrices($gameId, $gameName, $appid);
    }

    private function lookupGame(string $gameName, ?int $appid): ?string
    {
        if ($appid !== null) {
            $json = $this->httpGet(
                self::BASE . "/games/lookup/v1?appid={$appid}&key={$this->apiKey}"
            );
            if ($json) {
                $data = json_decode($json, true);
                if ($data && ($data['found'] ?? false) && isset($data['game']['id'])) {
                    return $data['game']['id'];
                }
            }
        }

        $json = $this->httpGet(
            self::BASE . '/games/lookup/v1?title=' . urlencode($gameName) . "&key={$this->apiKey}"
        );
        if ($json) {
            $data = json_decode($json, true);
            if ($data && ($data['found'] ?? false) && isset($data['game']['id'])) {
                return $data['game']['id'];
            }
        }

        return null;
    }

    private function fetchPrices(string $gameId, string $gameName, ?int $appid): array
    {
        $url = self::BASE . "/games/prices/v3?key={$this->apiKey}&country={$this->country}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; DiarioGames/1.0)',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([$gameId]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return [];
        }

        $data = json_decode($response, true);
        if (!$data || empty($data[0]['deals'])) {
            return [];
        }

        $results = [];
        foreach ($data[0]['deals'] as $deal) {
            $shopName = $deal['shop']['name'] ?? '';
            $storeInfo = $this->storeMap[$shopName] ?? null;
            if ($storeInfo === null) {
                continue;
            }

            $price = $deal['price']['amount'] ?? null;
            if ($price === null || $price <= 0) {
                continue;
            }

            $regular = $deal['regular']['amount'] ?? $price;
            $cut = (int)($deal['cut'] ?? 0);

            $platformNames = [];
            foreach ($deal['platforms'] ?? [] as $p) {
                $platformNames[] = $p['name'];
            }

            $storeUrl = $this->resolveStoreUrl($deal['url'] ?? '', $shopName);

            $results[] = [
                'storeName'    => $shopName,
                'storeLogo'    => $storeInfo['logo'],
                'price'        => (float) $price,
                'initialPrice' => $regular !== $price ? (float) $regular : null,
                'discount'     => $cut > 0 ? $cut : null,
                'currency'     => $deal['price']['currency'] ?? $this->currency,
                'url'          => $storeUrl,
                'platforms'    => implode(', ', $platformNames),
            ];
        }

        return $results;
    }

    private function resolveStoreUrl(string $itadUrl, string $storeName): string
    {
        $affId = $this->affiliateIds[$storeName] ?? '';

        if (empty($itadUrl)) {
            return $this->buildFallbackUrl($storeName, $affId);
        }

        $resolved = $this->followRedirect($itadUrl);
        if ($resolved === null) {
            return $this->buildFallbackUrl($storeName, $affId);
        }

        if ($affId === '') {
            return $resolved;
        }

        return $this->appendAffiliate($resolved, $storeName, $affId);
    }

    private function followRedirect(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_NOBODY         => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; DiarioGames/1.0)',
        ]);
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400 || empty($finalUrl) || $finalUrl === $url) {
            return null;
        }

        return $finalUrl;
    }

    private function appendAffiliate(string $url, string $storeName, string $affId): string
    {
        $params = match ($storeName) {
            'GreenManGaming'  => 'utm_source=affiliate&utm_medium=link&utm_campaign=',
            'Fanatical'       => 'ref=',
            'Humble Store'    => 'partner=',
            'GamersGate'      => 'aff=',
            'GameBillet'      => 'affiliate=',
            default           => 'ref=',
        };

        $parsed = parse_url($url);
        if ($parsed === false) {
            return $url;
        }

        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        $base .= $parsed['path'] ?? '/';

        $queryParams = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }

        $stripKeys = ['ref', 'aff', 'affiliate', 'partner', 'utm_source', 'utm_medium',
            'utm_campaign', 'utm_content', 'utm_term', 'awc', 'irclickid', 'irgwc',
            'sharedid', 'irpid', 'tracking', 'sv1', 'sv_campaign_id', 'utm_publisherID',
            'utm_publisherurl', 'utm_promotiontype', 'publisherID', 'publisherurl',
            'promotiontype', 'afsrc'];

        foreach ($stripKeys as $k) {
            unset($queryParams[$k]);
        }

        $sep = str_contains($base, '?') ? '&' : '?';
        $base .= $sep . $params . urlencode($affId);

        if (!empty($queryParams)) {
            $base .= '&' . http_build_query($queryParams);
        }

        return $base;
    }

    private function buildFallbackUrl(string $storeName, string $affId): string
    {
        $domain = $this->storeMap[$storeName]['domain'] ?? '';
        $url = "https://{$domain}/";
        if ($affId) {
            $url .= '?ref=' . urlencode($affId);
        }
        return $url;
    }
}
