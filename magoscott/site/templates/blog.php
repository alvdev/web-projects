<?php snippet('base', slots: true) ?>
<?php slot('default') ?>

<?php snippet('sections/headers/default') ?>

<?php
$sizes = "
        (min-width: 1200px) 25vw,
        (min-width: 900px) 33vw,
        (min-width: 600px) 50vw,
        100vw";
?>

<main class="pt-16 md:pt-28 lg:pt-36 bg-linear-to-bl from-red-600/30 via-indigo-950/30">
    <div class="container grid md:grid-cols-2 lg:grid-cols-3 gap-16">
        <?php foreach ($page->children()->listed() as $post): ?>
            <article class="grid">
                <a href="<?= $post->url() ?>" class="group">
                    <picture class="*:[img]:object-cover *:[img]:aspect-video *:[img]:rounded-2xl *:[img]:group-hover:rounded-[4rem] *:[img]:transition-all *:[img]:border-2 *:[img]:border-indigo-900/50 *:[img]:bg-violet-950">
                        <source
                            srcset="<?= $post->cover()->toFile()->srcset('avif') ?>"
                            sizes="<?= $sizes ?>"
                            type="image/avif">
                        <source
                            srcset="<?= $post->cover()->toFile()->srcset('webp') ?>"
                            sizes="<?= $sizes ?>"
                            type="image/webp">
                        <img
                            alt="<?= $post->cover()->toFile()->alt() ?>"
                            src="<?= $post->cover()->toFile()->resize(300)->url() ?>"
                            srcset="<?= $post->cover()->toFile()->srcset() ?>"
                            sizes="<?= $sizes ?>"
                            width="<?= $post->cover()->toFile()->resize(1800)->width() ?>"
                            height="<?= $post->cover()->toFile()->resize(1800)->height() ?>">
                    </picture>

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
