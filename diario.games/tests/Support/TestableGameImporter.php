<?php

declare(strict_types=1);

namespace Tests\Support;

use DiarioGames\IGDB\GameImporter;

class TestableGameImporter extends GameImporter
{
    public array $iconsFetched = [];
    public array $translated = [];
    public bool $steamAppIdValid = false;
    public ?int $liveSteamPlayers = null;

    protected function translateText(string $text, string $backend = 'opencode'): string
    {
        $this->translated[] = $text;
        return $text === '' ? '' : '[es] ' . $text;
    }

    protected function downloadImageTo(string $url, string $destPath): bool
    {
        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return file_put_contents($destPath, 'fake-bytes:' . $url) !== false;
    }

    protected function fetchThesvgIconFor(string $url): ?array
    {
        $this->iconsFetched[] = $url;
        return null;
    }

    protected function verifySteamAppId(int $appid): bool
    {
        return $this->steamAppIdValid;
    }

    protected function fetchSteamCurrentPlayers(string $apiKey, int $appid): ?int
    {
        return $this->liveSteamPlayers;
    }

    protected function steamApiKey(): string
    {
        return 'test-api-key';
    }

    protected function makeSteamCollector(string $apiKey): \Alv\SteamStats\SteamStatsCollector
    {
        return new class($apiKey) extends \Alv\SteamStats\SteamStatsCollector {
            public function downloadCapsule(int $appid, ?string $slug = null): ?string
            {
                return null;
            }
        };
    }
}
