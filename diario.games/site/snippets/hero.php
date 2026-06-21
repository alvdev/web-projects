<?php $allGames = $site->find('games')->children()->sortBy('modified', 'desc'); ?>
<?php $allPosts = $allGames->children()->filterBy('template', 'post')->sortBy('date', 'desc'); ?>
<?php $latestPost = $allPosts->first(); ?>
<?php if ($latestPost): $featured = $latestPost; $isPost = true; ?>
<?php else: $featured = $allGames->filterBy('featured', 'true')->first() ?? $allGames->first(); $isPost = false; ?>
<?php endif ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <?php if ($featured): ?>
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative rounded-xl overflow-hidden group">
        <?php $heroImg = $isPost ? ($featured->parent()->cover() ?? $featured->parent()->hero()) : ($featured->cover() ?? $featured->hero()) ?>
        <?php if ($heroImg): ?>
        <img src="<?= $heroImg->url() ?>" alt="<?= $featured->title() ?>" class="absolute inset-0 w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-surface via-surface/60 to-transparent"></div>
        <?php else: ?>
        <div class="absolute inset-0 bg-gradient-to-br from-surface to-surface-alt"></div>
        <?php endif ?>
        <div class="relative aspect-[21/9] flex items-end p-6">
            <div>
                <span class="text-xs uppercase tracking-widest text-neon-green"><?= $isPost ? 'Último post' : ($featured->featured()->isTrue() ? 'Featured' : 'Último añadido') ?></span>
                <h2 class="text-2xl font-bold text-text mt-1"><?= $featured->title() ?></h2>
                <p class="text-sm text-muted mt-1 line-clamp-2"><?= $isPost ? $featured->text()->kti() : $featured->summary()->kti() ?></p>
            </div>
        </div>
    </a>
    <?php endif ?>
    <?php snippet('steam-stats-tabs') ?>
</div>
