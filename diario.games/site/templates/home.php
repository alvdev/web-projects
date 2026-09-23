<?php snippet('header') ?>

<?php
$bannerConfig = site()->alvAffBanners();
$hasEnabledPrograms = $bannerConfig['enabled'] && !empty(array_filter($bannerConfig['programs'], fn($p) => $p['enabled']));
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8" data-home-charts-row>
    <?php snippet('steam-stats-tabs') ?>
    <?php snippet('twitch-stats-tabs') ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8" data-home-posts-row>
    <?php snippet('hero', ['latestPosts' => $latestPosts]) ?>
    <?php if ($latestPosts->count() > 0): ?>
        <?php snippet('home-latest-posts', ['posts' => $latestPosts]) ?>
    <?php else: ?>
        <div class="hidden lg:block" aria-hidden="true"></div>
    <?php endif ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php
    $i = 0;
    foreach ($genreGames as $genre => $games):
        $i++;
    ?>
    <div class="bg-surface/50 backdrop-blur-sm border-4 border-border rounded-xl p-4">
        <h2 class="uppercase tracking-wider text-shadow-neon-cyan mb-4">
            <?= htmlspecialchars($genre) ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
