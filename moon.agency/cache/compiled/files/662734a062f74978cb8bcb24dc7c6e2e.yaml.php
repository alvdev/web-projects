<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/web-projects.test/config/plugins/admin.yaml',
    'modified' => 1669482958,
    'size' => 2398,
    'data' => [
        'enabled' => true,
        'route' => '/admin',
        'cache_enabled' => true,
        'theme' => 'grav',
        'logo_text' => NULL,
        'body_classes' => NULL,
        'content_padding' => true,
        'twofa_enabled' => true,
        'sidebar' => [
            'activate' => 'tab',
            'hover_delay' => 100,
            'size' => 'auto'
        ],
        'dashboard' => [
            'days_of_stats' => 30
        ],
        'widgets_display' => [
            'dashboard-maintenance' => 'true',
            'dashboard-statistics' => 'true',
            'dashboard-notifications' => 'true',
            'dashboard-feed' => 'true',
            'dashboard-pages' => 'true'
        ],
        'pages' => [
            'show_parents' => 'both',
            'show_modular' => false,
            'parents_levels' => NULL
        ],
        'session' => [
            'timeout' => 1800
        ],
        'edit_mode' => 'normal',
        'frontend_preview_target' => 'inline',
        'show_github_msg' => false,
        'admin_icons' => 'line-awesome',
        'enable_auto_updates_check' => true,
        'notifications' => [
            'feed' => true,
            'dashboard' => true,
            'plugins' => true,
            'themes' => true
        ],
        'popularity' => [
            'enabled' => true,
            'ignore' => [
                0 => '/test*',
                1 => '/modular'
            ],
            'history' => [
                'daily' => '30',
                'monthly' => '12',
                'visitors' => '20'
            ]
        ],
        'whitelabel' => [
            'quicktray_recompile' => false,
            'codemirror_theme' => 'paper',
            'codemirror_fontsize' => 'md',
            'codemirror_md_font' => 'sans',
            'logo_custom' => [
                
            ],
            'logo_login' => [
                
            ],
            'color_scheme' => [
                'accents' => [
                    'primary-accent' => 'update',
                    'secondary-accent' => 'button',
                    'tertiary-accent' => 'notice'
                ],
                'colors' => [
                    'logo-bg' => '#007FD4',
                    'logo-link' => '#ffffff',
                    'nav-bg' => '#007acc',
                    'nav-text' => '#B5D6F4',
                    'nav-link' => '#b5d6f4',
                    'nav-selected-bg' => '#006bbd',
                    'nav-selected-link' => '#c9e3ff',
                    'nav-hover-bg' => '#0072c9',
                    'nav-hover-link' => '#c9e3ff',
                    'toolbar-bg' => '#006bbd',
                    'toolbar-text' => '#c9e3ff',
                    'page-bg' => '#e9f3ff',
                    'page-text' => '#2a587a',
                    'page-link' => '#3EA7E6',
                    'content-bg' => '#f5faff',
                    'content-text' => '#2A587A',
                    'content-link' => '#006bbd',
                    'content-link2' => '#519173',
                    'content-header' => '#636468',
                    'content-tabs-bg' => '#eef6ff',
                    'content-tabs-text' => '#41627a',
                    'button-bg' => '#027FD4',
                    'button-text' => '#ffffff',
                    'notice-bg' => '#8e5b8f',
                    'notice-text' => '#ffffff',
                    'update-bg' => '#519173',
                    'update-text' => '#ffffff',
                    'critical-bg' => '#f45857',
                    'critical-text' => '#ffffff',
                    'content-highlight' => '#ffffd7'
                ],
                'name' => NULL
            ],
            'custom_footer' => NULL,
            'custom_css' => NULL,
            'custom_presets' => NULL
        ],
        'show_beta_msg' => NULL,
        'pagemedia' => [
            'resize_width' => 0,
            'resize_height' => 0,
            'res_min_width' => 0,
            'res_min_height' => 0,
            'res_max_width' => 0,
            'res_max_height' => 0,
            'resize_quality' => 0.85
        ],
        'hide_page_types' => [
            0 => 'error',
            1 => 'faq',
            2 => 'flex-objects',
            3 => 'modular',
            4 => 'pagination',
            5 => 'photoswipe',
            6 => 'root',
            7 => 'form',
            8 => 'home',
            9 => 'list',
            10 => 'external',
            11 => 'contact'
        ]
    ]
];
