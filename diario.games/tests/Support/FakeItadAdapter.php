<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\Prices\Adapters\ItadAdapter;

class FakeItadAdapter extends ItadAdapter
{
    public array $getResponses = [];
    public ?string $postResponse = null;

    protected function httpGet(string $url): ?string
    {
        foreach ($this->getResponses as $needle => $json) {
            if (str_contains($url, $needle)) {
                return $json;
            }
        }
        return null;
    }

    protected function httpPostJson(string $url, string $jsonBody): ?string
    {
        return $this->postResponse;
    }
}
