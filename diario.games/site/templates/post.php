<?php snippet('header') ?>

<article class="max-w-3xl mx-auto">
    <?php if ($image = $page->headerImage()): ?>
    <div class="rounded-xl overflow-hidden mb-8">
        <img src="<?= $image->url() ?>" alt="<?= $page->title() ?>" class="w-full aspect-video object-cover">
    </div>
    <?php endif ?>

    <h1 class="text-3xl font-bold text-text mb-2"><?= $page->title() ?></h1>

    <div class="flex flex-wrap items-center gap-3 text-sm text-muted mb-6">
        <?php if ($game = $page->parentGame()): ?>
        <a href="<?= $game->url() ?>" class="text-neon-green hover:underline"><?= $game->title() ?></a>
        <?php endif ?>
        <?php if ($page->postDate()): ?>
        <span>• <?= $page->postDate() ?></span>
        <?php endif ?>
        <?php if ($page->author()->isNotEmpty()): ?>
        <span>• By <?= $page->author() ?></span>
        <?php endif ?>
    </div>

    <div class="text-text leading-relaxed mb-8">
        <?= $page->text()->kt() ?>
    </div>

    <?php $related = $page->relatedGames() ?>
    <?php if ($related->count() > 0): ?>
    <div class="border-t border-border pt-6 mt-8">
        <h3 class="text-sm font-semibold text-muted mb-3">Also appears in:</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($related as $game): ?>
            <div class="relative inline-flex items-center">
                <button type="button"
                    class="site-fav text-xs text-muted hover:text-yellow-400 transition mr-1"
                    data-slug="<?= $game->slug() ?>"
                    data-title="<?= htmlspecialchars($game->title()) ?>"
                    data-cover="<?= ($cover = $game->cover()) ? $cover->url() : (($hero = $game->hero()) ? $hero->url() : '') ?>">☆</button>
                <a href="<?= $game->url() ?>" class="text-xs px-3 py-1 rounded-full border border-neon-green/30 text-neon-green bg-neon-green/5 hover:bg-neon-green/10 transition">
                    <?= $game->title() ?>
                </a>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</article>

<?php snippet('footer') ?>
