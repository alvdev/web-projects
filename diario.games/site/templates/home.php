<?php snippet('header') ?>

<?php snippet('hero') ?>

<?php
$bannerConfig = site()->alvAffBanners();
$hasEnabledPrograms = $bannerConfig['enabled'] && !empty(array_filter($bannerConfig['programs'], fn($p) => $p['enabled']));
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php
    $i = 0;
    foreach ($genreGames as $genre => $games):
        $i++;
    ?>
    <div class="bg-surface/50 border border-border rounded-xl p-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-neon-cyan mb-4 pb-2 border-b border-border">
            <?= htmlspecialchars($genre) ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($games as $game): ?>
                <?php snippet('game-card', ['game' => $game]) ?>
            <?php endforeach ?>
        </div>
    </div>
    <?php
        if ($hasEnabledPrograms):
            snippet('affiliate-banner', ['grid' => true, 'itemCount' => $i]);
        endif;
    endforeach;
    ?>
</div>

<?php snippet('footer') ?>
