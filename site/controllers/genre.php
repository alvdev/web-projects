<?php

return function ($site) {
    $genreSlug = param('genre');
    $allGames = $site->find('games')->children()->listed();
    $games = $allGames->filterBy('genres', $genreSlug, ',')->sortBy('title', 'asc');

    $labels = [
        'accion' => 'Acción', 'aventura' => 'Aventura',
        'rpg' => 'RPG', 'shooter' => 'Shooter',
        'estrategia' => 'Estrategia', 'simulacion' => 'Simulación',
        'deportes' => 'Deportes y Carreras', 'terror' => 'Terror',
        'puzzle' => 'Puzzle', 'supervivencia' => 'Supervivencia',
        'mundo-abierto' => 'Mundo abierto', 'multijugador' => 'Multijugador',
    ];

    return [
        'genreSlug' => $genreSlug,
        'genreLabel' => $labels[$genreSlug] ?? $genreSlug,
        'games' => $games,
    ];
};
