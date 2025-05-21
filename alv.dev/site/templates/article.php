<?php snippet('base', slots: true) ?>

<?php slot('default') ?>
<section class="container flex">
    <hgroup class="pr-16 pt-16 w-9/12">
        <h1 class="text-5xl font-bold text-right">
            <span class="px-4 box-decoration-clone rounded bg-green-500 text-balance leading-normal"><?= $page->title()->html() ?></span>
        </h1>

        <?php if ($page->summary()->isNotEmpty()) : ?>
            <p class="text-lg mt-8  md:text-xl xl:text-2xl">
                <?= $page->summary() ?>
            </p>
        <?php endif ?>
    </hgroup>

    <div class="w-3/12 flex flex-col justify-center bg-black rounded-b text-white p-12">
        <div>Author</div>
        <div>Read time</div>
        <div>Social Share</div>
        <div>Tags</div>
    </div>
</section>

<div class="container flex gap-16 mt-16">
    <div class="prose max-w-full bg-white flex-1 [&>h2>a]:no-underline [&>h2>a]:scroll-mt-8 [&>*:not(figure)]:px-[5vw] [&>pre]:ml-[5vw]! [&>pre]:rounded-r-none!">
        <?php if ($page->image()) : ?>
            <img class="aspect-video rounded mt-4 mb-20! ring-16 ring-slate-100 shadow-xl" src="<?= $page->image()->url() ?>"
                alt="<?= $page->image()->alt() ?>" srcset="<?= $page->image()->srcset([100, 200, 400, 800]) ?>"
                height="<?= $page->image()->height() ?>" width="<?= $page->image()->width() ?>">
        <?php else: ?>
            <div class="screen-overlay px-0!">
                <img class="aspect-video max-w-full max-h-full rounded mt-4 mb-20! ring-16 ring-slate-100 shadow-2xl/30 shadow-slate-400 p-0" src="https://picsum.photos/900/600" alt="">
            </div>
        <?php endif ?>

        <?= $page->text()->toBlocks() ?>
    </div>

    <aside class="w-3/12 bg-slate-100 p-8 rounded">
        <div class="sticky top-8 my-1">
            <div class="text-base *:text-black [&_a]:text-green-400"
                id="toc">
                <h3 class="font-bold uppercase text-lg"><?= t('toc', 'Table of contents') ?></h3>

                <?php snippet('blocks/toc') ?>
            </div>
        </div>
    </aside>
</div>

<?php if ($page->similar()->count() > 0) : ?>
    <aside class="container mt-28">
        <div class="border border-gray-900 rounded-xs p-16 bg-white/90 backdrop-blur-md">
            <h3 class="inline-block font-bold text-5xl">
                <?= t('related_articles') ?>
            </h3>

            <div
                class="mt-12 grid grid-cols-2 gap-16 sm:[&>*:nth-child(n+3)]:before:absolute sm:*:before:border-t sm:*:before:border-gray-950 sm:*:before:w-full sm:*:before:-top-8 sm:[&>*:nth-child(odd)]:after:absolute sm:[&>*:nth-child(odd)]:after:border-r sm:[&>*:nth-child(odd)]:after:-right-8 sm:[&>*:nth-child(odd)]:after:top-0 sm:[&>*:nth-child(odd)]:after:h-full sm:[&>*:nth-child(odd)]:after:border-gray-950">

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
    </aside>
<?php endif ?>
<?php endslot() ?>
