<?php

$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(10);
$trending = $stats->getTrending(10);

function steamFormatPlayers(int $count): string
{
    if ($count >= 1000000) {
        return round($count / 1000000, 2) . 'M';
    }
    if ($count >= 1000) {
        return round($count / 1000) . 'K';
    }
    return (string) $count;
}

function steamSparkline(array $history, int $width = 100, int $height = 30): string
{
    if (empty($history)) {
        return '<svg width="' . $width . '" height="' . $height . '"><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#888888" font-size="10">No data</text></svg>';
    }

    $values = array_map(fn($point) => $point['players'] ?? 0, $history);
    $min = min($values);
    $max = max($values);
    $range = $max - $min ?: 1;
    $count = count($values);

    $points = [];
    foreach ($values as $i => $value) {
        $x = $count > 1 ? ($i / ($count - 1)) * $width : $width / 2;
        $y = $height - (($value - $min) / $range) * ($height - 4) - 2;
        $points[] = round($x, 1) . ',' . round($y, 1);
    }

    $polyline = implode(' ', $points);

    return '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '"><polyline points="' . $polyline . '" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/></svg>';
}
?>

<div class="bg-surface border border-border rounded-xl p-4">
    <h2 class="text-neon-magenta uppercase tracking-widest text-sm font-bold mb-4">Trending</h2>

    <div class="flex gap-4 border-b border-border mb-4">
        <button type="button"
                data-tab="most-played"
                class="steam-tab pb-2 text-sm font-semibold text-neon-cyan border-b-2 border-neon-cyan transition">
            Most Played
        </button>
        <button type="button"
                data-tab="trending"
                class="steam-tab pb-2 text-sm font-semibold text-muted border-b-2 border-transparent hover:text-neon-green transition">
            Trending
        </button>
    </div>

    <div data-tab-content="most-played">
        <?php if (empty($mostPlayed)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <div class="grid grid-cols-[32px_80px_1fr_80px_80px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($mostPlayed as $game): ?>
                    <span class="text-neon-cyan font-bold text-sm text-center"><?= $game['rank'] ?></span>
                    <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[80px] h-[30px] object-cover rounded" loading="lazy">
                    <span class="text-text text-sm truncate"><?= htmlspecialchars($game['name']) ?></span>
                    <span class="text-text text-sm text-right"><?= steamFormatPlayers($game['current_players']) ?></span>
                    <span class="text-muted text-sm text-right"><?= steamFormatPlayers($game['peak_players']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-tab-content="trending" class="hidden">
        <?php if (empty($trending)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <div class="grid grid-cols-[32px_80px_1fr_100px_80px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($trending as $game): ?>
                    <span class="text-neon-green font-bold text-sm text-center"><?= $game['rank'] ?></span>
                    <img src="<?= $game['capsule_image'] ?>" alt="" class="w-[80px] h-[30px] object-cover rounded" loading="lazy">
                    <span class="text-text text-sm truncate"><?= htmlspecialchars($game['name']) ?></span>
                    <span class="flex justify-center"><?= steamSparkline($game['history'] ?? []) ?></span>
                    <span class="text-text text-sm text-right"><?= steamFormatPlayers($game['current_players']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div class="mt-4 pt-3 border-t border-border">
        <a href="/steam-stats"
           data-tab-link
           class="text-neon-cyan text-sm font-semibold hover:underline transition">
            View Full Rankings &rarr;
        </a>
    </div>
</div>

<script>
(function() {
    var tabs = document.querySelectorAll('.steam-tab');
    var widget = tabs.length ? tabs[0].closest('.bg-surface') : null;
    if (!widget) return;

    var link = widget.querySelector('[data-tab-link]');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            var target = tab.getAttribute('data-tab');
            var isMostPlayed = target === 'most-played';

            tabs.forEach(function(t) {
                var isActive = t.getAttribute('data-tab') === target;
                t.classList.toggle('text-neon-cyan', isActive && isMostPlayed);
                t.classList.toggle('text-neon-green', isActive && !isMostPlayed);
                t.classList.toggle('text-muted', !isActive);
                t.classList.toggle('border-neon-cyan', isActive && isMostPlayed);
                t.classList.toggle('border-neon-green', isActive && !isMostPlayed);
                t.classList.toggle('border-transparent', !isActive);
            });

            widget.querySelectorAll('[data-tab-content]').forEach(function(content) {
                content.classList.toggle('hidden', content.getAttribute('data-tab-content') !== target);
            });

            if (link) {
                link.classList.toggle('text-neon-cyan', isMostPlayed);
                link.classList.toggle('text-neon-green', !isMostPlayed);
            }
        });
    });
})();
</script>
