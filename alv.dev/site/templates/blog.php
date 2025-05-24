<?php snippet('base', slots: true) ?>

<?php slot() ?>
<section>
    <div class="container flex items-center">
        <div class="w-1/2 pt-8 pr-16">
            <h1 class="text-10xl font-bold">
                <a href="<?= $page->url() ?>#content" class="hover:text-slate-700">
                    <?= $page->title()->html() ?>
                </a>
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
        <div>
            <?php foreach ($page->children()->listed()->pluck('tags', ',', true) as $tag) : ?>
                <a href="<?= url('blog/tag/' . $tag . '#content')  ?>">
                    <?= $tag ?>
                </a>
            <?php endforeach ?>
        </div>

        <?php if ($articles->isNotEmpty()) : ?>
            <div id="results"
                class="mt-24 grid grid-cols-1 gap-20 rounded-l-none"
                x-merge="append">

                <?php foreach ($articles as $key => $post) : ?>
                    <article class="relative">
                        <div class="flex gap-12 items-center">
                            <div class="w-1/3">
                                <?php if ($post->image()) : ?>
                                    <div class="screen-overlay-picture rounded before:rounded-sm">
                                        <img class="rounded-xs ring-6 ring-slate-100 shadow-2xl/30 shadow-slate-400"
                                            src="<?= $post->image()->url() ?>" alt="<?= $post->image()->alt() ?>"
                                            srcset="<?= $post->image()->srcset([100, 200, 400, 800]) ?>"
                                            height="<?= $post->image()->height() ?>"
                                            width="<?= $post->image()->width() ?>">
                                    </div>
                                <?php else : ?>
                                    <div class="screen-overlay-picture rounded before:rounded-sm">
                                        <img class="rounded-xs ring-6 ring-slate-100 shadow-2xl/30 shadow-slate-400"
                                            src="https://picsum.photos/600/400?random=<?= $key ?>" alt="">
                                    </div>

                                <?php endif ?>
                            </div>

                            <div class="w-2/3">
                                <div class="flex gap-6 text-slate-400 **:[svg]:w-3.5 **:[svg]:h-3.5 **:[svg]:fill-slate-500">
                                    <?php if ($post->date()->isNotEmpty()) : ?>
                                        <small class="flex items-center gap-1">
                                            <?= svg('assets/icons/calendar.svg') ?>
                                            <?= $post->date()->toDate('d.MM.yy') ?>
                                        </small>
                                    <?php endif ?>

                                    <?php if ($post->category()->isNotEmpty()) : ?>
                                        <small class="flex items-center gap-1">
                                            <?= svg('assets/icons/folder.svg') ?>
                                            <?php foreach ($post->category()->split(',') as $category) : ?>
                                                <a href="<?= '/blog/' . $post->category() . '#content' ?>">
                                                    <?= $post->category() ?>
                                                </a>
                                            <?php endforeach ?>
                                        </small>
                                    <?php endif ?>

                                    <?php if ($post->tags()->isNotEmpty()) : ?>
                                        <div class="flex items-center gap-1">
                                            <small class="">
                                                <?= svg('assets/icons/tag.svg') ?>
                                            </small>
                                            <small class="flex">
                                                <?php foreach ($post->tags()->split(',') as $tag) : ?>
                                                    <span class="flex not-last:after:content-[',\00a0']">
                                                        <a class="text-blue-700 hover:text-black lowercase" href="<?= url('blog/tag/' . $tag) ?>">
                                                            <?= $tag ?>
                                                        </a>
                                                    </span>
                                                <?php endforeach ?>
                                            </small>
                                        </div>
                                    <?php endif ?>
                                </div>

                                <a href="<?= $post->url() ?>"
                                    class="group flex gap-8 items-center justify-between font-bold text-xl transition-all">
                                    <h2 class="font-bold first-letter:uppercase text-3xl">
                                        <?= $post->title()->html() ?>
                                    </h2>
                                    <span class="text-black group-hover:animate-ping">
                                        🡭
                                    </span>
                                </a>
                                
                                <div class="mt-8">
                                    <?= $post->metadescription()->isNotEmpty() ? $post->metadescription()->excerpt(155) : $post->summary()->excerpt(155) ?>
                                    <a class="link-red" href="<?= $post->url() ?>">
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
