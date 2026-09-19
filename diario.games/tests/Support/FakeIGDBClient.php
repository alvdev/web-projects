<?php

declare(strict_types=1);

namespace Tests\Support;

use DiarioGames\IGDB\IGDBClient;

class FakeIGDBClient extends IGDBClient
{
    public array $gamesBySlug = [];
    public array $searchResults = [];
    public array $gamesResponse = [];
    public array $platforms = [['id' => 6, 'name' => 'PC (Microsoft Windows)']];
    public array $genres = [['id' => 12, 'name' => 'Role-playing (RPG)']];
    public array $themes = [['id' => 1, 'name' => 'Action']];
    public array $covers = [['id' => 1, 'image_id' => 'cover_abc']];
    public array $screenshots = [
        ['id' => 1, 'image_id' => 'shot_1'],
        ['id' => 2, 'image_id' => 'shot_2'],
    ];
    public array $videos = [['id' => 1, 'video_id' => 'yt123']];

    public function __construct()
    {
        parent::__construct('test-client-id', 'test-client-secret');
    }

    public function post(string $endpoint, string $body): array
    {
        return match ($endpoint) {
            'platforms' => $this->platforms,
            'genres' => $this->genres,
            'themes' => $this->themes,
            'covers' => $this->covers,
            'screenshots' => $this->screenshots,
            'videos' => $this->videos,
            default => [],
        };
    }

    public function fetchGameBySlug(string $slug): ?array
    {
        return $this->gamesBySlug[$slug] ?? null;
    }

    public function searchGames(string $query): array
    {
        return $this->searchResults;
    }

    public function fetchGames(array $fields, int $limit = 500, int $offset = 0, string $where = '', string $sort = ''): array
    {
        return $offset === 0 ? $this->gamesResponse : [];
    }
}
