<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledMarkdownFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/pages/02.models/list.md',
    'modified' => 1668972540,
    'size' => 329,
    'data' => [
        'header' => [
            'title' => 'Masajistas eróticas',
            'blog_url' => 'blog',
            'sitemap' => [
                'changefreq' => 'monthly',
                'priority' => 1.03
            ],
            'content' => [
                'items' => '@self.children',
                'order' => [
                    'by' => 'date',
                    'dir' => 'desc'
                ],
                'limit' => 6,
                'pagination' => true
            ],
            'feed' => [
                'description' => 'Sample Blog Description',
                'limit' => 10
            ],
            'pagination' => true,
            'slug' => 'masajistas-eroticas'
        ],
        'frontmatter' => 'title: \'Masajistas eróticas\'
blog_url: blog
sitemap:
    changefreq: monthly
    priority: 1.03
content:
    items: \'@self.children\'
    order:
        by: date
        dir: desc
    limit: 6
    pagination: true
feed:
    description: \'Sample Blog Description\'
    limit: 10
pagination: true
slug: masajistas-eroticas',
        'markdown' => ''
    ]
];
