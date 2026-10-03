<?php snippet('header') ?>

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

<?php snippet('home-upcoming-releases') ?>
<?php snippet('affiliate-banner', ['itemCount' => 1]) ?>

<?php snippet('footer') ?>
