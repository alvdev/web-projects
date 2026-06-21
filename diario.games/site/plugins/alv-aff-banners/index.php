<?php

use Kirby\Cms\App;

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
            $enabled = $enabledField->isNotEmpty()
                ? filter_var($enabledField->value(), FILTER_VALIDATE_BOOLEAN)
                : true;

            $programs = [];
            $raw = $this->alv_aff_programs()->yaml();
            foreach ($raw as $item) {
                $posRaw = $item['frequency'] ?? '1|2|4';

                $sm = 1;
                $md = 2;
                $xl = 4;

                if (is_string($posRaw) && str_contains($posRaw, '|')) {
                    $parts = explode('|', $posRaw);
                    $sm = (int) ($parts[0] ?? 1);
                    $md = (int) ($parts[1] ?? 2);
                    $xl = (int) ($parts[2] ?? 4);
                } elseif (is_numeric($posRaw)) {
                    $xl = (int) $posRaw;
                    $md = intdiv($xl, 2);
                    $sm = max(1, intdiv($md, 2));
                } elseif (is_string($posRaw)) {
                    if (preg_match('/sm:\s*(\d+)/i', $posRaw, $m)) $sm = (int) $m[1];
                    if (preg_match('/md:\s*(\d+)/i', $posRaw, $m)) $md = (int) $m[1];
                    if (preg_match('/xl:\s*(\d+)/i', $posRaw, $m)) $xl = (int) $m[1];
                }

                $enabledRaw = $item['enabled'] ?? true;
                if (is_string($enabledRaw)) {
                    $progEnabled = filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN);
                } else {
                    $progEnabled = (bool) $enabledRaw;
                }

                $programs[] = [
                    'name'           => $item['name'] ?? '',
                    'enabled'        => $progEnabled,
                    'sm_position'    => max(1, $sm),
                    'md_position'    => max(1, $md),
                    'xl_position'    => max(1, $xl),
                    'type'           => $item['type'] ?? 'instant-gaming',
                    'affiliate_id'   => $item['affiliate_id'] ?? '',
                    'banner_label'   => $item['banner_label'] ?? 'Ofertas destacadas',
                    'banner_sponsor' => $item['banner_sponsor'] ?? 'Patrocinado',
                ];
            }
            return [
                'enabled'  => $enabled,
                'programs' => $programs,
            ];
        },
    ],
]);
