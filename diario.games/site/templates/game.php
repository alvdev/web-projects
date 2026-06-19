<?php snippet('header') ?>

<div class="relative rounded-xl overflow-hidden mb-8">
    <div class="aspect-[21/9] bg-gradient-to-br from-surface to-surface-alt flex items-end p-6">
        <div>
            <h1 class="text-3xl font-bold text-text"><?= $page->title() ?></h1>
            <div class="flex flex-wrap gap-2 mt-2">
                <?php foreach ($page->genres() as $genre): ?>
                <span class="text-xs px-2 py-1 rounded border border-neon-cyan/30 text-neon-cyan bg-neon-cyan/5"><?= $genre ?></span>
                <?php endforeach ?>
                <?php if ($page->releaseDate()): ?>
                <span class="text-xs px-2 py-1 rounded text-muted">Release: <?= $page->releaseDate() ?></span>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="text-sm text-muted mb-4">
            <?php if ($page->developer()->isNotEmpty()): ?><span><?= $page->developer() ?></span><?php endif ?>
            <?php if ($page->publisher()->isNotEmpty()): ?><span> • <?= $page->publisher() ?></span><?php endif ?>
        </div>
        <?php if ($page->summary()->isNotEmpty()): ?>
        <div class="text-text leading-relaxed mb-8">
            <?= $page->summary()->kt() ?>
        </div>
        <?php endif ?>
    </div>
</div>

<?php $posts = $page->posts() ?>
<?php if ($posts->count() > 0): ?>
<div class="mt-8 pt-8 border-t border-border">
    <h2 class="text-lg font-bold text-neon-green mb-6">📰 Posts about <?= $page->title() ?></h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($posts as $post): ?>
            <?php snippet('post-card', ['post' => $post]) ?>
        <?php endforeach ?>
    </div>
</div>
<?php endif ?>

<?php snippet('footer') ?>
