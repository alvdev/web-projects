<?php
$sizes = $sizes ?? "
    (min-width: 1440px) 18vw,
    (min-width: 1024px) 25vw,
    (min-width: 768px) 30vw,
    45vw";

$avifSrcset = $image->staticSrcset('gallery-avif');
$webpSrcset = $image->staticSrcset('gallery-webp');
$defaultSrcset = $image->staticSrcset('gallery-default');
$full = $image->resize(1800);
$thumbUrl = $image->staticUrl(['width' => 600]);
?>
<li class="break-inside-avoid mb-4 overflow-clip border-2 border-violet-800/20 h-auto rounded-xl w-full">
    <picture>
        <source srcset="<?= $avifSrcset ?>" sizes="<?= $sizes ?>" type="image/avif">
        <source srcset="<?= $webpSrcset ?>" sizes="<?= $sizes ?>" type="image/webp">
        <img
            src="<?= $thumbUrl ?>"
            srcset="<?= $defaultSrcset ?>"
            sizes="<?= $sizes ?>"
            width="<?= $image->width() ?>"
            height="<?= $image->height() ?>"
            data-full="<?= $full->url() ?>"
            alt="<?= $image->alt()->or($image->filename()) ?>"
            loading="lazy"
            decoding="async"
            class="cursor-zoom-in hover:scale-105 transition w-full h-auto block"
            @click="open($event)">
    </picture>
</li>
