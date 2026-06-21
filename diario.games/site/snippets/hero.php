<?php
$allGames = $site->find('games')->children()->sortBy('modified', 'desc');
$allPosts = $allGames->children()->filterBy('template', 'post')->sortBy('date', 'desc');
$latestPost = $allPosts->first();
if ($latestPost):
    $featured = $latestPost;
    $isPost = true;
else:
    $featured = $allGames->filterBy('featured', 'true')->first() ?? $allGames->first();
    $isPost = false;
endif;

$accentPhrases = kirby()->option('diario.heroAccentPhrases', ['llega ahora', 'revelado', 'ya disponible']);
$accent = $accentPhrases[array_rand($accentPhrases)];

$eyebrowBg = $isPost ? 'bg-pink' : 'bg-yellow';
$eyebrowColor = $isPost ? 'text-bg' : 'text-bg';
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <?php if ($featured): ?>
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative bg-bg border-2 border-pink overflow-hidden group" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <div class="absolute top-3 right-3 z-10 -rotate-2 <?= $eyebrowBg ?> <?= $eyebrowColor ?> font-display text-xs uppercase tracking-widest px-2 py-1">
            <?= $isPost ? 'Último post' : 'Featured' ?>
        </div>
        <?php $heroImg = $isPost ? ($featured->parent()->cover() ?? $featured->parent()->hero()) : ($featured->cover() ?? $featured->hero()) ?>
        <?php if ($heroImg): ?>
        <img src="<?= $heroImg->url() ?>" alt="<?= $featured->title() ?>" class="w-full aspect-[21/9] object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-bg via-bg/70 to-transparent"></div>
        <?php else: ?>
        <div class="w-full aspect-[21/9]" style="background: linear-gradient(135deg, var(--color-pink), var(--color-purple));"></div>
        <?php endif ?>
        <div class="relative aspect-[21/9] flex items-end p-6 -mt-[var(--aspect-h,80%)]">
            <div class="w-full">
                <h2 class="font-display text-3xl md:text-5xl lg:text-6xl uppercase text-text leading-none"><?= $featured->title() ?></h2>
                <p class="font-script text-2xl md:text-3xl text-pink mt-1 neon-glow-pink"><?= $accent ?></p>
                <p class="font-body text-sm md:text-base text-muted mt-3 max-w-xl line-clamp-2"><?= $isPost ? $featured->text()->kti() : $featured->summary()->kti() ?></p>
                <div class="mt-4 inline-block border-2 border-pink text-pink font-display text-sm uppercase tracking-widest px-5 py-2 neon-glow-pink">► Leer</div>
            </div>
        </div>
    </a>
    <?php endif ?>

    <div class="bg-bg border-2 border-pink p-4" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <h3 class="font-display text-xs uppercase tracking-widest text-yellow glow-pulse mb-3">⚡ Trending</h3>
        <div class="space-y-3">
            <?php $trending = $allGames->limit(4); ?>
            <?php foreach ($trending as $game): ?>
            <a href="<?= $game->url() ?>" class="block font-body font-semibold text-sm text-muted hover:text-pink transition pb-2 border-b border-pink/20 last:border-0">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
