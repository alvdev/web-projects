<?php

namespace DiarioGames\IGDB;

class IGDBClient
{
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private ?int $tokenExpiresAt = null;
    private array $requestTimestamps = [];
    private string $tokenPath;

    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenPath = dirname(__DIR__, 2) . '/storage/igdb_token.json';
    }

    public function authenticate(): void
    {
        if ($this->accessToken && $this->tokenExpiresAt > time()) return;

        $cached = $this->loadCachedToken();
        if ($cached && $cached['expires_at'] > time()) {
            $this->accessToken = $cached['token'];
            $this->tokenExpiresAt = $cached['expires_at'];
            return;
        }

        $url = 'https://id.twitch.tv/oauth2/token?' . http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("IGDB auth failed: HTTP $httpCode");
        }

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'];
        $this->tokenExpiresAt = time() + $data['expires_in'] - 300;
        $this->cacheToken($this->accessToken, $this->tokenExpiresAt);
    }

    public function post(string $endpoint, string $body): array
    {
        $this->authenticate();
        $this->throttle();

        $ch = curl_init("https://api.igdb.com/v4/{$endpoint}");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Client-ID: ' . $this->clientId,
                'Authorization: Bearer ' . $this->accessToken,
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 429) {
            sleep(1);
            return $this->post($endpoint, $body);
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException("IGDB API error (HTTP $httpCode): " . substr($response, 0, 500));
        }

        return json_decode($response, true) ?? [];
    }

    public function fetchGames(array $fields, int $limit = 500, int $offset = 0, string $where = '', string $sort = ''): array
    {
        $body = 'fields ' . implode(',', $fields) . ';';
        $body .= " limit {$limit}; offset {$offset};";
        if ($where) $body .= " where {$where};";
        if ($sort) $body .= " sort {$sort};";
        return $this->post('games', $body);
    }

    public function fetchGameById(int $id): ?array
    {
        $result = $this->post('games', 'fields name,slug,summary,first_release_date,cover,screenshots,genres,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,platforms,rating,aggregated_rating; where id = ' . $id . ';');
        return $result[0] ?? null;
    }

    public function fetchGameBySlug(string $slug): ?array
    {
        $result = $this->post('games', 'fields name,slug,summary,first_release_date,cover,screenshots,genres,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,platforms,rating,aggregated_rating; where slug = "' . $slug . '";');
        return $result[0] ?? null;
    }

    public function searchGames(string $query): array
    {
        $safe = addslashes($query);
        return $this->post('games', "search \"{$safe}\"; fields name,slug,first_release_date,cover,genres.name,involved_companies.company.name,platforms.name,rating,summary; where version_parent = null; limit 20;");
    }

    public function fetchCovers(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->post('covers', 'fields image_id,game; where game = (' . implode(',', $ids) . '); limit 500;');
    }

    public function fetchScreenshots(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->post('screenshots', 'fields image_id,game; where game = (' . implode(',', $ids) . '); limit 500;');
    }

    public function fetchGenres(array $ids): array
    {
        if (empty($ids)) return [];
        return $this->post('genres', 'fields name; where id = (' . implode(',', $ids) . '); limit 500;');
    }

    private function throttle(): void
    {
        $now = microtime(true);
        $this->requestTimestamps = array_filter($this->requestTimestamps, fn($t) => $t > $now - 1);
        if (count($this->requestTimestamps) >= 4) {
            usleep(250000);
        }
        $this->requestTimestamps[] = $now;
    }

    private function loadCachedToken(): ?array
    {
        if (!file_exists($this->tokenPath)) return null;
        $data = json_decode(file_get_contents($this->tokenPath), true);
        return $data ?? null;
    }

    private function cacheToken(string $token, int $expiresAt): void
    {
        $dir = dirname($this->tokenPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($this->tokenPath, json_encode(['token' => $token, 'expires_at' => $expiresAt]));
    }
}
