<?php
$columnKey = $columnKey ?? '';
$title = $title ?? '';
$accent = $accent ?? 'text-neon-cyan';
$games = $games ?? [];
$linked = $linked ?? false;
$showHypes = $showHypes ?? false;
$context = $context ?? 'home';
$headingLevel = $headingLevel ?? 'h3';
$pageSize = $pageSize ?? 30;

$isPage = $context === 'page';

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
<section <?= $isPage ? 'data-releases-section' : 'data-home-column' ?>="<?= htmlspecialchars($columnKey) ?>">
    <<?= $headingLevel ?> class="mb-2 text-xs uppercase tracking-wider <?= $accent ?>"><?= htmlspecialchars($title) ?></<?= $headingLevel ?>>
    <div class="space-y-2">
        <?php foreach ($games as $index => $game): ?>
            <?php
            $hidden = ($isPage && $index >= $pageSize) ? ' hidden' : '';
            $rowClass = 'flex items-center gap-3 rounded-lg border border-border bg-surface p-2'
                . ($linked ? ' transition hover:border-neon-green/50' : '')
                . $hidden;
            if ($isPage) {
                $rowAttrs = 'data-release-row data-genre-item data-load-item data-load-index="' . $index . '"'
                    . ' data-genres="' . htmlspecialchars(json_encode($game['genres'], JSON_UNESCAPED_UNICODE)) . '"';
            } else {
                $rowAttrs = 'data-home-release';
            }
            ?>
            <?php if ($linked): ?>
                <a <?= $rowAttrs ?> href="<?= htmlspecialchars($game['local_url'] ?? '/games/by-igdb-id/' . $game['igdb_id']) ?>" class="<?= $rowClass ?>"><?= $rowInner($game, $showHypes) ?></a>
            <?php else: ?>
                <div <?= $rowAttrs ?> class="<?= $rowClass ?>"><?= $rowInner($game, $showHypes) ?></div>
            <?php endif ?>
        <?php endforeach ?>
    </div>
    <?php if ($isPage): ?>
        <div data-load-sentinel class="pt-3 text-center text-xs text-muted">Cargando más…</div>
    <?php endif ?>
</section>
