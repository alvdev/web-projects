<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledMarkdownFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/pages/legal/default.md',
    'modified' => 1668898217,
    'size' => 178,
    'data' => [
        'header' => [
            'content' => [
                'items' => '@self.children'
            ],
            'taxonomy' => [
                'category' => [
                    0 => 'legalcat'
                ],
                'tag' => [
                    0 => 'legal'
                ]
            ],
            'admin' => [
                'children_display_order' => 'default'
            ],
            'title' => 'Legal links'
        ],
        'frontmatter' => 'content:
    items: \'@self.children\'
taxonomy:
    category:
        - legalcat
    tag:
        - legal
admin:
    children_display_order: default
title: \'Legal links\'',
        'markdown' => ''
    ]
];
