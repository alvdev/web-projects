<?php

declare(strict_types=1);

namespace Tests\Support;

use Alv\TwitchStats\TwitchClient;

final class FakeTwitchClient extends TwitchClient
{
    public function __construct(
        private array $games = [],
        private array $streams = [],
        private array $spanish = [],
        private array $avatars = [],
    ) {
        parent::__construct('fake-id', 'fake-secret');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getTopGames(int $first = 100): array
    {
        return array_slice($this->games, 0, $first);
    }

    public function getStreams(int $first = 100, string $language = ''): array
    {
        $rows = $language === 'es' ? $this->spanish : $this->streams;

        return array_slice($rows, 0, $first);
    }

    public function getAvatars(array $userIds): array
    {
        $ids = array_flip(array_map('strval', $userIds));

        return array_intersect_key($this->avatars, $ids);
    }
}
