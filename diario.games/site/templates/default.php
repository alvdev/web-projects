<?php snippet('header') ?>

<div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
    <p class="font-graffiti text-7xl md:text-9xl text-pink uppercase neon-glow-pink leading-none">404</p>
    <?php snippet('script-accent', ['text' => 'no encontrado', 'color' => 'yellow', 'size' => 'lg']) ?>
    <p class="font-body text-muted mt-4 max-w-md">La página que buscas no existe o se ha movido. Vuelve al inicio para seguir explorando.</p>
    <a href="<?= url('/') ?>" class="mt-6 inline-block border-2 border-pink text-pink font-display text-base uppercase tracking-widest px-8 py-3 hover:bg-pink hover:text-bg transition neon-glow-pink">← Volver al inicio</a>
</div>

<?php snippet('footer') ?>
