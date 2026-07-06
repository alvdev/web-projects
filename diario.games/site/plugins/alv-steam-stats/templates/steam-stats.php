<?php snippet('header') ?>

<?php
$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(100);
$trending = $stats->getTrending(100);

$steamSlugMap = [];
try {
    $db = new \Alv\SteamStats\SteamStatsDB();
    $playerData = $db->getAllPlayerDataCached();
    foreach ($db->getAllGames() as $g) {
        $pd = $playerData[$g['appid']] ?? ['current_players' => 0, 'peak_24h' => 0];
        $capsulePath = dirname(__DIR__, 4) . '/content/games/' . $g['slug'] . '/steam-capsule.jpg';
        $capsuleUrl = file_exists($capsulePath)
            ? '/media/steam-capsule/' . $g['slug'] . '.jpg'
            : '';
        $steamSlugMap[$g['slug']] = [
            'appid' => (string)$g['appid'],
            'name' => $g['name'],
            'current_players' => $pd['current_players'],
            'peak_players' => $pd['peak_24h'],
            'capsule_image' => $capsuleUrl,
        ];
    }
} catch (\Throwable $e) {
}

function pageFormatPlayers(int $count): string
{
    if ($count >= 1000000) return round($count / 1000000, 1) . 'M';
    if ($count >= 1000) return round($count / 1000, 1) . 'K';
    return (string) $count;
}

function pageSparkline(array $history): string
{
    if (empty($history)) {
        return '<span class="text-xs text-muted">No data</span>';
    }
    $values = array_map(fn($p) => $p['players'] ?? 0, $history);
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
    foreach ($values as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * 100 : 50;
        $y = 30 - (($v - $min) / $range) * 28;
        $points[] = round($x, 1) . ',' . round($y, 1);
    }
    return '<svg width="100" height="30" viewBox="0 0 100 30"><polyline points="' . implode(' ', $points) . '" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/></svg>';
}
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-text">Steam Charts</h1>
    <p class="text-sm text-muted mt-1">Top 100 most played and trending games on Steam</p>
    <?php
    $warmCache = kirby()->cache('alv/steam-stats.cache')->get('warm-last-run');
    $lastRun = is_array($warmCache) ? ($warmCache['value'] ?? null) : null;
    if ($lastRun):
        $ago = time() - $lastRun;
    ?>
        <p class="text-xs text-muted mt-2">
            Last updated <span id="minutes-ago"><?= round($ago / 60) ?></span> min ago
        </p>
        <script>
            (function() {
                var ts = <?= $lastRun ?>;
                var el = document.getElementById('minutes-ago');
                if (el) {
                    el.textContent = Math.round((Date.now() / 1000 - ts) / 60);
                    setInterval(function() {
                        el.textContent = Math.round((Date.now() / 1000 - ts) / 60);
                    }, 60000);
                }
            })();
        </script>
    <?php endif ?>
</div>

<script type="application/json" id="steam-page-data">
    <?= json_encode($mostPlayed) ?>
</script>
<script type="application/json" id="steam-slug-map">
    <?= json_encode($steamSlugMap) ?>
</script>

<div class="bg-surface border border-border rounded-xl p-6 overflow-hidden">
    <!-- Tabs -->
    <div class="relative flex gap-4 justify-between mb-6 after:absolute after:inset-x-0 after:bottom-0 after:w-full after:h-0.5 after:bg-white/5 *:hover:cursor-pointer">
        <button type="button" class="steam-page-tab active w-full pt-2 pb-4 text-sm font-semibold uppercase tracking-wider text-neon-cyan border-b-2 border-neon-cyan" data-tab="most-played-full">
            Más jugados
        </button>
        <button type="button" class="steam-page-tab w-full pt-2 pb-4 text-sm font-bold uppercase tracking-wider text-muted border-b-2 border-transparent" data-tab="trending-full">
           Tendencia 
        </button>
        <button type="button" class="steam-page-tab w-full pt-2 pb-4 text-sm font-bold uppercase tracking-wider text-muted border-b-2 border-transparent" data-tab="favorites-full">
           Favoritos 
        </button>
    </div>

    <!-- Most Played Tab -->
    <div class="steam-page-tab-content" id="most-played-full">
        <div class="mb-6">
            <div class="grid grid-cols-[2fr_100px_100px] gap-x-6 text-text/70 text-sm">
                <span>Jugadores en Steam</span>
                <span class="text-right">Ahora</span>
                <span class="text-right">24h</span>
            </div>
        </div>
        <?php if (empty($mostPlayed)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <div class="divide-y divide-border/30">
                <?php foreach ($mostPlayed as $game): ?>
                    <div class="grid grid-cols-[160px_1fr_100px_100px] gap-x-6 items-center py-2">
                        <div class="relative flex items-center justify-center">
                            <span class="absolute -left-3 text-neon-cyan text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75"><?= $game['rank'] ?></span>
                            <button type="button"
                                class="steam-fav-page absolute -right-3 text-xl text-muted hover:text-yellow-400 bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0"
                                data-appid="<?= $game['appid'] ?>"
                                data-name="<?= htmlspecialchars($game['name']) ?>"
                                data-capsule="<?= $game['capsule_image'] ?>"
                                data-current="<?= $game['current_players'] ?>"
                                data-peak="<?= $game['peak_players'] ?>">☆</button>
                            <img src="<?= $game['capsule_image'] ?>" alt="" class="aspect-8/3 object-cover rounded" loading="lazy">
                        </div>
                        <span class="text-text text-base line-clamp-2"><?= htmlspecialchars($game['name']) ?></span>
                        <span class="text-text text-base text-right"><?= pageFormatPlayers($game['current_players']) ?></span>
                        <span class="text-muted text-base text-right"><?= pageFormatPlayers($game['peak_players']) ?></span>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <!-- Trending Tab -->
    <div class="steam-page-tab-content hidden" id="trending-full">
        <div class="mb-6">
            <div class="grid grid-cols-[1fr_100px_100px] gap-x-6 text-text/70 text-sm">
                <span>Crecimiento en Steam</span>
                <span class="text-center">Última semana</span>
                <span class="text-right">Ahora</span>
            </div>
        </div>
        <?php if (empty($trending)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <div class="divide-y divide-border/30">
                <?php foreach ($trending as $game): ?>
                    <div class="grid grid-cols-[160px_1fr_200px_100px] gap-x-6 items-center py-2">
                        <div class="relative flex items-center justify-center">
                            <span class="absolute -left-2 text-neon-green text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75"><?= $game['rank'] ?></span>
                            <button type="button"
                                class="steam-fav-page absolute -right-3 text-xl text-muted hover:text-yellow-400 bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0"
                                data-appid="<?= $game['appid'] ?>"
                                data-name="<?= htmlspecialchars($game['name']) ?>"
                                data-capsule="<?= $game['capsule_image'] ?>"
                                data-current="<?= $game['current_players'] ?>"
                                data-peak="0">☆</button>
                            <img src="<?= $game['capsule_image'] ?>" alt="" class="aspect-8/3 object-cover rounded" loading="lazy">
                        </div>
                        <span class="text-text text-base line-clamp-2"><?= htmlspecialchars($game['name']) ?></span>
                        <div class="flex ml-auto items-center"><?= pageSparkline($game['history'] ?? []) ?></div>
                        <span class="text-text text-base text-right"><?= pageFormatPlayers($game['current_players']) ?></span>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>

    <!-- Favorites Tab -->
    <div class="steam-page-tab-content hidden" id="favorites-full">
        <div class="mb-4">
            <div class="grid grid-cols-[2fr_100px_100px] gap-x-6 text-text/70 text-sm">
                <span>Jugadores en Steam</span>
                <span class="text-right">Ahora</span>
                <span class="text-right">24h</span>
            </div>
        </div>
        <div id="steam-favorites-page-list">
            <p class="text-muted text-sm text-center py-6" id="steam-favorites-page-empty">Loading...</p>
        </div>
    </div>
</div>

<script>
    (function() {
        var FAV_KEY = 'steam-favorites-v1';

        function fmtPlayers(n) {
            if (n >= 1000000) return (n / 1000000).toFixed(1).replace(/\.?0+$/, '') + 'M';
            if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.?0+$/, '') + 'K';
            return String(n);
        }

        function esc(s) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(s));
            return d.innerHTML;
        }

        function renderPageFavorites() {
            var data = document.getElementById('steam-page-data');
            var list = document.getElementById('steam-favorites-page-list');
            var empty = document.getElementById('steam-favorites-page-empty');
            if (!data || !list) return;

            var games;
            try {
                games = JSON.parse(data.textContent);
            } catch (e) {
                games = [];
            }
            var gamesByAppid = {};
            games.forEach(function(g) {
                gamesByAppid[g.appid] = g;
            });

            var slugMap = {};
            (function() {
                var el = document.getElementById('steam-slug-map');
                if (el) try {
                    slugMap = JSON.parse(el.textContent);
                } catch (e) {}
            })();

            var slugByAppid = {};
            (function() {
                Object.keys(slugMap).forEach(function(slug) {
                    var entry = slugMap[slug];
                    if (entry && entry.appid) slugByAppid[entry.appid] = entry;
                });
            })();

            var favs;
            try {
                favs = JSON.parse(localStorage.getItem(FAV_KEY) || '{}');
            } catch (e) {
                favs = {};
            }

            // Merge site favorites that have a Steam appid
            try {
                var siteFavs = JSON.parse(localStorage.getItem('site-favorites-v1') || '{}');
                Object.keys(siteFavs).forEach(function(slug) {
                    var map = slugMap[slug];
                    if (!map) return;
                    var appid = String(map.appid);
                    if (favs[appid]) return;
                    var sf = siteFavs[slug];
                    var mp = gamesByAppid[appid];
                    favs[appid] = {
                        name: mp ? mp.name : (map.name || sf.title || 'Unknown'),
                        capsule_image: mp ? mp.capsule_image : (sf.cover || ''),
                        current_players: map.current_players || (mp ? mp.current_players : 0),
                        peak_players: map.peak_players || (mp ? mp.peak_players : 0)
                    };
                });
            } catch (e) {}

            var appids = Object.keys(favs);

            if (appids.length === 0) {
                if (empty) empty.textContent = 'No favorite games yet.';
                return;
            }

            var rows = [];
            appids.forEach(function(appid) {
                var g = slugByAppid[appid] || gamesByAppid[appid] || favs[appid];
                if (!g) return;
                var ls = favs[appid];
                var capUrl = g.capsule_image || (ls && ls.capsule_image) || '';
                // Discard legacy centralized paths
                if (capUrl.indexOf('/assets/steam-capsules/') === 0) capUrl = '';
                rows.push({
                    appid: appid,
                    name: g.name || 'Unknown',
                    capsule_image: capUrl,
                    current_players: g.current_players || 0,
                    peak_players: g.peak_players || 0
                });
            });

            rows.sort(function(a, b) {
                return b.current_players - a.current_players;
            });

            var html = '';
            rows.forEach(function(g, i) {
                var imgSrc = g.capsule_image || '';
                var imgHtml = imgSrc ?
                    '<img src="' + imgSrc + '" alt="" class="aspect-8/3 object-cover rounded" loading="lazy">' :
                    '<div class="aspect-8/3 bg-surface-alt rounded flex items-center justify-center text-muted text-xl">--</div>';
                var cur = g.current_players;
                var peak = g.peak_players;
                html += '<div class="grid grid-cols-[160px_1fr_100px_100px] gap-x-6 items-center py-2">' +
                    '<div class="relative flex items-center justify-center">' +
                    '<span class="absolute -left-3 text-neon-magenta text-base text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75">' + (i + 1) + '</span>' +
                    '<button type="button" class="steam-fav-page-remove absolute -right-3 text-sm text-muted hover:text-white bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0" data-appid="' + g.appid + '">✕</button>' +
                    imgHtml +
                    '</div>' +
                    '<span class="text-text text-base line-clamp-2">' + esc(g.name) + '</span>' +
                    '<span class="text-text text-base text-right">' + fmtPlayers(cur) + '</span>' +
                    '<span class="text-muted text-base text-right">' + fmtPlayers(peak) + '</span>' +
                    '</div>';
            });

            list.innerHTML = html;
            if (empty) empty.style.display = 'none';
        }

        function updatePageStars() {
            var favs;
            try {
                favs = JSON.parse(localStorage.getItem(FAV_KEY) || '{}');
            } catch (e) {
                favs = {};
            }
            document.querySelectorAll('.steam-fav-page').forEach(function(btn) {
                var appid = btn.getAttribute('data-appid');
                var isFav = !!favs[appid];
                btn.textContent = isFav ? '\u2605' : '\u2606';
                btn.classList.toggle('text-yellow-400', isFav);
                btn.classList.toggle('text-muted', !isFav);
            });
        }

        // Toggle favorite from star buttons
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.steam-fav-page');
            if (!btn) return;
            var appid = btn.getAttribute('data-appid');
            var favs;
            try {
                favs = JSON.parse(localStorage.getItem(FAV_KEY) || '{}');
            } catch (e) {
                favs = {};
            }
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
            try {
                localStorage.setItem(FAV_KEY, JSON.stringify(favs));
            } catch (e) {}
            updatePageStars();
            renderPageFavorites();
            e.stopPropagation();
        });

        // Tab switching (3 tabs)
        var tabs = document.querySelectorAll('.steam-page-tab');
        var tabColors = {
            'most-played-full': 'neon-cyan',
            'trending-full': 'neon-green',
            'favorites-full': 'neon-magenta'
        };

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var target = tab.getAttribute('data-tab');
                tabs.forEach(function(t) {
                    var isActive = t.getAttribute('data-tab') === target;
                    var c = tabColors[target] || 'neon-cyan';
                    t.classList.toggle('text-' + c, isActive);
                    t.classList.toggle('text-muted', !isActive);
                    t.classList.toggle('border-' + c, isActive);
                    t.classList.toggle('border-transparent', !isActive);
                });
                document.querySelectorAll('.steam-page-tab-content').forEach(function(c) {
                    c.classList.toggle('hidden', c.getAttribute('id') !== target);
                });
                updatePageStars();
            });
        });

        // Remove favorite from page tab
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.steam-fav-page-remove');
            if (!btn) return;
            var appid = btn.getAttribute('data-appid');
            var favs;
            try {
                favs = JSON.parse(localStorage.getItem(FAV_KEY) || '{}');
            } catch (e) {
                favs = {};
            }
            delete favs[appid];
            try {
                localStorage.setItem(FAV_KEY, JSON.stringify(favs));
            } catch (e) {}
            // Also remove from site-favorites-v1
            try {
                var slugMap = JSON.parse((document.getElementById('steam-slug-map') || {}).textContent || '{}');
                var sf = JSON.parse(localStorage.getItem('site-favorites-v1') || '{}');
                Object.keys(slugMap).forEach(function(slug) {
                    if (String(slugMap[slug].appid) === appid) {
                        delete sf[slug];
                    }
                });
                localStorage.setItem('site-favorites-v1', JSON.stringify(sf));
            } catch (e) {}
            renderPageFavorites();
            e.stopPropagation();
        });

        updatePageStars();
        renderPageFavorites();
    })();
</script>

<?php snippet('footer') ?>
