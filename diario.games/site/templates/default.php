<?php snippet('header') ?>

<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold text-text mb-4"><?= $page->title() ?></h1>
    <div class="text-text leading-relaxed">
        <?= $page->text()->kt() ?>
    </div>
</div>

<?php snippet('footer') ?>
