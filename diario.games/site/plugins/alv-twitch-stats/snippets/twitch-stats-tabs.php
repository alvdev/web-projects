<?php

$stats = site()->twitchStats();
$games = $stats->getTopGames(10);
$streamers = $stats->getTopStreamers(10);
$spanish = $stats->getTopSpanish(10);

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
?>

<span class="hidden text-neon-green text-neon-magenta border-neon-green border-neon-magenta" aria-hidden="true"></span>

<div class="bg-surface border border-border rounded-xl p-4 flex flex-col" data-twitch-widget>
    <div class="relative flex gap-4 justify-between mb-4 after:absolute after:inset-x-0 after:bottom-0 after:w-full after:h-0.5 after:bg-white/5 *:hover:cursor-pointer">
        <button type="button"
            data-twitch-tab="games"
            class="twitch-tab w-full pb-2 text-sm font-semibold text-neon-cyan border-b-2 border-neon-cyan transition">
            Juegos
        </button>
        <button type="button"
            data-twitch-tab="streamers"
            class="twitch-tab w-full pb-2 text-sm font-semibold text-muted border-b-2 border-transparent hover:text-neon-green transition">
            Streamers
        </button>
        <button type="button"
            data-twitch-tab="spanish"
            class="twitch-tab w-full pb-2 text-sm font-semibold text-muted border-b-2 border-transparent hover:text-neon-magenta transition">
            En español
        </button>
    </div>

    <div data-twitch-tab-content="games" class="flex-1">
        <?php if (empty($games)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[2fr_45px_55px] gap-x-3 text-text/70 text-xs mb-3">
                <div>Juegos en Twitch</div>
                <div class="text-right">Ahora</div>
                <div class="text-right">Media 30d</div>
            </div>
            <div class="grid grid-cols-[80px_1fr_45px_55px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($games as $game): ?>
                    <?php [$gameUrl, $isImporting] = twitchGameLink($game, $igdbToSlug); $importAttr = $isImporting ? ' data-importing rel="nofollow"' : ''; $external = str_starts_with($gameUrl, 'http') ? ' target="_blank" rel="noopener"' : ''; ?>
                    <div class="relative flex items-center" data-twitch-row>
                        <span class="absolute -left-2.5 text-neon-cyan text-xs text-center bg-surface/70 w-4 h-4 rounded-full z-10"><?= $game['rank'] ?></span>
                        <a href="<?= htmlspecialchars($gameUrl) ?>" class="block"<?= $importAttr ?><?= $external ?>>
                            <?php if ($game['box_art_url']): ?>
                                <img src="<?= htmlspecialchars($game['box_art_url']) ?>" alt="<?= htmlspecialchars($game['name']) ?>" class="w-9 h-12 object-cover rounded" loading="lazy">
                            <?php else: ?>
                                <span class="w-9 h-12 rounded bg-surface-alt flex items-center justify-center text-muted text-[8px] text-center leading-tight">Sin imagen</span>
                            <?php endif ?>
                        </a>
                    </div>
                    <a href="<?= htmlspecialchars($gameUrl) ?>" class="text-text text-xs line-clamp-2 hover:underline"<?= $importAttr ?><?= $external ?>><?= htmlspecialchars($game['name']) ?></a>
                    <span class="text-text text-sm text-right"><?= twitchFormatViewers($game['viewers']) ?></span>
                    <span class="text-muted text-sm text-right"><?= twitchFormatViewers($game['avg_viewers']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-twitch-tab-content="streamers" class="hidden flex-1">
        <?php if (empty($streamers)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[2fr_45px_55px] gap-x-3 text-text/70 text-xs mb-3">
                <div>Streamers en directo</div>
                <div class="text-right">Ahora</div>
                <div class="text-right">Media 30d</div>
            </div>
            <div class="grid grid-cols-[80px_1fr_45px_55px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($streamers as $streamer): ?>
                    <div class="relative flex items-center" data-twitch-row>
                        <span class="absolute -left-2.5 text-neon-green text-xs text-center bg-surface/70 w-4 h-4 rounded-full z-10"><?= $streamer['rank'] ?></span>
                        <?php if ($streamer['avatar_url']): ?>
                            <img src="<?= htmlspecialchars($streamer['avatar_url']) ?>" alt="<?= htmlspecialchars($streamer['name']) ?>" class="w-8 h-8 rounded-full object-cover" loading="lazy">
                        <?php else: ?>
                            <span class="w-8 h-8 rounded-full bg-surface-alt flex items-center justify-center text-muted text-[10px]"><?= htmlspecialchars(mb_substr($streamer['name'], 0, 1)) ?></span>
                        <?php endif ?>
                    </div>
                    <a href="<?= htmlspecialchars($streamer['url']) ?>" target="_blank" rel="noopener" class="min-w-0">
                        <span class="block text-text text-xs truncate hover:underline"><?= htmlspecialchars($streamer['name']) ?></span>
                        <span class="block text-muted text-[10px] truncate"><?= htmlspecialchars($streamer['game_name']) ?></span>
                    </a>
                    <span class="text-text text-sm text-right"><?= twitchFormatViewers($streamer['viewers']) ?></span>
                    <span class="text-muted text-sm text-right"><?= twitchFormatViewers($streamer['avg_viewers']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-twitch-tab-content="spanish" class="hidden flex-1">
        <?php if (empty($spanish)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[2fr_45px_55px] gap-x-3 text-text/70 text-xs mb-3">
                <div>Streamers en español</div>
                <div class="text-right">Ahora</div>
                <div class="text-right">Media 30d</div>
            </div>
            <div class="grid grid-cols-[80px_1fr_45px_55px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($spanish as $streamer): ?>
                    <div class="relative flex items-center" data-twitch-row>
                        <span class="absolute -left-2.5 text-neon-magenta text-xs text-center bg-surface/70 w-4 h-4 rounded-full z-10"><?= $streamer['rank'] ?></span>
                        <?php if ($streamer['avatar_url']): ?>
                            <img src="<?= htmlspecialchars($streamer['avatar_url']) ?>" alt="<?= htmlspecialchars($streamer['name']) ?>" class="w-8 h-8 rounded-full object-cover" loading="lazy">
                        <?php else: ?>
                            <span class="w-8 h-8 rounded-full bg-surface-alt flex items-center justify-center text-muted text-[10px]"><?= htmlspecialchars(mb_substr($streamer['name'], 0, 1)) ?></span>
                        <?php endif ?>
                    </div>
                    <a href="<?= htmlspecialchars($streamer['url']) ?>" target="_blank" rel="noopener" class="min-w-0">
                        <span class="block text-text text-xs truncate hover:underline"><?= htmlspecialchars($streamer['name']) ?></span>
                        <span class="block text-muted text-[10px] truncate"><?= htmlspecialchars($streamer['game_name']) ?></span>
                    </a>
                    <span class="text-text text-sm text-right"><?= twitchFormatViewers($streamer['viewers']) ?></span>
                    <span class="text-muted text-sm text-right"><?= twitchFormatViewers($streamer['avg_viewers']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div class="mt-4 pt-3 border-t border-border flex items-center justify-between gap-2">
        <a href="/twitch-stats" class="text-neon-cyan text-sm font-semibold hover:underline transition">
            Ver rankings completos →
        </a>
        <span class="text-[10px] text-muted text-right">Datos de Twitch · 30d: TwitchTracker</span>
    </div>
</div>

<script>
    (function() {
        var widget = document.querySelector('[data-twitch-widget]');
        if (!widget || widget.getAttribute('data-twitch-init') === '1') return;
        widget.setAttribute('data-twitch-init', '1');

        var tabs = widget.querySelectorAll('[data-twitch-tab]');
        var colors = {
            games: 'neon-cyan',
            streamers: 'neon-green',
            spanish: 'neon-magenta'
        };

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var target = tab.getAttribute('data-twitch-tab');
                var color = colors[target] || 'neon-cyan';

                tabs.forEach(function(t) {
                    var isActive = t.getAttribute('data-twitch-tab') === target;
                    t.classList.toggle('text-' + color, isActive);
                    t.classList.toggle('border-' + color, isActive);
                    t.classList.toggle('text-muted', !isActive);
                    t.classList.toggle('border-transparent', !isActive);
                });

                widget.querySelectorAll('[data-twitch-tab-content]').forEach(function(content) {
                    content.classList.toggle('hidden', content.getAttribute('data-twitch-tab-content') !== target);
                });
            });
        });
    })();
</script>
