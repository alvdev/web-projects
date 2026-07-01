<?php

$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(10);
$trending = $stats->getTrending(10);

$steamSlugMap = [];
try {
    $db = new \Alv\SteamStats\SteamStatsDB();
    $playerData = $db->getAllPlayerData();
    $stats = site()->steamStats();
    foreach ($db->getAllGames() as $g) {
        $pd = $playerData[$g['appid']] ?? ['current_players' => 0, 'peak_24h' => 0];
        if ($pd['current_players'] === 0 && $pd['peak_24h'] === 0) {
            $live = $stats->getLivePlayerCount($g['appid']);
            if ($live > 0) {
                $pd['current_players'] = $live;
                $pd['peak_24h'] = $live;
            }
        }
        $steamSlugMap[$g['slug']] = [
            'appid' => (string)$g['appid'],
            'name' => $g['name'],
            'current_players' => $pd['current_players'],
            'peak_players' => $pd['peak_24h'],
        ];
    }
} catch (\Throwable $e) {
}

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

<div class="bg-surface border border-border rounded-xl p-4 flex flex-col">
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
        <button type="button"
            data-tab="favorites"
            class="steam-tab w-full pb-2 text-sm font-semibold text-muted border-b-2 border-transparent hover:text-neon-magenta transition">
            Favoritos
        </button>
    </div>

    <div data-tab-content="most-played" class="h-full">
        <?php if (empty($mostPlayed)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[2fr_50px_50px] gap-x-3 text-text/70 text-xs mb-4">
                <div>Jugadores en Steam</div>
                <div class="text-right">Ahora</div>
                <div class="text-right">24h</div>
            </div>
            <div class="grid grid-cols-[80px_1fr_50px_50px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($mostPlayed as $game): ?>
                    <div class="relative flex items-center">
                        <span class="absolute -left-2.5 text-neon-cyan text-xs text-center bg-surface/70 w-4 h-4 rounded-full z-10"><?= $game['rank'] ?></span>
                        <button type="button"
                            class="steam-fav absolute -right-2 text-sm text-muted hover:text-yellow-400 bg-surface/70 w-4 h-4 rounded-full transition z-10 leading-0"
                            data-appid="<?= $game['appid'] ?>"
                            data-name="<?= htmlspecialchars($game['name']) ?>"
                            data-capsule="<?= $game['capsule_image'] ?>"
                            data-current="<?= $game['current_players'] ?>"
                            data-peak="<?= $game['peak_players'] ?>">☆</button>
                        <img src="<?= $game['capsule_image'] ?>" alt="" class="w-20 h-7.5 object-cover rounded" loading="lazy">
                    </div>
                    <span class="text-text text-xs line-clamp-2"><?= htmlspecialchars($game['name']) ?></span>
                    <span class="text-text text-sm text-right"><?= steamFormatPlayers($game['current_players']) ?></span>
                    <span class="text-muted text-sm text-right"><?= steamFormatPlayers($game['peak_players']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-tab-content="trending" class="hidden h-full">
        <?php if (empty($trending)): ?>
            <p class="text-muted text-sm text-center py-6">No hay datos</p>
        <?php else: ?>
            <div class="grid grid-cols-[1fr_100px_50px] gap-x-3 text-text/70 text-xs mb-4">
                <div>Crecimiento en Steam</div>
                <div class="text-center">Última semana</div>
                <div class="text-right">Ahora</div>
            </div>
            <div class="grid grid-cols-[80px_1fr_100px_50px] gap-x-3 gap-y-2 items-center">
                <?php foreach ($trending as $game): ?>
                    <div class="relative flex items-center">
                        <span class="absolute -left-2.5 text-neon-cyan text-xs text-center bg-surface/70 w-4 h-4 rounded-full z-10"><?= $game['rank'] ?></span>
                        <button type="button"
                            class="steam-fav absolute -right-2 text-sm text-muted hover:text-yellow-400 bg-surface/70 w-4 h-4 rounded-full transition z-10 leading-0"
                            data-appid="<?= $game['appid'] ?>"
                            data-name="<?= htmlspecialchars($game['name']) ?>"
                            data-capsule="<?= $game['capsule_image'] ?>"
                            data-current="<?= $game['current_players'] ?>"
                            data-peak="0">☆</button>
                        <img src="<?= $game['capsule_image'] ?>" alt="" class="w-20 h-7.5 object-cover rounded" loading="lazy">
                    </div>
                    <span class="text-text text-xs line-clamp-2"><?= htmlspecialchars($game['name']) ?></span>
                    <span class="flex justify-center"><?= steamSparkline($game['history'] ?? []) ?></span>
                    <span class="text-text text-sm text-right"><?= steamFormatPlayers($game['current_players']) ?></span>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <div data-tab-content="favorites" class="hidden flex-1">
        <div class="grid grid-cols-[2fr_50px_50px] gap-x-3 text-text/70 text-xs mb-4">
            <div>Jugadores en Steam</div>
            <div class="text-right">Ahora</div>
            <div class="text-right">24h</div>
        </div>
        <div id="steam-favorites-list" class="grid grid-cols-[80px_1fr_50px_50px] gap-x-3 gap-y-2 items-center">
            <!-- populated by JS -->
        </div>
        <p id="steam-favorites-empty" class="text-muted text-sm text-balance text-center pb-6 hidden h-full flex items-center">
            No tienes juegos favoritos. Haz clic en ☆ junto a un juego para añadirlo.
        </p>
    </div>

    <div class="mt-4 pt-3 border-t border-border">
        <a href="/steam-stats"
            data-tab-link
            class="text-neon-cyan text-sm font-semibold hover:underline transition">
            View Full Rankings →
        </a>
    </div>
</div>

<script type="application/json" id="steam-slug-map">
    <?= json_encode($steamSlugMap) ?>
</script>
<script type="application/json" id="steam-most-played-data">
    <?= json_encode(array_column($mostPlayed, null, 'appid')) ?>
</script>

<script>
    (function() {
        var STEAM_FAV_KEY = 'steam-favorites-v1';
        var SITE_FAV_KEY = 'site-favorites-v1';

        var slugMap = {};
        (function() {
            var el = document.getElementById('steam-slug-map');
            if (el) try {
                slugMap = JSON.parse(el.textContent);
            } catch (e) {}
        })();

        var mostPlayedByAppid = {};
        (function() {
            var el = document.getElementById('steam-most-played-data');
            if (el) try {
                mostPlayedByAppid = JSON.parse(el.textContent);
            } catch (e) {}
        })();

        function getLS(k) {
            try {
                return JSON.parse(localStorage.getItem(k) || '{}');
            } catch (e) {
                return {};
            }
        }

        function setLS(k, v) {
            try {
                localStorage.setItem(k, JSON.stringify(v));
            } catch (e) {}
        }

        function toggleSteamFav(btn) {
            var appid = btn.getAttribute('data-appid');
            var favs = getLS(STEAM_FAV_KEY);
            if (favs[appid]) {
                delete favs[appid];
            } else {
                favs[appid] = {
                    name: btn.getAttribute('data-name'),
                    capsule_image: btn.getAttribute('data-capsule'),
                    current_players: parseInt(btn.getAttribute('data-current'), 10) || 0,
                    peak_players: parseInt(btn.getAttribute('data-peak'), 10) || 0
                };
            }
            setLS(STEAM_FAV_KEY, favs);
            updateSteamStars();
            renderFavorites();
        }

        function updateSteamStars() {
            var favs = getLS(STEAM_FAV_KEY);
            document.querySelectorAll('.steam-fav').forEach(function(btn) {
                var appid = btn.getAttribute('data-appid');
                var isFav = !!favs[appid];
                btn.textContent = isFav ? '\u2605' : '\u2606';
                btn.classList.toggle('text-yellow-400', isFav);
                btn.classList.toggle('text-muted', !isFav);
            });
        }

        function renderFavorites() {
            var steamFavs = getLS(STEAM_FAV_KEY);
            var siteFavs = getLS(SITE_FAV_KEY);
            var list = document.getElementById('steam-favorites-list');
            var empty = document.getElementById('steam-favorites-empty');
            var showMoreLink = document.getElementById('steam-favorites-more');

            // Merge site favorites that have a Steam appid into steamFavs
            Object.keys(siteFavs).forEach(function(slug) {
                var map = slugMap[slug];
                if (!map) return;
                var appid = String(map.appid);
                if (steamFavs[appid]) return; // already in steam favs
                var sf = siteFavs[slug];
                var mp = mostPlayedByAppid[appid];
                steamFavs[appid] = {
                    name: mp ? mp.name : (map.name || sf.title || 'Unknown'),
                    capsule_image: mp ? mp.capsule_image : (sf.cover || ''),
                    current_players: mp ? mp.current_players : (map.current_players || 0),
                    peak_players: mp ? mp.peak_players : (map.peak_players || 0)
                };
            });

            var appids = Object.keys(steamFavs).sort(function(a, b) {
                return (steamFavs[b].current_players || 0) - (steamFavs[a].current_players || 0);
            });
            var display = appids.slice(0, 10);

            if (appids.length === 0) {
                list.innerHTML = '';
                empty.classList.remove('hidden');
                if (showMoreLink) showMoreLink.classList.add('hidden');
                return;
            }
            empty.classList.add('hidden');

            var html = '';
            display.forEach(function(appid, i) {
                var g = steamFavs[appid];
                if (!g) return;
                var imgSrc = g.capsule_image || '';
                var imgHtml = imgSrc ?
                    '<img src="' + escapeAttr(imgSrc) + '" alt="" class="w-20 h-7.5 object-cover rounded" loading="lazy">' :
                    '<div class="w-20 h-7.5 bg-surface-alt rounded flex items-center justify-center text-muted text-xs">--</div>';
                var cur = g.current_players;
                var peak = g.peak_players;
                html += '' +
                    '<div class="relative flex items-center">' +
                    '<span class="absolute -left-2.5 text-neon-cyan text-xs text-center bg-surface/70 w-4 h-4 rounded-full z-10">' + (i + 1) + '</span>' +
                    '<button type="button" class="steam-fav-remove absolute -right-2 text-[10px] text-muted hover:text-white bg-surface/70 w-4 h-4 rounded-full transition z-10 leading-0" data-appid="' + appid + '">\u2715</button>' +
                    imgHtml +
                    '</div>' +
                    '<span class="text-text text-xs line-clamp-2">' + escapeHtml(g.name || 'Unknown') + '</span>' +
                    '<span class="text-text text-sm text-right">' + (cur ? fmtPlayers(cur) : '<span class="text-muted">&mdash;</span>') + '</span>' +
                    '<span class="text-muted text-sm text-right">' + (peak ? fmtPlayers(peak) : '<span class="text-muted">&mdash;</span>') + '</span>';
            });
            list.innerHTML = html;
            if (showMoreLink) {
                showMoreLink.classList.toggle('hidden', appids.length <= 10);
            }
        }

        function fmtPlayers(n) {
            if (n >= 1000000) return (n / 1000000).toFixed(2).replace(/\.?0+$/, '') + 'M';
            if (n >= 1000) return Math.round(n / 1000) + 'K';
            return String(n);
        }

        function escapeHtml(s) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(s));
            return d.innerHTML;
        }

        function escapeAttr(s) {
            return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        // Tab switching
        var tabs = document.querySelectorAll('.steam-tab');
        var widget = tabs.length ? tabs[0].closest('.bg-surface') : null;
        if (!widget) return;
        var link = widget.querySelector('[data-tab-link]');

        var tabColors = {
            'most-played': 'neon-cyan',
            'trending': 'neon-green',
            'favorites': 'neon-magenta'
        };

        function activateTab(target) {
            tabs.forEach(function(t) {
                var isActive = t.getAttribute('data-tab') === target;
                var color = tabColors[target] || 'neon-cyan';
                t.classList.toggle('text-' + color, isActive);
                t.classList.toggle('text-muted', !isActive);
                t.classList.toggle('border-' + color, isActive);
                t.classList.toggle('border-transparent', !isActive);
            });

            widget.querySelectorAll('[data-tab-content]').forEach(function(content) {
                content.classList.toggle('hidden', content.getAttribute('data-tab-content') !== target);
            });

            if (link) {
                var color = tabColors[target] || 'neon-cyan';
                ['neon-cyan', 'neon-green', 'neon-magenta'].forEach(function(c) {
                    link.classList.toggle('text-' + c, c === color);
                });
            }
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                activateTab(tab.getAttribute('data-tab'));
            });
        });

        // Favorite toggles (delegated)
        widget.addEventListener('click', function(e) {
            var btn = e.target.closest('.steam-fav');
            if (btn) {
                toggleSteamFav(btn);
                e.stopPropagation();
                return;
            }
            var removeBtn = e.target.closest('.steam-fav-remove');
            if (removeBtn) {
                var appid = removeBtn.getAttribute('data-appid');
                var favs = getLS(STEAM_FAV_KEY);
                delete favs[appid];
                // Also remove from site-favorites-v1 if it matches a slug
                Object.keys(slugMap).forEach(function(slug) {
                    if (String(slugMap[slug].appid) === appid) {
                        var sf = getLS(SITE_FAV_KEY);
                        delete sf[slug];
                        setLS(SITE_FAV_KEY, sf);
                    }
                });
                setLS(STEAM_FAV_KEY, favs);
                updateSteamStars();
                renderFavorites();
                e.stopPropagation();
            }
        });

        // Init
        updateSteamStars();
        renderFavorites();
    })();
</script>
