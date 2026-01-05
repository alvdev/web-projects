<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/gallery') ?>

<?php
$sizes = "
        (min-width: 1200px) 25vw,
        (min-width: 900px) 33vw,
        (min-width: 600px) 50vw,
        100vw";
?>

<div x-data="{ selectedTab: 'pictures' }">
    <section id="intro" class="relative pt-16 md:pt-28 lg:pt-36 bg-linear-to-bl from-red-600/30 to-indigo-950/30 to-50%">
        <div class="container relative w-full flex flex-col md:flex-row md:flex-wrap items-center gap-8 lg:gap-16 justify-center text-xl md:text-2xl lg:text-3xl">
            <button x-on:click="selectedTab = 'pictures'" x-bind:class="selectedTab === 'pictures' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Fotos</button>

            <button x-on:click="selectedTab = 'videos'" x-bind:class="selectedTab === 'videos' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Videos</button>

            <button x-on:click="selectedTab = 'people'" x-bind:class="selectedTab === 'people' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Famosos</button>

            <button x-on:click="selectedTab = 'book'" x-bind:class="selectedTab === 'book' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Book</button>
        </div>
    </section>

    <?php
    function render_lightweight_gallery($images, $thumbSize = 600, $fullSize = 1800)
    {
        global $sizes; // use sizes defined above
        $i = 0;
        foreach ($images as $image):
            $i++;
            $thumb = $image->resize($thumbSize);
            $full  = $image->resize($fullSize);
            // Fallbacks in case resize returns null (safety)
            $thumbUrl = $thumb ? $thumb->url() : $image->url();
            $fullUrl  = $full  ? $full->url()  : $image->url();
    ?>
            <li>
                <picture class="block rounded-xl border-2 border-indigo-900/50 bg-violet-950 mb-4 overflow-clip">
                    <source
                        data-srcset="<?= $image->srcset('avif') ?? $image->src() ?>"
                        sizes="<?= $sizes ?>"
                        type="image/avif">
                    <source
                        data-srcset="<?= $image->srcset('webp') ?? $image->src() ?>"
                        sizes="<?= $sizes ?>"
                        type="image/webp">
                    <img
                        x-on:click="openLightbox($event)"
                        src="<?= $thumbUrl ?>"
                        alt="<?= $image->alt()->or($image->filename()) ?>"
                        data-index="<?= $i ?>"
                        data-full="<?= $fullUrl ?>"
                        data-srcset="<?= $image->srcset() ?>"
                        sizes="<?= $sizes ?>"
                        width="<?= $thumb ? $thumb->width() : '' ?>"
                        height="<?= $thumb ? $thumb->height() : '' ?>"
                        class="brightness-110 saturate-110 contrast-110 hover:scale-110 transition-all duration-300"
                        loading="lazy">
                </picture>
            </li>
    <?php
        endforeach;
    }
    ?>

    <!-- Pictures -->
    <div
        x-show="selectedTab === 'pictures'"
        x-cloak>
        <?php snippet('gallery', ['images' => $page->images()]) ?>
    </div>

    <!-- Videos -->
    <template x-if="selectedTab === 'videos'">
        <div x-transition x-cloak class="container mt-16 md:mt-28 lg:mt-36 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
            <?php foreach ($page->videoList()->toStructure() as $item): ?>
                <div class="flex gap-8 *:w-full *:h-auto *:rounded-xl *:ring-2 *:ring-indigo-900/50 *:aspect-video">
                    <?php snippet('video', ['video' => $item->video()->toEmbed()]) ?>
                </div>
            <?php endforeach ?>
        </div>
    </template>

    <!-- People -->
    <div
        x-show="selectedTab === 'people'"
        x-cloak>
        <?php snippet('gallery', ['images' => $page->find('people')->images()]) ?>
    </div>

    <!-- Book -->
    <template x-if="selectedTab === 'book'">
        <div x-transition x-cloak class="container mt-16 md:mt-28 lg:mt-36 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8">
            <?php foreach ($page->find('book')->images() as $image): ?>
                <?php
                // Thumbnail AVIF optimizado
                $thumb = $image->thumb([
                    'width'   => 341,
                    'height'  => 511,
                    'crop'    => true,
                    'format'  => 'avif',
                    'quality' => 70,
                ]);
                ?>
                <div class="relative">
                    <img
                        src="<?= $thumb->url() ?>"
                        width="<?= $thumb->width() ?>"
                        height="<?= $thumb->height() ?>"
                        alt="<?= $image->alt()->or($image->filename()) ?>"
                        loading="lazy"
                        decoding="async"
                        class="w-full h-auto rounded-xl ring-2 ring-indigo-900/50 aspect-2/3 object-cover saturate-110 contrast-110">

                    <a
                        href="<?= $image->url() ?>"
                        download
                        class="absolute bottom-2 left-2 right-2 md:bottom-4 md:left-4 md:right-4 flex gap-2 justify-center items-center py-2 md:py-4 text-lg md:text-xl lg:text-xl xl:text-2xl uppercase bg-linear-to-t from-black/60 to-violet-600/60 rounded-xl hover:bg-violet-600/60 transition-all">
                        Descargar
                        <svg xmlns="http://www.w3.org/2000/svg" height="32px" viewBox="0 -960 960 960" width="32px" fill="#e3e3e3">
                            <path d="m480-320 160-160-56-56-64 64v-168h-80v168l-64-64-56 56 160 160Zm0 240q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
                        </svg>
                    </a>
                </div>
            <?php endforeach ?>
        </div>
    </template>
</div>

<?php endslot() ?>
