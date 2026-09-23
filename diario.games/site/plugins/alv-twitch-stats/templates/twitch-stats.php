<?php snippet('header') ?>

<?php
$stats = site()->twitchStats();
$games = $stats->getTopGames(100);
$streamers = $stats->getTopStreamers(100);
$spanish = $stats->getTopSpanish(100);

$igdbToSlug = [];
foreach (site()->index()->filterBy('intendedTemplate', 'game') as $gamePage) {
    $igdbId = (int) $gamePage->content()->get('IgdbId')->value();
    if ($igdbId) {
        $igdbToSlug[$igdbId] = $gamePage->slug();
    }
}

if (!function_exists('twitchFormatViewers')) {
    function twitchFormatViewers(?int $count): string
    {
        if ($count === null) {
            return '–';
        }
        if ($count >= 1000000) {
            return round($count / 1000000, 2) . 'M';
        }
        if ($count >= 1000) {
            return round($count / 1000, 1) . 'K';
        }
        return (string) $count;
    }
}

if (!function_exists('twitchGameLink')) {
    function twitchGameLink(array $game, array $igdbToSlug): array
    {
        $igdbId = $game['igdb_id'] ?? null;
        if ($igdbId) {
            return isset($igdbToSlug[$igdbId])
                ? ['/' . $igdbToSlug[$igdbId], false]
                : ['/games/by-igdb-id/' . $igdbId, true];
        }

        return ['https://www.twitch.tv/directory/category/' . rawurlencode((string) ($game['id'] ?? '')), false];
    }
}

if (!function_exists('twitchFormatChange')) {
    function twitchFormatChange(?float $pct): string
    {
        if ($pct === null) {
            return '<span class="text-muted text-sm">–</span>';
        }

        $sign = $pct >= 0 ? '+' : '';
        $color = $pct >= 0 ? 'text-neon-green' : 'text-red-400';

        return '<span class="' . $color . ' text-sm font-semibold">' . $sign . round($pct, 1) . '%</span>';
    }
}

if (!function_exists('twitchSparkline')) {
    function twitchSparkline(array $history, int $width = 100, int $height = 30): string
    {
        if (empty($history)) {
            return '<span class="text-xs text-muted">–</span>';
        }

        $values = array_map(fn ($point) => (int) ($point['viewers'] ?? 0), $history);
        $min = min($values);
        $max = max($values);
        $range = $max - $min;

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

        return '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">'
            . '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="#a970ff" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>'
            . '</svg>';
    }
}

$warmLastRun = kirby()->cache('alv/twitch-stats.cache')->get('warm-last-run');
$lastRun = is_int($warmLastRun) ? $warmLastRun : null;
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-text">Twitch Charts</h1>
    <p class="text-sm text-muted mt-1">Juegos y streamers más vistos en Twitch</p>
    <p class="text-xs text-muted mt-2">
        Datos de Twitch · Medias de 30 días: TwitchTracker
        <?php if ($lastRun): ?>
            · Actualizado hace <span id="minutes-ago"><?= round((time() - $lastRun) / 60) ?></span> min
        <?php endif ?>
    </p>
    <?php if ($lastRun): ?>
        <script>
            (function() {
                var ts = <?= $lastRun ?>;
                var el = document.getElementById('minutes-ago');
                if (!el) return;
                var update = function() {
                    el.textContent = Math.round((Date.now() / 1000 - ts) / 60);
                };
                update();
                setInterval(update, 60000);
            })();
        </script>
    <?php endif ?>
</div>

<div class="bg-surface border border-border rounded-xl p-6 overflow-hidden" data-twitch-page>
    <span class="hidden text-neon-green text-neon-magenta border-neon-green border-neon-magenta" aria-hidden="true"></span>

    <div class="relative flex gap-4 justify-between mb-6 after:absolute after:inset-x-0 after:bottom-0 after:w-full after:h-0.5 after:bg-white/5 *:hover:cursor-pointer">
        <button type="button" class="twitch-page-tab w-full pt-2 pb-4 text-sm font-semibold uppercase tracking-wider text-neon-cyan border-b-2 border-neon-cyan" data-twitch-page-tab="games">
            Juegos
        </button>
        <button type="button" class="twitch-page-tab w-full pt-2 pb-4 text-sm font-semibold uppercase tracking-wider text-muted border-b-2 border-transparent" data-twitch-page-tab="streamers">
            Streamers
        </button>
        <button type="button" class="twitch-page-tab w-full pt-2 pb-4 text-sm font-semibold uppercase tracking-wider text-muted border-b-2 border-transparent" data-twitch-page-tab="spanish">
            En español
        </button>
    </div>

    <div data-twitch-page-content="games">
        <?php if (empty($games)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[120px_1fr_90px_90px_110px_70px_130px] gap-x-6 text-text/70 text-sm mb-4">
                <span>Juego</span>
                <span></span>
                <span class="text-right">Ahora</span>
                <span class="text-right">Media 30d</span>
                <span class="text-right">Horas 30d</span>
                <span class="text-right">Rank</span>
                <span class="text-center">7 días</span>
            </div>
            <div class="divide-y divide-border/30">
                <?php foreach ($games as $game): ?>
                    <?php [$gameUrl, $isImporting] = twitchGameLink($game, $igdbToSlug); $importAttr = $isImporting ? ' data-importing rel="nofollow"' : ''; $external = str_starts_with($gameUrl, 'http') ? ' target="_blank" rel="noopener"' : ''; ?>
                    <div class="grid grid-cols-[120px_1fr_90px_90px_110px_70px_130px] gap-x-6 items-center py-2" data-twitch-page-row>
                        <div class="relative flex items-center">
                            <span class="absolute -left-3 text-neon-cyan text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75"><?= $game['rank'] ?></span>
                            <a href="<?= htmlspecialchars($gameUrl) ?>" class="block"<?= $importAttr ?><?= $external ?>>
                                <?php if ($game['box_art_url']): ?>
                                    <img src="<?= htmlspecialchars($game['box_art_url']) ?>" alt="<?= htmlspecialchars($game['name']) ?>" class="w-9 h-12 object-cover rounded" loading="lazy">
                                <?php else: ?>
                                    <span class="w-9 h-12 rounded bg-surface-alt flex items-center justify-center text-muted text-[8px] text-center leading-tight">Sin imagen</span>
                                <?php endif ?>
                            </a>
                        </div>
                        <a href="<?= htmlspecialchars($gameUrl) ?>" class="text-text text-base line-clamp-2 hover:underline"<?= $importAttr ?><?= $external ?>><?= htmlspecialchars($game['name']) ?></a>
                        <span class="text-text text-base text-right"><?= twitchFormatViewers($game['viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($game['avg_viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($game['hours_watched']) ?></span>
                        <span class="text-muted text-sm text-right">#<?= $game['twitch_rank'] ?? '–' ?></span>
                        <div class="flex flex-col items-center gap-1">
                            <?= twitchSparkline($game['history'] ?? []) ?>
                            <?= twitchFormatChange($game['change_pct']) ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-twitch-page-content="streamers" class="hidden">
        <?php if (empty($streamers)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[120px_1fr_130px_90px_90px_80px_100px_130px] gap-x-6 text-text/70 text-sm mb-4">
                <span>Streamer</span>
                <span></span>
                <span>Categoría</span>
                <span class="text-right">Ahora</span>
                <span class="text-right">Media 30d</span>
                <span class="text-right">Máx. 30d</span>
                <span class="text-right">Seguidores</span>
                <span class="text-center">7 días</span>
            </div>
            <div class="divide-y divide-border/30">
                <?php foreach ($streamers as $streamer): ?>
                    <div class="grid grid-cols-[120px_1fr_130px_90px_90px_80px_100px_130px] gap-x-6 items-center py-2" data-twitch-page-row>
                        <div class="relative flex items-center">
                            <span class="absolute -left-3 text-neon-green text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75"><?= $streamer['rank'] ?></span>
                            <?php if ($streamer['avatar_url']): ?>
                                <img src="<?= htmlspecialchars($streamer['avatar_url']) ?>" alt="<?= htmlspecialchars($streamer['name']) ?>" class="w-10 h-10 rounded-full object-cover" loading="lazy">
                            <?php else: ?>
                                <span class="w-10 h-10 rounded-full bg-surface-alt flex items-center justify-center text-muted text-sm"><?= htmlspecialchars(mb_substr($streamer['name'], 0, 1)) ?></span>
                            <?php endif ?>
                        </div>
                        <a href="<?= htmlspecialchars($streamer['url']) ?>" target="_blank" rel="noopener" class="text-text text-base truncate hover:underline"><?= htmlspecialchars($streamer['name']) ?></a>
                        <span class="text-muted text-sm truncate"><?= htmlspecialchars($streamer['game_name']) ?></span>
                        <span class="text-text text-base text-right"><?= twitchFormatViewers($streamer['viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($streamer['avg_viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($streamer['max_viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($streamer['followers_total']) ?></span>
                        <div class="flex flex-col items-center gap-1">
                            <?= twitchSparkline($streamer['history'] ?? []) ?>
                            <?= twitchFormatChange($streamer['change_pct']) ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-twitch-page-content="spanish" class="hidden">
        <?php if (empty($spanish)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[120px_1fr_130px_90px_90px_80px_100px_130px] gap-x-6 text-text/70 text-sm mb-4">
                <span>Streamer</span>
                <span></span>
                <span>Categoría</span>
                <span class="text-right">Ahora</span>
                <span class="text-right">Media 30d</span>
                <span class="text-right">Máx. 30d</span>
                <span class="text-right">Seguidores</span>
                <span class="text-center">7 días</span>
            </div>
            <div class="divide-y divide-border/30">
                <?php foreach ($spanish as $streamer): ?>
                    <div class="grid grid-cols-[120px_1fr_130px_90px_90px_80px_100px_130px] gap-x-6 items-center py-2" data-twitch-page-row>
                        <div class="relative flex items-center">
                            <span class="absolute -left-3 text-neon-magenta text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75"><?= $streamer['rank'] ?></span>
                            <?php if ($streamer['avatar_url']): ?>
                                <img src="<?= htmlspecialchars($streamer['avatar_url']) ?>" alt="<?= htmlspecialchars($streamer['name']) ?>" class="w-10 h-10 rounded-full object-cover" loading="lazy">
                            <?php else: ?>
                                <span class="w-10 h-10 rounded-full bg-surface-alt flex items-center justify-center text-muted text-sm"><?= htmlspecialchars(mb_substr($streamer['name'], 0, 1)) ?></span>
                            <?php endif ?>
                        </div>
                        <a href="<?= htmlspecialchars($streamer['url']) ?>" target="_blank" rel="noopener" class="text-text text-base truncate hover:underline"><?= htmlspecialchars($streamer['name']) ?></a>
                        <span class="text-muted text-sm truncate"><?= htmlspecialchars($streamer['game_name']) ?></span>
                        <span class="text-text text-base text-right"><?= twitchFormatViewers($streamer['viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($streamer['avg_viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($streamer['max_viewers']) ?></span>
                        <span class="text-muted text-base text-right"><?= twitchFormatViewers($streamer['followers_total']) ?></span>
                        <div class="flex flex-col items-center gap-1">
                            <?= twitchSparkline($streamer['history'] ?? []) ?>
                            <?= twitchFormatChange($streamer['change_pct']) ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
</div>

<script>
    (function() {
        var page = document.querySelector('[data-twitch-page]');
        if (!page) return;

        var tabs = page.querySelectorAll('[data-twitch-page-tab]');
        var colors = {
            games: 'neon-cyan',
            streamers: 'neon-green',
            spanish: 'neon-magenta'
        };

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var target = tab.getAttribute('data-twitch-page-tab');
                var color = colors[target] || 'neon-cyan';

                tabs.forEach(function(t) {
                    var isActive = t.getAttribute('data-twitch-page-tab') === target;
                    t.classList.toggle('text-' + color, isActive);
                    t.classList.toggle('border-' + color, isActive);
                    t.classList.toggle('text-muted', !isActive);
                    t.classList.toggle('border-transparent', !isActive);
                });

                page.querySelectorAll('[data-twitch-page-content]').forEach(function(content) {
                    content.classList.toggle('hidden', content.getAttribute('data-twitch-page-content') !== target);
                });
            });
        });
    })();
</script>

<?php snippet('footer') ?>
