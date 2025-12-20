<?php snippet('base', slots: true) ?>
<?php slot('default') ?>

<?php
$sizes = "
        (min-width: 1200px) 25vw,
        (min-width: 900px) 33vw,
        (min-width: 600px) 50vw,
        100vw";
?>

<main class="bg-linear-to-tl from-indigo-950 via-indigo-950 via-50% to-black">
    <article class="container w-11/12 md:w-10/12 lg:w-4/5 xl:w-3/5 pt-64">
        <div class="relative pl-4 md:pl-8 before:absolute before:left-0 before:w-0.5 md:before:w-1 before:h-full before:bg-red-600 before:rounded-full">
            <div class="absolute w-14 md:w-full -left-7 md:-left-[50px] -top-4 *:border-3 md:*:border-4 *:border-red-600 *:rounded-full *:shadow-[0_0_50px_rgb(255_255_255/30%)]">
                <?= asset('assets/images/scott-face.webp')->crop(100, 100) ?>
            </div>

            <h1 class="pl-8 md:pl-12 text-4xl md:text-6xl lg:text-7xl font-bold text-shadow-lg leading-none text-balance first-letter:uppercase">
                <?= $page->title() ?>
            </h1>

            <?php if ($summary = $page->summary()): ?>
                <p class="mt-4 md:mt-8 text-xl md:text-2xl lg:text-3xl text-white/80"><?= $summary ?></p>
            <?php endif ?>
        </div>

        <?php if ($image = $page->cover()->toFile()): ?>
            <picture>
                <source
                    srcset="<?= $image->srcset('avif') ?>"
                    sizes="<?= $sizes ?>"
                    type="image/avif">
                <source
                    srcset="<?= $image->srcset('webp') ?>"
                    sizes="<?= $sizes ?>"
                    type="image/webp">
                <img class="mt-16 rounded-2xl ring-4 ring-violet-500/20 aspect-video object-cover w-full bg-violet-950"
                    alt="<?= $image->alt() ?>"
                    src="<?= $image->resize(300)->url() ?>"
                    srcset="<?= $image->srcset() ?>"
                    sizes="<?= $sizes ?>"
                    width="<?= $image->resize(1800)->width() ?>"
                    height="<?= $image->resize(1800)->height() ?>">
            </picture>
        <?php endif ?>

        <div class="relative mt-16 prose prose-2xl md:prose-p:text-2xl lg:prose-p:text-3xl text-5xl text-pretty pl-4 md:pl-8 text-white/90 before:absolute before:left-0 before:w-0.5 md:before:w-1 before:h-full before:bg-red-600 before:rounded-full">
            <?= $page->text()->toBlocks() ?>
        </div>
    </article>
</main>

<?php endslot() ?>
