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

                            <div class="fixed -z-10 right-0 -top-[25vw] block w-[50vw] h-[50vw] border-t-transparent border-b-transparent border-r-[#00ff77] border-t-[50vw] border-b-[50vw] border-r-[50vw]">
                            </div>
                        </div>
                    </header>

                    <div class="container pl-0 flex flex-1 mt-24">
                        <div id="content" class="scroll-mt-16 w-5/6">
                            <div class="bg-white bg-opacity-90 backdrop-blur-md border-y border-r border-gray-900 rounded-r">
                                <div class="container py-16">
                                    <?php snippet('blocks/categories') ?>
                                </div>

                                <?php if ($articles->isNotEmpty()) : ?>
                                    <div id="results"
                                        class="container py-16 border-t border-black grid grid-cols-2 gap-16 rounded-l-none sm:[&>*:nth-child(n+3)]:before:absolute sm:[&>*]:before:border-t sm:[&>*]:before:border-gray-950 sm:before:[&>*]:w-full sm:before:[&>*]:-top-8 sm:after:[&>*:nth-child(odd)]:absolute sm:after:[&>*:nth-child(odd)]:border-r sm:after:[&>*:nth-child(odd)]:-right-8 sm:after:[&>*:nth-child(odd)]:top-0 sm:after:[&>*:nth-child(odd)]:h-full sm:after:[&>*:nth-child(odd)]:border-gray-950"
                                        x-merge="append">

                                        <?php foreach ($articles as $key => $article) : ?>
                                            <article class="relative">
                                                <div class="flex justify-between gap-4 text-sm text-gray-500">
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

                                                <h2 class="font-bold mb-3 first-letter:uppercase sm:text-lg xl:text-2xl">
                                                    <?= $article->title()->html() ?>
                                                </h2>

                                                <img class="float-left rounded-md w-2/5 aspect-video my-2 mr-6"
                                                    src="https://picsum.photos/600/400?random=<?= $key ?>" alt="">
                                                <div><?= $article->text()->toBlocks()->excerpt(200) ?>
                                                    <a class="link-red" href="<?= $article->url() ?>">
                                                        🡒<?= t('read_more') ?>
                                                    </a>
                                                </div>
                                            </article>
                                        <?php endforeach ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>

                        <aside class="w-1/6">
                            <div class="glass-dark sticky top-16 my-2 rounded-l-none p-8">
                                <?php foreach ($page->children()->listed()->pluck('tags', ',', true) as $tag) : ?>
                                    <a href="<?= url('blog/tag/' . $tag . '#content')  ?>">
                                        <?= html($tag) ?>
                                    </a>
                                <?php endforeach ?>
                            </div>
                        </aside>
                    </div>

                    <div class="container w-5/6 py-0">
                        <?php snippet('pagination-ajax') ?>
                    </div>

                </div>
            </main>

            <?php snippet('footer') ?>
        </div>
    </div>
</body>

</html>
