<?php
$releases = site()->releases();
$recentlyReleased = $releases->getRecentlyReleased(8);
$upcoming = $releases->getNotableUpcoming(8);
$anticipated = $releases->getAnticipated(8);

if (empty($recentlyReleased) && empty($upcoming) && empty($anticipated)) return;
?>
<div data-home-releases class="bg-surface/50 backdrop-blur-sm border-4 border-border rounded-xl p-4">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h2 class="uppercase tracking-wider text-shadow-neon-cyan">Lanzamientos</h2>
        <a href="/lanzamientos" class="shrink-0 text-xs text-neon-cyan transition hover:text-neon-magenta">Ver calendario completo →</a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php if (!empty($recentlyReleased)): ?>
            <?php snippet('release-column', [
                'columnKey' => 'recent',
                'title' => 'Recién lanzados',
                'accent' => 'text-neon-green',
                'games' => $recentlyReleased,
                'linked' => true,
                'context' => 'home',
            ]) ?>
        <?php endif ?>

        <?php if (!empty($upcoming)): ?>
            <?php snippet('release-column', [
                'columnKey' => 'upcoming',
                'title' => 'Próximos lanzamientos',
                'accent' => 'text-neon-cyan',
                'games' => $upcoming,
                'context' => 'home',
            ]) ?>
        <?php endif ?>

        <?php if (!empty($anticipated)): ?>
            <?php snippet('release-column', [
                'columnKey' => 'anticipated',
                'title' => 'Más esperados',
                'accent' => 'text-neon-magenta',
                'games' => $anticipated,
                'showHypes' => true,
                'context' => 'home',
            ]) ?>
        <?php endif ?>
    </div>
</div>
