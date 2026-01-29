<?php

// 1. Define Helper Functions (Same as before, but wrapped in a check)
if (!function_exists('getYoutubeId')) {
    function getYoutubeId(string $url): ?string
    {
        $parts = parse_url($url);

        // Standard or embedded URLs
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['v'])) {
                return $query['v'];
            }
        }

        // Shortened URLs (youtu.be/ID_HERE) or embed path
        if (isset($parts['path'])) {
            $path = trim($parts['path'], '/');
            if (strlen($path) === 11) {
                return $path;
            }
        }
        return null;
    }
}

if (!function_exists('getYoutubeThumbnailUrl')) {
    function getYoutubeThumbnailUrl(string $videoId, string $quality = 'mqdefault'): string
    {
        // mqdefault is a good balance for the panel
        return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
    }
}

// 2. Register the Custom Structure Column Snippet
Kirby::plugin('yourcompany/youtube-preview', [
    'structure' => [
        'fields' => [
            'preview' => [
                'columns' => [
                    'preview' => [
                        // The 'html' key is used for custom rendering logic
                        'html' => function ($field, $entry) {
                            $url = $entry->video_url()->value();
                            $videoId = getYoutubeId($url);

                            if (!$videoId) {
                                return 'Invalid URL';
                            }

                            $thumbnailUrl = getYoutubeThumbnailUrl($videoId, 'default');

                            // The image tag is rendered directly in the column. 
                            // Add inline styles for a clean appearance.
                            return '<img src="' . $thumbnailUrl . '" 
                                style="width: 100%; height: 40px; object-fit: cover; border-radius: 3px;" 
                                alt="Video Thumbnail">';
                        }
                    ]
                ]
            ]
        ]
    ]
]);
