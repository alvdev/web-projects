<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/default') ?>

<main>
    <?php snippet('sections/tickets', ['class' => 'md:pt-28 lg:pt-36']) ?>
</main>

<?php endslot() ?>
