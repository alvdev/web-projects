<?php

namespace DiarioGames\IGDB;

class GameImporter
{
    private IGDBClient $client;
    private string $gamesDir;

    public function __construct(IGDBClient $client)
    {
        $this->client = $client;
        $this->gamesDir = dirname(__DIR__, 2) . '/content/games';
    }

    public function import(array $gameData): ?string
    {
        if (empty($gameData['slug'])) return null;
        $slug = $gameData['slug'];
        $dir = "{$this->gamesDir}/{$slug}";

        if (is_dir($dir)) return $slug;

        mkdir($dir, 0755, true);

        $genreIds = $this->extractIds($gameData['genres'] ?? []);
        $platformIds = $this->extractIds($gameData['platforms'] ?? []);

        $genreNames = $this->resolveGenreNames($genreIds);
        $platformNames = $this->resolvePlatformNames($platformIds);
        [$developer, $publisher] = $this->resolveInvolvedCompanies($gameData);

        $releaseDate = '';
        if (!empty($gameData['first_release_date'])) {
            $releaseDate = date('Y-m-d', $gameData['first_release_date']);
        }

        $name = $this->stringVal($gameData['name'] ?? '');
        $summary = \DiarioGames\IGDB\translate($this->stringVal($gameData['summary'] ?? ''));
        $rating = $this->stringVal($gameData['rating'] ?? '');
        $aggRating = $this->stringVal($gameData['aggregated_rating'] ?? '');

        $content = "Title: {$name}\n\n----\n\nTemplate: game\n\n----\n\nSummary: {$summary}\n\n----\n\nReleaseDate: {$releaseDate}\n\n----\n\nDeveloper: {$developer}\n\n----\n\nPublisher: {$publisher}\n\n----\n\nGenres: {$genreNames}\n\n----\n\nPlatforms: {$platformNames}\n\n----\n\nIgdbId: {$gameData['id']}\n\n----\n\nRating: {$rating}\n\n----\n\nAggregatedRating: {$aggRating}\n\n----\n\nFeatured:\n\n----\n";
        file_put_contents("{$dir}/game.txt", $content);

        $coverId = $gameData['cover'] ?? null;
        if ($coverId) {
            $coverData = $this->client->fetchCovers([$gameData['id']]);
            $firstCover = $coverData[0] ?? null;
            if ($firstCover && !empty($firstCover['image_id'])) {
                $this->downloadCover($slug, $dir, $firstCover['image_id']);
            }
        }

        $screenshotIds = $gameData['screenshots'] ?? [];
        if (!empty($screenshotIds)) {
            $screenshots = $this->client->fetchScreenshots([$gameData['id']]);
            $firstShot = $screenshots[0] ?? null;
            if ($firstShot && !empty($firstShot['image_id'])) {
                $this->downloadHero($slug, $dir, $firstShot['image_id']);
            }
        }

        return $slug;
    }

    public function importBySlug(string $slug): ?string
    {
        $data = $this->client->fetchGameBySlug($slug);
        if (!$data) return null;
        return $this->import($data);
    }

    private function resolveGenreNames(array $ids): string
    {
        if (empty($ids)) return '';
        $genres = $this->client->fetchGenres($ids);
        $names = array_column($genres, 'name');
        if (empty($names)) return '';
        return \DiarioGames\IGDB\translate(implode(', ', $names));
    }

    private function resolvePlatformNames(array $ids): string
    {
        if (empty($ids)) return '';
        $platforms = $this->client->post('platforms', 'fields name; where id = (' . implode(',', $ids) . '); limit 500;');
        return implode(', ', array_column($platforms, 'name'));
    }

    private function stringVal(mixed $value): string
    {
        if (is_string($value)) return $value;
        if (is_numeric($value)) return (string) $value;
        if (is_array($value) && isset($value[0]) && is_string($value[0])) return $value[0];
        return '';
    }

    private function extractIds(array $items): array
    {
        if (empty($items)) return [];
        // Items can be bare ints ([1, 2, 3]) or objects ([['id' => 1], ['id' => 2]])
        if (is_int($items[0] ?? null)) {
            return $items;
        }
        return array_column($items, 'id');
    }

    private function resolveInvolvedCompanies(array $gameData): array
    {
        $companies = $gameData['involved_companies'] ?? [];
        if (empty($companies)) return ['', ''];

        // If bare IDs, fetch expanded data
        if (is_int($companies[0] ?? null)) {
            $companies = $this->client->post('involved_companies',
                'fields company.name,developer,publisher; where id = (' . implode(',', $companies) . '); limit 500;');
        }

        $developer = '';
        $publisher = '';
        foreach ($companies as $ic) {
            if (!empty($ic['developer'])) {
                $developer = $ic['company']['name'] ?? '';
            }
            if (!empty($ic['publisher'])) {
                $publisher = $ic['company']['name'] ?? '';
            }
        }
        return [$developer, $publisher];
    }

    private function downloadCover(string $slug, string $dir, string $imageId): void
    {
        $url = igdbImageUrl($imageId, 'cover_big');
        $path = "{$dir}/{$slug}.jpg";
        if (downloadImage($url, $path)) {
            file_put_contents(
                "{$dir}/{$slug}.jpg.txt",
                "Title: Cover\n\n----\n\nTemplate: cover\n\n----\n"
            );
        }
    }

    private function downloadHero(string $slug, string $dir, string $imageId): void
    {
        $url = igdbImageUrl($imageId, 'screenshot_huge');
        $path = "{$dir}/{$slug}-hero.jpg";
        if (downloadImage($url, $path)) {
            file_put_contents(
                "{$dir}/{$slug}-hero.jpg.txt",
                "Title: Hero\n\n----\n\nTemplate: hero\n\n----\n"
            );
        }
    }
}
