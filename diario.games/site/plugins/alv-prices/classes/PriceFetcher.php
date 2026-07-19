<?php

namespace Alv\Prices;

use Alv\Prices\Adapters\ItadAdapter;
use Alv\SteamStats\SteamStatsDB;

class PriceFetcher
{
    private StorePriceDB $db;
    private ?ItadAdapter $itad = null;
    private int $cacheTtl;

    public function __construct(?StorePriceDB $db = null, int $cacheTtl = 86400)
    {
        $this->db = $db ?? new StorePriceDB();
        $this->cacheTtl = $cacheTtl;
    }

    public function setItad(ItadAdapter $adapter): void
    {
        $this->itad = $adapter;
    }

    public function fetch(string $slug, string $gameName): array
    {
        $appid = $this->resolveAppid($slug);

        $cached = $this->db->getAllPrices($slug);
        $allFresh = !empty($cached) && !$this->anyExpired($cached);

        if ($allFresh) {
            return $this->formatResults($cached);
        }

        if ($this->itad !== null) {
            try {
                $itadPrices = $this->itad->fetchAllPrices($gameName, $appid);
                foreach ($itadPrices as $price) {
                    $this->db->upsertPrice($slug, $price['storeName'], [
                        'price'        => $price['price'],
                        'initialPrice' => $price['initialPrice'],
                        'discount'     => $price['discount'],
                        'currency'     => $price['currency'],
                        'url'          => $price['url'],
                        'platforms'    => $price['platforms'] ?? '',
                    ]);
                }
            } catch (\Throwable $e) {
                error_log("PriceFetcher: ITAD failed for {$slug}: " . $e->getMessage());
            }
        }

        $cached = $this->db->getAllPrices($slug);
        return $this->formatResults($cached);
    }

    private function anyExpired(array $cached): bool
    {
        foreach ($cached as $row) {
            if ($this->db->isExpired((int)$row['scraped_at'], $this->cacheTtl)) {
                return true;
            }
        }
        return false;
    }

    private function formatResults(array $rows): array
    {
        $results = [];
        $storeMap = [
            'Steam'            => 'steam',
            'GOG'              => 'gogdotcom',
            'Epic Games Store' => 'epicgames',
            'GreenManGaming'   => 'greenmangaming',
            'Fanatical'        => 'fanatical',
            'Humble Store'     => 'humble',
            'GameBillet'       => 'gamebillet',
            'GamersGate'       => 'gamersgate',
            'AllYouPlay'       => 'allyouplay',
            'IndieGala Store'  => 'indiegala',
            'WinGameStore'     => 'wingamestore',
            'JoyBuggy'         => 'joybuggy',
            'eTail.Market'     => 'etail',
        ];

        foreach ($rows as $row) {
            if ($row['price'] === null) {
                continue;
            }
            $results[] = [
                'storeName'    => $row['store'],
                'storeLogo'    => $storeMap[$row['store']] ?? 'globe',
                'price'        => $row['price'],
                'initialPrice' => $row['initial_price'],
                'discount'     => $row['discount_percent'],
                'currency'     => $row['currency'] ?? 'EUR',
                'url'          => $row['url'],
                'platforms'    => $row['platforms'] ?? '',
            ];
        }

        usort($results, fn($a, $b) => $a['price'] <=> $b['price']);

        return $results;
    }

    public static function createFromEnv(): self
    {
        $instance = new self(
            db: new StorePriceDB(),
            cacheTtl: (int)(env('PRICE_CACHE_TTL', '86400'))
        );

        $itadKey = env('ITAD_API_KEY', '');
        if ($itadKey) {
            $affiliateIds = [];
            $affMap = [
                'GreenManGaming'  => 'PRICE_GREENMANGAMING_AFF_ID',
                'Fanatical'       => 'PRICE_FANATICAL_AFF_ID',
                'Humble Store'    => 'PRICE_HUMBLE_AFF_ID',
                'GamersGate'      => 'PRICE_GAMERSGATE_AFF_ID',
                'GameBillet'      => 'PRICE_GAMEBILLET_AFF_ID',
                'Instant Gaming'  => 'INSTANT_GAMING_IGR',
            ];
            foreach ($affMap as $store => $envKey) {
                $val = env($envKey, '');
                if ($val) {
                    $affiliateIds[$store] = $val;
                }
            }
            $instance->setItad(new ItadAdapter($itadKey, $affiliateIds));
        }

        return $instance;
    }

    private function resolveAppid(string $slug): ?int
    {
        try {
            $db = new SteamStatsDB();
            $game = $db->getGameBySlug($slug);
            return $game ? (int)$game['appid'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
