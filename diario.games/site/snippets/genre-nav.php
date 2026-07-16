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
$genreKeys = array_keys($allGenres);
$total = count($genreKeys);
$perColumn = (int) ceil($total / 3);
$columns = array_chunk($genreKeys, $perColumn);
?>
<div id="categories-nav" class="relative shrink-0">
    <button type="button" popovertarget="categories-popup"
        class="px-3 py-1.5 text-sm bg-surface border border-border rounded-lg text-text hover:border-neon-cyan transition whitespace-nowrap">
        Categorías
    </button>
    <div id="categories-popup" popover
         class="w-140 max-w-[90vw] bg-surface border border-border rounded-lg shadow-xl z-50 p-4">
        <div class="flex gap-4">
            <?php foreach ($columns as $i => $colGenres): ?>
            <div class="flex-1 flex flex-col gap-1">
                <?php if ($i === 0): ?>
                <a href="<?= url('games') ?>" class="text-sm text-neon-cyan hover:text-neon-magenta transition whitespace-nowrap font-medium">Todos</a>
                <?php endif ?>
                <?php foreach ($colGenres as $genre): ?>
                <a href="<?= url('genre/' . urlencode($genre)) ?>" class="text-sm text-muted hover:text-neon-cyan transition whitespace-nowrap">
                    <?= htmlspecialchars($genre) ?>
                </a>
                <?php endforeach ?>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>
