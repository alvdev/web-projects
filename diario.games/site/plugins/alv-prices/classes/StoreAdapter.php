<?php

namespace Alv\Prices;

abstract class StoreAdapter
{
    protected string $name;
    protected string $logo;
    protected string $currency = 'EUR';

    abstract public function getName(): string;

    abstract public function getLogo(): string;

    abstract public function searchGame(string $gameName): ?string;

    abstract public function scrapePrice(string $url): ?array;

    abstract public function getAffiliateUrl(string $gameName): string;

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function fetchPrice(string $gameName, ?int $appid = null): ?array
    {
        $url = $this->searchGame($gameName);
        if ($url === null) {
            return null;
        }

        $price = $this->scrapePrice($url);
        if ($price === null) {
            return null;
        }

        $price['url'] = $url;
        return $price;
    }

    protected function httpGet(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; DiarioGames/1.0)',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Accept-Language: es,en;q=0.9',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        return $response;
    }

    protected function httpGetHtml(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: es,en;q=0.9',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        return $response;
    }

    protected function httpGetProxy(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: es,en;q=0.9',
            ],
            CURLOPT_PROXY        => env('PROXY_HOST') . ':' . env('PROXY_PORT'),
            CURLOPT_PROXYUSERPWD => env('PROXY_USER') . ':' . env('PROXY_PASS'),
            CURLOPT_PROXYTYPE    => CURLPROXY_HTTP,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            return null;
        }

        return $response;
    }
}
