<?php snippet('header') ?>

<?php
$releases = site()->releases();
$recentlyReleased = $releases->getRecentlyReleased(100);
$upcoming = $releases->getNotableUpcoming(100);
$anticipated = $releases->getAnticipated(100);
$year = date('Y');

$allGames = array_merge($recentlyReleased, $upcoming, $anticipated);
$total = count($allGames);

$genreCounts = [];
foreach ($allGames as $game) {
    foreach ($game['genres'] as $genre) {
        $genreCounts[$genre] = ($genreCounts[$genre] ?? 0) + 1;
    }
}
ksort($genreCounts);
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
    <div class="bg-surface/50 backdrop-blur-sm border-4 border-border rounded-xl p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php if (!empty($recentlyReleased)): ?>
                <?php snippet('release-column', [
                    'columnKey' => 'recent',
                    'title' => 'Recién lanzados',
                    'accent' => 'text-neon-green',
                    'games' => $recentlyReleased,
                    'linked' => true,
                    'context' => 'page',
                    'headingLevel' => 'h2',
                ]) ?>
            <?php endif ?>

            <?php if (!empty($upcoming)): ?>
                <?php snippet('release-column', [
                    'columnKey' => 'upcoming',
                    'title' => 'Próximos lanzamientos',
                    'accent' => 'text-neon-cyan',
                    'games' => $upcoming,
                    'context' => 'page',
                    'headingLevel' => 'h2',
                ]) ?>
            <?php endif ?>

            <?php if (!empty($anticipated)): ?>
                <?php snippet('release-column', [
                    'columnKey' => 'anticipated',
                    'title' => 'Más esperados',
                    'accent' => 'text-neon-magenta',
                    'games' => $anticipated,
                    'showHypes' => true,
                    'context' => 'page',
                    'headingLevel' => 'h2',
                ]) ?>
            <?php endif ?>
        </div>
    </div>

    <p data-releases-empty class="hidden text-muted">No hay lanzamientos para este filtro.</p>
<?php endif ?>

<?php snippet('affiliate-banner', ['standalone' => true]) ?>

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
        var PAGE_SIZE = 30;
        var root = document.querySelector('[data-releases-filter]');
        var chips = root ? Array.prototype.slice.call(root.querySelectorAll('[data-genre]')) : [];
        var sections = Array.prototype.slice.call(document.querySelectorAll('[data-releases-section]'));
        var empty = document.querySelector('[data-releases-empty]');
        var activeFilter = '*';
        var revealed = {};

        function rowsOf(section) {
            return Array.prototype.slice.call(section.querySelectorAll('[data-load-item]'));
        }

        function updateSentinel(section) {
            var sentinel = section.querySelector('[data-load-sentinel]');
            if (!sentinel) return;
            var key = section.getAttribute('data-releases-section');
            var done = revealed[key] >= rowsOf(section).length;
            sentinel.classList.toggle('hidden', activeFilter !== '*' || done);
        }

        function applyReveal(section) {
            if (activeFilter !== '*') return;
            var key = section.getAttribute('data-releases-section');
            rowsOf(section).forEach(function(row, index) {
                row.classList.toggle('hidden', index >= revealed[key]);
            });
            updateSentinel(section);
        }

        function applyFilter(selected) {
            activeFilter = selected;

            sections.forEach(function(section) {
                var rows = rowsOf(section);

                if (selected === '*') {
                    section.classList.toggle('hidden', rows.length === 0);
                    applyReveal(section);
                    return;
                }

                var visible = 0;
                rows.forEach(function(row) {
                    var genres = [];
                    try {
                        genres = JSON.parse(row.getAttribute('data-genres') || '[]');
                    } catch (e) {}
                    var match = genres.indexOf(selected) !== -1;
                    row.classList.toggle('hidden', !match);
                    if (match) visible++;
                });
                section.classList.toggle('hidden', visible === 0);
                updateSentinel(section);
            });

            if (empty) {
                var anyVisible = sections.some(function(section) {
                    return !section.classList.contains('hidden');
                });
                empty.classList.toggle('hidden', anyVisible);
            }
        }

        sections.forEach(function(section) {
            var key = section.getAttribute('data-releases-section');
            var rows = rowsOf(section);
            revealed[key] = Math.min(PAGE_SIZE, rows.length);
            applyReveal(section);

            var sentinel = section.querySelector('[data-load-sentinel]');
            if (!sentinel || rows.length <= revealed[key]) return;

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (!entry.isIntersecting || activeFilter !== '*') return;
                    revealed[key] = Math.min(revealed[key] + PAGE_SIZE, rows.length);
                    applyReveal(section);
                    if (revealed[key] >= rows.length) observer.disconnect();
                });
            }, { rootMargin: '240px 0px' });
            observer.observe(sentinel);
        });

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

                applyFilter(selected);
            });
        });
    })();
</script>

<?php snippet('footer') ?>
