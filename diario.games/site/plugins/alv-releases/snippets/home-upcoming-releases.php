<?php
$releases = site()->releases();
$recentlyReleased = $releases->getRecentlyReleased(8);
$upcoming = $releases->getNotableUpcoming(8);
$anticipated = $releases->getAnticipated(8);

if (empty($recentlyReleased) && empty($upcoming) && empty($anticipated)) return;

$columns = [
    'recent' => ['title' => 'Recién lanzados', 'accent' => 'text-neon-green', 'games' => $recentlyReleased],
    'upcoming' => ['title' => 'Próximos lanzamientos', 'accent' => 'text-neon-cyan', 'games' => $upcoming],
    'anticipated' => ['title' => 'Más esperados', 'accent' => 'text-neon-magenta', 'games' => $anticipated],
];

$rowInner = function (array $game, bool $showHypes = false): string {
    ob_start(); ?>
    <div class="h-16 w-12 shrink-0 overflow-hidden rounded bg-surface-alt">
        <?php if ($game['cover_url']): ?>
            <img src="<?= htmlspecialchars($game['cover_url']) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
        <?php endif ?>
    </div>
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold leading-tight text-text"><?= htmlspecialchars($game['name']) ?></p>
        <p class="text-xs text-neon-cyan"><?= htmlspecialchars($game['display_date']) ?></p>
        <?php if (!empty($game['platforms'])): ?>
            <p class="text-[10px] text-muted"><?= htmlspecialchars(implode(' · ', $game['platforms'])) ?></p>
        <?php endif ?>
    </div>
    <?php if ($showHypes): ?>
        <span class="shrink-0 text-[10px] text-muted"><?= number_format($game['hypes']) ?> seguidores</span>
    <?php endif ?>
    <?php return (string) ob_get_clean();
};
?>
<div data-home-releases class="bg-surface/50 backdrop-blur-sm border-4 border-border rounded-xl p-4">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="uppercase tracking-wider text-shadow-neon-cyan">Lanzamientos</h2>
        <a href="/lanzamientos" class="shrink-0 text-xs text-neon-cyan transition hover:text-neon-magenta">Ver calendario completo →</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($columns as $key => $column): ?>
            <?php if (empty($column['games'])) continue; ?>
            <section data-home-column="<?= $key ?>">
                <h3 class="mb-2 text-xs uppercase tracking-wider <?= $column['accent'] ?>"><?= $column['title'] ?></h3>
                <div class="space-y-2">
                    <?php foreach ($column['games'] as $game): ?>
                        <?php if ($key === 'recent'): ?>
                            <a data-home-release
                               href="<?= htmlspecialchars($game['local_url'] ?? '/games/by-igdb-id/' . $game['igdb_id']) ?>"
                               class="flex items-center gap-3 rounded-lg border border-border bg-surface p-2 transition hover:border-neon-green/50">
                                <?= $rowInner($game) ?>
                            </a>
                        <?php else: ?>
                            <div data-home-release class="flex items-center gap-3 rounded-lg border border-border bg-surface p-2">
                                <?= $rowInner($game, $key === 'anticipated') ?>
                            </div>
                        <?php endif ?>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>
</div>
