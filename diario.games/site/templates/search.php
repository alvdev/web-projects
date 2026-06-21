<?php snippet('header') ?>

<div class="max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <h1 class="font-display text-4xl md:text-5xl uppercase text-pink leading-none neon-glow-pink">Buscar</h1>
        <?php snippet('script-accent', ['text' => 'encuentra tu próxima obsesión', 'color' => 'yellow', 'size' => 'md']) ?>
    </div>

    <form class="mb-8">
        <div class="flex gap-2">
            <input type="search" name="q" value="<?= html($query) ?>" placeholder="busca juegos, posts..." class="flex-1 bg-bg border-2 border-pink text-text font-display text-lg placeholder-muted px-4 py-3 focus:outline-none focus:border-yellow transition" style="box-shadow: 0 0 0 0 rgba(255,43,214,0);" onfocus="this.style.boxShadow='0 0 16px rgba(255,43,214,0.5)'" onblur="this.style.boxShadow='0 0 0 0 rgba(255,43,214,0)'">
            <button type="submit" class="border-2 border-pink text-pink font-display uppercase tracking-widest px-6 py-3 hover:bg-pink hover:text-bg transition neon-glow-pink">► Buscar</button>
        </div>
    </form>

    <?php if ($query): ?>
    <p class="font-body text-sm text-muted mb-4">
        <?php if ($results->count() > 0): ?>
            <?= $results->count() ?> resultado<?= $results->count() !== 1 ? 's' : '' ?> para "<?= html($query) ?>"
        <?php else: ?>
            Sin resultados para "<?= html($query) ?>"
        <?php endif ?>
    </p>

    <div class="space-y-4">
        <?php foreach ($results as $result): ?>
        <a href="<?= $result->url() ?>" class="block bg-bg border-2 border-pink hover:border-yellow p-4 transition" style="box-shadow: 0 0 12px rgba(255,43,214,0.3);">
            <div class="font-graffiti text-xs text-yellow uppercase tracking-widest mb-1"><?= $result->intendedTemplate()->name() === 'post' ? 'Post' : 'Game' ?></div>
            <h3 class="font-display text-lg uppercase text-text"><?= $result->title() ?></h3>
            <p class="font-body text-sm text-muted mt-1"><?= $result->summary()->excerpt(140) ?></p>
        </a>
        <?php endforeach ?>
    </div>

    <?php if (empty($results->count())): ?>
    <div class="border-2 border-pink p-8 text-center bg-bg mt-4" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <p class="font-graffiti text-3xl text-pink uppercase neon-glow-pink">Sin resultados</p>
        <?php snippet('script-accent', ['text' => 'intenta otra cosa', 'color' => 'yellow', 'size' => 'md']) ?>
    </div>
    <?php endif ?>

    <?php if ($pagination && $pagination->pages() > 1): ?>
    <nav class="flex justify-center gap-2 mt-8">
        <?php if ($pagination->hasPrevPage()): ?>
        <a href="<?= $pagination->prevPageUrl() ?>" class="font-display text-sm uppercase tracking-widest px-4 py-2 border-2 border-pink text-pink hover:bg-pink hover:text-bg transition">← Anterior</a>
        <?php endif ?>
        <?php if ($pagination->hasNextPage()): ?>
        <a href="<?= $pagination->nextPageUrl() ?>" class="font-display text-sm uppercase tracking-widest px-4 py-2 border-2 border-pink text-pink hover:bg-pink hover:text-bg transition">Siguiente →</a>
        <?php endif ?>
    </nav>
    <?php endif ?>
    <?php endif ?>
</div>

<?php snippet('footer') ?>
