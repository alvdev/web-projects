<?php snippet('header') ?>

<article class="prose-lg dark:prose-invert max-w-3xl mx-auto">
    <?php if ($image = $page->headerImage()): ?>
    <div class="mb-8">
        <img src="<?= $image->url() ?>" alt="<?= $page->title() ?>" class="w-full aspect-video object-cover rounded-xl">
    </div>
    <?php endif ?>

    <h1 class="text-3xl font-bold text-text mb-2"><?= $page->title() ?></h1>

    <div class="flex flex-wrap items-center gap-3 text-sm text-muted mb-6">
        <?php if ($game = $page->parentGame()): ?>
        <a href="<?= $game->url() ?>" class="text-neon-green hover:underline"><?= $game->title() ?></a>
        <?php endif ?>
        <span>• Noticia</span>
        <?php if ($page->newsDate()): ?>
        <span>• <?= $page->newsDate() ?></span>
        <?php endif ?>
        <?php if ($page->newsType()): ?>
        <span class="px-2 py-0.5 rounded border border-neon-green/30 text-neon-green text-xs"><?= $page->newsType() ?></span>
        <?php endif ?>
        <?php if ($page->author()->isNotEmpty()): ?>
        <span>• Por <?= $page->author() ?></span>
        <?php endif ?>
        <?php if ($page->source()): ?>
        <a href="<?= $page->source() ?>" target="_blank" rel="noopener" class="text-neon-cyan hover:underline">Fuente</a>
        <?php endif ?>
    </div>

    <?php if ($page->summary()->isNotEmpty()): ?>
    <div class="bg-surface/50 border border-border rounded-xl p-6 mb-8 text-muted">
        <?= $page->summary()->kt() ?>
    </div>
    <?php endif ?>

    <div class="text-text leading-relaxed mb-8">
        <?= $page->text()->kt() ?>
    </div>
</article>

<?php snippet('footer') ?>
