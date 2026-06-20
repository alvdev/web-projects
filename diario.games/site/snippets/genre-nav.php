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
?>
<nav class="flex gap-4 overflow-x-auto text-sm">
    <a href="<?= url('games') ?>" class="text-muted hover:text-neon-cyan transition whitespace-nowrap">All</a>
    <?php foreach (array_keys($allGenres) as $genre): ?>
    <a href="<?= url('genre/' . urlencode($genre)) ?>" class="text-muted hover:text-neon-cyan transition whitespace-nowrap">
        <?= htmlspecialchars($genre) ?>
    </a>
    <?php endforeach ?>
</nav>
