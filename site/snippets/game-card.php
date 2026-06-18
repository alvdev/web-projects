<?php $game = $game ?? $page; ?>
<a href="<?= $game->url() ?>" class="block bg-surface border border-border rounded-lg overflow-hidden hover:border-neon-cyan/50 transition group">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($cover = $game->cover()): ?>
        <img src="<?= $cover->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-muted text-sm">No image</div>
        <?php endif ?>
    </div>
    <div class="p-3">
        <h3 class="font-semibold text-text text-sm"><?= $game->title() ?></h3>
        <div class="flex items-center gap-2 mt-1 text-xs text-muted">
            <span class="text-neon-cyan"><?= implode(', ', array_slice($game->genres(), 0, 2)) ?></span>
            <?php if ($game->releaseDate()): ?>
            <span>• <?= $game->releaseDate() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
