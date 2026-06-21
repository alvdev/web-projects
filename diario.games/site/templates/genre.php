<?php snippet('header') ?>

<?php
$genreSlug = $genreSlug ?? param('genre');
$allGames = $site->find('games')->children();
$games = $allGames->filter(function ($game) use ($genreSlug) {
    $gl = $game->genreList();
    return is_array($gl) && in_array($genreSlug, $gl);
})->sortBy('title', 'asc');

$phrase = \GamePage::GENRE_PHRASES[$genreSlug] ?? null;
?>

<div class="text-center mb-6">
    <h1 class="font-display text-5xl md:text-7xl uppercase text-pink leading-none neon-glow-pink"><?= htmlspecialchars(urldecode($genreSlug)) ?></h1>
    <?php if ($phrase): ?>
        <?php snippet('script-accent', ['text' => $phrase, 'color' => 'yellow', 'size' => 'lg']) ?>
    <?php endif ?>
</div>

<?php snippet('marquee', ['phrase' => urldecode($genreSlug), 'color' => 'pink', 'bg' => 'black', 'speed' => 'medium']) ?>

<?php if ($games->count() > 0): ?>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
    <?php foreach ($games as $game): ?>
        <?php snippet('game-card', ['game' => $game]) ?>
    <?php endforeach ?>
</div>
<?php else: ?>
<div class="border-2 border-pink p-8 text-center bg-bg mt-6" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <p class="font-graffiti text-3xl md:text-4xl text-pink uppercase neon-glow-pink">Próximamente</p>
    <?php snippet('script-accent', ['text' => 'vuelve pronto', 'color' => 'yellow', 'size' => 'md']) ?>
</div>
<?php endif ?>

<?php snippet('footer') ?>
