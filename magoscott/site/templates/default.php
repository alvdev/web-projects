<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<main class="bg-linear-to-tl from-indigo-950 via-indigo-950 via-50% to-black">
    <div class="container pt-64 *:[p]:text-3xl *:[p]:text-white/80 *:first-of-type:[p]:mt-8">
        <h1 class="font-bold text-8xl leading-snug first-letter:uppercase">
            <?= $page->title() ?>
        </h1>

        <?= $page->text()->kt() ?>
    </div>
</main>

<?php endslot() ?>
