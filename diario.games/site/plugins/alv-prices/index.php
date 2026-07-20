<?php

use Kirby\Cms\App;

require_once __DIR__ . '/classes/StorePriceDB.php';
require_once __DIR__ . '/classes/StoreAdapter.php';
require_once __DIR__ . '/classes/PriceFetcher.php';
require_once __DIR__ . '/classes/adapters/ItadAdapter.php';
require_once __DIR__ . '/classes/adapters/InstantGamingAdapter.php';

App::plugin('alv/prices', [
    'snippets' => [
        'price-comparison' => __DIR__ . '/snippets/price-comparison.php',
    ],
    'siteMethods' => [
        'priceComparison' => function (string $slug, string $gameName): array {
            try {
                $fetcher = \Alv\Prices\PriceFetcher::createFromEnv();
                return $fetcher->fetch($slug, $gameName);
            } catch (\Throwable $e) {
                error_log('priceComparison error: ' . $e->getMessage());
                return [];
            }
        },
    ],
]);
