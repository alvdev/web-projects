<?php

return function ($site) {
    $query = get('q');
    $results = [];

    if ($query && strlen(trim($query)) > 0) {
        try {
            $results = loupe(trim($query))->paginate(20);
        } catch (\Exception $e) {
            $results = $site->search(trim($query), 'title|summary|text');
            $results = $results->paginate(20);
        }
    }

    return [
        'query' => $query,
        'results' => $results ?? [],
        'pagination' => $results->pagination() ?? null,
    ];
};
