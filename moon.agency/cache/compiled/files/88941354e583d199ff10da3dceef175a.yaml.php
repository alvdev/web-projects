<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/wamp/www/web-projects/moon.agency/user/plugins/antispam/languages.yaml',
    'modified' => 1662082684,
    'size' => 568,
    'data' => [
        'en' => [
            'PLUGIN_ANTISPAM' => [
                'NOSCRIPT' => '[email address is javascript encrypted]',
                'TARGET_BLANK' => 'add target="_blank" to mailto links',
                'CHANGE_ON_OUTPUT' => 'add encryption on output (not cacheable, but can avoid conflicts with other plugins)'
            ]
        ],
        'de' => [
            'PLUGIN_ANTISPAM' => [
                'NOSCRIPT' => '[Mailadresse ist Javascript-verschlüsselt]',
                'TARGET_BLANK' => 'als Ziel für erzeugte Mailto-Links target="_blank" setzen',
                'CHANGE_ON_OUTPUT' => 'Verschlüsselung erst beim Output einbauen (kann nicht im Cache gespeichert werden, vermeidet aber Konflikte mit manchen anderen Plugins)'
            ]
        ]
    ]
];
