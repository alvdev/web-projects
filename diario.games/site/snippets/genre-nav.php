<?php

$games = $site->find('games')->children();
$allGenres = [];
foreach ($games as $game) {
    foreach ($game->genreList() as $genre) {
        $genre = trim($genre);
        if ($genre) $allGenres[$genre] = true;
    }
}
ksort($allGenres);
$currentGenre = urldecode(param('genre') ?? '');
?>
<nav class="flex gap-4 overflow-x-auto font-body font-semibold uppercase tracking-widest text-sm scrollbar-hide">
    <a href="<?= url('games') ?>" class="text-muted hover:text-pink transition whitespace-nowrap">All</a>
    <?php foreach (array_keys($allGenres) as $genre): ?>
    <a href="<?= url('genre/' . urlencode($genre)) ?>" class="whitespace-nowrap transition pb-1 border-b-2 <?= $currentGenre === $genre ? 'text-pink border-pink' : 'text-muted border-transparent hover:text-pink' ?>">
        <?= htmlspecialchars($genre) ?>
    </a>
    <?php endforeach ?>
</nav>
<style>.scrollbar-hide::-webkit-scrollbar{display:none}.scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}</style>
