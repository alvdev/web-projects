<?php snippet('layouts/base', slots: true) ?>

<main class="pt-48">
    <?= snippet('sections/heroHome') ?>

    <div class="main-content">
        <?= snippet('sections/featuresWithPicture') ?>
        <?= snippet('sections/quoteWithPicture2Cols') ?>
        <?= snippet('sections/servicePlans') ?>
        <?= snippet('sections/featureCards') ?>
        <?= snippet('sections/featureCardsWithPicture') ?>
        <?= snippet('components/faqs') ?>
    </div>
</main>

<?php endsnippet() ?>
