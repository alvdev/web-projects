<?php snippet('header') ?>

<main>

    <h1>Blog</h1>

    <?php foreach ($page->children() as $article): ?>
        <?php snippet('article', ['article' => $article]) ?>
    <?php endforeach ?>

</main>

<?php snippet('footer') ?>
