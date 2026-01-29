<?php

$tab  = get('tab');
$page_num = get('page', 1);
$limit = 24;

// Cache identification
$cacheId = 'gallery-' . $page->id() . '-' . $tab . '-p' . $page_num;
// Use a dedicated cache if possible, otherwise fallback to pages cache
$cache = $kirby->cache('gallery') ?? $kirby->cache('pages');
$useCache = true; // Always cache the JSON fragments in production

if ($useCache && $data = $cache->get($cacheId)) {
    echo json_encode($data);
    exit;
}

$html = '';
$hasMore = false;

if (($tab === 'pictures' || $tab === 'people' || $tab === 'book') && $section = $page->find($tab)) {
    $images = $section->images();
    if ($images->count() > 0) {
        $pagedImages = $images->paginate($limit, ['page' => $page_num]);
        $html = '';
        foreach ($pagedImages as $image) {
            $snippetName = ($tab === 'book') ? 'gallery-book' : 'gallery-item';
            $html .= snippet($snippetName, ['image' => $image], true);
        }
        $hasMore = $pagedImages->pagination()->hasNextPage();
    }
} elseif ($tab === 'videos') {
    $html = snippet('gallery-videos', ['page' => $page], true);
    $hasMore = false;
}

$response = [
  'html' => $html,
  'more' => $hasMore
];

// Cache the response indefinitely (or until manual clear)
// Only cache if we actually have content and it's not a "Procesando" state
if ($useCache && !empty($html) && !Str::contains($html, 'Procesando')) {
    $cache->set($cacheId, $response, 525600);
}

echo json_encode($response);
