<?php

return function ($site) {
    $games = $site->find('games')->children()->sortBy('title', 'asc');

    $allGenres = [];
    foreach ($games as $game) {
        $gl = $game->genreList();
        if (!is_array($gl)) continue;
        foreach ($gl as $genre) {
            $genre = trim($genre);
            if ($genre) $allGenres[$genre] = true;
        }
    }

    $genreGames = [];
    foreach (array_keys($allGenres) as $genre) {
        $filtered = $games->filter(function ($g) use ($genre) {
            $gl = $g->genreList();
            return is_array($gl) && in_array($genre, $gl);
        })->limit(3);

        if ($filtered->count() > 0) {
            $genreGames[$genre] = $filtered;
        }
    }

    return [
        'genreGames' => $genreGames,
    ];
};
