<?php $game = $game ?? $page; ?>
<a href="<?= $game->url() ?>" class="block bg-bg border-2 border-pink overflow-hidden hover:border-yellow transition group" style="box-shadow: 0 0 0 0 rgba(255,43,214,0); transition: box-shadow 200ms ease;" onmouseover="this.style.boxShadow='0 0 16px rgba(255,43,214,0.5)'" onmouseout="this.style.boxShadow='0 0 0 0 rgba(255,43,214,0)'">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($hero = $game->hero()): ?>
        <img src="<?= $hero->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php elseif ($cover = $game->cover()): ?>
        <img src="<?= $cover->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center font-graffiti text-pink text-xs uppercase tracking-widest">No cover</div>
        <?php endif ?>
    </div>
    <div class="p-3 bg-surface">
        <h3 class="font-display text-sm md:text-base uppercase text-text leading-tight line-clamp-2"><?= $game->title() ?></h3>
        <div class="flex items-center gap-2 mt-1 font-body text-xs text-muted">
            <span class="text-pink uppercase tracking-wider"><?= htmlspecialchars(implode(', ', array_slice($game->genreList(), 0, 2))) ?></span>
            <?php if ($game->releaseYear()): ?>
            <span class="text-cyan">• <?= $game->releaseYear() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
