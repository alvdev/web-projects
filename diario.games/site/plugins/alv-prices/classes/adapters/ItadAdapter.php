<?php

namespace Alv\Prices\Adapters;

use Alv\Prices\StoreAdapter;

class ItadAdapter extends StoreAdapter
{
    protected string $name = 'ITAD';
    protected string $logo = 'itad';
    private string $apiKey;
    private string $country;

    private const BASE = 'https://api.isthereanydeal.com';

    private array $storeMap = [
        'Steam'            => ['logo' => 'steam'],
        'GOG'              => ['logo' => 'gogdotcom'],
        'Epic Games Store' => ['logo' => 'epicgames'],
        'GreenManGaming'   => ['logo' => 'greenmangaming'],
        'Fanatical'        => ['logo' => 'fanatical'],
        'Humble Store'     => ['logo' => 'humble'],
        'GameBillet'       => ['logo' => 'gamebillet'],
        'GamersGate'       => ['logo' => 'gamersgate'],
        'AllYouPlay'       => ['logo' => 'allyouplay'],
        'IndieGala Store'  => ['logo' => 'indiegala'],
        'GamesPlanet US'   => ['logo' => 'gamesplanet'],
        'GamesPlanet FR'   => ['logo' => 'gamesplanet'],
        'GamesPlanet DE'   => ['logo' => 'gamesplanet'],
        'GamesPlanet UK'   => ['logo' => 'gamesplanet'],
        'WinGameStore'     => ['logo' => 'wingamestore'],
        'JoyBuggy'         => ['logo' => 'joybuggy'],
        'eTail.Market'     => ['logo' => 'etail'],
    ];

    public function __construct(string $apiKey, string $country = 'ES')
    {
        $this->apiKey = $apiKey;
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

        return $this->fetchPrices($gameId);
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

    private function fetchPrices(string $gameId): array
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

            $results[] = [
                'storeName'    => $shopName,
                'storeLogo'    => $storeInfo['logo'],
                'price'        => (float) $price,
                'initialPrice' => $regular !== $price ? (float) $regular : null,
                'discount'     => $cut > 0 ? $cut : null,
                'currency'     => $deal['price']['currency'] ?? $this->currency,
                'url'          => $deal['url'] ?? '',
                'platforms'    => implode(', ', $platformNames),
            ];
        }

        return $results;
    }
}
