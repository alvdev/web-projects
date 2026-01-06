<?php snippet('base', slots: true) ?>
<?php slot('default') ?>

<?php snippet('sections/headers/default') ?>

<?php
$sizes = "
        (min-width: 1200px) 25vw,
        (min-width: 900px) 33vw,
        (min-width: 600px) 50vw,
        100vw";

$srcset169Avif = [
    '300w'  => ['width' => 300, 'height' => 169, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
    '600w'  => ['width' => 600, 'height' => 338, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
    '900w'  => ['width' => 900, 'height' => 506, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
    '1200w' => ['width' => 1200, 'height' => 675, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35],
    '1800w' => ['width' => 1800, 'height' => 1013, 'crop' => true, 'format' => 'avif', 'quality' => 70, 'sharpen' => 35]
];

$srcset169Webp = [
    '300w'  => ['width' => 300, 'height' => 169, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
    '600w'  => ['width' => 600, 'height' => 338, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
    '900w'  => ['width' => 900, 'height' => 506, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
    '1200w' => ['width' => 1200, 'height' => 675, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35],
    '1800w' => ['width' => 1800, 'height' => 1013, 'crop' => true, 'format' => 'webp', 'quality' => 80, 'sharpen' => 35]
];

$srcset169Default = [
    '300w'  => ['width' => 300, 'height' => 169, 'crop' => true],
    '600w'  => ['width' => 600, 'height' => 338, 'crop' => true],
    '900w'  => ['width' => 900, 'height' => 506, 'crop' => true],
    '1200w' => ['width' => 1200, 'height' => 675, 'crop' => true],
    '1800w' => ['width' => 1800, 'height' => 1013, 'crop' => true]
];
?>

<main class="pt-16 md:pt-28 lg:pt-36 bg-linear-to-bl from-red-600/30 via-indigo-950/30">
    <div class="container grid md:grid-cols-2 lg:grid-cols-3 gap-16">
        <?php foreach ($page->children()->listed() as $post): ?>
            <article class="grid">
                <a href="<?= $post->url() ?>" class="group">
                    <?php if ($post->cover()->isNotEmpty()): ?>
                        <picture class="*:[img]:object-cover *:[img]:aspect-video *:[img]:rounded-2xl *:[img]:group-hover:rounded-[4rem] *:[img]:transition-all *:[img]:border-2 *:[img]:border-indigo-900/50 *:[img]:bg-violet-950">
                            <source
                                srcset="<?= $post->cover()->toFile()->srcset($srcset169Avif) ?>"
                                sizes="<?= $sizes ?>"
                                type="image/avif">
                            <source
                                srcset="<?= $post->cover()->toFile()->srcset($srcset169Webp) ?>"
                                sizes="<?= $sizes ?>"
                                type="image/webp">
                            <img
                                alt="<?= $post->cover()->toFile()->alt() ?>"
                                src="<?= $post->cover()->toFile()->crop(600, 338)->url() ?>"
                                srcset="<?= $post->cover()->toFile()->srcset($srcset169Default) ?>"
                                sizes="<?= $sizes ?>"
                                width="1800"
                                height="1013">
                        </picture>
                    <?php else: ?>
                        <?php $defaultCover = asset('/assets/images/headers/post-placeholder.webp'); ?>
                        <picture class="*:[img]:object-cover *:[img]:aspect-video *:[img]:rounded-2xl *:[img]:group-hover:rounded-[4rem] *:[img]:transition-all *:[img]:border-2 *:[img]:border-indigo-900/50 *:[img]:bg-violet-950">
                            <source
                                srcset="<?= $defaultCover->srcset($srcset169Avif) ?>"
                                sizes="<?= $sizes ?>"
                                type="image/avif">
                            <source
                                srcset="<?= $defaultCover->srcset($srcset169Webp) ?>"
                                sizes="<?= $sizes ?>"
                                type="image/webp">
                            <img
                                alt="Placeholder image for <?= $post->title() ?>"
                                src="<?= $defaultCover->crop(600, 338)->url() ?>"
                                srcset="<?= $defaultCover->srcset($srcset169Default) ?>"
                                sizes="<?= $sizes ?>"
                                width="1800"
                                height="1013">
                        </picture>
                    <?php endif ?>

                    <h2 class="mt-8 relative pl-12 text-3xl group-hover:text-violet-300 before:absolute before:left-0 before:top-1 before:w-8 before:h-8 before:bg-red-500 before:rounded-full">
                        <?= $post->title() ?>
                    </h2>
                </a>

                <p class="mt-8 text-white/80">
                    <?= $post->summary()->listed()->excerpt(200) ?? $post->text()->excerpt(200) ?>
                </p>

                <a href="<?= $post->url() ?>" class="ml-auto font-semibold uppercase text-red-400 hover:text-red-500">Seguir leyendo →</a>
            </article>
        <?php endforeach ?>
    </div>
</main>

<?= $page->text()->kirbytext() ?>

<?php endslot() ?>
