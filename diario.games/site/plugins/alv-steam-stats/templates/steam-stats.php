<?php snippet('header') ?>

<?php
$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(100);
$trending = $stats->getTrending(100);

function pageFormatPlayers(int $count): string {
    if ($count >= 1000000) return round($count / 1000000, 2) . 'M';
    if ($count >= 1000) return round($count / 1000, 1) . 'K';
    return (string) $count;
}

function pageSparkline(array $history): string {
    if (count($history) < 2) {
        return '<span class="text-xs text-muted">No data</span>';
    }
    $values = array_map(fn($p) => $p['players'] ?? 0, $history);
    $min = min($values);
    $max = max($values);
    $range = max(1, $max - $min);
    $points = [];
    foreach ($values as $i => $v) {
        $x = ($i / (count($values) - 1)) * 100;
        $y = 30 - (($v - $min) / $range) * 28;
        $points[] = round($x, 1) . ',' . round($y, 1);
    }
    return '<svg width="100" height="30" viewBox="0 0 100 30"><polyline points="' . implode(' ', $points) . '" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/></svg>';
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-text">Steam Charts</h1>
    <p class="text-sm text-muted mt-1">Top 100 most played and trending games on Steam</p>
</div>

<!-- Tabs -->
<div class="flex gap-0 mb-6 border-b border-border">
    <button type="button" class="steam-page-tab active px-4 py-2 text-sm font-bold uppercase tracking-wider text-neon-cyan border-b-2 border-neon-cyan" data-tab="most-played-full">
        Most Played
    </button>
    <button type="button" class="steam-page-tab px-4 py-2 text-sm font-bold uppercase tracking-wider text-muted border-b-2 border-transparent" data-tab="trending-full">
        Trending
    </button>
</div>

<!-- Most Played Tab -->
<div class="steam-page-tab-content" id="most-played-full">
    <div class="bg-surface border border-border rounded-xl overflow-hidden">
        <!-- Table Header -->
        <div class="grid grid-cols-[50px_120px_1fr_120px_120px] gap-4 p-4 border-b border-border bg-surface-alt">
            <span class="text-xs font-bold text-muted uppercase">#</span>
            <span></span>
            <span class="text-xs font-bold text-muted uppercase">Title</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Current Players</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Peak Players</span>
        </div>
        <!-- Table Body -->
        <?php if (empty($mostPlayed)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <?php foreach ($mostPlayed as $game): ?>
            <div class="grid grid-cols-[50px_120px_1fr_120px_120px] gap-4 p-4 border-b border-border/30 hover:bg-surface-alt/50 transition">
                <span class="text-lg font-bold text-neon-cyan"><?= $game['rank'] ?></span>
                <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[120px] h-[45px] object-cover rounded" loading="lazy">
                <div class="flex items-center">
                    <a href="https://store.steampowered.com/app/<?= $game['appid'] ?>/" target="_blank" rel="noopener" class="text-base font-semibold text-text hover:text-neon-cyan transition">
                        <?= htmlspecialchars($game['name']) ?>
                    </a>
                </div>
                <span class="text-sm text-text text-right font-mono"><?= pageFormatPlayers($game['current_players']) ?></span>
                <span class="text-sm text-muted text-right font-mono"><?= pageFormatPlayers($game['peak_players']) ?></span>
            </div>
            <?php endforeach ?>
        <?php endif ?>
    </div>
</div>

<!-- Trending Tab -->
<div class="steam-page-tab-content hidden" id="trending-full">
    <div class="bg-surface border border-border rounded-xl overflow-hidden">
        <!-- Table Header -->
        <div class="grid grid-cols-[50px_120px_1fr_150px_120px] gap-4 p-4 border-b border-border bg-surface-alt">
            <span class="text-xs font-bold text-muted uppercase">#</span>
            <span></span>
            <span class="text-xs font-bold text-muted uppercase">Title</span>
            <span class="text-xs font-bold text-muted uppercase text-center">7 Days</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Current Players</span>
        </div>
        <!-- Table Body -->
        <?php if (empty($trending)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <?php foreach ($trending as $game): ?>
            <div class="grid grid-cols-[50px_120px_1fr_150px_120px] gap-4 p-4 border-b border-border/30 hover:bg-surface-alt/50 transition">
                <span class="text-lg font-bold text-neon-green"><?= $game['rank'] ?></span>
                <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[120px] h-[45px] object-cover rounded" loading="lazy">
                <div class="flex items-center">
                    <a href="https://store.steampowered.com/app/<?= $game['appid'] ?>/" target="_blank" rel="noopener" class="text-base font-semibold text-text hover:text-neon-green transition">
                        <?= htmlspecialchars($game['name']) ?>
                    </a>
                </div>
                <div class="flex justify-center items-center"><?= pageSparkline($game['history'] ?? []) ?></div>
                <span class="text-sm text-text text-right font-mono"><?= pageFormatPlayers($game['current_players']) ?></span>
            </div>
            <?php endforeach ?>
        <?php endif ?>
    </div>
</div>

<script>
(function() {
    var tabs = document.querySelectorAll('.steam-page-tab');
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = tab.getAttribute('data-tab');
            var isMostPlayed = target === 'most-played-full';
            tabs.forEach(function(t) {
                var isActive = t.getAttribute('data-tab') === target;
                t.classList.toggle('text-neon-cyan', isActive && isMostPlayed);
                t.classList.toggle('text-neon-green', isActive && !isMostPlayed);
                t.classList.toggle('text-muted', !isActive);
                t.classList.toggle('border-neon-cyan', isActive && isMostPlayed);
                t.classList.toggle('border-neon-green', isActive && !isMostPlayed);
                t.classList.toggle('border-transparent', !isActive);
            });
            document.querySelectorAll('.steam-page-tab-content').forEach(function(c) {
                c.classList.toggle('hidden', c.getAttribute('id') !== target);
            });
        });
    });
})();
</script>

<?php snippet('footer') ?>
