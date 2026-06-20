<?php snippet('header') ?>

<?php
$genreSlug = $genreSlug ?? param('genre');
$allGames = $site->find('games')->children();
$games = $allGames->filter(function ($game) use ($genreSlug) {
    $gl = $game->genreList();
    return is_array($gl) && in_array($genreSlug, $gl);
})->sortBy('title', 'asc');
?>

<h1 class="text-2xl font-bold text-neon-cyan mb-6"><?= htmlspecialchars(urldecode($genreSlug)) ?></h1>

<?php if ($games->count() > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($games as $game): ?>
        <?php snippet('game-card', ['game' => $game]) ?>
    <?php endforeach ?>
</div>
<?php else: ?>
<p class="text-muted">No games found in this category yet.</p>
<?php endif ?>

<?php snippet('footer') ?>
