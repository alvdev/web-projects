<?php

use Alv\AffBanners\AffiliateBanners;
use Kirby\Cms\App;

require_once __DIR__ . '/classes/AffiliateBanners.php';

App::plugin('alv/aff-banners', [
    'options' => [
        'enabled'   => true,
        'programs'  => [],
    ],
    'snippets' => [
        'affiliate-banner' => __DIR__ . '/snippets/affiliate-banner.php',
    ],
    'siteMethods' => [
        'alvAffBanners' => function () {
            $enabledField = $this->alv_aff_banner_enabled();

            return [
                'enabled' => AffiliateBanners::isEnabled(
                    $enabledField->isNotEmpty() ? $enabledField->value() : null
                ),
                'programs' => AffiliateBanners::parsePrograms($this->alv_aff_programs()->yaml()),
            ];
        },
    ],
]);
