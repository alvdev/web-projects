</main>

<footer class="border-t border-border mt-12 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-wrap justify-between items-center gap-4 text-sm text-muted">
            <div>
                &copy; <?= date('Y') ?> Diario.Games. All rights reserved.
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-neon-cyan transition">About</a>
                <a href="#" class="hover:text-neon-cyan transition">Contact</a>
                <a href="#" class="hover:text-neon-cyan transition">Privacy</a>
            </div>
        </div>
    </div>
</footer>

<script>
<?php
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
?>
(function(){
    var KEY = 'site-favorites-v1';
    var slugMap = <?= json_encode($steamSlugMap) ?>;

    function get(){ try { return JSON.parse(localStorage.getItem(KEY) || '{}'); } catch(e){ return {}; } }
    function set(v){ try { localStorage.setItem(KEY, JSON.stringify(v)); } catch(e){} }

    function updateStars(){
        var favs = get();
        document.querySelectorAll('.site-fav').forEach(function(btn){
            var slug = btn.getAttribute('data-slug');
            if (!slugMap[slug]) {
                btn.style.display = 'none';
                return;
            }
            btn.style.display = '';
            var isFav = !!favs[slug];
            btn.textContent = isFav ? '\u2605' : '\u2606';
            btn.classList.toggle('text-yellow-400', isFav);
            btn.classList.toggle('text-muted', !isFav);
        });
    }

    document.addEventListener('click', function(e){
        var btn = e.target.closest('.site-fav');
        if (!btn) return;
        var slug = btn.getAttribute('data-slug');
        if (!slug || !slugMap[slug]) return;
        e.preventDefault();
        e.stopPropagation();
        var favs = get();
        if (favs[slug]) {
            delete favs[slug];
        } else {
            favs[slug] = {
                title: btn.getAttribute('data-title') || '',
                cover: btn.getAttribute('data-cover') || ''
            };
        }
        set(favs);
        updateStars();
    });

    updateStars();
})();
</script>
</body>
</html>
