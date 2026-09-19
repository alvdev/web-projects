<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\StoreAdapter;

class FakeStoreAdapter extends StoreAdapter
{
    public int $fetchCalls = 0;

    public function __construct(
        private string $storeName,
        private ?array $price,
        private bool $throws = false
    ) {
    }

    public function getName(): string
    {
        return $this->storeName;
    }

    public function getLogo(): string
    {
        return strtolower(str_replace(' ', '-', $this->storeName));
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
        return '';
    }

    public function fetchPrice(string $gameName, ?int $appid = null): ?array
    {
        $this->fetchCalls++;
        if ($this->throws) {
            throw new \RuntimeException('adapter failure');
        }
        return $this->price;
    }
}
