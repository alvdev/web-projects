<?php

return function ($page, $cat, $tag) {
    $articles = $page->children()->listed()->flip();

    if ($tag) $articles = $articles->filterBy('tags', $tag);
    if ($cat) $articles = $articles->filterBy('category', $cat);

    $articles = $articles->paginate(2);
    $pagination = $articles->pagination();

    return /* [
        'articles' => $articles,
        'tag' => $tag,
        'pagination' => $pagination,
    ]; */

        // Compacted version
        compact('articles', 'cat', 'tag', 'pagination');
};
