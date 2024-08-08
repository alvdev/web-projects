<?php

return function ($page) {
    return [
        'tags' => $page->tags()->split(','),
        'categories' => $page->blueprint()->field('category')['options'],
    ];
};
    