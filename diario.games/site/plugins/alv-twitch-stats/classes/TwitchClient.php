<?php

namespace Alv\TwitchStats;

class TwitchClient
{
    private string $clientId;
    private string $clientSecret;
    private string $tokenPath;
    private int $timeout;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;

    public function __construct(string $clientId, string $clientSecret, ?string $tokenPath = null, int $timeout = 10)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenPath = $tokenPath ?? dirname(__DIR__, 4) . '/storage/twitch_token.json';
        $this->timeout = $timeout;
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function getTopGames(int $first = 100): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $json = $this->getJson('https://api.twitch.tv/helix/games/top?first=' . $this->clampFirst($first));

        return $json === null ? [] : self::parseTopGames($json);
    }

    public function getStreams(int $first = 100, string $language = ''): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $url = 'https://api.twitch.tv/helix/streams?first=' . $this->clampFirst($first);
        if ($language !== '') {
            $url .= '&language=' . rawurlencode($language);
        }

        $json = $this->getJson($url);

        return $json === null ? [] : self::parseStreams($json);
    }

    public function getAvatars(array $userIds): array
    {
        if (!$this->isConfigured() || empty($userIds)) {
            return [];
        }

        $avatars = [];
        $ids = array_values(array_unique(array_map('strval', $userIds)));
        foreach (array_chunk($ids, 100) as $chunk) {
            $query = implode('&', array_map(fn (string $id): string => 'id=' . rawurlencode($id), $chunk));
            $json = $this->getJson('https://api.twitch.tv/helix/users?' . $query);
            if ($json !== null) {
                $avatars += self::parseAvatars($json);
            }
        }

        return $avatars;
    }

    public static function parseTopGames(array $json): array
    {
        $rows = $json['data'] ?? null;
        if (!is_array($rows)) {
            return [];
        }

        $games = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (string) ($row['id'] ?? '');
            $name = trim((string) ($row['name'] ?? ''));
            if ($id === '' || $name === '') {
                continue;
            }

            $igdbId = isset($row['igdb_id']) && is_numeric($row['igdb_id']) ? (int) $row['igdb_id'] : null;

            $games[] = [
                'id' => $id,
                'name' => $name,
                'box_art_url' => self::boxArt((string) ($row['box_art_url'] ?? '')),
                'igdb_id' => $igdbId,
            ];
        }

        return $games;
    }

    public static function parseStreams(array $json): array
    {
        $rows = $json['data'] ?? null;
        if (!is_array($rows)) {
            return [];
        }

        $streams = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $userId = (string) ($row['user_id'] ?? '');
            $login = (string) ($row['user_login'] ?? '');
            if ($userId === '' || $login === '') {
                continue;
            }

            $streams[] = [
                'user_id' => $userId,
                'user_login' => $login,
                'user_name' => (string) ($row['user_name'] ?? $login),
                'game_id' => (string) ($row['game_id'] ?? ''),
                'game_name' => (string) ($row['game_name'] ?? ''),
                'viewer_count' => (int) ($row['viewer_count'] ?? 0),
            ];
        }

        return $streams;
    }

    public static function parseAvatars(array $json): array
    {
        $rows = $json['data'] ?? null;
        if (!is_array($rows)) {
            return [];
        }

        $avatars = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (string) ($row['id'] ?? '');
            $url = (string) ($row['profile_image_url'] ?? '');
            if ($id === '' || $url === '') {
                continue;
            }

            $avatars[$id] = $url;
        }

        return $avatars;
    }

    public static function aggregateByGame(array $streams): array
    {
        $aggregated = [];
        foreach ($streams as $stream) {
            $gameId = (string) ($stream['game_id'] ?? '');
            if ($gameId === '') {
                continue;
            }

            if (!isset($aggregated[$gameId])) {
                $aggregated[$gameId] = ['viewers' => 0, 'channels' => 0];
            }

            $aggregated[$gameId]['viewers'] += (int) ($stream['viewer_count'] ?? 0);
            $aggregated[$gameId]['channels']++;
        }

        return $aggregated;
    }

    public static function boxArt(string $template, int $width = 144, int $height = 192): string
    {
        if ($template === '') {
            return '';
        }

        return str_replace(['{width}', '{height}'], [(string) $width, (string) $height], $template);
    }

    protected function getJson(string $url): ?array
    {
        if (!$this->authenticate()) {
            return null;
        }

        $response = $this->curl($url, [
            'Client-Id: ' . $this->clientId,
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json',
        ]);

        if ($response === null) {
            return null;
        }

        $data = json_decode($response, true);

        return is_array($data) ? $data : null;
    }

    protected function authenticate(): bool
    {
        if ($this->accessToken !== null && $this->tokenExpiresAt !== null && $this->tokenExpiresAt > time()) {
            return true;
        }

        $cached = $this->loadCachedToken();
        if ($cached !== null && $cached['expires_at'] > time()) {
            $this->accessToken = $cached['token'];
            $this->tokenExpiresAt = $cached['expires_at'];
            return true;
        }

        $url = 'https://id.twitch.tv/oauth2/token?' . http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        $response = $this->curl($url, [], ['post' => true]);
        if ($response === null) {
            return false;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['access_token'])) {
            return false;
        }

        $this->accessToken = (string) $data['access_token'];
        $this->tokenExpiresAt = time() + (int) ($data['expires_in'] ?? 3600) - 300;
        $this->cacheToken($this->accessToken, $this->tokenExpiresAt);

        return true;
    }

    private function loadCachedToken(): ?array
    {
        if (!is_file($this->tokenPath)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($this->tokenPath), true);
        if (!is_array($data) || empty($data['token']) || empty($data['expires_at'])) {
            return null;
        }

        return ['token' => (string) $data['token'], 'expires_at' => (int) $data['expires_at']];
    }

    private function cacheToken(string $token, int $expiresAt): void
    {
        $dir = dirname($this->tokenPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        @file_put_contents($this->tokenPath, json_encode(['token' => $token, 'expires_at' => $expiresAt]));
    }

    private function clampFirst(int $first): int
    {
        return max(1, min(100, $first));
    }

    private function curl(string $url, array $headers = [], array $options = []): ?string
    {
        $ch = curl_init($url);
        $set = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'DiarioGames/1.0 (+https://diario.games)',
        ];
        if (!empty($headers)) {
            $set[CURLOPT_HTTPHEADER] = $headers;
        }
        if (!empty($options['post'])) {
            $set[CURLOPT_POST] = true;
        }
        curl_setopt_array($ch, $set);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response)) {
            return null;
        }

        return $response;
    }
}
