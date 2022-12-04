<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/plugins/flex-objects/blueprints/flex-objects/contacts.yaml',
    'modified' => 1669976016,
    'size' => 3843,
    'data' => [
        'title' => 'Contacts',
        'description' => 'Simple contact directory with tags.',
        'type' => 'flex-objects',
        'config' => [
            'admin' => [
                'router' => [
                    'path' => '/contacts'
                ],
                'menu' => [
                    'list' => [
                        'route' => '/contacts',
                        'title' => 'Contacts',
                        'icon' => 'fa-address-card-o',
                        'authorize' => [
                            0 => 'admin.contacts.list',
                            1 => 'admin.super'
                        ],
                        'priority' => 2
                    ]
                ],
                'template' => 'default',
                'permissions' => [
                    'admin.contacts' => [
                        'type' => 'crudpl',
                        'label' => 'Contacts'
                    ]
                ],
                'list' => [
                    'title' => 'name',
                    'fields' => [
                        'published' => [
                            'field' => [
                                'type' => 'toggle',
                                'label' => 'Publ'
                            ],
                            'width' => 8
                        ],
                        'last_name' => [
                            'link' => 'edit'
                        ],
                        'first_name' => [
                            'link' => 'edit'
                        ],
                        'email' => NULL,
                        'website' => NULL,
                        'tags' => NULL
                    ]
                ],
                'edit' => [
                    'title' => [
                        'template' => '{{ object.last_name ?? \'Last\' }}, {{ object.first_name ?? \'First Name\' }}'
                    ]
                ],
                'preview' => [
                    'enabled' => false,
                    'route' => NULL
                ],
                'export' => [
                    'enabled' => true,
                    'method' => 'jsonSerialize',
                    'formatter' => [
                        'class' => 'Grav\\Framework\\File\\Formatter\\YamlFormatter'
                    ],
                    'filename' => 'contacts'
                ]
            ],
            'site' => [
                'templates' => [
                    'collection' => [
                        'paths' => [
                            0 => 'flex/{TYPE}/collection/{LAYOUT}{EXT}'
                        ]
                    ],
                    'object' => [
                        'paths' => [
                            0 => 'flex/{TYPE}/object/{LAYOUT}{EXT}'
                        ]
                    ],
                    'defaults' => [
                        'type' => 'contacts',
                        'layout' => 'default'
                    ]
                ]
            ],
            'data' => [
                'object' => 'Grav\\Common\\Flex\\Types\\Generic\\GenericObject',
                'collection' => 'Grav\\Common\\Flex\\Types\\Generic\\GenericCollection',
                'index' => 'Grav\\Common\\Flex\\Types\\Generic\\GenericIndex',
                'storage' => [
                    'class' => 'Grav\\Framework\\Flex\\Storage\\SimpleStorage',
                    'options' => [
                        'formatter' => [
                            'class' => 'Grav\\Framework\\File\\Formatter\\JsonFormatter'
                        ],
                        'folder' => 'user-data://flex-objects/contacts.json'
                    ]
                ],
                'search' => [
                    'options' => [
                        'contains' => 1
                    ],
                    'fields' => [
                        0 => 'last_name',
                        1 => 'first_name',
                        2 => 'email'
                    ]
                ]
            ]
        ],
        'form' => [
            'validation' => 'loose',
            'fields' => [
                'published' => [
                    'type' => 'toggle',
                    'label' => 'Published',
                    'highlight' => 1,
                    'default' => 1,
                    'options' => [
                        1 => 'PLUGIN_ADMIN.YES',
                        0 => 'PLUGIN_ADMIN.NO'
                    ],
                    'validate' => [
                        'type' => 'bool',
                        'required' => true
                    ]
                ],
                'last_name' => [
                    'type' => 'text',
                    'label' => 'Last Name',
                    'validate' => [
                        'required' => true
                    ]
                ],
                'first_name' => [
                    'type' => 'text',
                    'label' => 'First Name',
                    'validate' => [
                        'required' => true
                    ]
                ],
                'email' => [
                    'type' => 'email',
                    'label' => 'Email Address',
                    'validate' => [
                        'required' => true
                    ]
                ],
                'website' => [
                    'type' => 'url',
                    'label' => 'Website URL'
                ],
                'tags' => [
                    'type' => 'selectize',
                    'size' => 'large',
                    'label' => 'Tags',
                    'classes' => 'fancy',
                    'validate' => [
                        'type' => 'commalist'
                    ]
                ]
            ]
        ]
    ]
];
