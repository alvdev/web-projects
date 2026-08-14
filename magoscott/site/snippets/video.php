<?php
// usage: snippet('video', ['video' => $videoObject, 'class' => 'optional classes', 'sizes' => 'optional sizes string'])
if (!isset($video) || !$video) return;

$sizes = $sizes ?? '(min-width: 1024px) 50vw, 100vw';

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

<div x-data="{ playing: false }" class="relative w-full aspect-video overflow-hidden group <?= $class ?? '' ?>">
    <div x-show="!playing" @click="playing = true" class="absolute inset-0 cursor-pointer group">
        <?php if ($thumb = $video->image()): ?>
            <?php $thumbUrl = str_replace('i.ytimg.com', 'img.youtube.com', (string) $thumb); ?>
            <?php if (preg_match('#/vi/([A-Za-z0-9_-]{11})/#', $thumbUrl, $ytMatch)): ?>
                <?php $ytId = $ytMatch[1]; ?>
                <img
                    src="<?= url('youtube-thumbs/' . $ytId . '-mq.jpg') ?>"
                    srcset="<?= url('youtube-thumbs/' . $ytId . '-mq.jpg') ?> 320w, <?= url('youtube-thumbs/' . $ytId . '-hq.jpg') ?> 480w, <?= url('youtube-thumbs/' . $ytId . '-sd.jpg') ?> 640w"
                    sizes="<?= $sizes ?>"
                    alt="Play Video"
                    referrerpolicy="no-referrer"
                    onerror="this.style.display='none'"
                    class="w-full h-full object-cover transition-opacity"
                    loading="lazy">
            <?php else: ?>
                <img src="<?= $thumbUrl ?>" alt="Play Video" referrerpolicy="no-referrer" onerror="this.style.display='none'" class="w-full h-full object-cover transition-opacity" loading="lazy">
            <?php endif ?>
        <?php endif ?>
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
