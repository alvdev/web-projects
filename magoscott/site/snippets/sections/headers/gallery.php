<?php 
$sizes = "100vw";
?>

<header id="home-header" class="bg-linear-to-tl from-red-600/30 via-indigo-950/30 via-50% to-black/30">
    <div class="absolute top-0 -z-10 w-full opacity-90 *:mask-b-to-90% *:[img]:w-full *:[img]:object-cover *:[img]:aspect-video">
        <?php if ($image = $page->cover()->toFile()): ?>
            <picture>
                <!-- Mobile: Vertical 1:3 Crop (Strict) -->
                <source media="(max-width: 600px)" srcset="<?= $image->srcset('avif-mobile') ?>" sizes="100vw">
                <source media="(max-width: 600px)" srcset="<?= $image->srcset('webp-mobile') ?>" sizes="100vw">

                <!-- Desktop: Landscape -->
                <source srcset="<?= $image->srcset('avif') ?>" type="image/avif" sizes="(min-width: 1024px) 50vw, 100vw">
                <source srcset="<?= $image->srcset('webp') ?>" type="image/webp" sizes="(min-width: 1024px) 50vw, 100vw">
                <img
                    srcset="<?= $image->srcset('default') ?>"
                    sizes="<?= $sizes ?>"
                    src="<?= $image->resize(600)->url() ?>"
                    width="<?= $image->width() ?>"
                    height="<?= $image->height() ?>"
                    alt="Scott - Magoscott"
                    fetchpriority="high"
                    class="w-full h-full object-cover">
            </picture>
        <?php endif ?>
    </div>

    <div class="container relative pt-28 md:pt-36 lg:pt-64">
        <hgroup class="text-center text-balance lg:max-w-2/3 mx-auto text-shadow-sm text-shadow-black">
            <h1 class="text-5xl md:text-7xl font-bold leading-none">
                <?= $page->headerTitle() ?>
            </h1>
            <p class="mt-8 text-2xl md:text-4xl">
                <?= $page->description() ?>
            </p>
        </hgroup>
    </div>
</header>
