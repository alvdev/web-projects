<?php

namespace Alv\TwitchStats;

class TwitchTrackerClient
{
    private ?\Closure $http;
    private int $timeout;

    public function __construct(?\Closure $http = null, int $timeout = 10)
    {
        $this->http = $http;
        $this->timeout = $timeout;
    }

    public function getGameSummary(string|int $twitchId): ?array
    {
        return self::parseSummary($this->fetch('https://twitchtracker.com/api/games/summary/' . rawurlencode((string) $twitchId)));
    }

    public function getChannelSummary(string $login): ?array
    {
        if ($login === '') {
            return null;
        }

        return self::parseSummary($this->fetch('https://twitchtracker.com/api/channels/summary/' . rawurlencode($login)));
    }

    public static function parseSummary(?string $body): ?array
    {
        if ($body === null || trim($body) === '') {
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data) || !isset($data['avg_viewers'], $data['rank'])) {
            return null;
        }

        if (!is_numeric($data['avg_viewers']) || !is_numeric($data['rank'])) {
            return null;
        }

        foreach ($data as $key => $value) {
            if (is_numeric($value)) {
                $data[$key] = (int) $value;
            }
        }

        return $data;
    }

    protected function fetch(string $url): ?string
    {
        if ($this->http !== null) {
            $response = ($this->http)($url);

            return is_string($response) && $response !== '' ? $response : null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'DiarioGames/1.0 (+https://diario.games)',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response)) {
            return null;
        }

        return $response;
    }
}
