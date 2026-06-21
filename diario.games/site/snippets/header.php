<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= vite()->js('assets/src/js/app.js') ?>
    <?= vite()->css('assets/src/js/app.js') ?>
    <?php snippet('meta-kit/seo') ?>
</head>
<body class="debug-screens bg-dark text-text min-h-screen">

<header class="border-b border-border bg-surface/50 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-3 shrink-0">
            <img src="<?= url('logo.png') ?>" alt="Diario.Games" class="h-10 w-auto">
        </a>

        <?php snippet('genre-nav') ?>

        <a href="<?= url('search') ?>" class="text-neon-magenta hover:text-neon-cyan transition shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6">
