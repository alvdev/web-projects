<?php

return function ($site) {
    $games = $site->find('games')->children()->listed()->sortBy('title', 'asc');

    $genreKeys = [
        'accion', 'aventura', 'rpg', 'shooter', 'estrategia',
        'simulacion', 'deportes', 'terror', 'puzzle',
        'supervivencia', 'mundo-abierto', 'multijugador',
    ];

    $genreGames = [];
    foreach ($genreKeys as $genre) {
        $filtered = $games->filterBy('genres', $genre, ',')->limit(2);
        if ($filtered->count() > 0) {
            $genreGames[$genre] = $filtered;
        }
    }

    return [
        'genreGames' => $genreGames,
    ];
};
