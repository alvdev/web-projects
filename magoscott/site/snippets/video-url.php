<?php
/**
 * Lite YouTube snippet that accepts a raw YouTube URL.
 * Usage: snippet('video-url', ['url' => $youtubeUrl, 'class' => 'optional classes'])
 */
if (!isset($url) || !$url) return;

// Extract video ID from the URL
$videoId = null;
$urlString = (string)$url;

// Handle various YouTube URL formats
if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $urlString, $matches)) {
    $videoId = $matches[1];
}

if (!$videoId) return;

// Build iframe with autoplay
$iframeSrc = "https://www.youtube-nocookie.com/embed/{$videoId}?autoplay=1";
$thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";
?>

<div x-data="{ playing: false }" class="relative w-full aspect-video bg-black overflow-hidden group <?= $class ?? '' ?>">
    <div x-show="!playing" @click="playing = true" class="absolute inset-0 cursor-pointer group">
        <img src="<?= $thumbnailUrl ?>" alt="Play Video" class="w-full h-full object-cover transition-opacity" loading="lazy">
        <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/10 transition-colors">
            <div class="bg-red-600 text-white rounded-full p-4 shadow-lg group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
        </div>
    </div>
    <template x-if="playing">
        <iframe 
            class="w-full h-full absolute inset-0" 
            src="<?= $iframeSrc ?>" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
            allowfullscreen
            loading="lazy">
        </iframe>
    </template>
</div>
