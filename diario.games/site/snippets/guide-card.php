<?php $guide = $guide ?? $page; ?>
<a href="<?= $guide->url() ?>"
   class="block rounded-lg overflow-hidden hover:border-neon-magenta/50 transition group relative aspect-video border border-border">
    <?php if ($image = $guide->headerImage()): ?>
    <img src="<?= $image->url() ?>" alt="<?= $guide->title() ?>"
         class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-300">
    <?php else: ?>
    <div class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-neon-magenta/20 to-surface-alt text-muted text-sm gap-2">
        <svg class="w-10 h-10 text-neon-magenta/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
    </div>
    <?php endif ?>

    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

    <span class="absolute top-2 left-2 px-2 py-0.5 text-[10px] font-bold rounded bg-neon-magenta text-white shadow-lg">Guia</span>

    <div class="absolute bottom-0 left-0 right-0 p-3">
        <h3 class="font-semibold text-white text-sm leading-tight"><?= $guide->title() ?></h3>
        <div class="flex items-center gap-2 mt-1.5">
            <?php if ($guide->category()): ?>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-neon-cyan/20 text-neon-cyan"><?= $guide->category() ?></span>
            <?php endif ?>
            <?php if ($guide->difficulty()): ?>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-neon-magenta/20 text-neon-magenta"><?= $guide->difficulty() ?></span>
            <?php endif ?>
            <?php if ($guide->guideDate()): ?>
            <span class="text-[10px] text-white/60 ml-auto"><?= $guide->guideDate() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
