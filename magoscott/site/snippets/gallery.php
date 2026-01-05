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
    class="w-full h-full select-none"
>
    <div class="container mt-16 md:mt-28 lg:mt-36">
        <ul
            x-ref="gallery"
            class="mt-8 columns-2 md:columns-3 lg:columns-4 gap-4"
        >
            <?php foreach ($images as $index => $image): ?>
                <?php
                    $thumb = $image->resize($thumbSize);
                    $full  = $image->resize($fullSize);
                ?>
                <li class="break-inside-avoid mb-4">
                    <img
                        src="<?= $thumb->url() ?>"
                        data-full="<?= $full->url() ?>"
                        data-index="<?= $index ?>"
                        alt="<?= $image->alt()->or($image->filename()) ?>"
                        loading="lazy"
                        width="<?= $thumb->width() ?>"
                        height="<?= $thumb->height() ?>"
                        class="rounded-xl cursor-zoom-in hover:scale-105 transition"
                        @click="open($event)"
                    >
                </li>
            <?php endforeach ?>
        </ul>
    </div>

    <!-- Lightbox -->
    <template x-teleport="body">
        <div
            x-show="opened"
            x-cloak
            @click="close"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 cursor-zoom-out"
        >
            <div class="relative">
                <img
                    :src="active"
                    class="max-w-[90vw] max-h-[90vh] rounded-2xl"
                >

                <!-- Prev -->
                <button
                    @click.stop="prev"
                    class="absolute left-[-3rem] top-1/2 -translate-y-1/2 text-white text-4xl"
                >
                    ‹
                </button>

                <!-- Next -->
                <button
                    @click.stop="next"
                    class="absolute right-[-3rem] top-1/2 -translate-y-1/2 text-white text-4xl"
                >
                    ›
                </button>
            </div>
        </div>
    </template>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('gallery', () => ({
        opened: false,
        active: null,
        index: 0,
        visible: true,

        open(e) {
            const img = e.target
            this.index = Number(img.dataset.index)
            this.active = img.dataset.full
            this.opened = true
        },

        close() {
            this.opened = false
            this.active = null
        },

        next() {
            const imgs = this.$refs.gallery.querySelectorAll('img')
            this.index = (this.index + 1) % imgs.length
            this.active = imgs[this.index].dataset.full
        },

        prev() {
            const imgs = this.$refs.gallery.querySelectorAll('img')
            this.index = (this.index - 1 + imgs.length) % imgs.length
            this.active = imgs[this.index].dataset.full
        }
    }))
})
</script>
