<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/home') ?>
<?php snippet('sections/intro', ['class' => 'pt-16 pt-44 md:pt-48 lg:pt-56']) ?>
<?php snippet('sections/shows', ['class' => 'mt-24 md:mt-28 lg:mt-36']) ?>
<?php snippet('sections/tours', ['class' => 'mt-24 md:mt-28 lg:mt-36']) ?>

<?php endslot() ?>
