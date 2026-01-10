<?php 
$sizes = "100vw";
?>

<header id="home-header" class="bg-linear-to-tl from-red-600/30 via-indigo-950/30 via-50% to-black/30">
    <div class="absolute top-0 -z-10 w-full opacity-90 *:mask-b-to-90% *:[img]:w-full *:[img]:object-cover *:[img]:aspect-video">
        <?php if ($image = $page->cover()->toFile()): ?>
            <picture>
                <!-- Mobile: Vertical 1:3 Crop (Strict) -->
                <source media="(max-width: 600px)" srcset="<?= $image->staticSrcset('avif-mobile') ?>" type="image/avif">
                <source media="(max-width: 600px)" srcset="<?= $image->staticSrcset('webp-mobile') ?>" type="image/webp">

                <!-- Desktop: Landscape -->
                <source srcset="<?= $image->staticSrcset('avif') ?>" type="image/avif" sizes="(min-width: 1024px) 50vw, 100vw">
                <source srcset="<?= $image->staticSrcset('webp') ?>" type="image/webp" sizes="(min-width: 1024px) 50vw, 100vw">
                <img
                    srcset="<?= $image->staticSrcset('default') ?>"
                    sizes="(min-width: 1024px) 50vw, 100vw"
                    src="<?= $image->staticUrl(['width' => 1200]) ?>"
                    width="1200"
                    height="675"
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
