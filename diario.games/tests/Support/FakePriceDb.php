<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\StorePriceDB;

class FakePriceDb extends StorePriceDB
{
    public array $rows = [];
    public array $upserts = [];
    public ?int $now = null;

    public function __construct()
    {
    }

    public function getAllPrices(string $slug): array
    {
        return $this->rows[$slug] ?? [];
    }

    public function getPrice(string $slug, string $store): ?array
    {
        foreach ($this->rows[$slug] ?? [] as $row) {
            if ($row['store'] === $store) {
                return $row;
            }
        }
        return null;
    }

    public function upsertPrice(string $slug, string $store, array $priceData): void
    {
        $this->upserts[] = ['slug' => $slug, 'store' => $store, 'priceData' => $priceData];

        $row = [
            'slug'             => $slug,
            'store'            => $store,
            'url'              => $priceData['url'] ?? '',
            'price'            => $priceData['price'] ?? null,
            'initial_price'    => $priceData['initialPrice'] ?? null,
            'discount_percent' => $priceData['discount'] ?? 0,
            'currency'         => $priceData['currency'] ?? 'EUR',
            'platforms'        => $priceData['platforms'] ?? '',
            'scraped_at'       => $this->now ?? time(),
        ];

        $this->rows[$slug] = array_values(array_filter(
            $this->rows[$slug] ?? [],
            fn ($r) => $r['store'] !== $store
        ));
        $this->rows[$slug][] = $row;
    }

    public function isExpired(int $scrapedAt, int $ttl = 86400, ?int $now = null): bool
    {
        return (($now ?? $this->now ?? time()) - $scrapedAt) >= $ttl;
    }
}
