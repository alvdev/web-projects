<?php snippet('header') ?>

<?php
$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(100);
$trending = $stats->getTrending(100);

$steamSlugMap = [];
$appIdToSlug = [];
try {
    $db = new \Alv\SteamStats\SteamStatsDB();
    $playerData = $db->getAllPlayerDataCached();
    foreach ($db->getAllGames() as $g) {
        $pd = $playerData[$g['appid']] ?? ['current_players' => 0, 'peak_24h' => 0];
        $yearMonth = \DiarioGames\IGDB\resolveGamePath($g['slug']);
        $capsulePath = $yearMonth ? dirname(__DIR__, 4) . '/content/games/' . $yearMonth . '/' . $g['slug'] . '/steam-capsule.jpg' : null;
        $capsuleUrl = ($capsulePath && file_exists($capsulePath))
            ? '/media/steam-capsule/' . $g['slug'] . '.jpg'
            : '';
        $steamSlugMap[$g['slug']] = [
            'appid' => (string)$g['appid'],
            'name' => $g['name'],
            'current_players' => $pd['current_players'],
            'peak_players' => $pd['peak_24h'],
            'capsule_image' => $capsuleUrl,
        ];
        $appIdToSlug[(int)$g['appid']] = $g['slug'];
    }
} catch (\Throwable $e) {
}

$existingSlugs = [];
foreach (site()->find('games')->children()->children()->children()->filterBy('intendedTemplate', 'game') as $p) {
    $existingSlugs[$p->slug()] = true;
}

$gamePageUrl = function (array $game) use ($appIdToSlug): string {
    $appid = (int)($game['appid'] ?? 0);
    return isset($appIdToSlug[$appid])
        ? '/' . $appIdToSlug[$appid]
        : '/games/by-appid/' . $appid;
};

$needsImport = function (array $game) use ($appIdToSlug, $existingSlugs): bool {
    $appid = (int)($game['appid'] ?? 0);
    if (!isset($appIdToSlug[$appid])) return true;
    return !isset($existingSlugs[$appIdToSlug[$appid]]);
};

$importNeededAppids = [];
foreach ($appIdToSlug as $appid => $slug) {
    if (!isset($existingSlugs[$slug])) {
        $importNeededAppids[] = $appid;
    }
}

function pageFormatPlayers(int $count): string
{
    if ($count >= 1000000) return round($count / 1000000, 2) . 'M';
    if ($count >= 1000) return round($count / 1000, 1) . 'K';
    return (string) $count;
}

function pageFormatGrowth(array $game): string
{
    if (!empty($game['is_new'])) {
        return '<span class="text-neon-green font-semibold">Nuevo</span>';
    }
    $pct = $game['growth_pct'] ?? 0;
    $sign = $pct >= 0 ? '+' : '';
    $color = $pct >= 0 ? 'text-neon-green' : 'text-red-400';
    return '<span class="' . $color . ' font-semibold">' . $sign . round($pct, 1) . '%</span>';
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

    if ($range === 0) {
        $range = 1;
        $min = $min - 1;
    }

    $count = count($values);
    $points = [];
    $dots = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * 100 : 50;
        $y = 30 - (($v - $min) / $range) * 28;
        $points[] = round($x, 1) . ',' . round($y, 1);

        $ts = (int)($history[$i]['timestamp'] ?? 0);
        $players = (int)($history[$i]['players'] ?? $v);
        $dots[] = '<circle cx="' . round($x, 1) . '" cy="' . round($y, 1) . '" r="6" fill="transparent" class="sparkline-dot" data-ts="' . $ts . '" data-players="' . $players . '"/>';
    }
    return '<svg width="100" height="30" viewBox="0 0 100 30">'
        . '<polyline points="' . implode(' ', $points) . '" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>'
        . implode('', $dots)
        . '</svg>';
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
<script type="application/json" id="steam-trending-data">
    <?= json_encode($trending) ?>
</script>
<script type="application/json" id="steam-slug-map">
    <?= json_encode($steamSlugMap) ?>
</script>
<script type="application/json" id="steam-import-needed">
    <?= json_encode(array_values($importNeededAppids)) ?>
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
            <div class="grid grid-cols-[2fr_100px_100px_100px] gap-x-6 text-text/70 text-sm">
                <span>Jugadores en Steam</span>
                <span class="text-right">Ahora</span>
                <span class="text-right">24h</span>
                <span class="text-right">Máx. histórico</span>
            </div>
        </div>
        <?php if (empty($mostPlayed)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <div class="divide-y divide-border/30">
                <?php foreach (array_slice($mostPlayed, 0, 20) as $game): ?>
                    <?php $gameUrl = $gamePageUrl($game); $isImporting = $needsImport($game) ? ' data-importing' : '' ?>
                    <div class="grid grid-cols-[160px_1fr_100px_100px_100px] gap-x-6 items-center py-2">
                        <div class="relative flex items-center justify-center">
                            <span class="absolute -left-3 text-neon-cyan text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75"><?= $game['rank'] ?></span>
                            <button type="button"
                                class="steam-fav-page absolute -right-3 text-xl text-muted hover:text-yellow-400 bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0"
                                data-appid="<?= $game['appid'] ?>"
                                data-name="<?= htmlspecialchars($game['name']) ?>"
                                data-capsule="<?= $game['capsule_image'] ?>"
                                data-current="<?= $game['current_players'] ?>"
                                data-peak="<?= $game['peak_players'] ?>">☆</button>
                            <a href="<?= $gameUrl ?>" class="block"<?= $isImporting ?>><img src="<?= $game['capsule_image'] ?>" alt="<?= htmlspecialchars($game['name']) ?>" class="aspect-8/3 object-cover rounded" loading="lazy"></a>
                        </div>
                        <a href="<?= $gameUrl ?>" class="text-text text-base line-clamp-2 hover:underline"<?= $isImporting ?>><?= htmlspecialchars($game['name']) ?></a>
                        <span class="text-text text-base text-right"><?= pageFormatPlayers($game['current_players']) ?></span>
                        <span class="text-muted text-base text-right"><?= pageFormatPlayers($game['peak_players']) ?></span>
                        <span class="text-muted text-base text-right"><?= pageFormatPlayers($game['all_time_peak'] ?? 0) ?></span>
                    </div>
                <?php endforeach ?>
            </div>
            <div data-infinite-sentinel></div>
        <?php endif ?>
    </div>

    <!-- Trending Tab -->
    <div class="steam-page-tab-content hidden" id="trending-full">
        <div class="mb-6">
            <div class="grid grid-cols-[1fr_60px_160px_70px_70px] gap-x-6 text-text/70 text-sm">
                <span>Crecimiento en Steam</span>
                <span class="text-center">7 días</span>
                <span class="text-center">Última semana</span>
                <span class="text-right">Ahora</span>
                <span class="text-right">Máx. histórico</span>
            </div>
        </div>
        <?php if (empty($trending)): ?>
            <p class="text-muted text-sm text-center py-6">No data available.</p>
        <?php else: ?>
            <div class="divide-y divide-border/30">
                <?php foreach (array_slice($trending, 0, 20) as $game): ?>
                    <?php $gameUrl = $gamePageUrl($game); $isImporting = $needsImport($game) ? ' data-importing' : '' ?>
                    <div class="grid grid-cols-[160px_1fr_60px_160px_70px_70px] gap-x-6 items-center py-2">
                        <div class="relative flex items-center justify-center">
                            <span class="absolute -left-2 text-neon-green text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75"><?= $game['rank'] ?></span>
                            <button type="button"
                                class="steam-fav-page absolute -right-3 text-xl text-muted hover:text-yellow-400 bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0"
                                data-appid="<?= $game['appid'] ?>"
                                data-name="<?= htmlspecialchars($game['name']) ?>"
                                data-capsule="<?= $game['capsule_image'] ?>"
                                data-current="<?= $game['current_players'] ?>"
                                data-peak="0">☆</button>
                            <a href="<?= $gameUrl ?>" class="block"<?= $isImporting ?>><img src="<?= $game['capsule_image'] ?>" alt="<?= htmlspecialchars($game['name']) ?>" class="aspect-8/3 object-cover rounded" loading="lazy"></a>
                        </div>
                        <a href="<?= $gameUrl ?>" class="text-text text-base line-clamp-2 hover:underline"<?= $isImporting ?>><?= htmlspecialchars($game['name']) ?></a>
                        <span class="text-center text-sm whitespace-nowrap"><?= pageFormatGrowth($game) ?></span>
                        <div class="flex ml-auto items-center"><?= pageSparkline($game['history'] ?? []) ?></div>
                        <span class="text-text text-base text-right"><?= pageFormatPlayers($game['current_players']) ?></span>
                        <span class="text-muted text-base text-right"><?= pageFormatPlayers($game['all_time_peak'] ?? 0) ?></span>
                    </div>
                <?php endforeach ?>
            </div>
            <div data-infinite-sentinel></div>
        <?php endif ?>
    </div>

    <!-- Favorites Tab -->
    <div class="steam-page-tab-content hidden" id="favorites-full">
        <div class="mb-4">
            <div class="grid grid-cols-[2fr_100px_100px_100px] gap-x-6 text-text/70 text-sm">
                <span>Jugadores en Steam</span>
                <span class="text-right">Ahora</span>
                <span class="text-right">24h</span>
                <span class="text-right">Máx. histórico</span>
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

        var pageSlugByAppid = {};
        (function() {
            var el = document.getElementById('steam-slug-map');
            if (el) try {
                var map = JSON.parse(el.textContent);
                Object.keys(map).forEach(function(slug) {
                    var entry = map[slug];
                    if (entry && entry.appid) pageSlugByAppid[entry.appid] = slug;
                });
            } catch (e) {}
        })();

        var pageImportNeeded = {};
        (function() {
            var el = document.getElementById('steam-import-needed');
            if (el) try {
                JSON.parse(el.textContent).forEach(function(appid) {
                    pageImportNeeded[appid] = true;
                });
            } catch (e) {}
        })();

        function gamePageUrl(appid) {
            var slug = pageSlugByAppid[appid];
            return slug ? '/' + slug : '/games/by-appid/' + appid;
        }

        function gameImportingAttr(appid) {
            if (!pageSlugByAppid[appid]) return ' data-importing';
            return pageImportNeeded[appid] ? ' data-importing' : '';
        }

        function fmtPlayers(n) {
            if (n >= 1000000) return (n / 1000000).toFixed(2).replace(/\.?0+$/, '') + 'M';
            if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.?0+$/, '') + 'K';
            return String(n);
        }

        function fmtGrowth(game) {
            if (game.is_new) return '<span class="text-neon-green font-semibold">Nuevo</span>';
            var pct = game.growth_pct || 0;
            var sign = pct >= 0 ? '+' : '';
            var cls = pct >= 0 ? 'text-neon-green' : 'text-red-400';
            return '<span class="' + cls + ' font-semibold">' + sign + pct.toFixed(1) + '%</span>';
        }

        function esc(s) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(s));
            return d.innerHTML;
        }

        var BATCH_SIZE = 20;
        var renderedCount = { 'most-played-full': 20, 'trending-full': 20 };
        var scrollObserver = null;

        function buildSparkline(history) {
            if (!history || history.length === 0) return '<span class="text-xs text-muted">No data</span>';
            var values = history.map(function(p) { return p.players || 0; });
            var min = Math.min.apply(null, values);
            var max = Math.max.apply(null, values);
            var range = max - min;
            if (range === 0) { range = 1; min = min - 1; }
            var svgParts = ['<svg width="100" height="30" viewBox="0 0 100 30">'];
            var points = [];
            var dots = [];
            var count = values.length;
            values.forEach(function(v, i) {
                var x = count > 1 ? (i / (count - 1)) * 100 : 50;
                var y = 30 - ((v - min) / range) * 28;
                var rx = Math.round(x * 10) / 10;
                var ry = Math.round(y * 10) / 10;
                points.push(rx + ',' + ry);
                var ts = (history[i] && history[i].timestamp) || 0;
                var pl = (history[i] && history[i].players) || v;
                dots.push('<circle cx="' + rx + '" cy="' + ry + '" r="6" fill="transparent" class="sparkline-dot" data-ts="' + ts + '" data-players="' + pl + '"/>');
            });
            svgParts.push('<polyline points="' + points.join(' ') + '" fill="none" stroke="#39ff14" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>');
            svgParts.push.apply(svgParts, dots);
            svgParts.push('</svg>');
            return svgParts.join('');
        }

        function renderGameRow(game, tabId) {
            if (!game.capsule_image) game.capsule_image = '';
            var url = gamePageUrl(game.appid);
            var importingAttr = gameImportingAttr(game.appid);
            if (tabId === 'most-played-full') {
                return '<div class="grid grid-cols-[160px_1fr_100px_100px_100px] gap-x-6 items-center py-2">'
                    + '<div class="relative flex items-center justify-center">'
                    + '<span class="absolute -left-3 text-neon-cyan text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75">' + game.rank + '</span>'
                    + '<button type="button" class="steam-fav-page absolute -right-3 text-xl text-muted hover:text-yellow-400 bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0" data-appid="' + game.appid + '" data-name="' + esc(game.name) + '" data-capsule="' + game.capsule_image + '" data-current="' + game.current_players + '" data-peak="' + game.peak_players + '">☆</button>'
                    + '<a href="' + url + '" class="block"' + importingAttr + '><img src="' + game.capsule_image + '" alt="' + esc(game.name) + '" class="aspect-8/3 object-cover rounded" loading="lazy"></a>'
                    + '</div>'
                    + '<a href="' + url + '" class="text-text text-base line-clamp-2 hover:underline"' + importingAttr + '>' + esc(game.name) + '</a>'
                    + '<span class="text-text text-base text-right">' + fmtPlayers(game.current_players) + '</span>'
                    + '<span class="text-muted text-base text-right">' + fmtPlayers(game.peak_players) + '</span>'
                    + '<span class="text-muted text-base text-right">' + fmtPlayers(game.all_time_peak || 0) + '</span>'
                    + '</div>';
            }
            if (tabId === 'trending-full') {
                return '<div class="grid grid-cols-[160px_1fr_60px_160px_70px_70px] gap-x-6 items-center py-2">'
                    + '<div class="relative flex items-center justify-center">'
                    + '<span class="absolute -left-2 text-neon-green text-sm text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75">' + game.rank + '</span>'
                    + '<button type="button" class="steam-fav-page absolute -right-3 text-xl text-muted hover:text-yellow-400 bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0" data-appid="' + game.appid + '" data-name="' + esc(game.name) + '" data-capsule="' + game.capsule_image + '" data-current="' + game.current_players + '" data-peak="0">☆</button>'
                    + '<a href="' + url + '" class="block"' + importingAttr + '><img src="' + game.capsule_image + '" alt="' + esc(game.name) + '" class="aspect-8/3 object-cover rounded" loading="lazy"></a>'
                    + '</div>'
                    + '<a href="' + url + '" class="text-text text-base line-clamp-2 hover:underline"' + importingAttr + '>' + esc(game.name) + '</a>'
                    + '<span class="text-center text-sm whitespace-nowrap">' + fmtGrowth(game) + '</span>'
                    + '<div class="flex ml-auto items-center">' + buildSparkline(game.history || []) + '</div>'
                    + '<span class="text-text text-base text-right">' + fmtPlayers(game.current_players) + '</span>'
                    + '<span class="text-muted text-base text-right">' + fmtPlayers(game.all_time_peak || 0) + '</span>'
                    + '</div>';
            }
            return '';
        }

        function loadMoreRows() {
            var visibleTab = document.querySelector('.steam-page-tab-content:not(.hidden)');
            if (!visibleTab) return;
            var tabId = visibleTab.id;
            if (tabId === 'favorites-full') return;

            var dataId = tabId === 'trending-full' ? 'steam-trending-data' : 'steam-page-data';
            var dataEl = document.getElementById(dataId);
            if (!dataEl) return;
            var allGames;
            try { allGames = JSON.parse(dataEl.textContent); } catch(e) { return; }

            var start = renderedCount[tabId];
            var end = Math.min(start + BATCH_SIZE, allGames.length);
            var batch = allGames.slice(start, end);
            if (batch.length === 0) return;

            var html = '';
            batch.forEach(function(game) {
                html += renderGameRow(game, tabId);
            });

            var sentinel = visibleTab.querySelector('[data-infinite-sentinel]');
            if (sentinel) {
                sentinel.insertAdjacentHTML('beforebegin', html);
            }

            renderedCount[tabId] = end;
            updatePageStars();

            if (end < allGames.length) {
                observeSentinel();
            } else if (sentinel) {
                sentinel.remove();
            }
        }

        function observeSentinel() {
            if (scrollObserver) scrollObserver.disconnect();
            var visibleTab = document.querySelector('.steam-page-tab-content:not(.hidden)');
            if (!visibleTab) return;
            var tabId = visibleTab.id;
            if (tabId === 'favorites-full' || renderedCount[tabId] >= 100) return;
            var sentinel = visibleTab.querySelector('[data-infinite-sentinel]');
            if (!sentinel) return;
            scrollObserver = new IntersectionObserver(function(entries) {
                if (entries[0].isIntersecting) {
                    scrollObserver.disconnect();
                    loadMoreRows();
                }
            }, { rootMargin: '400px' });
            scrollObserver.observe(sentinel);
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
                        capsule_image: mp ? mp.capsule_image : '',
                        current_players: map.current_players || (mp ? mp.current_players : 0),
                        peak_players: map.peak_players || (mp ? mp.peak_players : 0)
                    };
                });
            } catch (e) {}

            // Build all_time_peak map from both datasets
            var peakMap = {};
            games.forEach(function(g) {
                if (g.all_time_peak) peakMap[g.appid] = g.all_time_peak;
            });
            try {
                var trendEl = document.getElementById('steam-trending-data');
                if (trendEl) {
                    JSON.parse(trendEl.textContent).forEach(function(g) {
                        if (g.all_time_peak) peakMap[g.appid] = g.all_time_peak;
                    });
                }
            } catch(e) {}

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
                var mp = gamesByAppid[appid];
                var capUrl = g.capsule_image || (mp && mp.capsule_image) || (ls && ls.capsule_image) || 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/' + appid + '/capsule_231x87.jpg';
                // Discard legacy centralized paths and site page covers
                if (capUrl.indexOf('/assets/steam-capsules/') === 0) capUrl = '';
                if (capUrl.indexOf('/media/pages/games/') === 0) capUrl = 'https://shared.akamai.steamstatic.com/store_item_assets/steam/apps/' + appid + '/capsule_231x87.jpg';
                rows.push({
                    appid: appid,
                    name: g.name || 'Unknown',
                    capsule_image: capUrl,
                    current_players: g.current_players || 0,
                    peak_players: g.peak_players || 0,
                    all_time_peak: peakMap[appid] || g.all_time_peak || 0,
                    rank: (gamesByAppid[appid] && gamesByAppid[appid].rank) || null
                });
            });

            rows.sort(function(a, b) {
                if (a.rank && b.rank) return a.rank - b.rank;
                if (a.rank) return -1;
                if (b.rank) return 1;
                return b.current_players - a.current_players;
            });

            var html = '';
            rows.forEach(function(g, i) {
                var url = gamePageUrl(g.appid);
                var importingAttr = gameImportingAttr(g.appid);
                var imgSrc = g.capsule_image || '';
                var imgHtml = imgSrc ?
                    '<a href="' + url + '" class="block"' + importingAttr + '><img src="' + imgSrc + '" alt="' + esc(g.name || '') + '" class="aspect-8/3 object-cover rounded" loading="lazy"></a>' :
                    '<div class="aspect-8/3 bg-surface-alt rounded flex items-center justify-center text-muted text-xl">--</div>';
                var cur = g.current_players;
                var peak = g.peak_players;
                html += '<div class="grid grid-cols-[160px_1fr_100px_100px_100px] gap-x-6 items-center py-2">' +
                    '<div class="relative flex items-center justify-center">' +
                    '<span class="absolute -left-3 text-neon-magenta text-base text-center bg-surface/70 w-6 h-6 rounded-full z-10 leading-5.75 cursor-help"' + (g.rank ? '' : ' title="Fuera del top 100"') + '>' + (g.rank || '-') + '</span>' +
                    '<button type="button" class="steam-fav-page-remove absolute -right-3 text-sm text-muted hover:text-white bg-surface/70 w-6 h-6 rounded-full transition z-10 leading-0" data-appid="' + g.appid + '">✕</button>' +
                    imgHtml +
                    '</div>' +
                    '<a href="' + url + '" class="text-text text-base line-clamp-2 hover:underline"' + importingAttr + '>' + esc(g.name) + '</a>' +
                    '<span class="text-text text-base text-right">' + fmtPlayers(cur) + '</span>' +
                    '<span class="text-muted text-base text-right">' + fmtPlayers(peak) + '</span>' +
                    '<span class="text-muted text-base text-right">' + fmtPlayers(g.all_time_peak || 0) + '</span>' +
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
                observeSentinel();
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
        observeSentinel();
    })();
</script>

<div id="sparkline-tooltip" class="sparkline-tooltip">
    <div class="sparkline-tooltip-inner">
        <span class="sparkline-tooltip-label"></span>
    </div>
</div>

<style>
.sparkline-tooltip {
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    opacity: 0;
    transform: translate(-50%, calc(-100% - 8px));
    transition: opacity 0.12s ease;
}
.sparkline-tooltip.visible {
    opacity: 1;
}
.sparkline-tooltip-inner {
    background: rgba(15, 15, 20, 0.96);
    border: 1px solid #39ff14;
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 11px;
    line-height: 1.3;
    color: #e0e0e0;
    white-space: nowrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.5);
}
.sparkline-tooltip-label {
    font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', monospace;
}
</style>

<script>
(function() {
    if (window.__sparklineTooltipsInited) return;
    window.__sparklineTooltipsInited = true;

    var DAYS = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

    function fmtPlayersTooltip(n) {
        if (n >= 1000000) return (n / 1000000).toFixed(2).replace(/\.?0+$/, '') + 'M';
        if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.?0+$/, '') + 'K';
        return String(n);
    }

    var tip = document.getElementById('sparkline-tooltip');
    var label = tip ? tip.querySelector('.sparkline-tooltip-label') : null;
    if (!tip || !label) return;

    var currentDot = null;

    function show(e, dot) {
        var ts = parseInt(dot.getAttribute('data-ts'), 10);
        var players = parseInt(dot.getAttribute('data-players'), 10);
        var d = new Date(ts * 1000);
        var day = DAYS[d.getDay()];
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        label.textContent = day + ' ' + h + ':' + m + ' — ' + fmtPlayersTooltip(players) + ' jugadores';
        currentDot = dot;
        tip.classList.add('visible');
    }

    function move(e) {
        tip.style.left = e.clientX + 'px';
        tip.style.top = e.clientY + 'px';
    }

    function hide() {
        tip.classList.remove('visible');
        currentDot = null;
    }

    document.addEventListener('mouseover', function(e) {
        var dot = e.target.closest('.sparkline-dot');
        if (!dot) return;
        show(e, dot);
    }, true);

    document.addEventListener('mousemove', function(e) {
        if (!currentDot) return;
        move(e);
    }, true);

    document.addEventListener('mouseout', function(e) {
        var dot = e.target.closest('.sparkline-dot');
        if (dot && !dot.contains(e.relatedTarget)) {
            hide();
        }
    }, true);
})();
</script>

<?php snippet('footer') ?>
