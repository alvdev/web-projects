<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\Adapters\InstantGamingAdapter;

class FakeInstantGamingAdapter extends InstantGamingAdapter
{
    public array $hits = [];

    protected function searchAlgolia(string $query): array
    {
        return $this->hits;
    }
}
