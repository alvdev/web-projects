<?php $news = $news ?? $page; ?>
<a href="<?= $news->url() ?>"
   class="block rounded-lg overflow-hidden hover:border-neon-green/50 transition group relative aspect-video border border-border">
    <?php if ($image = $news->headerImage()): ?>
    <img src="<?= $image->url() ?>" alt="<?= $news->title() ?>"
         class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-300">
    <?php else: ?>
    <div class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-neon-green/20 to-surface-alt text-muted text-sm gap-2">
        <svg class="w-10 h-10 text-neon-green/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
    </div>
    <?php endif ?>

    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

    <span class="absolute top-2 left-2 px-2 py-0.5 text-[10px] font-bold rounded bg-neon-green text-black shadow-lg">Noticia</span>

    <div class="absolute bottom-0 left-0 right-0 p-3">
        <h3 class="font-semibold text-white text-sm leading-tight"><?= $news->title() ?></h3>
        <div class="flex items-center gap-2 mt-1.5">
            <?php if ($news->newsType()): ?>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-neon-green/20 text-neon-green"><?= $news->newsType() ?></span>
            <?php endif ?>
            <?php if ($news->newsDate()): ?>
            <span class="text-[10px] text-white/60 ml-auto"><?= $news->newsDate() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
