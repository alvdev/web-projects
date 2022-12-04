<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledMarkdownFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/pages/04.contact/contact.md',
    'modified' => 1669479332,
    'size' => 2759,
    'data' => [
        'header' => [
            'title' => 'Contacto',
            'form' => [
                'name' => 'contact-form',
                'fields' => [
                    'left' => [
                        'type' => 'fieldset',
                        'classes' => 'col-md-6',
                        'fields' => [
                            0 => [
                                'name' => 'name',
                                'label' => 'Nombre',
                                'classes' => 'form-control with-icon rt-icon2-user',
                                'autofocus' => 'on',
                                'autocomplete' => 'on',
                                'type' => 'text',
                                'validate' => [
                                    'required' => true
                                ]
                            ],
                            1 => [
                                'name' => 'email',
                                'label' => 'Email',
                                'classes' => 'form-control with-icon rt-icon2-mail',
                                'type' => 'email',
                                'validate' => [
                                    'rule' => 'email',
                                    'required' => true
                                ]
                            ],
                            2 => [
                                'name' => 'phone',
                                'label' => 'Teléfono',
                                'classes' => 'form-control with-icon rt-icon2-phone5',
                                'type' => 'number',
                                'validate' => [
                                    'rule' => 'tel',
                                    'required' => false
                                ]
                            ]
                        ]
                    ],
                    'right' => [
                        'type' => 'fieldset',
                        'classes' => 'col-md-6',
                        'fields' => [
                            0 => [
                                'name' => 'message',
                                'label' => 'Mensaje',
                                'classes' => 'form-control with-icon rt-icon2-pen',
                                'rows' => 8,
                                'size' => 'long',
                                'placeholder' => '',
                                'type' => 'textarea',
                                'id' => 'message',
                                'validate' => [
                                    'required' => true
                                ]
                            ]
                        ]
                    ]
                ],
                'buttons' => [
                    0 => [
                        'type' => 'submit',
                        'value' => 'Enviar',
                        'classes' => 'theme_button color1 bottommargin_0'
                    ],
                    1 => [
                        'type' => 'reset',
                        'value' => 'Borrar',
                        'classes' => 'theme_button inverse bottommargin_0'
                    ]
                ],
                'process' => [
                    0 => [
                        'turnstile' => true
                    ],
                    1 => [
                        'email' => [
                            'from' => '{{ config.plugins.email.from }}',
                            'to' => [
                                0 => '{{ config.plugins.email.from }}',
                                1 => '{{ form.value.email }}'
                            ],
                            'subject' => '[Feedback] {{ form.value.name|e }}',
                            'body' => '{% include \'forms/data.html.twig\' %}'
                        ]
                    ],
                    2 => [
                        'save' => [
                            'fileprefix' => 'feedback-',
                            'dateformat' => 'Ymd-His-u',
                            'extension' => 'txt',
                            'body' => '{% include \'forms/data.txt.twig\' %}'
                        ]
                    ],
                    3 => [
                        'message' => 'Gracias por contactar con nuestra agencia. Le responderemos a la mayor brevedad posible.'
                    ],
                    4 => [
                        'display' => 'thankyou'
                    ]
                ]
            ],
            'slug' => 'contacto'
        ],
        'frontmatter' => 'title: Contacto
form:
    name: contact-form
    fields:
        left:
            type: fieldset
            classes: col-md-6
            fields:
                -
                    name: name
                    label: Nombre
                    classes: \'form-control with-icon rt-icon2-user\'
                    autofocus: \'on\'
                    autocomplete: \'on\'
                    type: text
                    validate:
                        required: true
                -
                    name: email
                    label: Email
                    classes: \'form-control with-icon rt-icon2-mail\'
                    type: email
                    validate:
                        rule: email
                        required: true
                -
                    name: phone
                    label: Teléfono
                    classes: \'form-control with-icon rt-icon2-phone5\'
                    type: number
                    validate:
                        rule: tel
                        required: false
        right:
            type: fieldset
            classes: col-md-6
            fields:
                -
                    name: message
                    label: Mensaje
                    classes: \'form-control with-icon rt-icon2-pen\'
                    rows: 8
                    size: long
                    placeholder: \'\'
                    type: textarea
                    id: message
                    validate:
                        required: true
    buttons:
        -
            type: submit
            value: Enviar
            classes: \'theme_button color1 bottommargin_0\'
        -
            type: reset
            value: Borrar
            classes: \'theme_button inverse bottommargin_0\'
    process:
        -
            turnstile: true
        -
            email:
                from: \'{{ config.plugins.email.from }}\'
                to:
                    - \'{{ config.plugins.email.from }}\'
                    - \'{{ form.value.email }}\'
                subject: \'[Feedback] {{ form.value.name|e }}\'
                body: \'{% include \'\'forms/data.html.twig\'\' %}\'
        -
            save:
                fileprefix: feedback-
                dateformat: Ymd-His-u
                extension: txt
                body: \'{% include \'\'forms/data.txt.twig\'\' %}\'
        -
            message: \'Gracias por contactar con nuestra agencia. Le responderemos a la mayor brevedad posible.\'
        -
            display: thankyou
slug: contacto',
        'markdown' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Praesentium amet ea dicta neque, ut quis omnis quos nam, pariatur, minus, fugit suscipit aspernatur sint ullam quas explicabo. Alias, officiis, dolor!'
    ]
];
