<?php
require 'kirby/bootstrap.php';
$kirby = new Kirby\Cms\App;

$sections = ['pictures', 'people', 'book'];

foreach ($sections as $slug) {
    if ($section = page('galeria/' . $slug)) {
        echo "Processing $slug...\n";
        foreach ($section->images() as $image) {
            pregenerateStaticThumbnails($image);
            echo " - Generated: " . $image->filename() . "\n";
        }
    }
}

// Also process the main gallery cover for LCP
if ($galleryPage = page('galeria')) {
    echo "Processing main gallery cover...\n";
    if ($cover = $galleryPage->cover()->toFile()) {
        pregenerateStaticThumbnails($cover);
        echo " - Generated Cover: " . $cover->filename() . "\n";
    }
}
echo "Done!";
