<?php snippet('header') ?>

<h1 class="text-2xl font-bold text-text mb-6">All Games</h1>

<?php $allGames = $page->children()->sortBy('title', 'asc'); ?>
<?php if ($allGames->count() > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($allGames as $game): ?>
        <?php snippet('game-card', ['game' => $game]) ?>
    <?php endforeach ?>
</div>
<?php else: ?>
<p class="text-muted">No games added yet.</p>
<?php endif ?>

<?php snippet('footer') ?>
