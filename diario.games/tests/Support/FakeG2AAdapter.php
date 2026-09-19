<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\Adapters\G2AAdapter;

class FakeG2AAdapter extends G2AAdapter
{
    public ?string $token = 'token-1';
    public ?array $offers = null;

    protected function getToken(): ?string
    {
        return $this->token;
    }

    protected function getOffers(string $token, string $productId): ?array
    {
        return $this->offers;
    }
}
