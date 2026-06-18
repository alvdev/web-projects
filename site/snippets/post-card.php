<?php $post = $post ?? $page; ?>
<a href="<?= $post->url() ?>" class="block bg-surface border border-border rounded-lg overflow-hidden hover:border-neon-magenta/50 transition group">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($image = $post->headerImage()): ?>
        <img src="<?= $image->url() ?>" alt="<?= $post->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-muted text-sm">No image</div>
        <?php endif ?>
    </div>
    <div class="p-3">
        <h3 class="font-semibold text-text text-sm"><?= $post->title() ?></h3>
        <div class="text-xs text-muted mt-1">
            <?php if ($game = $post->parentGame()): ?>
            <span class="text-neon-green"><?= $game->title() ?></span>
            <?php endif ?>
            <?php if ($post->postDate()): ?>
            <span>• <?= $post->postDate() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
