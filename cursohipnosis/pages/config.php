<?php

define('BASE_URL', dirname($_SERVER['PHP_SELF'], 2) . '/');

$pageTitles = [
    '/' => 'Presentación',
    'contacto' => 'Contacto',
    'testimonios' => 'Testimonios',
    'cursos' => 'Cursos',
];

function assetUrl($file) {
    return BASE_URL . 'dist/' . $file;
}

function getTitle()
{
    $url = rtrim($_SERVER['REQUEST_URI'], '/');
    
    if (str_ends_with($url, 'curso')) {
    return $pageTitles[$url] ?? '404';
    }
    
    return $url;
}
