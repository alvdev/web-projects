<?php

return function ($site) {
    $games = $site->find('games')->children()->sortBy('title', 'asc');

    $allGenres = [];
    foreach ($games as $game) {
        foreach ($game->genreList() as $genre) {
            $genre = trim($genre);
            if ($genre) $allGenres[$genre] = true;
        }
    }

    $genreGames = [];
    foreach (array_keys($allGenres) as $genre) {
        $filtered = $games->filter(function ($g) use ($genre) {
            return in_array($genre, $g->genreList());
        })->limit(2);

        if ($filtered->count() > 0) {
            $genreGames[$genre] = $filtered;
        }
    }

    return [
        'genreGames' => $genreGames,
    ];
};
