<?php

namespace DiarioGames\IGDB;

class AutoFetcher
{
    private IGDBClient $client;
    private GameImporter $importer;
    private int $imported = 0;
    private int $skipped = 0;

    public function __construct(IGDBClient $client, GameImporter $importer)
    {
        $this->client = $client;
        $this->importer = $importer;
    }

    public function run(int $maxGames = 0): array
    {
        $allowedKeywords = ['pc', 'xbox', 'playstation', 'nintendo', 'android'];
        $allPlatforms = $this->client->fetchAllPlatforms();
        $allowedPlatformIds = [];
        foreach ($allPlatforms as $p) {
            $lower = mb_strtolower($p['name']);
            foreach ($allowedKeywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    $allowedPlatformIds[] = $p['id'];
                    break;
                }
            }
        }

        if (empty($allowedPlatformIds)) {
            throw new \RuntimeException('No allowed platform IDs found from IGDB');
        }

        $platformWhere = 'platforms = (' . implode(',', $allowedPlatformIds) . ')';

        $offset = 0;
        $limit = 500;

        while (true) {
            if ($maxGames > 0 && $this->imported >= $maxGames) break;

            $games = $this->client->fetchGames(
                ['name', 'slug', 'summary', 'first_release_date', 'cover', 'screenshots',
                 'videos', 'websites.category', 'websites.url',
                 'genres', 'themes', 'involved_companies', 'platforms', 'rating', 'aggregated_rating'],
                $limit,
                $offset,
                $platformWhere,
                'rating desc'
            );

            if (empty($games)) break;

            foreach ($games as $game) {
                if ($maxGames > 0 && $this->imported >= $maxGames) break 2;

                $result = $this->importer->import($game);
                if ($result) {
                    $this->imported++;
                    echo "  [{$this->imported}] imported: {$game['name']}\n";
                } else {
                    $this->skipped++;
                }
            }

            $offset += $limit;
            echo "  offset {$offset}, imported {$this->imported}, skipped {$this->skipped}\n";
        }

        return ['imported' => $this->imported, 'skipped' => $this->skipped];
    }
}
