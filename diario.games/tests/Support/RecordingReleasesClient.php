<?php

declare(strict_types=1);

namespace Tests\Support;

use DiarioGames\IGDB\IGDBClient;

final class RecordingReleasesClient extends IGDBClient
{
    public array $whereClauses = [];

    public function __construct(
        private array $upcoming = [],
        private array $anticipated = [],
        private array $recentlyReleased = []
    ) {
        parent::__construct('test-id', 'test-secret');
    }

    public function post(string $endpoint, string $body): array
    {
        return $endpoint === 'platforms'
            ? [['id' => 6, 'name' => 'PC (Microsoft Windows)']]
            : [];
    }

    public function fetchGames(array $fields, int $limit = 500, int $offset = 0, string $where = '', string $sort = ''): array
    {
        if ($offset > 0) {
            return [];
        }

        $this->whereClauses[] = $where;

        if ($sort === 'hypes desc' && str_contains($where, 'hypes > 0')) {
            return array_slice($this->anticipated, 0, $limit);
        }
        if (str_contains($where, 'first_release_date <=')) {
            return array_slice($this->recentlyReleased, 0, $limit);
        }

        return array_slice($this->upcoming, 0, $limit);
    }
}
