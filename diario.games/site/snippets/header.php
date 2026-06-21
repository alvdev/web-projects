<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?= vite()->js('assets/src/js/app.js') ?>
    <?= vite()->css('assets/src/js/app.js') ?>
    <?php snippet('meta-kit/seo') ?>
</head>
<body class="debug-screens grain bg-bg text-text min-h-screen">

<header class="border-b-2 border-pink bg-bg sticky top-0 z-50" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-1 shrink-0 neon-glow-pink">
            <span class="font-graffiti text-2xl md:text-3xl tracking-widest text-pink uppercase">Diario</span><span class="font-graffiti text-2xl md:text-3xl tracking-widest text-yellow uppercase">.Games</span>
        </a>

        <div class="hidden md:block flex-1 min-w-0">
            <?php snippet('genre-nav') ?>
        </div>

        <a href="<?= url('search') ?>" class="text-pink hover:text-yellow transition shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </a>

        <button type="button" class="md:hidden text-pink" aria-label="Abrir menú" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>
    <div id="mobile-menu" class="hidden md:hidden border-t border-pink/40 bg-bg">
        <?php snippet('genre-nav') ?>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 relative" style="z-index: 2;">
