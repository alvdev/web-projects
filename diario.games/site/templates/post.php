<?php snippet('header') ?>

<article class="max-w-3xl mx-auto">
    <?php if ($image = $page->headerImage()): ?>
    <div class="border-2 border-pink overflow-hidden mb-8" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <img src="<?= $image->url() ?>" alt="<?= $page->title() ?>" class="w-full aspect-[21/9] max-h-[60vh] object-cover">
    </div>
    <?php endif ?>

    <h1 class="font-display text-4xl md:text-6xl uppercase text-text leading-none mb-3"><?= $page->title() ?></h1>

    <div class="flex flex-wrap items-center gap-3 font-body text-sm text-muted mb-8 border-b border-border pb-4">
        <?php if ($game = $page->parentGame()): ?>
        <span class="font-script text-xl md:text-2xl text-yellow">sobre <?= $game->title() ?></span>
        <?php endif ?>
        <?php if ($page->postDate()): ?>
        <span>• <?= $page->postDate() ?><?php if (preg_match('/^\d{4}/', (string)$page->postDate(), $m)): ?> <span class="text-cyan"><?= $m[0] ?></span><?php endif ?></span>
        <?php endif ?>
        <?php if ($page->author()->isNotEmpty()): ?>
        <span>• Por <span class="text-pink"><?= $page->author() ?></span></span>
        <?php endif ?>
    </div>

    <div class="font-body text-lg text-text leading-relaxed mb-8 [&>blockquote]:border-l-4 [&>blockquote]:border-pink [&>blockquote]:pl-4 [&>blockquote]:my-6 [&>blockquote]:font-script [&>blockquote]:text-2xl [&>h2]:font-display [&>h2]:uppercase [&>h2]:text-2xl [&>h2]:mt-8 [&>h2]:mb-3 [&>h3]:font-display [&>h3]:uppercase [&>h3]:text-xl [&>h3]:mt-6 [&>h3]:mb-2 [&_a]:text-pink [&_a]:underline [&_a]:decoration-pink/40 [&_a]:underline-offset-2 hover:[&_a]:text-yellow drop-cap">
        <?= $page->text()->kt() ?>
    </div>

    <?php $related = $page->relatedGames() ?>
    <?php if ($related->count() > 0): ?>
    <div class="border-t-2 border-pink pt-6 mt-8">
        <h3 class="font-display text-sm uppercase tracking-widest text-yellow mb-3">También aparece en</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($related as $game): ?>
            <a href="<?= $game->url() ?>" class="font-body text-xs px-3 py-1 border-2 border-pink text-pink uppercase tracking-wider hover:bg-pink hover:text-bg transition">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</article>

<?php snippet('footer') ?>
