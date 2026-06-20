<?php $featured = $site->find('games')->children()->filterBy('featured', 'true')->first(); ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <?php if ($featured): ?>
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative rounded-xl overflow-hidden group">
        <?php $heroImg = $featured->cover() ?? $featured->hero() ?>
        <?php if ($heroImg): ?>
        <img src="<?= $heroImg->url() ?>" alt="<?= $featured->title() ?>" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-surface via-surface/60 to-transparent"></div>
        <?php else: ?>
        <div class="absolute inset-0 bg-gradient-to-br from-surface to-surface-alt"></div>
        <?php endif ?>
        <div class="relative aspect-[21/9] flex items-end p-6">
            <div>
                <span class="text-xs uppercase tracking-widest text-neon-green">Featured</span>
                <h2 class="text-2xl font-bold text-text mt-1"><?= $featured->title() ?></h2>
                <p class="text-sm text-muted mt-1 line-clamp-2"><?= $featured->summary()->kti() ?></p>
            </div>
        </div>
    </a>
    <?php endif ?>
    <?php if ($featured): ?>
    <div class="bg-surface border border-border rounded-xl p-4">
    <?php else: ?>
    <div class="lg:col-span-3 bg-surface border border-border rounded-xl p-4">
    <?php endif ?>
        <h3 class="text-xs uppercase tracking-widest text-neon-magenta mb-3">Trending</h3>
        <div class="space-y-3">
            <?php $trending = $site->find('games')->children()->sortBy('release_date', 'desc')->limit(4); ?>
            <?php foreach ($trending as $game): ?>
            <a href="<?= $game->url() ?>" class="block text-sm text-muted hover:text-text transition pb-2 border-b border-border last:border-0">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
