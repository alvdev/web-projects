<?php

return function ($site) {
    $query = get('q');
    $results = null;

    if ($query && strlen(trim($query)) > 0) {
        try {
            $results = \arnoson\KirbyLoupe::search(query: trim($query), paginate: 20);
        } catch (\Exception $e) {
            $results = null;
        }

        if (!$results || $results->count() === 0) {
            $results = $site->search(trim($query), 'title|summary|text');
            $results = $results->paginate(20);
        }
    }

    $results = $results instanceof \Kirby\Cms\Pages ? $results : new \Kirby\Cms\Pages([]);

    return [
        'query' => $query,
        'results' => $results,
        'pagination' => $results->pagination(),
    ];
};
