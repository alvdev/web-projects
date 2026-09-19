<?php

declare(strict_types=1);

namespace Tests\Support;

final class PluginClasses
{
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $root = dirname(__DIR__, 2);

        $files = [
            '/site/plugins/alv-ai/classes/AIClient.php',
            '/site/plugins/alv-steam-stats/classes/SteamStatsDB.php',
            '/site/plugins/alv-steam-stats/classes/SteamStats.php',
            '/site/plugins/alv-steam-stats/classes/SteamStatsCollector.php',
            '/site/plugins/alv-prices/classes/StorePriceDB.php',
            '/site/plugins/alv-prices/classes/StoreAdapter.php',
            '/site/plugins/alv-prices/classes/adapters/ItadAdapter.php',
            '/site/plugins/alv-prices/classes/adapters/G2AAdapter.php',
            '/site/plugins/alv-prices/classes/adapters/InstantGamingAdapter.php',
            '/site/plugins/alv-prices/classes/PriceFetcher.php',
            '/site/plugins/alv-igdb/classes/helpers.php',
            '/site/plugins/alv-igdb/classes/IGDBClient.php',
            '/site/plugins/alv-igdb/classes/GameImporter.php',
            '/site/plugins/alv-igdb/classes/AutoFetcher.php',
        ];

        foreach ($files as $file) {
            require_once $root . $file;
        }

        self::$loaded = true;
    }
}
