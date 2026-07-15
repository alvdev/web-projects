<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= vite()->js('assets/src/js/app.js') ?>
    <?= vite()->js('assets/src/js/steam-chart.js') ?>
    <?= vite()->css('assets/src/js/app.js') ?>
    <?= vite()->css('assets/src/js/steam-chart.js') ?>
    <?php snippet('meta-kit/seo') ?>
</head>
<body class="debug-screens bg-dark text-text min-h-screen">
<div class="bg"></div>

<header class="border-b border-border bg-surface/50 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-3 shrink-0">
            <img src="<?= url('/assets/images/diario-games-logo.webp') ?>" alt="Diario.Games" class="h-10 w-auto">
        </a>

        <div id="steam-header-search" class="relative shrink-0">
            <input type="text"
                placeholder="Buscar juegos..."
                class="w-48 lg:w-64 px-3 py-1.5 text-sm bg-surface border border-border rounded-lg text-text placeholder-muted focus:outline-none focus:border-neon-cyan transition">
            <div class="steam-search-results hidden absolute top-full right-0 mt-1 w-72 bg-surface border border-border rounded-lg shadow-xl max-h-96 overflow-y-auto z-50"></div>
        </div>

        <?php snippet('genre-nav') ?>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6">
