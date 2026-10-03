<?php snippet('header') ?>

<?php
$allGames = $page->children()->children()->children()->filterBy('intendedTemplate', 'game')->sortBy('title', 'asc');

$allGenres = [];
foreach ($allGames as $game) {
    foreach ($game->genreList() as $genre) {
        $genre = trim($genre);
        if ($genre === '') continue;
        $allGenres[$genre] = ($allGenres[$genre] ?? 0) + 1;
    }
}
ksort($allGenres);
?>

<h1 class="text-2xl font-bold text-text mb-6">Todos los juegos</h1>

<?php if (count($allGenres) > 1): ?>
<div data-genre-filter class="mb-6 flex flex-wrap gap-2">
    <button type="button" data-genre="*" aria-pressed="true"
            class="cursor-pointer rounded-lg border border-neon-cyan px-3 py-1.5 text-xs text-neon-cyan transition">
        Todos (<?= $allGames->count() ?>)
    </button>
    <?php foreach ($allGenres as $genre => $count): ?>
        <button type="button" data-genre="<?= htmlspecialchars($genre) ?>" aria-pressed="false"
                class="cursor-pointer rounded-lg border border-border px-3 py-1.5 text-xs text-muted transition">
            <?= htmlspecialchars($genre) ?> (<?= $count ?>)
        </button>
    <?php endforeach ?>
</div>
<?php endif ?>

<?php if ($allGames->count() > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($allGames as $game): ?>
        <?php
        $genres = array_values(array_filter($game->genreList()));
        ?>
        <div data-genre-item data-genres="<?= htmlspecialchars(json_encode($genres, JSON_UNESCAPED_UNICODE)) ?>">
            <?php snippet('game-card', ['game' => $game]) ?>
        </div>
    <?php endforeach ?>
</div>

<script>
    (function() {
        var root = document.querySelector('[data-genre-filter]');
        if (!root) return;

        var chips = Array.prototype.slice.call(root.querySelectorAll('[data-genre]'));
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-genre-item]'));

        chips.forEach(function(chip) {
            chip.addEventListener('click', function() {
                var selected = chip.getAttribute('data-genre');

                chips.forEach(function(candidate) {
                    var active = candidate === chip;
                    candidate.classList.toggle('border-neon-cyan', active);
                    candidate.classList.toggle('text-neon-cyan', active);
                    candidate.classList.toggle('border-border', !active);
                    candidate.classList.toggle('text-muted', !active);
                    candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
                });

                items.forEach(function(item) {
                    var genres = [];
                    try {
                        genres = JSON.parse(item.getAttribute('data-genres') || '[]');
                    } catch (e) {}
                    item.classList.toggle('hidden', selected !== '*' && genres.indexOf(selected) === -1);
                });
            });
        });
    })();
</script>
<?php else: ?>
<p class="text-muted">No games added yet.</p>
<?php endif ?>

<?php snippet('footer') ?>
