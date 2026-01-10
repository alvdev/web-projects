<?php

/**
 * Reusable lightweight gallery
 *
 * @param Kirby\Cms\Files $images
 * @param int $thumbSize
 * @param int $fullSize
 */
$thumbSize ??= 600;
$fullSize  ??= 1800;

if (!$images || $images->count() === 0) {
    return;
}
?>

<div
    x-data="gallery()"
    x-show="visible"
    x-cloak
    @keyup.right.window="next"
    @keyup.left.window="prev"
    class="w-full h-full select-none">
    <div class="container mt-16 md:mt-28 lg:mt-36">
        <ul
            x-ref="gallery"
            class="mt-8 columns-2 md:columns-3 lg:columns-4 gap-4">
            <?php foreach ($images as $image): ?>
                <?php snippet('gallery-item', ['image' => $image]) ?>
            <?php endforeach ?>
        </ul>
    </div>

    <!-- Lightbox -->
    <template x-teleport="body">
        <div
            x-show="opened"
            x-cloak
            @click="close"
            class="fixed inset-0 z-50 flex items-center justify-center bg-violet-500/70 cursor-zoom-out backdrop-blur-xs">
            <div class="relative">
                <img
                    :src="active"
                    class="max-w-[90vw] max-h-[90vh] rounded-2xl border-4 border-white/50 shadow-xl">

                <!-- Prev -->
                <button
                    @click.stop="prev"
                    class="absolute -left-4 md:-left-8 top-1/2 -translate-y-1/2 w-8 h-8 md:w-16 md:h-16 bg-black/80 hover:bg-black text-white rounded-full flex items-center justify-center transition-colors shadow-lg group ring-2 md:ring-4 ring-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-6 md:h-6 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Next -->
                <button
                    @click.stop="next"
                    class="absolute -right-4 md:-right-8 top-1/2 -translate-y-1/2 w-8 h-8 md:w-16 md:h-16 bg-black/80 hover:bg-black text-white rounded-full flex items-center justify-center transition-colors shadow-lg group ring-2 md:ring-4 ring-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>
