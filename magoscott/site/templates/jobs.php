<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/jobs') ?>

<main>
    <?php snippet('sections/timeline', ['class' => 'pt-36']) ?>
</main>

<?php endslot() ?>
