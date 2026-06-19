<?php snippet('header') ?>

<?php
$genreSlug = $genreSlug ?? param('genre');

$labels = [
    'accion' => 'Acción', 'aventura' => 'Aventura',
    'rpg' => 'RPG', 'shooter' => 'Shooter',
    'estrategia' => 'Estrategia', 'simulacion' => 'Simulación',
    'deportes' => 'Deportes y Carreras', 'terror' => 'Terror',
    'puzzle' => 'Puzzle', 'supervivencia' => 'Supervivencia',
    'mundo-abierto' => 'Mundo abierto', 'multijugador' => 'Multijugador',
];

$genreLabel = $labels[$genreSlug] ?? $genreSlug;
$allGames = $site->find('games')->children()->listed();
$games = $allGames->filter(function ($game) use ($genreSlug) {
    $genres = $game->content()->get('genres')->split(',');
    $genres = array_map('trim', $genres);
    return in_array($genreSlug, $genres);
})->sortBy('title', 'asc');
?>

<h1 class="text-2xl font-bold text-neon-cyan mb-6"><?= $genreLabel ?></h1>

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
