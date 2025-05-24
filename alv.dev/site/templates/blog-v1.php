<?php snippet('base', slots: true) ?>

<?php slot() ?>
<section>
    <div class="container flex items-center">
        <div class="w-1/2 pt-8 pr-16">
            <h1 class="text-10xl font-bold">
                <?= $page->title()->html() ?>
            </h1>
            <p class="text-lg text-balance mt-8 md:text-xl xl:text-2xl">
                <?= $page->text()->html() ?>
            </p>
        </div>

        <div class="screen-overlay w-1/2 [&>img]:max-h-[80vh]  self-stretch bg-green-500 h-auto">
            <!-- <?= asset('assets/images/header-placeholder.webp') ?> -->
        </div>
    </div>

    <div class="container py-16">
        <?php snippet('blocks/categories') ?>
    </div>
</section>


<div class="container flex flex-1 mb-16">
    <div id="content" class="scroll-mt-16 relative">
        <div class="">
            <?php foreach ($page->children()->listed()->pluck('tags', ',', true) as $tag) : ?>
                <a href="<?= url('blog/tag/' . $tag . '#content')  ?>">
                    <?= html($tag) ?>
                </a>
            <?php endforeach ?>
        </div>

        <?php if ($articles->isNotEmpty()) : ?>
            <div id="results"
                class="mt-24 grid grid-cols-2 gap-16 rounded-l-none sm:[&>*:nth-child(n+3)]:before:absolute sm:*:before:border-t sm:*:before:border-gray-400 sm:*:before:w-full sm:*:before:-top-8 sm:[&>*:nth-child(odd)]:after:absolute sm:[&>*:nth-child(odd)]:after:border-r sm:[&>*:nth-child(odd)]:after:-right-8 sm:[&>*:nth-child(odd)]:after:top-0 sm:[&>*:nth-child(odd)]:after:h-full sm:[&>*:nth-child(odd)]:after:border-gray-400"
                x-merge="append">

                <?php foreach ($articles as $key => $article) : ?>
                    <article class="relative">
                        <div class="flex gap-6 text-slate-400 **:[svg]:w-3.5 **:[svg]:h-3.5 **:[svg]:fill-slate-500">
                            <?php if ($article->date()->isNotEmpty()) : ?>
                                <small class="flex items-center gap-1">
                                    <?= svg('assets/icons/calendar.svg') ?>
                                    <?= $article->date()->toDate('d.MM.yy') ?>
                                </small>
                            <?php endif ?>

                            <?php if ($article->category()->isNotEmpty()) : ?>
                                <small class="flex items-center gap-1">
                                    <?= svg('assets/icons/folder.svg') ?>
                                    <?php foreach($article->category()->split(',') as $category) : ?>
                                    <a href="<?= '/blog/' . $article->category() . '#content' ?>">
                                        <?= $article->category() ?>
                                    </a>
                                    <?php endforeach ?>
                                </small>
                            <?php endif ?>

                            <?php if ($article->tags()->isNotEmpty()) : ?>
                                <div class="flex items-center gap-1">
                                    <small class="">
                                        <?= svg('assets/icons/tag.svg') ?>
                                    </small>
                                    <small class="flex">
                                        <?php foreach ($article->tags()->split(',') as $tag) : ?>
                                            <span class="flex not-last:after:content-[',\00a0']">
                                                <a class="text-blue-700 hover:text-black lowercase" href="<?= url('blog/tag/' . $tag) ?>">
                                                    <?= trim($tag, ' ') ?>
                                                </a>
                                            </span>
                                        <?php endforeach ?>
                                    </small>
                                </div>
                            <?php endif ?>
                        </div>

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

                        <div class="grid grid-cols-2 gap-6 items-center">
                            <?php if ($article->image()) : ?>
                                <div class="screen-overlay-picture rounded before:rounded-sm">
                                    <img class="rounded-xs ring-6 ring-slate-100 shadow-2xl/30 shadow-slate-400"
                                        src="<?= $article->image()->url() ?>" alt="<?= $article->image()->alt() ?>"
                                        srcset="<?= $article->image()->srcset([100, 200, 400, 800]) ?>"
                                        height="<?= $article->image()->height() ?>"
                                        width="<?= $article->image()->width() ?>">
                                </div>
                            <?php else : ?>
                                <div class="screen-overlay-picture rounded before:rounded-sm">
                                    <img class="rounded-xs ring-6 ring-slate-100 shadow-2xl/30 shadow-slate-400"
                                        src="https://picsum.photos/600/400?random=<?= $key ?>" alt="">
                                </div>

                            <?php endif ?>
                            <div>
                                <div class="text-base line-clamp-7 hyphens-auto">
                                    <?= $article->text()->toBlocks()->excerpt(200) ?>
                                    <a class="link-red" href="<?= $article->url() ?>">
                                        🡒<?= t('read_more') ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php snippet('pagination-ajax') ?>
    </div>
</div>
<?php endslot() ?>
