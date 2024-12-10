<!DOCTYPE html>
<html lang="<?= t('lang', 'es') ?>">

<?php snippet('head') ?>

<body class="debug-screens flex flex-col font-mono">
    <div class="flex">
        <?php snippet('navbar') ?>

        <div class="">
            <main class="flex flex-col">
                <header class="container mt-20">
                    <hgroup class="container">
                        <h1 class="text-6xl font-bold">
                            <?= $page->title()->html() ?>
                        </h1>
                        <p class="text-lg mt-8  md:text-xl xl:text-2xl">
                            <?php if ($page->summary()) : ?>
                                <?= $page->summary() ?>
                            <?php endif ?>
                        </p>

                        <div class="bg-decoration-square"></div>
                    </hgroup>

                    <?php if ($page->image()) : ?>
                        <img class="w-full aspect-video rounded-r mt-8 shadow-xl" src="<?= $page->image()->url() ?>"
                            alt="<?= $page->image()->alt() ?>" srcset="<?= $page->image()->srcset([100, 200, 400, 800]) ?>"
                            height="<?= $page->image()->height() ?>" width="<?= $page->image()->width() ?>">
                    <?php endif ?>
                </header>

                <div class="container mt-16 pl-0">
                    <div class="bg-page flex border-y border-gray-900">
                        <div class="prose max-w-full bg-white py-8 flex-1 [&>h2>a]:no-underline [&>h2>a]:scroll-mt-8 [&>*:not(figure)]:px-[5vw] [&>pre]:!ml-[5vw] [&>pre]:!rounded-r-none">
                            <?= $page->text()->toBlocks() ?>
                        </div>

                        <aside class="glass-dark w-2/6 rounded-l-none">
                            <div class="sticky top-8 my-1">
                                <div class="text-base [&>*]:text-white [&_a]:text-green-400"
                                    id="toc">
                                    <h3 class="font-bold uppercase text-lg"><?= t('toc', 'Table of contents') ?></h3>

                                    <?php snippet('blocks/toc') ?>
                                </div>
                            </div>
                        </aside>
                    </div>
                </div>
            </main>

            <aside class="container mt-28">
                <?php if ($page->similar()->count() > 0) : ?>
                    <div class="border border-gray-900 rounded p-16 bg-white bg-opacity-90 backdrop-blur-md">
                        <h3 class="inline-block font-bold text-5xl">
                            <?= t('related_articles') ?>
                        </h3>

                        <div
                            class="mt-12 grid grid-cols-2 gap-16 sm:[&>*:nth-child(n+3)]:before:absolute sm:[&>*]:before:border-t sm:[&>*]:before:border-gray-950 sm:before:[&>*]:w-full sm:before:[&>*]:-top-8 sm:after:[&>*:nth-child(odd)]:absolute sm:after:[&>*:nth-child(odd)]:border-r sm:after:[&>*:nth-child(odd)]:-right-8 sm:after:[&>*:nth-child(odd)]:top-0 sm:after:[&>*:nth-child(odd)]:h-full sm:after:[&>*:nth-child(odd)]:border-gray-950">

                            <?php foreach (
                                $page->similar([
                                    'index' => $page->siblings(false)->listed(),
                                    'fields' => 'tags',
                                    'languageFilter' => true,
                                ])->limit(2) as $article
                            ) : ?>
                                <article class="relative">
                                    <h4 class="text-black-text-800 sm:text-lg xl:text-xl font-bold leading-7 mb-4">
                                        <?= $article->title() ?>
                                    </h4>

                                    <a class="link-red" href="<?= $article->url() ?>">
                                        🡒<?= t('read_more', 'Read more') ?>
                                    </a>
                                </article>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endif ?>
            </aside>

            <?= css('assets/css/prism.css') ?>
            <?= js('assets/js/prism.js') ?>
            <?php snippet('footer') ?>
        </div>
    </div>
</body>

</html>
