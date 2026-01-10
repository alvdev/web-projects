<?php

$tab  = get('tab');
$page_num = get('page', 1);
$limit = 24;

// Cache identification
$cacheId = 'gallery-' . $page->id() . '-' . $tab . '-p' . $page_num;
$cache = $kirby->cache('pages');
$useCache = $kirby->option('debug') === false;

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

// Cache the response
$cache->set($cacheId, $response);

echo json_encode($response);
