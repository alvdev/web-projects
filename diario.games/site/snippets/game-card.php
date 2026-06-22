<?php $game = $game ?? $page; ?>
<a href="<?= $game->url() ?>" class="relative block bg-surface border-2 border-border rounded-lg overflow-hidden hover:border-neon-magenta/50 transition group">
    <div class="aspect-2/3 bg-surface-alt overflow-hidden">
        <?php if ($hero = $game->hero()): ?>
        <img src="<?= $hero->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php elseif ($cover = $game->cover()): ?>
        <img src="<?= $cover->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-muted text-sm">No image</div>
        <?php endif ?>
    </div>
    <div class="absolute top-0 inset-x-0 p-3 pb-20 bg-linear-to-b from-black/90 via-black/60 via-50% to-transparent">
        <h3 class="font-semibold text-text text-sm"><?= $game->title() ?></h3>
        <div class="flex items-center gap-2 mt-1 text-xs text-muted">
            <span class="text-neon-cyan"><?= implode(', ', array_slice($game->genreList(), 0, 2)) ?></span>
            <?php if ($game->releaseYear()): ?>
            <span>• <?= $game->releaseYear() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
