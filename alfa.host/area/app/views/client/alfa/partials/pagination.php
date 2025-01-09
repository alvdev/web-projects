<?php
////////////////////////////////////////////////////////////////////////////////
// Pagination replica from config
////////////////////////////////////////////////////////////////////////////////
// Number of results to display per page
Configure::set('Blesta.results_per_page', 10);
// Set pagination settings
Configure::set('Blesta.pagination', [
    'show' => 'if_needed',
    'total_results' => 0,
    'pages_to_show' => 5,
    'results_per_page' => Configure::get('Blesta.results_per_page'),
    'uri' => WEBDIR,
    'uri_labels' => [
        'page' => 'p',
        'per_page' => 'pp'
    ],
    'navigation' => [
        'current' => [
            'link' => true,
            'attributes' => ['class' => 'active']
        ],
        'first' => [
            'show' => 'always'
        ],
        'prev' => [
            'show' => 'always'
        ],
        'next' => [
            'show' => 'always'
        ],
        'last' => [
            'show' => 'always',
            'attributes' => ['class' => 'next']
        ]
    ],
    'params' => []
]);
// Set pagination settings
Configure::set('Blesta.pagination_client', [
    'show' => 'if_needed',
    'total_results' => 0,
    'pages_to_show' => 5,
    'results_per_page' => Configure::get('Blesta.results_per_page'),
    'uri' => WEBDIR,
    'uri_labels' => [
        'page' => 'p',
        'per_page' => 'pp'
    ],
    'navigation' => [
        'surround' => [
            'attributes' => [
                'class' => 'mt-2 flex gap-2 w-fit ml-auto mt-6'
            ]
        ],
        'current' => [
            'link' => true,
            'attributes' => ['class' => 'page-item active']
        ],
        'first' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item']
        ],
        'prev' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item mr-6']
        ],
        'next' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item ml-6']
        ],
        'last' => [
            'show' => 'always',
            'attributes' => ['class' => 'page-item next']
        ],
        'numerical' => [
            'attributes' => ['class' => 'page-item']
        ]
    ],
    'params' => []
]);
// Configurations to override on pagination to help enabled AJAX
$linkStyles = 'gap-2 mt-2 border px-4 py-1 rounded-md font-semibold uppercase text-sm';
Configure::set('Blesta.pagination_ajax', [
    'merge_get' => false,
    'navigation' => [
        'current' => [
            'link_attributes' => ['class' => 'page-link ajax bg-slate-500 w-6 h-6 text-center block text-white rounded-full']
        ],
        'first' => [
            'link_attributes' => ['class' => 'page-link ajax ' . $linkStyles]
        ],
        'prev' => [
            'link_attributes' => ['class' => 'page-link ajax ' . $linkStyles]
        ],
        'next' => [
            'link_attributes' => ['class' => 'page-link ajax ' . $linkStyles]
        ],
        'last' => [
            'link_attributes' => ['class' => 'page-link ajax ' . $linkStyles]
        ],
        'numerical' => [
            :wq
            'link_attributes' => ['class' => 'page-link ajax hover:bg-slate-200 w-6 h-6 text-center block hover:text-black rounded-full block pb-1']
        ]
    ]
]);
