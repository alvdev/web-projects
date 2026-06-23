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
    $range = $max - $min;

    // If all values are the same, use a fixed range to show a horizontal line
    if ($range === 0) {
        $range = 1;
        $min = $min - 1;
    }

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
    <div class="relative flex gap-4 justify-between mb-4 after:absolute after:inset-x-0 after:bottom-0 after:w-full after:h-0.5 after:bg-white/5 *:hover:cursor-pointer">
        <button type="button"
            data-tab="most-played"
            class="steam-tab w-full pb-2 text-sm font-semibold text-neon-cyan border-b-2 border-neon-cyan transition">
            Más jugados
        </button>
        <button type="button"
            data-tab="trending"
            class="steam-tab w-full pb-2 text-sm font-semibold text-muted border-b-2 border-transparent hover:text-neon-green transition">
            Tendencia
        </button>
    </div>

    <div data-tab-content="most-played">
        <?php if (empty($mostPlayed)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[2fr_80px_80px] gap-x-3 text-text/70 text-xs mb-4">
                <div>Jugadores en Steam</div>
                <div class="text-right">Ahora</div>
                <div class="text-right">24h</div>
            </div>
            <div class="grid grid-cols-[1fr_1fr_80px_80px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($mostPlayed as $game): ?>
                    <div class="relative flex items-center">
                        <span class="absolute -left-2.5 text-neon-cyan text-xs text-center bg-surface/70 w-4 h-4 rounded-full"><?= $game['rank'] ?></span>
                        <img src="<?= $game['capsule_image'] ?>" alt="" class="w-20 h-7.5 object-cover rounded" loading="lazy">
                    </div>
                    <span class="text-text text-xs line-clamp-2"><?= htmlspecialchars($game['name']) ?></span>
                    <span class="text-text text-sm text-right"><?= steamFormatPlayers($game['current_players']) ?></span>
                    <span class="text-muted text-sm text-right"><?= steamFormatPlayers($game['peak_players']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-tab-content="trending" class="hidden">
        <?php if (empty($trending)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[1fr_100px_50px] gap-x-3 text-text/70 text-xs mb-4">
                <div>Crecimiento en Steam</div>
                <div class="text-center">Última semana</div>
                <div class="text-right">Ahora</div>
            </div>
            <div class="grid grid-cols-[1fr_1fr_100px_50px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($trending as $game): ?>
                    <div class="relative flex items-center">
                        <span class="absolute -left-2.5 text-neon-cyan text-xs text-center bg-surface/70 w-4 h-4 rounded-full"><?= $game['rank'] ?></span>
                        <img src="<?= $game['capsule_image'] ?>" alt="" class="w-20 h-7.5 object-cover rounded" loading="lazy">
                    </div>
                    <span class="text-text text-xs line-clamp-2"><?= htmlspecialchars($game['name']) ?></span>
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
