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
    <section id="intro" class="relative bg-linear-to-bl from-red-600/30 to-indigo-950/30 to-50% pt-36">
        <div class="container relative z-50 flex items-center gap-16 justify-center text-3xl">
            <button x-on:click="selectedTab = 'pictures'" x-bind:class="selectedTab === 'pictures' ? 'bg-white text-black' : ''" class="min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black mt-8 uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Imágenes</button>

            <button x-on:click="selectedTab = 'videos'" x-bind:class="selectedTab === 'videos' ? 'bg-white text-black' : ''" class="min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black mt-8 uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Videos</button>
        </div>
    </section>

    <!-- Videos -->
    <div x-show="selectedTab === 'videos'" x-transition x-cloak class="container mt-36 grid grid-cols-3 gap-8">
        <?php foreach ($page->videoList()->toStructure() as $item): ?>
            <div class="flex gap-8 *:w-full *:h-auto *:rounded-xl *:ring-2 *:ring-indigo-900/50 *:aspect-video">
                <?= $item->video()->toEmbed()->code() ?>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Pictures -->
    <div x-show="selectedTab === 'pictures'" x-transition x-cloak x-data="{
        imageGalleryOpened: false,
        imageGalleryActiveUrl: null,
        imageGalleryImageIndex: null,
        imageGallery: [
            <?php foreach ($page->images() as $image): ?>
            {
                photo: '<?= $image->url() ?>',
                alt: '<?= $image->alt()->or($image->filename()) ?>',
                srcset: '<?= $image->srcset() ?>'
            },
            <?php endforeach ?>
        ],
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

        <div class="container mt-36 opacity-0 duration-1000 delay-300 select-none ease animate-fade-in-view" style="translate: none; rotate: none; scale: none; opacity: 1; transform: translate(0px, 0px);">
            <ul x-ref="gallery" id="gallery" class="mt-8 columns-2 *:break-inside-avoid items-center md:columns-3 lg:columns-4 gap-4">
                <template x-for="(image, index) in imageGallery">
                    <li>
                        <picture class="block rounded-xl border-2 border-indigo-900/50 bg-violet-950 mb-4 overflow-clip">
                            <source
                                :srcset="image.srcset"
                                sizes="<?= $sizes ?>"
                                type="image/avif">
                            <source
                                :srcset="image.srcset"
                                sizes="<?= $sizes ?>"
                                type="image/webp">
                            <img
                                x-on:click="imageGalleryOpen"
                                :src="image.photo"
                                :alt="image.alt"
                                :data-index="index+1"
                                :srcset="image.srcset"
                                sizes="<?= $sizes ?>"
                                width="<?= $image->resize(1800)->width() ?>"
                                height="<?= $image->resize(1800)->height() ?>"
                                class="brightness-125 saturate-110 contrast-110 hover:scale-110 transition-all duration-300"
                                loading="lazy">
                        </picture>
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
</div>

<?php endslot() ?>
