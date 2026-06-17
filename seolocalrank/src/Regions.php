<?php

declare(strict_types=1);

namespace SeoLocalRank;

/**
 * Curated list of Google country/language combinations.
 *
 * Each entry corresponds to a row in the region autocomplete.
 * The fields mirror valentin.app's google_sites array, plus a search
 * label that's rendered in the dropdown.
 */
final class Regions
{
    /**
     * @return list<array{name:string,gl:string,lang:string,hl:string,dir:string,url:string,label:string}>
     */
    public static function all(): array
    {
        $rows = [
            ['United States',     'US', 'English',                 'en',    'ltr', 'https://www.google.us/search'],
            ['United Kingdom',    'GB', 'English',                 'en',    'ltr', 'https://www.google.co.uk/search'],
            ['Canada',            'CA', 'English',                 'en',    'ltr', 'https://www.google.ca/search'],
            ['Canada',            'CA', 'Français',                'fr',    'ltr', 'https://www.google.ca/search'],
            ['Australia',         'AU', 'English',                 'en',    'ltr', 'https://www.google.com.au/search'],
            ['New Zealand',       'NZ', 'English',                 'en',    'ltr', 'https://www.google.co.nz/search'],
            ['Ireland',           'IE', 'English',                 'en',    'ltr', 'https://www.google.ie/search'],

            ['Spain',             'ES', 'Español',                 'es',    'ltr', 'https://www.google.es/search'],
            ['Spain',             'ES', 'Català',                  'ca',    'ltr', 'https://www.google.es/search'],
            ['Spain',             'ES', 'Galego',                  'gl',    'ltr', 'https://www.google.es/search'],
            ['Spain',             'ES', 'Euskara',                 'eu',    'ltr', 'https://www.google.es/search'],
            ['Mexico',            'MX', 'Español (Latinoamérica)', 'es-419','ltr', 'https://www.google.com.mx/search'],
            ['Mexico',            'MX', 'English',                 'en',    'ltr', 'https://www.google.com.mx/search'],
            ['Argentina',         'AR', 'Español (Latinoamérica)', 'es-419','ltr', 'https://www.google.com.ar/search'],
            ['Chile',             'CL', 'Español (Latinoamérica)', 'es-419','ltr', 'https://www.google.cl/search'],
            ['Colombia',          'CO', 'Español (Latinoamérica)', 'es-419','ltr', 'https://www.google.com.co/search'],
            ['Peru',              'PE', 'Español (Latinoamérica)', 'es-419','ltr', 'https://www.google.com.pe/search'],

            ['France',            'FR', 'Français',                'fr',    'ltr', 'https://www.google.fr/search'],
            ['Germany',           'DE', 'Deutsch',                 'de',    'ltr', 'https://www.google.de/search'],
            ['Austria',           'AT', 'Deutsch',                 'de',    'ltr', 'https://www.google.at/search'],
            ['Switzerland',       'CH', 'Deutsch',                 'de',    'ltr', 'https://www.google.ch/search'],
            ['Switzerland',       'CH', 'Français',                'fr',    'ltr', 'https://www.google.ch/search'],
            ['Switzerland',       'CH', 'Italiano',                'it',    'ltr', 'https://www.google.ch/search'],
            ['Italy',             'IT', 'Italiano',                'it',    'ltr', 'https://www.google.it/search'],
            ['Portugal',          'PT', 'Português (Portugal)',    'pt-PT', 'ltr', 'https://www.google.pt/search'],
            ['Brazil',            'BR', 'Português (Brasil)',      'pt-BR', 'ltr', 'https://www.google.com.br/search'],
            ['Netherlands',       'NL', 'Nederlands',              'nl',    'ltr', 'https://www.google.nl/search'],
            ['Belgium',           'BE', 'Nederlands',              'nl',    'ltr', 'https://www.google.be/search'],
            ['Belgium',           'BE', 'Français',                'fr',    'ltr', 'https://www.google.be/search'],
            ['Luxembourg',        'LU', 'Français',                'fr',    'ltr', 'https://www.google.lu/search'],
            ['Luxembourg',        'LU', 'Deutsch',                 'de',    'ltr', 'https://www.google.lu/search'],

            ['Poland',            'PL', 'Polski',                  'pl',    'ltr', 'https://www.google.pl/search'],
            ['Czechia',           'CZ', 'Čeština',                 'cs',    'ltr', 'https://www.google.cz/search'],
            ['Slovakia',          'SK', 'Slovenčina',              'sk',    'ltr', 'https://www.google.sk/search'],
            ['Hungary',           'HU', 'Magyar',                  'hu',    'ltr', 'https://www.google.hu/search'],
            ['Romania',           'RO', 'Română',                  'ro',    'ltr', 'https://www.google.ro/search'],
            ['Bulgaria',          'BG', 'Български',               'bg',    'ltr', 'https://www.google.bg/search'],
            ['Greece',            'GR', 'Ελληνικά',                'el',    'ltr', 'https://www.google.gr/search'],
            ['Croatia',           'HR', 'Hrvatski',                'hr',    'ltr', 'https://www.google.hr/search'],
            ['Serbia',            'RS', 'Српски',                  'sr',    'ltr', 'https://www.google.rs/search'],
            ['Slovenia',          'SI', 'Slovenščina',             'sl',    'ltr', 'https://www.google.si/search'],
            ['Bosnia & Herzegovina','BA','Bosanski',               'bs',    'ltr', 'https://www.google.ba/search'],
            ['Albania',           'AL', 'Shqip',                   'sq',    'ltr', 'https://www.google.al/search'],
            ['North Macedonia',   'MK', 'Македонски',              'mk',    'ltr', 'https://www.google.mk/search'],
            ['Lithuania',         'LT', 'Lietuvių',                'lt',    'ltr', 'https://www.google.lt/search'],
            ['Latvia',            'LV', 'Latviešu',                'lv',    'ltr', 'https://www.google.lv/search'],
            ['Estonia',           'EE', 'Eesti',                   'et',    'ltr', 'https://www.google.ee/search'],
            ['Ukraine',           'UA', 'Українська',              'uk',    'ltr', 'https://www.google.com.ua/search'],
            ['Ukraine',           'UA', 'Русский',                 'ru',    'ltr', 'https://www.google.com.ua/search'],
            ['Russia',            'RU', 'Русский',                 'ru',    'ltr', 'https://www.google.ru/search'],
            ['Belarus',           'BY', 'Беларуская',              'be',    'ltr', 'https://www.google.by/search'],
            ['Turkey',            'TR', 'Türkçe',                  'tr',    'ltr', 'https://www.google.com.tr/search'],

            ['Sweden',            'SE', 'Svenska',                 'sv',    'ltr', 'https://www.google.se/search'],
            ['Norway',            'NO', 'Norsk',                   'no',    'ltr', 'https://www.google.no/search'],
            ['Denmark',           'DK', 'Dansk',                   'da',    'ltr', 'https://www.google.dk/search'],
            ['Finland',           'FI', 'Suomi',                   'fi',    'ltr', 'https://www.google.fi/search'],
            ['Iceland',           'IS', 'Íslenska',                'is',    'ltr', 'https://www.google.is/search'],

            ['Japan',             'JP', '日本語',                   'ja',    'ltr', 'https://www.google.co.jp/search'],
            ['Japan',             'JP', 'English',                 'en',    'ltr', 'https://www.google.co.jp/search'],
            ['South Korea',       'KR', '한국어',                   'ko',    'ltr', 'https://www.google.co.kr/search'],
            ['China',             'CN', '中文 (简体)',              'zh-CN', 'ltr', 'https://www.google.com.hk/search'],
            ['China',             'CN', '中文 (繁體)',              'zh-TW', 'ltr', 'https://www.google.com.hk/search'],
            ['Hong Kong',         'HK', '中文 (繁體)',              'zh-HK', 'ltr', 'https://www.google.com.hk/search'],
            ['Taiwan',            'TW', '中文 (繁體)',              'zh-TW', 'ltr', 'https://www.google.com.tw/search'],
            ['Singapore',         'SG', 'English',                 'en',    'ltr', 'https://www.google.com.sg/search'],
            ['Singapore',         'SG', '中文 (简体)',              'zh-CN', 'ltr', 'https://www.google.com.sg/search'],
            ['Singapore',         'SG', 'Melayu',                  'ms',    'ltr', 'https://www.google.com.sg/search'],
            ['Malaysia',          'MY', 'Melayu',                  'ms',    'ltr', 'https://www.google.com.my/search'],
            ['Malaysia',          'MY', 'English',                 'en',    'ltr', 'https://www.google.com.my/search'],
            ['Indonesia',         'ID', 'Bahasa Indonesia',        'id',    'ltr', 'https://www.google.co.id/search'],
            ['Indonesia',         'ID', 'English',                 'en',    'ltr', 'https://www.google.co.id/search'],
            ['Thailand',          'TH', 'ไทย',                     'th',    'ltr', 'https://www.google.co.th/search'],
            ['Thailand',          'TH', 'English',                 'en',    'ltr', 'https://www.google.co.th/search'],
            ['Vietnam',           'VN', 'Tiếng Việt',              'vi',    'ltr', 'https://www.google.com.vn/search'],
            ['Vietnam',           'VN', 'English',                 'en',    'ltr', 'https://www.google.com.vn/search'],
            ['Philippines',       'PH', 'English',                 'en',    'ltr', 'https://www.google.com.ph/search'],
            ['Philippines',       'PH', 'Filipino',                'fil',   'ltr', 'https://www.google.com.ph/search'],

            ['India',             'IN', 'English',                 'en',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'हिन्दी',                   'hi',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'বাংলা',                   'bn',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'తెలుగు',                   'te',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'मराठी',                   'mr',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'தமிழ்',                   'ta',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'ગુજરાતી',                 'gu',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'ಕನ್ನಡ',                   'kn',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'മലയാളം',                  'ml',    'ltr', 'https://www.google.co.in/search'],
            ['India',             'IN', 'ਪੰਜਾਬੀ',                   'pa',    'ltr', 'https://www.google.co.in/search'],
            ['Pakistan',          'PK', 'اردو',                    'ur',    'rtl', 'https://www.google.com.pk/search'],
            ['Pakistan',          'PK', 'English',                 'en',    'ltr', 'https://www.google.com.pk/search'],
            ['Bangladesh',        'BD', 'বাংলা',                   'bn',    'ltr', 'https://www.google.com.bd/search'],
            ['Bangladesh',        'BD', 'English',                 'en',    'ltr', 'https://www.google.com.bd/search'],
            ['Sri Lanka',         'LK', 'සිංහල',                  'si',    'ltr', 'https://www.google.lk/search'],
            ['Sri Lanka',         'LK', 'English',                 'en',    'ltr', 'https://www.google.lk/search'],
            ['Nepal',             'NP', 'नेपाली',                  'ne',    'ltr', 'https://www.google.com.np/search'],
            ['United Arab Emirates','AE','العربية',                  'ar',    'rtl', 'https://www.google.ae/search'],
            ['United Arab Emirates','AE','English',                 'en',    'ltr', 'https://www.google.ae/search'],
            ['Saudi Arabia',      'SA', 'العربية',                  'ar',    'rtl', 'https://www.google.com.sa/search'],
            ['Saudi Arabia',      'SA', 'English',                 'en',    'ltr', 'https://www.google.com.sa/search'],
            ['Qatar',             'QA', 'العربية',                  'ar',    'rtl', 'https://www.google.com.qa/search'],
            ['Kuwait',            'KW', 'العربية',                  'ar',    'rtl', 'https://www.google.com.kw/search'],
            ['Bahrain',           'BH', 'العربية',                  'ar',    'rtl', 'https://www.google.com.bh/search'],
            ['Oman',              'OM', 'العربية',                  'ar',    'rtl', 'https://www.google.com.om/search'],
            ['Israel',            'IL', 'עברית',                   'he',    'rtl', 'https://www.google.co.il/search'],
            ['Israel',            'IL', 'العربية',                  'ar',    'rtl', 'https://www.google.co.il/search'],
            ['Egypt',             'EG', 'العربية',                  'ar',    'rtl', 'https://www.google.com.eg/search'],
            ['Egypt',             'EG', 'English',                 'en',    'ltr', 'https://www.google.com.eg/search'],
            ['Morocco',           'MA', 'العربية',                  'ar',    'rtl', 'https://www.google.co.ma/search'],
            ['Morocco',           'MA', 'Français',                'fr',    'ltr', 'https://www.google.co.ma/search'],
            ['Tunisia',           'TN', 'العربية',                  'ar',    'rtl', 'https://www.google.tn/search'],
            ['Tunisia',           'TN', 'Français',                'fr',    'ltr', 'https://www.google.tn/search'],
            ['Algeria',           'DZ', 'العربية',                  'ar',    'rtl', 'https://www.google.dz/search'],
            ['Algeria',           'DZ', 'Français',                'fr',    'ltr', 'https://www.google.dz/search'],
            ['Jordan',            'JO', 'العربية',                  'ar',    'rtl', 'https://www.google.jo/search'],
            ['Lebanon',           'LB', 'العربية',                  'ar',    'rtl', 'https://www.google.com.lb/search'],
            ['Lebanon',           'LB', 'Français',                'fr',    'ltr', 'https://www.google.com.lb/search'],
            ['South Africa',      'ZA', 'English',                 'en',    'ltr', 'https://www.google.co.za/search'],
            ['South Africa',      'ZA', 'Afrikaans',               'af',    'ltr', 'https://www.google.co.za/search'],
            ['Nigeria',           'NG', 'English',                 'en',    'ltr', 'https://www.google.com.ng/search'],
            ['Kenya',             'KE', 'English',                 'en',    'ltr', 'https://www.google.co.ke/search'],
            ['Kenya',             'KE', 'Kiswahili',               'sw',    'ltr', 'https://www.google.co.ke/search'],
            ['Egypt',             'EG', 'Français',                'fr',    'ltr', 'https://www.google.com.eg/search'],

            ['Afghanistan',       'AF', 'پښتو',                    'ps',    'rtl', 'https://www.google.com.af/search'],
            ['Iran',              'IR', 'فارسی',                   'fa',    'rtl', 'https://www.google.com/search'],
        ];

        return array_map(
            static fn (array $r): array => [
                'name'  => $r[0],
                'gl'    => $r[1],
                'lang'  => $r[2],
                'hl'    => $r[3],
                'dir'   => $r[4],
                'url'   => $r[5],
                'label' => $r[0] . ' — ' . $r[2] . ' (' . strtolower($r[1]) . '/' . $r[3] . ')',
            ],
            $rows,
        );
    }
}
