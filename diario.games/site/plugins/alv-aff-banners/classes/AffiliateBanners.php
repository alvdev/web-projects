<?php

namespace Alv\AffBanners;

final class AffiliateBanners
{
    public static function isEnabled(mixed $raw, bool $default = true): bool
    {
        if ($raw === null || $raw === '') {
            return $default;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }

    public static function parsePrograms(array $raw): array
    {
        $programs = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            [$sm, $md, $xl] = self::parseFrequency($item['frequency'] ?? '1|2|4');
            $enabledRaw = $item['enabled'] ?? true;

            $programs[] = [
                'name'           => $item['name'] ?? '',
                'enabled'        => is_string($enabledRaw)
                    ? filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN)
                    : (bool) $enabledRaw,
                'sm_position'    => max(1, $sm),
                'md_position'    => max(1, $md),
                'xl_position'    => max(1, $xl),
                'type'           => $item['type'] ?? 'instant-gaming',
                'affiliate_id'   => $item['affiliate_id'] ?? '',
                'banner_label'   => $item['banner_label'] ?? 'Ofertas destacadas',
                'banner_sponsor' => $item['banner_sponsor'] ?? 'Patrocinado',
            ];
        }

        return $programs;
    }

    public static function matchingPrograms(int $itemCount, array $programs): array
    {
        $matching = [];

        foreach ($programs as $program) {
            if (empty($program['enabled'])) {
                continue;
            }

            $bpType = null;

            if ($itemCount === $program['sm_position']) {
                $bpType = 'sm';
            } elseif ($itemCount === $program['md_position']) {
                $bpType = 'md';
            } elseif ($itemCount === $program['xl_position']) {
                $bpType = 'xl';
            }

            if ($bpType === null) {
                continue;
            }

            $program['_bpType'] = $bpType;
            $matching[] = $program;
        }

        return $matching;
    }

    private static function parseFrequency(mixed $raw): array
    {
        $sm = 1;
        $md = 2;
        $xl = 4;

        if (is_string($raw) && str_contains($raw, '|')) {
            $parts = explode('|', $raw);
            $sm = (int) ($parts[0] ?? 1);
            $md = (int) ($parts[1] ?? 2);
            $xl = (int) ($parts[2] ?? 4);
        } elseif (is_numeric($raw)) {
            $xl = (int) $raw;
            $md = intdiv($xl, 2);
            $sm = max(1, intdiv($md, 2));
        } elseif (is_string($raw)) {
            if (preg_match('/sm:\s*(\d+)/i', $raw, $m)) $sm = (int) $m[1];
            if (preg_match('/md:\s*(\d+)/i', $raw, $m)) $md = (int) $m[1];
            if (preg_match('/xl:\s*(\d+)/i', $raw, $m)) $xl = (int) $m[1];
        }

        return [$sm, $md, $xl];
    }
}
