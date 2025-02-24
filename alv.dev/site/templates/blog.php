<!DOCTYPE html>
<html lang="<?= t('lang', 'es') ?>">

<?php

use Kirby\Cms\Blueprint;

snippet('head') ?>

<body class="debug-screens flex flex-col font-mono">
    <div class="flex">
        <?php snippet('navbar') ?>

        <div>
            <main class="flex gap-24">
                <div>
                    <header class="container mt-20">
                        <div class="flex items-center">
                            <hgroup class="w-1/2">
                                <h1 class="text-10xl font-bold -ml-2">
                                    <?= $page->title()->html() ?>
                                </h1>
                                <p class="text-lg text-balance mt-8 md:text-xl xl:text-2xl">
                                    <?= $page->text()->html() ?>
                                </p>
                            </hgroup>

                            <div class="w-1/2 [&>img]:max-h-[60vh] flex justify-end">
                                <?= asset('assets/images/header-placeholder.webp') ?>
                            </div>

                            <div class="bg-decoration-square"></div>
                        </div>
                    </header>

                    <div class="container pl-0 flex flex-1 mt-24">
                        <div id="content" class="scroll-mt-16 w-5/6">
                            <div class="bg-page bg-white border-y border-r border-gray-900">
                                <div class="container py-16">
                                    <?php snippet('blocks/categories') ?>
                                </div>

                                <?php if ($articles->isNotEmpty()) : ?>
                                    <div id="results"
                                        class="container py-16 border-t border-black grid grid-cols-2 gap-16 rounded-l-none sm:[&>*:nth-child(n+3)]:before:absolute sm:*:before:border-t sm:*:before:border-gray-400 sm:*:before:w-full sm:*:before:-top-8 sm:[&>*:nth-child(odd)]:after:absolute sm:[&>*:nth-child(odd)]:after:border-r sm:[&>*:nth-child(odd)]:after:-right-8 sm:[&>*:nth-child(odd)]:after:top-0 sm:[&>*:nth-child(odd)]:after:h-full sm:[&>*:nth-child(odd)]:after:border-gray-400"
                                        x-merge="append">

                                        <?php foreach ($articles as $key => $article) : ?>
                                            <article class="relative">
                                                <a href="<?= $article->url() ?>"
                                                    class="group flex items-center justify-between mb-8 font-bold text-xl transition-all"
                                                    taget="_blank">
                                                    <h2 class="font-bold first-letter:uppercase text-2xl">
                                                        <?= $article->title()->html() ?>
                                                    </h2>
                                                    <span class="text-black group-hover:animate-ping">
                                                        🡭
                                                    </span>
                                                </a>

                                                <div class="flex justify-between gap-4 mb-8 bg-gray-950 rounded-full px-3 py-1 text-xs text-gray-400">
                                                    <div class="flex gap-4">
                                                        <?php if ($article->category()->isNotEmpty()) : ?>
                                                            <span class="flex items-center gap-1 [&>svg]:w-3 [&>svg]:h-3">
                                                                <?= svg('assets/icons/folder.svg') ?><?= $article->category() ?>
                                                            </span>
                                                        <?php endif ?>

                                                        <?php if ($article->tags()->isNotEmpty()) : ?>
                                                            <span class="flex items-center gap-1 [&>svg]:w-3 [&>svg]:h-3">
                                                                <?= svg('assets/icons/tag.svg') ?><?= $article->tags() ?>
                                                            </span>
                                                        <?php endif ?>
                                                    </div>

                                                    <?php if ($article->date()->isNotEmpty()) : ?>
                                                        <span class="flex items-center gap-1 [&>svg]:w-3 [&>svg]:h-3">
                                                            <?= svg('assets/icons/calendar.svg') ?><?= $article->date()->toDate('j/M/Y') ?>
                                                        </span>
                                                    <?php endif ?>
                                                </div>

                                                <?php if ($article->image()) : ?>
                                                    <img class="float-left rounded-md w-2/5 aspect-video my-2 mr-4 shadow-xl"
                                                        src="<?= $article->image()->url() ?>" alt="<?= $article->image()->alt() ?>"
                                                        srcset="<?= $article->image()->srcset([100, 200, 400, 800]) ?>"
                                                        height="<?= $article->image()->height() ?>"
                                                        width="<?= $article->image()->width() ?>">
                                                <?php else : ?>
                                                    <img class="float-left rounded-md w-2/5 aspect-video my-2 mr-6 shadow-xl"
                                                        src="https://picsum.photos/600/400?random=<?= $key ?>" alt="">

                                                <?php endif ?>
                                                <div class="text-base">
                                                    <?= $article->text()->toBlocks()->excerpt(200) ?>
                                                    <a class="link-red" href="<?= $article->url() ?>">
                                                        🡒<?= t('read_more') ?>
                                                    </a>
                                                </div>
                                            </article>
                                        <?php endforeach ?>
                                    </div>
                                <?php endif ?>
                            </div>

                            <?php snippet('pagination-ajax') ?>
                        </div>

                        <aside class="glass-dark w-1/6 p-0 rounded-l-none">
                            <div class="sticky top-16 my-2 rounded-l-none p-8">
                                <?php foreach ($page->children()->listed()->pluck('tags', ',', true) as $tag) : ?>
                                    <a href="<?= url('blog/tag/' . $tag . '#content')  ?>">
                                        <?= html($tag) ?>
                                    </a>
                                <?php endforeach ?>
                            </div>
                        </aside>
                    </div>
                </div>
            </main>

            <?php snippet('footer') ?>
        </div>
    </div>
</body>

</html>
