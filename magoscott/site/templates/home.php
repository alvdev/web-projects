<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/home') ?>
<?php snippet('sections/intro', ['class' => 'pt-16 md:pt-28 lg:pt-36']) ?>
<?php snippet('sections/shows', ['class' => 'mt-24 md:mt-28 lg:mt-36']) ?>
<?php snippet('sections/tours', ['class' => 'mt-24 md:mt-28 lg:mt-36']) ?>

<?php endslot() ?>
