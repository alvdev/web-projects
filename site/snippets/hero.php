<?php $featured = $site->find('games')->children()->listed()->filterBy('featured', 'true')->first(); ?>
<?php if ($featured): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative rounded-xl overflow-hidden group">
        <div class="aspect-[21/9] bg-gradient-to-br from-surface to-surface-alt flex items-end p-6">
            <div>
                <span class="text-xs uppercase tracking-widest text-neon-green">Featured</span>
                <h2 class="text-2xl font-bold text-text mt-1"><?= $featured->title() ?></h2>
                <p class="text-sm text-muted mt-1 line-clamp-2"><?= $featured->summary()->kti() ?></p>
            </div>
        </div>
    </a>
    <div class="bg-surface border border-border rounded-xl p-4">
        <h3 class="text-xs uppercase tracking-widest text-neon-magenta mb-3">Trending</h3>
        <div class="space-y-3">
            <?php $trending = $site->find('games')->children()->listed()->sortBy('date', 'desc')->limit(4); ?>
            <?php foreach ($trending as $game): ?>
            <a href="<?= $game->url() ?>" class="block text-sm text-muted hover:text-text transition pb-2 border-b border-border last:border-0">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>
