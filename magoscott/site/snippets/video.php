<?php
// usage: snippet('video', ['video' => $videoObject, 'class' => 'optional classes'])
if (!isset($video) || !$video) return;

$iframe = $video->code();
// Inject autoplay=1 into the src
$iframe = preg_replace_callback('/src="([^"]+)"/', function($matches) {
    $url = $matches[1];
    $separator = (parse_url($url, PHP_URL_QUERY) == NULL) ? '?' : '&';
    return 'src="' . $url . $separator . 'autoplay=1"';
}, $iframe);
// Add styling classes to the iframe
$iframe = str_replace('<iframe', '<iframe class="w-full h-full absolute inset-0"', $iframe);
// Common replacements
$iframe = str_replace(['<iframe', 'youtube.com'], ['<iframe loading="lazy"', 'youtube-nocookie.com'], $iframe);
?>

<div x-data="{ playing: false }" class="relative w-full aspect-video bg-black overflow-hidden group <?= $class ?? '' ?>">
    <div x-show="!playing" @click="playing = true" class="absolute inset-0 cursor-pointer group">
        <img src="<?= $video->image() ?>" alt="Play Video" class="w-full h-full object-cover transition-opacity" loading="lazy">
        <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/10 transition-colors">
            <div class="bg-red-600 text-white rounded-full p-4 shadow-lg group-hover:scale-110 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
        </div>
    </div>
    <template x-if="playing">
        <?= $iframe ?>
    </template>
</div>
