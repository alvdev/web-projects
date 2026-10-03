<?php snippet('header') ?>

<?php
$releases = site()->releases();
$recentlyReleased = $releases->getRecentlyReleased(30);
$upcoming = $releases->getNotableUpcoming(60);
$anticipated = $releases->getAnticipated(30);
$year = date('Y');

$affBanners = site()->alvAffBanners();
$hasEnabledPrograms = $affBanners['enabled'] && !empty(array_filter($affBanners['programs'], fn($p) => $p['enabled']));

$sections = [
    'recent' => ['title' => 'Recién lanzados', 'accent' => 'text-neon-green', 'games' => $recentlyReleased, 'link' => true, 'hypes' => false],
    'upcoming' => ['title' => 'Próximos lanzamientos', 'accent' => 'text-neon-cyan', 'games' => $upcoming, 'link' => false, 'hypes' => false],
    'anticipated' => ['title' => 'Más esperados', 'accent' => 'text-neon-magenta', 'games' => $anticipated, 'link' => false, 'hypes' => true],
];

$allGames = array_merge($recentlyReleased, $upcoming, $anticipated);
$total = count($allGames);

$genreCounts = [];
foreach ($allGames as $game) {
    foreach ($game['genres'] as $genre) {
        $genreCounts[$genre] = ($genreCounts[$genre] ?? 0) + 1;
    }
}
ksort($genreCounts);

$rowInner = function (array $game, bool $showHypes = false): string {
    ob_start(); ?>
    <div class="h-20 w-14 shrink-0 overflow-hidden rounded bg-surface-alt">
        <?php if ($game['cover_url']): ?>
            <img src="<?= htmlspecialchars($game['cover_url']) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
        <?php endif ?>
    </div>
    <div class="min-w-0 flex-1">
        <h3 class="text-sm font-semibold leading-tight text-text"><?= htmlspecialchars($game['name']) ?></h3>
        <p class="mt-1 text-xs text-neon-cyan"><?= htmlspecialchars($game['display_date']) ?></p>
        <?php if (!empty($game['platforms'])): ?>
            <p class="mt-0.5 text-[10px] text-muted"><?= htmlspecialchars(implode(' · ', $game['platforms'])) ?></p>
        <?php endif ?>
        <?php if (!empty($game['genres'])): ?>
            <p class="mt-0.5 text-[10px] text-muted"><?= htmlspecialchars(implode(', ', $game['genres'])) ?></p>
        <?php endif ?>
    </div>
    <?php if ($showHypes): ?>
        <span class="shrink-0 text-xs text-neon-magenta"><?= number_format($game['hypes']) ?> seguidores</span>
    <?php endif ?>
    <?php return (string) ob_get_clean();
};
?>

<section class="mb-8">
    <h1 class="text-2xl font-bold text-neon-cyan mb-3">Próximos lanzamientos de videojuegos <?= $year ?></h1>
    <p class="text-muted max-w-3xl">
        Calendario de lanzamientos de videojuegos de <?= $year ?> en PC y consolas: lo recién salido, las fechas
        confirmadas y los títulos más esperados.
    </p>
</section>

<?php if ($total > 0 && count($genreCounts) > 1): ?>
<div data-releases-filter class="mb-6 flex flex-wrap gap-2">
    <button type="button" data-genre="*" aria-pressed="true"
            class="cursor-pointer rounded-lg border border-neon-cyan px-3 py-1.5 text-xs text-neon-cyan transition">
        Todos (<?= $total ?>)
    </button>
    <?php foreach ($genreCounts as $genre => $count): ?>
        <button type="button" data-genre="<?= htmlspecialchars($genre) ?>" aria-pressed="false"
                class="cursor-pointer rounded-lg border border-border px-3 py-1.5 text-xs text-muted transition">
            <?= htmlspecialchars($genre) ?> (<?= $count ?>)
        </button>
    <?php endforeach ?>
</div>
<?php endif ?>

<?php if ($total === 0): ?>
    <p class="text-muted">No hay lanzamientos disponibles en este momento. Vuelve pronto.</p>
<?php else: ?>
    <?php $i = 0; ?>
    <?php foreach ($sections as $key => $section): ?>
        <?php if (empty($section['games'])) continue; $i++; ?>
        <section data-releases-section="<?= $key ?>" class="mb-8">
            <h2 class="mb-3 uppercase tracking-wider <?= $section['accent'] ?>"><?= $section['title'] ?></h2>
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <?php foreach ($section['games'] as $game): ?>
                    <?php $genresJson = htmlspecialchars(json_encode($game['genres'], JSON_UNESCAPED_UNICODE)); ?>
                    <?php if ($section['link']): ?>
                        <a data-release-row data-genre-item data-genres="<?= $genresJson ?>"
                           href="<?= htmlspecialchars($game['local_url'] ?? '/games/by-igdb-id/' . $game['igdb_id']) ?>"
                           class="flex items-center gap-3 rounded-lg border border-border bg-surface p-3 transition hover:border-neon-green/50">
                            <?= $rowInner($game, $section['hypes']) ?>
                        </a>
                    <?php else: ?>
                        <div data-release-row data-genre-item data-genres="<?= $genresJson ?>"
                             class="flex items-center gap-3 rounded-lg border border-border bg-surface p-3">
                            <?= $rowInner($game, $section['hypes']) ?>
                        </div>
                    <?php endif ?>
                <?php endforeach ?>
            </div>
        </section>
        <?php if ($hasEnabledPrograms): ?>
            <?php snippet('affiliate-banner', ['grid' => true, 'itemCount' => $i]) ?>
        <?php endif ?>
    <?php endforeach ?>

    <p data-releases-empty class="hidden text-muted">No hay lanzamientos para este filtro.</p>
<?php endif ?>

<?php
$listItems = [];
$position = 1;
foreach ($allGames as $game) {
    if (!$game['release_date']) {
        continue;
    }
    $item = [
        '@type' => 'VideoGame',
        'name' => $game['name'],
        'releaseDate' => $game['release_date'],
    ];
    if ($game['cover_url']) {
        $item['image'] = $game['cover_url'];
    }
    if (!empty($game['platforms'])) {
        $item['gamePlatform'] = $game['platforms'];
    }
    $listItems[] = ['@type' => 'ListItem', 'position' => $position++, 'item' => $item];
}
?>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Lanzamientos de videojuegos ' . $year,
    'itemListElement' => $listItems,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<p class="mt-8 text-xs text-muted">
    Datos de IGDB — <a href="https://www.igdb.com" target="_blank" rel="noopener" class="text-neon-cyan hover:text-white">igdb.com</a>
</p>

<script>
    (function() {
        var root = document.querySelector('[data-releases-filter]');
        if (!root) return;

        var chips = Array.prototype.slice.call(root.querySelectorAll('[data-genre]'));
        var items = Array.prototype.slice.call(document.querySelectorAll('[data-genre-item]'));
        var sections = Array.prototype.slice.call(document.querySelectorAll('[data-releases-section]'));
        var empty = document.querySelector('[data-releases-empty]');

        function apply(selected) {
            items.forEach(function(item) {
                var genres = [];
                try {
                    genres = JSON.parse(item.getAttribute('data-genres') || '[]');
                } catch (e) {}
                item.classList.toggle('hidden', selected !== '*' && genres.indexOf(selected) === -1);
            });

            var visibleTotal = 0;
            sections.forEach(function(section) {
                var visible = section.querySelectorAll('[data-genre-item]:not(.hidden)').length;
                section.classList.toggle('hidden', visible === 0);
                visibleTotal += visible;
            });

            if (empty) {
                empty.classList.toggle('hidden', visibleTotal > 0);
            }
        }

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

                apply(selected);
            });
        });
    })();
</script>

<?php snippet('footer') ?>
