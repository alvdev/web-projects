<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/gallery') ?>

<?php
$sizes = "
        (min-width: 1200px) 25vw,
        (min-width: 900px) 33vw,
        (min-width: 600px) 50vw,
        100vw";

// Only pre-process the pictures gallery for the lightbox functionality
// Use minimal data - no AVIF/WebP srcset variants (let browser handle via Accept headers)
$galleryImages = [];
foreach ($page->images() as $image) {
    $galleryImages[] = [
        'photo' => $image->thumb(['width' => 1200, 'quality' => 80])->url(),
        'alt' => $image->alt()->or($image->filename())->value(),
        'width' => $image->width(),
        'height' => $image->height(),
    ];
}
$galleryImagesJson = json_encode($galleryImages, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<script>
    window.galleryData = {
        pictures: <?= $galleryImagesJson ?>
    };
</script>

<div x-data="{ selectedTab: 'pictures' }">
    <section id="intro" class="relative pt-16 md:pt-28 lg:pt-36 bg-linear-to-bl from-red-600/30 to-indigo-950/30 to-50%">
        <div class="container relative w-full flex flex-col md:flex-row md:flex-wrap items-center gap-8 lg:gap-16 justify-center text-xl md:text-2xl lg:text-3xl">
            <button x-on:click="selectedTab = 'pictures'" x-bind:class="selectedTab === 'pictures' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Fotos</button>

            <button x-on:click="selectedTab = 'videos'" x-bind:class="selectedTab === 'videos' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Videos</button>

            <button x-on:click="selectedTab = 'people'" x-bind:class="selectedTab === 'people' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Famosos</button>

            <button x-on:click="selectedTab = 'book'" x-bind:class="selectedTab === 'book' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Book</button>
        </div>
    </section>

    <!-- Pictures -->
    <template x-if="selectedTab === 'pictures'">
        <div x-transition x-cloak x-data="{
            imageGalleryOpened: false,
            imageGalleryActiveUrl: null,
            imageGalleryImageIndex: null,
            imageGallery: window.galleryData.pictures,
            imageGalleryOpen(event) {
                this.imageGalleryImageIndex = event.target.dataset.index;
                this.imageGalleryActiveUrl = event.target.src;
                this.imageGalleryOpened = true;
            },
            imageGalleryClose() {
                this.imageGalleryOpened = false;
                setTimeout(() => this.imageGalleryActiveUrl = null, 300);
            },
            imageGalleryNext(){
                this.imageGalleryImageIndex = (this.imageGalleryImageIndex == this.imageGallery.length) ? 1 : (parseInt(this.imageGalleryImageIndex) + 1);
                this.imageGalleryActiveUrl = this.$refs.gallery.querySelector('[data-index=\'' + this.imageGalleryImageIndex + '\']').src;
            },
            imageGalleryPrev() {
                this.imageGalleryImageIndex = (this.imageGalleryImageIndex == 1) ? this.imageGallery.length : (parseInt(this.imageGalleryImageIndex) - 1);
                this.imageGalleryActiveUrl = this.$refs.gallery.querySelector('[data-index=\'' + this.imageGalleryImageIndex + '\']').src;
            }
        }"
            @image-gallery-next.window="imageGalleryNext()"
            @image-gallery-prev.window="imageGalleryPrev()"
            @keyup.right.window="imageGalleryNext();"
            @keyup.left.window="imageGalleryPrev();"
            class="w-full h-full select-none">
    
            <div class="container mt-16 md:mt-28 lg:mt-36 opacity-0 duration-1000 delay-300 select-none ease animate-fade-in-view" style="translate: none; rotate: none; scale: none; opacity: 1; transform: translate(0px, 0px);">
                <ul x-ref="gallery" id="gallery" class="mt-8 columns-2 *:break-inside-avoid items-center md:columns-3 lg:columns-4 gap-4">
                    <template x-for="(image, index) in imageGallery">
                        <li>
                            <div class="block rounded-xl border-2 border-indigo-900/50 bg-violet-950 mb-4 overflow-clip">
                                <img
                                    x-on:click="imageGalleryOpen"
                                    :src="image.photo"
                                    :alt="image.alt"
                                    :data-index="index+1"
                                    :width="image.width"
                                    :height="image.height"
                                    class="brightness-110 saturate-110 contrast-110 hover:scale-110 transition-all duration-300"
                                    loading="lazy">
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
    
            <template x-teleport="body">
                <div
                    x-show="imageGalleryOpened"
                    x-transition:enter="transition ease-in-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:leave="transition ease-in-in duration-300"
                    x-transition:leave-end="opacity-0"
                    @click="imageGalleryClose"
                    @keydown.window.escape="imageGalleryClose"
                    x-trap.inert.noscroll="imageGalleryOpened"
                    class="fixed inset-0 z-99 flex items-center justify-center bg-violet-900/60 backdrop-blur-xs select-none cursor-zoom-out" x-cloak>
                    <div class="flex relative justify-center items-center w-11/12 xl:w-4/5 h-11/12">
                        <div @click="$event.stopPropagation(); $dispatch('image-gallery-prev')" class="flex absolute left-0 justify-center items-center w-14 h-14 text-white rounded-full translate-x-10 cursor-pointer xl:-translate-x-24 2xl:-translate-x-32 bg-white/10 hover:bg-white/20">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </div>
                        <img
                            x-show="imageGalleryOpened"
                            x-transition:enter="transition ease-in-out duration-300"
                            x-transition:enter-start="opacity-0 transform scale-50"
                            x-transition:leave="transition ease-in-in duration-300"
                            x-transition:leave-end="opacity-0 transform scale-50"
                            class="object-contain object-center w-auto h-auto select-none cursor-zoom-out rounded-2xl border-4 border-violet-950/80 bg-violet-950" :src="imageGalleryActiveUrl" alt="" style="display: none;">
                        <div @click="$event.stopPropagation(); $dispatch('image-gallery-next');" class="flex absolute right-0 justify-center items-center w-14 h-14 text-white rounded-full -translate-x-10 cursor-pointer xl:translate-x-24 2xl:translate-x-32 bg-white/10 hover:bg-white/20">
                            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Videos -->
    <template x-if="selectedTab === 'videos'">
        <div x-transition x-cloak class="container mt-16 md:mt-28 lg:mt-36 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
            <?php foreach ($page->videoList()->toStructure() as $item): ?>
                <?php if ($video = $item->video()->toEmbed()): ?>
                    <div class="flex gap-8 *:w-full *:h-auto *:rounded-xl *:ring-2 *:ring-indigo-900/50 *:aspect-video">
                        <?php snippet('video', ['video' => $video]) ?>
                    </div>
                <?php endif ?>
            <?php endforeach ?>
        </div>
    </template>

    <!-- People - Simple PHP rendering with lazy loading -->
    <template x-if="selectedTab === 'people'">
        <div x-transition x-cloak class="container mt-16 md:mt-28 lg:mt-36">
            <ul class="columns-2 *:break-inside-avoid items-center md:columns-3 lg:columns-4 gap-4">
                <?php if ($peoplePage = $page->find('people')): ?>
                    <?php foreach ($peoplePage->images() as $image): ?>
                        <li>
                            <div class="block rounded-xl border-2 border-indigo-900/50 bg-violet-950 mb-4 overflow-clip">
                                <img
                                    src="<?= $image->thumb(['width' => 600, 'quality' => 80])->url() ?>"
                                    alt="<?= $image->alt()->or($image->filename()) ?>"
                                    width="<?= $image->width() ?>"
                                    height="<?= $image->height() ?>"
                                    class="brightness-110 saturate-110 contrast-110 hover:scale-110 transition-all duration-300"
                                    loading="lazy">
                            </div>
                        </li>
                    <?php endforeach ?>
                <?php endif ?>
            </ul>
        </div>
    </template>

    <!-- Book - Simple PHP rendering with lazy loading -->
    <template x-if="selectedTab === 'book'">
        <div x-transition x-cloak class="container mt-16 md:mt-28 lg:mt-36 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8">
            <?php if ($bookPage = $page->find('book')): ?>
                <?php foreach ($bookPage->images() as $image): ?>
                    <div class="relative flex gap-8">
                        <img
                            alt="<?= $image->alt() ?>"
                            src="<?= $image->thumb(['width' => 400, 'quality' => 80])->url() ?>"
                            width="<?= $image->width() ?>"
                            height="<?= $image->height() ?>"
                            loading="lazy" 
                            class="w-full h-auto rounded-xl ring-2 ring-indigo-900/50 aspect-2/3 object-cover saturate-110 contrast-110">
    
                        <div class="absolute inset-x-0 bottom-0">
                            <a href="<?= $image->url() ?>" download="<?= $image->title() ?>" class="absolute bottom-2 left-2 right-2 md:bottom-4 md:left-4 md:right-4 flex gap-2 justify-center items-center py-2 md:py-4 text-lg md:text-xl lg:text-xl xl:text-2xl uppercase bg-linear-to-t from-black/60 to-violet-600/60 rounded-xl hover:bg-violet-600/60 transition-all">
                                Descargar
                                <svg xmlns="http://www.w3.org/2000/svg" height="32px" viewBox="0 -960 960 960" width="32px" fill="#e3e3e3">
                                    <path d="m480-320 160-160-56-56-64 64v-168h-80v168l-64-64-56 56 160 160Zm0 240q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>
    </template>
</div>

<?php endslot() ?>
