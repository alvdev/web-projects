<?php

namespace DiarioGames\IGDB;

class GameImporter
{
    private const GENRE_MAP = [
        'Adventure' => 'Aventura',
        'Role-playing (RPG)' => 'RPG',
        'Shooter' => 'Shooter',
        'Fighting' => 'Lucha',
        'Real Time Strategy (RTS)' => 'Estrategia',
        'Strategy' => 'Estrategia',
        'Turn-based strategy (TBS)' => 'Estrategia',
        'Simulator' => 'Simulación',
        'Sport' => 'Deportes',
        'Racing' => 'Carreras',
        'MOBA' => 'MOBA',
    ];

    private const TAG_MAP = [
        'Arcade' => 'Arcade',
        'Hack and slash/Beat \'em up' => 'Hack and slash',
        'Indie' => 'Indie',
        'Music' => 'Música',
        'Pinball' => 'Pinball',
        'Platform' => 'Plataformas',
        'Point-and-click' => 'Point-and-click',
        'Puzzle' => 'Puzle',
        'Quiz/Trivia' => 'Trivial',
        'Tactical' => 'Táctico',
        'Visual Novel' => 'Novela visual',
        'Card & Board Game' => 'Juego de mesa',
    ];

    private const THEME_TO_GENRE = [
        'Horror' => 'Terror',
        'Survival' => 'Supervivencia',
        'Open world' => 'Mundo abierto',
    ];

    private const THEME_TO_TAG = [
        'Action' => 'Acción',
        'Fantasy' => 'Fantasía',
        'Science fiction' => 'Ciencia ficción',
        'Comedy' => 'Comedia',
        'Drama' => 'Drama',
        'Historical' => 'Histórico',
        'Stealth' => 'Sigilo',
        'Thriller' => 'Thriller',
        'Warfare' => 'Guerra',
        'Sandbox' => 'Sandbox',
        'Business' => 'Negocios',
        'Educational' => 'Educativo',
        'Erotic' => 'Erótico',
        'Kids' => 'Infantil',
        'Mystery' => 'Misterio',
        'Non-fiction' => 'No ficción',
        'Party' => 'Fiesta',
        'Romance' => 'Romance',
        '4X (explore, expand, exploit, and exterminate)' => '4X',
    ];

    private IGDBClient $client;
    private string $gamesDir;

    public function __construct(IGDBClient $client)
    {
        $this->client = $client;
        $this->gamesDir = dirname(__DIR__, 4) . '/content/games';
    }

    private const EXCLUDED_PATTERNS = ['/season/i', '/battle.?pass/i', '/dlc.?pack/i'];

    public static function isExcluded(array $gameData): bool
    {
        $name = $gameData['name'] ?? '';
        foreach (self::EXCLUDED_PATTERNS as $pattern) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }
        return false;
    }

    public function import(array $gameData): ?string
    {
        if (empty($gameData['slug'])) return null;

        if (self::isExcluded($gameData)) {
            echo "  skipped: {$gameData['name']} (excluded)\n";
            return null;
        }

        $slug = \DiarioGames\IGDB\romanToDigits($gameData['slug']);
        $rawSlug = $gameData['slug'];

        // Migrate legacy directory with roman numeral slug
        if ($slug !== $rawSlug) {
            $oldDir = "{$this->gamesDir}/{$rawSlug}";
            if (is_dir($oldDir) && !is_dir("{$this->gamesDir}/{$slug}")) {
                rename($oldDir, "{$this->gamesDir}/{$slug}");
            }
        }

        $dir = "{$this->gamesDir}/{$slug}";

        if (is_dir($dir)) {
            $this->importMissingMedia($gameData, $slug, $dir);
            return $slug;
        }

        mkdir($dir, 0755, true);

        $platformIds = $this->extractIds($gameData['platforms'] ?? []);

        [$genreNames, $tagNames] = $this->resolveGenresAndTags($gameData);
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

        $allScreenshots = [];
        $screenshotStr = '';
        $screenshotIds = $gameData['screenshots'] ?? [];
        if (!empty($screenshotIds)) {
            $allScreenshots = $this->client->fetchScreenshots([$gameData['id']]);
            $ids = array_column($allScreenshots, 'image_id');
            $screenshotStr = implode(', ', $ids);
        }

        $videoStr = '';
        $videoYtIds = [];
        $videoIds = $gameData['videos'] ?? [];
        if (!empty($videoIds)) {
            $allVideos = $this->client->fetchGameVideos([$gameData['id']]);
            $videoYtIds = array_column($allVideos, 'video_id');
            $videoStr = implode(', ', $videoYtIds);
        }

        $websiteStr = '';
        $websiteData = $gameData['websites'] ?? [];
        if (!empty($websiteData)) {
            $pairs = [];
            foreach ($websiteData as $w) {
                if (!empty($w['url'])) {
                    $cat = $w['category'] ?? 0;
                    $pairs[] = $cat . ':' . $w['url'];
                }
            }
            $websiteStr = implode(', ', $pairs);
        }

        $content = "Title: {$name}\n\n----\n\nTemplate: game\n\n----\n\nSummary: {$summary}\n\n----\n\nReleaseDate: {$releaseDate}\n\n----\n\nDeveloper: {$developer}\n\n----\n\nPublisher: {$publisher}\n\n----\n\nGenres: {$genreNames}\n\n----\n\nTags: {$tagNames}\n\n----\n\nPlatforms: {$platformNames}\n\n----\n\nIgdbId: {$gameData['id']}\n\n----\n\nRating: {$rating}\n\n----\n\nAggregatedRating: {$aggRating}\n\n----\n\nFeatured:\n\n----\n\nScreenshots: {$screenshotStr}\n\n----\n\nVideos: {$videoStr}\n\n----\n\nWebsites: {$websiteStr}\n\n----\n";
        file_put_contents("{$dir}/game.txt", $content);

        $coverId = $gameData['cover'] ?? null;
        if ($coverId) {
            $coverData = $this->client->fetchCovers([$gameData['id']]);
            $firstCover = $coverData[0] ?? null;
            if ($firstCover && !empty($firstCover['image_id'])) {
                $this->downloadCover($slug, $dir, $firstCover['image_id']);
            }
        }

        $firstShot = $allScreenshots[0] ?? null;
        if ($firstShot && !empty($firstShot['image_id'])) {
            $this->downloadHero($slug, $dir, $firstShot['image_id']);
        }

        if (!empty($allScreenshots)) {
            $shotIds = array_column($allScreenshots, 'image_id');
            $this->downloadScreenshots($slug, $dir, $shotIds);
        }

        if (!empty($videoYtIds)) {
            $this->downloadVideoThumbs($slug, $dir, $videoYtIds);
        }

        return $slug;
    }

    public function importBySlug(string $slug): ?string
    {
        $data = $this->client->fetchGameBySlug($slug);
        if (!$data) return null;
        return $this->import($data);
    }

    private function resolveGenresAndTags(array $gameData): array
    {
        $genreIds = $this->extractIds($gameData['genres'] ?? []);
        $themeIds = $this->extractIds($gameData['themes'] ?? []);

        $genreNames = [];
        $tagNames = [];

        if (!empty($genreIds)) {
            $igdbGenres = $this->client->fetchGenres($genreIds);
            foreach (array_column($igdbGenres, 'name') as $name) {
                if (isset(self::GENRE_MAP[$name])) {
                    $genreNames[self::GENRE_MAP[$name]] = true;
                } elseif (isset(self::TAG_MAP[$name])) {
                    $tagNames[self::TAG_MAP[$name]] = true;
                } else {
                    $tagNames[$name] = true;
                }
            }
        }

        if (!empty($themeIds)) {
            $igdbThemes = $this->client->fetchThemes($themeIds);
            foreach (array_column($igdbThemes, 'name') as $name) {
                if (isset(self::THEME_TO_GENRE[$name])) {
                    $genreNames[self::THEME_TO_GENRE[$name]] = true;
                } elseif (isset(self::THEME_TO_TAG[$name])) {
                    $tagNames[self::THEME_TO_TAG[$name]] = true;
                } else {
                    $tagNames[$name] = true;
                }
            }
        }

        return [implode(', ', array_keys($genreNames)), implode(', ', array_keys($tagNames))];
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

    private function importMissingMedia(array $gameData, string $slug, string $dir): void
    {
        $coverPath = "{$dir}/{$slug}.jpg";
        if (!file_exists($coverPath)) {
            $coverData = $this->client->fetchCovers([$gameData['id']]);
            $firstCover = $coverData[0] ?? null;
            if ($firstCover && !empty($firstCover['image_id'])) {
                $this->downloadCover($slug, $dir, $firstCover['image_id']);
                echo "  downloaded missing cover for {$slug}\n";
            }
        }

        $heroPath = "{$dir}/{$slug}-hero.jpg";
        $screenshotsMissing = false;
        $screenshotFilesMissing = false;
        $gameTxtPath = "{$dir}/game.txt";
        $gameContent = file_get_contents($gameTxtPath);

        if ($gameContent !== false) {
            $screenshotsMissing = preg_match('/^Screenshots:\s*$/m', $gameContent);

            if (preg_match('/^Tags:\s*$/m', $gameContent)) {
                [$genreNames, $tagNames] = $this->resolveGenresAndTags($gameData);
                if ($tagNames !== '') {
                    if (preg_match('/^Genres:\s*$/m', $gameContent)) {
                        $gameContent = preg_replace('/^Genres:.*$/m', "Genres: {$genreNames}", $gameContent);
                    }
                    $gameContent = preg_replace('/^Tags:.*$/m', "Tags: {$tagNames}", $gameContent);
                    file_put_contents($gameTxtPath, $gameContent);
                    echo "  updated genres/tags for {$slug}\n";
                }
                $gameContent = file_get_contents($gameTxtPath);
            }
        }

        if (!glob("{$dir}/screenshot-*.jpg")) {
            $screenshotFilesMissing = true;
        }

        if (!file_exists($heroPath) || $screenshotsMissing || $screenshotFilesMissing) {
            $allScreenshots = $this->client->fetchScreenshots([$gameData['id']]);

            if (!file_exists($heroPath)) {
                $firstShot = $allScreenshots[0] ?? null;
                if ($firstShot && !empty($firstShot['image_id'])) {
                    $this->downloadHero($slug, $dir, $firstShot['image_id']);
                    echo "  downloaded missing hero for {$slug}\n";
                }
            }

            if (!empty($allScreenshots)) {
                $shotIds = array_column($allScreenshots, 'image_id');
                if ($screenshotsMissing) {
                    $newValue = implode(', ', $shotIds);
                    $gameContent = preg_replace('/^Screenshots:.*$/m', "Screenshots: {$newValue}", $gameContent);
                    file_put_contents($gameTxtPath, $gameContent);
                    echo "  updated screenshots for {$slug}\n";
                }
                $this->downloadScreenshots($slug, $dir, $shotIds);
            }
        }

        if ($gameContent !== false) {
            $gameContent = rtrim($gameContent);

            if (!str_ends_with($gameContent, '----')) {
                $gameContent .= "\n\n----";
            }
            $gameContent .= "\n";

            $videoIds = $gameData['videos'] ?? [];
            if (!empty($videoIds)) {
                $allVideos = $this->client->fetchGameVideos([$gameData['id']]);
                $ytIds = array_column($allVideos, 'video_id');
                $videoStr = implode(', ', $ytIds);
                if (!preg_match('/^Videos:/m', $gameContent)) {
                    $gameContent .= "Videos: {$videoStr}\n\n----\n";
                    file_put_contents($gameTxtPath, $gameContent);
                    echo "  added videos for {$slug}\n";
                } elseif (preg_match('/^Videos:\s*$/m', $gameContent)) {
                    $gameContent = preg_replace('/^Videos:.*$/m', "Videos: {$videoStr}", $gameContent);
                    file_put_contents($gameTxtPath, $gameContent);
                    echo "  updated videos for {$slug}\n";
                }
                $this->downloadVideoThumbs($slug, $dir, $ytIds);
            }

            $websiteData = $gameData['websites'] ?? [];
            if (!empty($websiteData)) {
                $pairs = [];
                foreach ($websiteData as $w) {
                    if (!empty($w['url'])) {
                        $cat = $w['category'] ?? 0;
                        $pairs[] = $cat . ':' . $w['url'];
                    }
                }
                $websiteStr = implode(', ', $pairs);
                if (!preg_match('/^Websites:/m', $gameContent)) {
                    $gameContent .= "Websites: {$websiteStr}\n\n----\n";
                    file_put_contents($gameTxtPath, $gameContent);
                    echo "  added websites for {$slug}\n";
                } elseif (preg_match('/^Websites:\s*$/m', $gameContent)) {
                    $gameContent = preg_replace('/^Websites:.*$/m', "Websites: {$websiteStr}", $gameContent);
                    file_put_contents($gameTxtPath, $gameContent);
                    echo "  updated websites for {$slug}\n";
                }
            }
        }
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

    public function downloadScreenshots(string $slug, string $dir, array $imageIds): void
    {
        foreach ($imageIds as $i => $imageId) {
            $path = "{$dir}/screenshot-{$i}.jpg";
            if (file_exists($path)) continue;
            $url = igdbImageUrl($imageId, 'screenshot_huge');
            if (downloadImage($url, $path)) {
                file_put_contents(
                    "{$dir}/screenshot-{$i}.jpg.txt",
                    "Title: Screenshot {$i}\n\n----\n\nTemplate: screenshot\n\n----\n"
                );
            }
        }
    }

    public function downloadVideoThumbs(string $slug, string $dir, array $videoIds): void
    {
        foreach ($videoIds as $i => $ytId) {
            $path = "{$dir}/video-{$i}.jpg";
            if (file_exists($path)) continue;
            $url = 'https://img.youtube.com/vi/' . $ytId . '/hqdefault.jpg';
            if (downloadImage($url, $path)) {
                file_put_contents(
                    "{$dir}/video-{$i}.jpg.txt",
                    "Title: Video {$i}\n\n----\n\nTemplate: video-thumb\n\n----\n"
                );
            }
        }
    }
}
