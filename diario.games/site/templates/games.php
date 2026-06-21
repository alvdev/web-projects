<?php snippet('header') ?>

<div class="flex items-end justify-between mb-6">
    <h1 class="font-display text-4xl md:text-6xl uppercase text-pink leading-none neon-glow-pink">Todos los juegos</h1>
    <?php snippet('script-accent', ['text' => 'explora el catálogo', 'color' => 'yellow', 'size' => 'md']) ?>
</div>

<?php $allGames = $page->children()->sortBy('title', 'asc'); ?>
<?php if ($allGames->count() > 0): ?>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php foreach ($allGames as $game): ?>
        <?php snippet('game-card', ['game' => $game]) ?>
    <?php endforeach ?>
</div>
<?php else: ?>
<div class="border-2 border-pink p-8 text-center bg-bg" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <p class="font-graffiti text-3xl md:text-4xl text-pink uppercase neon-glow-pink">Próximamente</p>
    <?php snippet('script-accent', ['text' => 'vuelve pronto', 'color' => 'yellow', 'size' => 'md']) ?>
</div>
<?php endif ?>

<?php snippet('footer') ?>
