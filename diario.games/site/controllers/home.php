<?php

return function ($site) {
    $games = $site->find('games')->children()->children()->children()->filterBy('intendedTemplate', 'game')->sortBy('title', 'asc');

    $latestPosts = $games->children()
        ->filter(fn($p) => in_array($p->intendedTemplate()->name(), ['news', 'guide'], true))
        ->sortBy('date', 'desc')
        ->limit(5);

    return [
        'latestPosts' => $latestPosts,
    ];
};
