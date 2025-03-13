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
    if (!$include) return BASE_URL . 'dist/' . $path;
    if ($include) return file_get_contents(BASE_URL . 'dist/' . $path);
}

function title()
{
    $url = rtrim($_SERVER['REQUEST_URI'], '/');
    
    if (str_ends_with($url, 'curso')) {
    return $pageTitles[$url] ?? '404';
    }
    
    return $url;
}
