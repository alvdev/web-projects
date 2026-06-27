<?php

return function ($site) {
    $query = get('q');
    $results = [];

    if ($query && strlen(trim($query)) > 0) {
        $q = strtolower(trim($query));

        $games = site()->index()->filterBy('intendedTemplate', 'game');
        foreach ($games as $game) {
            if (count($results) >= 20) break;
            if ($game->content()->get('Screenshots')->isEmpty() && $game->content()->get('Videos')->isEmpty()) continue;
            $title = $game->title()->value();
            if (!str_contains(strtolower($title), $q)) continue;
            $releaseDate = $game->content()->get('ReleaseDate')->value();
            $year = preg_match('/^\d{4}/', $releaseDate, $m) ? $m[0] : '';
            $results[] = [
                'slug' => $game->slug(),
                'name' => $title,
                'platforms' => \DiarioGames\IGDB\normalizePlatformNames($game->content()->get('Platforms')->value()),
                'year' => $year,
                'url' => $game->url(),
                'summary' => $game->summary()->excerpt(120),
                'exists' => true,
            ];
        }
    }

    return [
        'query' => $query,
        'results' => $results,
        'pagination' => null,
    ];
};
