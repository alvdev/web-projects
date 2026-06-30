<?php snippet('header') ?>

<?php
$stats = site()->steamStats();
$mostPlayed = $stats->getMostPlayed(100);
$trending = $stats->getTrending(100);

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
} catch (\Throwable $e) {}

function pageFormatPlayers(int $count): string {
    if ($count >= 1000000) return round($count / 1000000, 2) . 'M';
    if ($count >= 1000) return round($count / 1000, 1) . 'K';
    return (string) $count;
}

function pageSparkline(array $history): string {
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
    (function(){
        var ts = <?= $lastRun ?>;
        var el = document.getElementById('minutes-ago');
        if (el) {
            el.textContent = Math.round((Date.now() / 1000 - ts) / 60);
            setInterval(function(){
                el.textContent = Math.round((Date.now() / 1000 - ts) / 60);
            }, 60000);
        }
    })();
    </script>
    <?php endif ?>
</div>

<!-- Tabs -->
<div class="flex gap-0 mb-6 border-b border-border">
    <button type="button" class="steam-page-tab active px-4 py-2 text-sm font-bold uppercase tracking-wider text-neon-cyan border-b-2 border-neon-cyan" data-tab="most-played-full">
        Most Played
    </button>
    <button type="button" class="steam-page-tab px-4 py-2 text-sm font-bold uppercase tracking-wider text-muted border-b-2 border-transparent" data-tab="trending-full">
        Trending
    </button>
    <button type="button" class="steam-page-tab px-4 py-2 text-sm font-bold uppercase tracking-wider text-muted border-b-2 border-transparent" data-tab="favorites-full">
        Favorites
    </button>
</div>

<script type="application/json" id="steam-page-data">
<?= json_encode($mostPlayed) ?>
</script>
<script type="application/json" id="steam-slug-map">
<?= json_encode($steamSlugMap) ?>
</script>

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

<!-- Favorites Tab -->
<div class="steam-page-tab-content hidden" id="favorites-full">
    <div class="bg-surface border border-border rounded-xl overflow-hidden">
        <div class="grid grid-cols-[50px_120px_1fr_120px_120px] gap-4 p-4 border-b border-border bg-surface-alt">
            <span class="text-xs font-bold text-muted uppercase">#</span>
            <span></span>
            <span class="text-xs font-bold text-muted uppercase">Title</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Current Players</span>
            <span class="text-xs font-bold text-muted uppercase text-right">Peak Players</span>
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
        if (n >= 1000000) return (n / 1000000).toFixed(2).replace(/\.?0+$/, '') + 'M';
        if (n >= 1000) return Math.round(n / 1000) + 'K';
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
        try { games = JSON.parse(data.textContent); } catch(e) { games = []; }
        var gamesByAppid = {};
        games.forEach(function(g) { gamesByAppid[g.appid] = g; });

        var slugMap = {};
        (function(){
            var el = document.getElementById('steam-slug-map');
            if (el) try { slugMap = JSON.parse(el.textContent); } catch(e){}
        })();

        var favs;
        try { favs = JSON.parse(localStorage.getItem(FAV_KEY) || '{}'); } catch(e) { favs = {}; }

        // Merge site favorites that have a Steam appid
        try {
            var siteFavs = JSON.parse(localStorage.getItem('site-favorites-v1') || '{}');
            Object.keys(siteFavs).forEach(function(slug){
                var map = slugMap[slug];
                if (!map) return;
                var appid = String(map.appid);
                if (favs[appid]) return;
                var sf = siteFavs[slug];
                var mp = gamesByAppid[appid];
                favs[appid] = {
                    name: mp ? mp.name : (map.name || sf.title || 'Unknown'),
                    capsule_image: mp ? mp.capsule_image : (sf.cover || ''),
                    current_players: mp ? mp.current_players : (map.current_players || 0),
                    peak_players: mp ? mp.peak_players : (map.peak_players || 0)
                };
            });
        } catch(e) {}

        var appids = Object.keys(favs);

        if (appids.length === 0) {
            if (empty) empty.textContent = 'No favorite games yet.';
            return;
        }

        var rows = [];
        appids.forEach(function(appid) {
            var g = gamesByAppid[appid] || favs[appid];
            if (!g) return;
            rows.push({
                appid: appid,
                name: g.name || 'Unknown',
                capsule_image: g.capsule_image || '',
                current_players: g.current_players || 0,
                peak_players: g.peak_players || 0
            });
        });

        rows.sort(function(a, b) { return b.current_players - a.current_players; });

        var html = '';
        rows.forEach(function(g, i) {
            var imgSrc = g.capsule_image || '';
            var imgHtml = imgSrc
                ? '<img src="' + imgSrc + '" alt="" class="w-[120px] h-[45px] object-cover rounded" loading="lazy">'
                : '<div class="w-[120px] h-[45px] bg-surface-alt rounded flex items-center justify-center text-muted text-xs">--</div>';
            var cur = g.current_players;
            var peak = g.peak_players;
            html += '<div class="grid grid-cols-[50px_120px_1fr_120px_120px] gap-4 p-4 border-b border-border/30 hover:bg-surface-alt/50 transition">'
                + '<div class="flex items-center gap-2">'
                + '<span class="text-lg font-bold text-neon-magenta">' + (i + 1) + '</span>'
                + '<button type="button" class="steam-fav-page-remove text-xs text-muted hover:text-neon-magenta transition" data-appid="' + g.appid + '">✕</button>'
                + '</div>'
                + imgHtml
                + '<div class="flex items-center"><a href="https://store.steampowered.com/app/' + g.appid + '/" target="_blank" rel="noopener" class="text-base font-semibold text-text hover:text-neon-magenta transition">' + esc(g.name) + '</a></div>'
                + '<span class="text-sm text-text text-right font-mono">' + (cur ? fmtPlayers(cur) : '<span class="text-muted">&mdash;</span>') + '</span>'
                + '<span class="text-sm text-muted text-right font-mono">' + (peak ? fmtPlayers(peak) : '<span class="text-muted">&mdash;</span>') + '</span>'
                + '</div>';
        });

        list.innerHTML = html;
        if (empty) empty.style.display = 'none';
    }

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
        });
    });

    // Remove favorite from page tab
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.steam-fav-page-remove');
        if (!btn) return;
        var appid = btn.getAttribute('data-appid');
        var favs;
        try { favs = JSON.parse(localStorage.getItem(FAV_KEY) || '{}'); } catch(e) { favs = {}; }
        delete favs[appid];
        try { localStorage.setItem(FAV_KEY, JSON.stringify(favs)); } catch(e) {}
        // Also remove from site-favorites-v1
        try {
            var slugMap = JSON.parse((document.getElementById('steam-slug-map') || {}).textContent || '{}');
            var sf = JSON.parse(localStorage.getItem('site-favorites-v1') || '{}');
            Object.keys(slugMap).forEach(function(slug){
                if (String(slugMap[slug].appid) === appid) {
                    delete sf[slug];
                }
            });
            localStorage.setItem('site-favorites-v1', JSON.stringify(sf));
        } catch(e) {}
        renderPageFavorites();
        e.stopPropagation();
    });

    renderPageFavorites();
})();
</script>

<?php snippet('footer') ?>
