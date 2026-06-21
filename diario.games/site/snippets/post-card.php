<?php $post = $post ?? $page; ?>
<a href="<?= $post->url() ?>" class="block bg-bg border-2 border-pink overflow-hidden hover:border-yellow transition group" style="box-shadow: 0 0 0 0 rgba(255,43,214,0); transition: box-shadow 200ms ease;" onmouseover="this.style.boxShadow='0 0 16px rgba(255,43,214,0.5)'" onmouseout="this.style.boxShadow='0 0 0 0 rgba(255,43,214,0)'">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($image = $post->headerImage()): ?>
        <img src="<?= $image->url() ?>" alt="<?= $post->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center font-graffiti text-pink text-xs uppercase tracking-widest">No image</div>
        <?php endif ?>
    </div>
    <div class="p-3 bg-surface">
        <h3 class="font-display text-sm md:text-base uppercase text-text leading-tight line-clamp-2"><?= $post->title() ?></h3>
        <div class="font-body text-xs text-muted mt-1">
            <?php if ($game = $post->parentGame()): ?>
            <span class="block font-script text-base md:text-lg text-yellow leading-none">sobre <?= $game->title() ?></span>
            <?php endif ?>
            <?php if ($post->postDate()): ?>
            <span class="block mt-1"><?= $post->postDate() ?><?php if (preg_match('/^(\d{4})/', (string)$post->postDate(), $m)): ?> <span class="text-cyan"><?= $m[1] ?></span><?php endif ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
