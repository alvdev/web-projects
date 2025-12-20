<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<main>
    <section class="bg-linear-to-tl from-red-600 via-indigo-950 via-50% to-black">
        <div class="container pt-28 md:pt-36 lg:pt-64 flex flex-col lg:flex-row items-center gap-8 md:gap-16 lg:gap-32 lg:*:w-1/2">
            <div>
                <hgroup class="*:[h1]:text-4xl md:*:[h1]:text-7xl *:[h1]:font-bold *:[h1]:leading-none *:[h1]:text-balance">
                    <h1><?= $page->headerTitle() ?></h1>
                </hgroup>

                <div class="*:[p]:mt-8 *:[p]:text-xl md:*:[p]:text-2xl *:[p]:text-pretty *:[p]:text-white/80">
                    <?= $page->description() ?>
                </div>

                <?php $buyLink = $page->buyButton()->toObject() ?>
                <a href="<?= $buyLink->link()->toUrl() ?>"
                    class="mt-8 text-2xl inline-block min-w-2/3 border-2 uppercase whitespace-nowrap text-center rounded-full px-8 pt-4 pb-4.75 hover:border-fuchsia-500 hover:text-fuchsia-500">
                    <?= $buyLink->anchor() ?>
                </a>
            </div>

            <div class="sticky top-36">
                <?= $page->cover()->toFile() ?>
            </div>
        </div>
    </section>

    <section class="pt-8 md:pt-16 lg:pt-24 bg-linear-to-bl from-red-600 to-indigo-950 to-50%">
        <div class="container">
            <h2 class="text-4xl font-bold text-shadow-sm text-shadow-black/50 md:text-7xl text-balance pt-16 md:pt-24 lg:pt-32">
                <?= $page->videoTitle() ?>
            </h2>

            <div class="mt-16 *:w-full *:h-auto *:aspect-video *:rounded-2xl *:border-4 *:border-violet-800/20">
                <?= $page->video()->toEmbed()->code() ?>
            </div>
        </div>
    </section>
</main>

<?php endslot() ?>
