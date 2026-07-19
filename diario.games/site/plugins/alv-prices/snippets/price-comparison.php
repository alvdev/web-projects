<?php

$prices = $prices ?? [];

if (empty($prices)) return;

$storeFavicons = [
    'Steam'            => 'store.steampowered.com',
    'GOG'              => 'gog.com',
    'Epic Games Store' => 'store.epicgames.com',
    'GreenManGaming'   => 'greenmangaming.com',
    'Fanatical'        => 'fanatical.com',
    'Humble Store'     => 'humblebundle.com',
    'GameBillet'       => 'gamebillet.com',
    'GamersGate'       => 'gamersgate.com',
    'AllYouPlay'       => 'allyouplay.com',
    'IndieGala Store'  => 'indiegala.com',
    'WinGameStore'     => 'wingamestore.com',
    'JoyBuggy'         => 'joybuggy.com',
    'eTail.Market'     => 'etail.market',
];

$faviconDir = __DIR__ . '/../favicons';
if (!is_dir($faviconDir)) {
    mkdir($faviconDir, 0755, true);
}

$resolveFavicon = function (string $domain) use ($faviconDir): string {
    $file = $faviconDir . '/' . $domain . '.png';
    if (!file_exists($file)) {
        $src = 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=32';
        $img = @file_get_contents($src);
        if ($img !== false) {
            @file_put_contents($file, $img);
        }
    }
    return file_exists($file)
        ? '/site/plugins/alv-prices/favicons/' . $domain . '.png'
        : 'https://www.google.com/s2/favicons?domain=' . urlencode($domain) . '&sz=32';
};
?>
<div class="mt-16 mb-10">
    <h2 class="text-lg font-bold text-neon-green mb-6">Comparar precios</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($prices as $item): ?>
            <?php
            $storeName = htmlspecialchars($item['storeName'] ?? '', ENT_QUOTES);
            $storeLogo = htmlspecialchars($item['storeLogo'] ?? '', ENT_QUOTES);
            $price = $item['price'] ?? 0;
            $initial = $item['initialPrice'] ?? null;
            $discount = $item['discount'] ?? null;
            $currency = $item['currency'] ?? 'EUR';
            $url = $item['url'] ?? '#';
            $platforms = $item['platforms'] ?? '';
            $faviconDomain = $storeFavicons[$item['storeName']] ?? null;

            $currencySymbol = match ($currency) {
                'EUR' => '€',
                'USD' => '$',
                'GBP' => '£',
                default => $currency,
            };
            ?>
            <a href="<?= htmlspecialchars($url) ?>"
               target="_blank" rel="noopener sponsored"
               data-store="<?= htmlspecialchars($item['storeName'] ?? '') ?>"
               data-price="<?= $price ?>"
               class="flex items-center gap-4 p-4 bg-surface border border-border rounded-xl
                      hover:border-neon-cyan/50 hover:bg-surface-alt transition group">
                <?php
                $svgPath = __DIR__ . '/../../../../assets/svgs/' . $storeLogo . '.svg';
                if (file_exists($svgPath)): ?>
                    <span class="w-8 h-8 shrink-0 flex items-center justify-center text-white [&_svg]:w-full [&_svg]:h-full"><?= svg('assets/svgs/' . $storeLogo . '.svg') ?></span>
                <?php elseif ($faviconDomain):
                    $faviconSrc = $resolveFavicon($faviconDomain); ?>
                    <img src="<?= htmlspecialchars($faviconSrc) ?>"
                         alt="<?= $storeName ?>" width="16" height="16"
                         class="w-8 h-8 rounded shrink-0">
                <?php else: ?>
                    <div class="w-8 h-8 flex items-center justify-center rounded bg-surface-alt text-muted text-xs font-bold shrink-0">
                        <?= mb_substr($storeName, 0, 1) ?>
                    </div>
                <?php endif ?>

                <div class="flex-1 min-w-0">
                    <span class="text-sm text-muted truncate block"><?= $storeName ?></span>
                    <?php if ($platforms): ?>
                        <span class="text-[10px] text-muted/60 block mt-0.5"><?= htmlspecialchars($platforms) ?></span>
                    <?php endif ?>
                </div>

                <div class="text-right shrink-0">
                    <?php if ($discount && $initial): ?>
                    <div class="flex items-center gap-2 justify-end">
                        <span class="text-xs text-muted line-through"><?= $currencySymbol . number_format($initial, 2) ?></span>
                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-neon-green/15 text-neon-green rounded">-<?= $discount ?>%</span>
                    </div>
                    <?php endif ?>
                    <span class="text-lg font-bold text-neon-green group-hover:scale-110 transition-transform inline-block">
                        <?= $currencySymbol . number_format($price, 2) ?>
                    </span>
                </div>
            </a>
        <?php endforeach ?>
    </div>
</div>
