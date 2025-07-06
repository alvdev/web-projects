<?php snippet('layouts/base', slots: true) ?>

<main class="pt-56">
    <article class="article">
        <section class="hero container bg-ellipse-b-blue flex items-center gap-16 pt-4">
            <?php if ($cover = $page->cover()->toFile()): ?>
                <div class="relative -mb-16 z-10 w-2/3 p-1 bg-linear-to-b from-blue-300/90 to-white/50 rounded-xl backdrop-blur-sm shadow-slate-300/50 shadow-sm">
                    <img srcset="<?= $cover->srcset() ?>"
                        class="relative z-0 rounded-lg aspect-video object-cover" alt="">

                    <hgroup>
                        <h1 class="absolute z-20 inset-x-1 top-1 text-white bg-gradient-to-b from-black/80 rounded-t-lg overflow-clip font-bold text-6xl px-12 pt-6 pb-16">
                            <?= $page->title() ?>
                        </h1>

                        <?php if ($page->summary()->isNotEmpty()): ?>
                            <h1 class="absolute z-20 inset-x-1 bottom-1 px-12 pt-16 pb-8 text-white bg-gradient-to-t from-blue-700/80 rounded-b-lg overflow-clip first-letter:uppercase text-4xl font-bold text-pretty leading-[1.15]"><?= $page->summary()->kirbytextinline() ?></h1>
                        <?php endif ?>
                    </hgroup>
                </div>

                <div class="sticky top-8 mb-8 w-1/3 grid gap-4 pl-16 text-white">
                    <div>
                        Author: <?= $page->author()->kirbytextinline() ?>
                    </div>
                    <div>
                        Published on: <?= $page->date()->toDate('j \d\e F, Y') ?>
                    </div>
                    <div>
                        Categories: <?= $site->categories()->join(', ') ?><br>
                        Global categories: 
                        <?= page('blog')->content()->get('categories') ?>
                    </div>
                    <div>
                        Tags: <?= $page->tags() ?>
                    </div>
                    <div>
                        Reading time: <?= $page->readingTime() ?> minutes
                    </div>
                    <divi>
                        Share this article:
                        <div>
                            <span>LinkedIn</span>
                            <span>X</span>
                            <span>Facebook</span>
                            <span>Reddit</span>
                        </div>
                    </divi>
                </div>
            <?php endif ?>
        </section>

        <div class="main-content">
            <div class="container mt-24 flex gap-16">
                <div class="w-2/3 prose-xl px-2 pt-8 pb-24 text-pretty">
                    <?= $page->body()->toBlocks() ?>
                </div>

                <aside class="bg-divider w-1/3 py-16">
                    <div class="pl-16">
                        <div>
                            Block quotes are a great way to highlight important information or quotes from the article. You can use them to draw attention to key points or to provide additional context for your readers.
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </article>
</main>

<?php endsnippet() ?>
