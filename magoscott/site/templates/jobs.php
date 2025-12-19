<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/jobs') ?>

<main>
    <?php snippet('sections/timeline', ['class' => 'md:pt-28 lg:pt-36']) ?>
</main>

<?php endslot() ?>
