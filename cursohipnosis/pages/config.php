<?php

$protocol = $_SERVER['REQUEST_SCHEME'] . '://';
$domain = 'localhost';

define('BASE_URL', $protocol . $domain . dirname($_SERVER['PHP_SELF'], 2) . '/');

$pageTitles = [
    '/' => 'Presentación',
    'contacto' => 'Contacto',
    'testimonios' => 'Testimonios',
    'cursos' => 'Cursos',
];

function asset($path, $include = false) {
    if (!$include) {
        return '/dist/' . ltrim($path, '/');
    }

    $file = $_SERVER['DOCUMENT_ROOT'] . '/dist/' . ltrim($path, '/');

    if (file_exists($file)) {
        return file_get_contents($file);
    }

    return '';
}

function title()
{
    $url = rtrim($_SERVER['REQUEST_URI'], '/');
    
    if (str_ends_with($url, 'curso')) {
    return $pageTitles[$url] ?? '404';
    }
    
    return $url;
}
