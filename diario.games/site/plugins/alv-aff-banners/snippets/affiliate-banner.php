<?php

$grid = $grid ?? false;
$itemCount = $itemCount ?? 0;

$config = site()->alvAffBanners();

if (!$config['enabled']) return;
if (empty($config['programs'])) return;

$enabledPrograms = array_filter($config['programs'], fn($p) => $p['enabled']);
if (empty($enabledPrograms)) return;

if (!isset($GLOBALS['alv_aff_banners_init'])) {
    $GLOBALS['alv_aff_banners_init'] = true;
    $GLOBALS['alv_aff_banners_shown'] = [];
    $GLOBALS['alv_aff_scripts_emitted'] = [];
?>
<style>
.alv-aff-bp-sm { display: block !important; }
.alv-aff-bp-md { display: none !important; }
.alv-aff-bp-xl { display: none !important; }
@media (min-width: 768px) {
    .alv-aff-bp-sm { display: none !important; }
    .alv-aff-bp-md { display: block !important; }
    .alv-aff-bp-xl { display: none !important; }
}
@media (min-width: 1280px) {
    .alv-aff-bp-sm { display: none !important; }
    .alv-aff-bp-md { display: none !important; }
    .alv-aff-bp-xl { display: block !important; }
}
</style>
<?php
}

$matching = [];

foreach ($enabledPrograms as $program) {
    $bpType = null;

    if ($itemCount === $program['sm_position']) {
        $bpType = 'sm';
    } elseif ($itemCount === $program['md_position']) {
        $bpType = 'md';
    } elseif ($itemCount === $program['xl_position']) {
        $bpType = 'xl';
    }

    if ($bpType === null) continue;

    $showKey = $program['name'] . '_' . $bpType;
    if (in_array($showKey, $GLOBALS['alv_aff_banners_shown'])) continue;

    $GLOBALS['alv_aff_banners_shown'][] = $showKey;
    $program['_bpType'] = $bpType;
    $matching[] = $program;
}

if (empty($matching)) return;

$grouped = [];
foreach ($matching as $program) {
    $grouped[$program['_bpType']][] = $program;
}

foreach ($grouped as $bpType => $programs):
    $bpClass = "alv-aff-bp-{$bpType}";
?>
<section class="alv-aff-banner-wrapper <?= $grid ? 'col-span-full' : '-mx-4 px-4' ?> <?= $bpClass ?>" aria-label="Ofertas de juegos">
    <?php foreach ($programs as $program): ?>
        <?php
        $name = htmlspecialchars($program['name'] ?? 'Affiliate', ENT_QUOTES);
        $type = $program['type'] ?? 'instant-gaming';
        $affiliateId = htmlspecialchars($program['affiliate_id'] ?? '', ENT_QUOTES);
        $label = htmlspecialchars($program['banner_label'] ?? 'Ofertas destacadas', ENT_QUOTES);
        $sponsor = htmlspecialchars($program['banner_sponsor'] ?? 'Patrocinado', ENT_QUOTES);
        $selector = 'ig-aff-banner-' . strtolower(str_replace(' ', '-', $program['name'] ?? 'affiliate')) . '-' . $bpType;
        ?>

        <?php if (!$grid): ?>
        <div class="max-w-7xl mx-auto mb-3 flex items-center justify-between">
            <p class="text-xs uppercase tracking-widest text-muted"><?= $label ?></p>
            <span class="text-[10px] text-muted/60"><?= $sponsor ?> · <?= $name ?></span>
        </div>
        <?php endif ?>

        <?php if ($type === 'instant-gaming' && !in_array($selector, $GLOBALS['alv_aff_scripts_emitted'])): ?>
        <script>
          window.igBannerConfig = window.igBannerConfig || {
            lang: 'es',
            igr: '<?= $affiliateId ?>',
            banners: []
          };
          if (!window.igBannerConfig.banners.includes('<?= $selector ?>')) {
            window.igBannerConfig.banners.push('<?= $selector ?>');
          }
          window.igBannerConfig.igr = '<?= $affiliateId ?>';
        </script>
        <?php
            $GLOBALS['alv_aff_scripts_emitted'][] = $selector;
            if (!in_array('ig_loader', $GLOBALS['alv_aff_scripts_emitted'])):
                $GLOBALS['alv_aff_scripts_emitted'][] = 'ig_loader';
        ?>
        <script src="https://www.instant-gaming.com/api/banner/partner/loader.js" defer></script>
        <?php endif ?>
        <?php endif ?>

        <?php if ($type === 'instant-gaming'): ?>
        <div class="<?= $selector ?> min-h-30 w-full"></div>
        <?php elseif ($type === 'generic' && $affiliateId): ?>
        <a href="<?= $affiliateId ?>" target="_blank" rel="noopener sponsored" class="block min-h-30 w-full bg-surface border border-border rounded-xl hover:border-neon-cyan transition">
            <div class="flex items-center justify-center h-full p-6 text-center text-muted hover:text-text">
                <?= $label ?>
            </div>
        </a>
        <?php endif ?>
    <?php endforeach ?>
</section>
<?php endforeach;
