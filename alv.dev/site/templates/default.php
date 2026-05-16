<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('header') ?>
<!-- Blocks from the visual panel -->

<!-- Separator -->
<div class="container mt-24">
    <div class="relative -z-50 border-6 border-gray-950 rounded-xs">
    </div>
</div>

<div class="container">
    <?= $page->text()->toBlocks() ?>
</div>

<div>
    <?php snippet('sections/skills-grid') ?>
    <?php snippet('sections/projects') ?>
    <?php snippet('sections/contact-form') ?>
    <?php snippet('sections/about-me') ?>
    <?php snippet('sections/latest-posts') ?>
</div>
<?php endslot() ?>
