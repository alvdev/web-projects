<?php snippet('layouts/base', slots: true) ?>

<main class="pt-48">
    <div class="main-content">
        <h1>
            <?= page()->title()->html() ?>
        </h1>
        <?php foreach (page()->body()->toLayouts() as $layout): ?>
            <?php foreach ($layout->columns() as $column): ?>
                <?= $column->blocks() ?>
            <?php endforeach ?>
        <?php endforeach ?>
    </div>
</main>

<?php endsnippet() ?>
