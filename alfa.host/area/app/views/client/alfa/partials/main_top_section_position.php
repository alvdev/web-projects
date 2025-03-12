<?php

/**
 * Get the padding classes for the header and main sections when the main top section is visible
 *
 * @return array
 */

function getMainTopSectionPosition(): array
{
    $pages = [
        'urlContains' => ['order/config/preconfig'],
        'urlEndsWith' => ['client'],
    ];
    $headerPadding = '';
    $mainPadding = 'has-[.alert-dismissable]:pt-36';
    $request_uri = $_SERVER['REQUEST_URI'];

    foreach ($pages['urlContains'] as $url) {
        if (str_contains($request_uri, $url)) {
            $headerPadding .= '';
            $mainPadding .= ' pt-28 pb-32';
            return [
                'header' => $headerPadding,
                'main' => $mainPadding
            ]; // Early exit on first match
        }
    }

    // Check URL ends with conditions (with/without trailing slash)
    $normalizedUri = rtrim($request_uri, '/');
    foreach ($pages['urlEndsWith'] as $url) {
        if (str_ends_with($normalizedUri, $url)) {
            $headerPadding .= '';
            $mainPadding .= ' pt-28 pb-32';
            return [
                'header' => $headerPadding,
                'main' => $mainPadding
            ]; // Early exit on first match
        }
    }

    $headerPadding = 'pb-0';
    $mainPadding .= ' pt-24 pb-32';
    return [
        'header' => $headerPadding,
        'main' => $mainPadding
    ];
}

return getMainTopSectionPosition();
