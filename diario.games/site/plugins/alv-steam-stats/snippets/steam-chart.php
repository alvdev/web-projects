<?php if (!isset($data) || !$data): return; endif; ?>
<div class="mt-8 pt-8 border-t border-border" id="steam-chart-section">
    <h2 class="text-lg font-bold text-neon-green mb-6">Jugadores en Steam</h2>

    <div class="bg-surface border border-border rounded-xl p-6">
        <!-- Stats summary -->
        <?php
        $gameSlug = $data['game']['slug'] ?? '';
        $yearMonth = $data['game']['year_month'] ?? '';
        $capsuleLocal = $yearMonth ? dirname(__DIR__, 4) . '/content/games/' . $yearMonth . '/' . $gameSlug . '/steam-capsule.jpg' : null;
        $capsuleUrl = ($capsuleLocal && file_exists($capsuleLocal)) ? '/media/steam-capsule/' . $gameSlug . '.jpg' : null;
        ?>
        <div class="flex items-center gap-4 mb-6">
            <?php if ($capsuleUrl): ?>
                <img src="<?= $capsuleUrl ?>"
                     alt="<?= esc($data['game']['name'] ?? '') ?>"
                     class="h-[100px] w-auto rounded border border-border shrink-0"
                     loading="lazy">
            <?php endif ?>
            <div class="grid grid-cols-4 gap-4 text-center flex-1">
                <div>
                    <div class="text-xs uppercase tracking-wider text-muted">Ahora</div>
                    <div class="text-xl font-bold text-neon-cyan" id="steam-current">-</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-muted">Pico 24h</div>
                    <div class="text-xl font-bold text-neon-magenta" id="steam-peak-24h">-</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-muted">Pico 3 meses</div>
                    <div class="text-xl font-bold text-neon-green" id="steam-peak-3m">-</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider text-muted"><?= esc($data['peak_all_time_age'] ?? 'Max. historico') ?></div>
                    <div class="text-xl font-bold text-yellow-400" id="steam-peak-alltime">-</div>
                </div>
            </div>
        </div>

        <!-- Time range tabs -->
        <div class="flex flex-wrap gap-1 mb-4 w-fit mx-auto pb-2">
            <?php
            $availableTabs = $data['available_tabs'] ?? ['48h', '1w', '1m', '3m', '6m', '1y', 'max'];
            $first = true;
            foreach ($availableTabs as $tab):
                $upperTab = strtoupper($tab);
                $isMax = $tab === 'max';
            ?>
                <button type="button"
                    class="steam-range-tab px-3 py-1 text-xs font-semibold rounded transition
                    <?= $first ? 'bg-neon-cyan/20 text-neon-cyan' : 'text-muted hover:text-text' ?>"
                    data-range="<?= $tab ?>">
                    <?= $isMax ? 'MAX' : strtoupper($tab) ?>
                </button>
            <?php
                $first = false;
            endforeach;
            ?>
        </div>

        <!-- Chart canvas -->
        <div class="relative" style="height: 300px;">
            <canvas id="steam-chart-canvas"></canvas>
        </div>
    </div>
</div>

<script>
window.__STEAM_CHART_DATA = <?= json_encode($data) ?>;
</script>
