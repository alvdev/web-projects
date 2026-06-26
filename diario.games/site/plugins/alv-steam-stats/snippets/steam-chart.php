<?php if (!isset($data) || !$data): return; endif; ?>
<div class="mt-8 pt-8 border-t border-border" id="steam-chart-section">
    <h2 class="text-lg font-bold text-neon-green mb-6">Jugadores en Steam</h2>

    <div class="bg-surface border border-border rounded-xl p-6">
        <!-- Stats summary -->
        <div class="grid grid-cols-3 gap-4 mb-6 text-center">
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
        </div>

        <!-- Time range tabs -->
        <div class="flex flex-wrap gap-1 mb-4 border-b border-border pb-2">
            <?php $tabs = ['48h', '1w', '1m', '3m', '6m', '1y', 'max']; ?>
            <?php foreach ($tabs as $i => $tab): ?>
                <button type="button"
                    class="steam-range-tab px-3 py-1 text-xs font-semibold rounded transition
                    <?= $i === 0 ? 'bg-neon-cyan/20 text-neon-cyan' : 'text-muted hover:text-text' ?>"
                    data-range="<?= $tab ?>">
                    <?= strtoupper($tab) ?>
                </button>
            <?php endforeach ?>
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
