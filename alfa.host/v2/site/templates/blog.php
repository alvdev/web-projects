<?php snippet('layouts/base', slots: true) ?>

<main class="pt-48">
    <?php snippet('sections/heroBlog') ?>

    <section class="main-content">
        <div class="container mt-16">
            <div class="border-2 border-red-500">
                <?= $page->categories() ?>xxxx
            </div>
            <div class="border-2 border-blue-500">
                <?= $site->children()->template('blog')->categories() ?>
            </div>
            <?php foreach (page('blog')->children()->listed()->sortBy('date', 'desc') as $post): ?>
                <article>
                    <a href="<?= $post->url() ?>" class="text-emerald-600 font-bold">
                        <?= $post->title() ?>
                    </a>

                    <?= $post->body()->toBlocks() ?>
                </article>
            <?php endforeach ?>
        </div>
    </section>
</main>

<?php endsnippet() ?>
