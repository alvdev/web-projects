<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php $games = $page->children()->listed() ?>
<?php $firstGame = $games->first() ?>
<div x-data="{selectedTab: '<?= $firstGame ? $firstGame->slug() : '' ?>'}">
    <section id="intro" class="relative pt-28 md:pt-48 bg-linear-to-bl from-indigo-900 via-indigo-950 via-50% to-black">
        <div class="container relative w-full flex flex-col md:flex-row md:flex-wrap items-center gap-8 lg:gap-16 justify-center text-xl md:text-2xl lg:text-3xl">
            <?php foreach ($games as $game): ?>
                <button x-on:click="selectedTab = '<?= $game->slug() ?>'" x-bind:class="selectedTab === '<?= $game->slug() ?>' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3"><?= $game->title() ?></button>
            <?php endforeach ?>
        </div>
    </section>

    <div class="bg-linear-to-tl from-red-600 via-indigo-950 via-50% to-black">
        <?php foreach ($games as $game): ?>
            <div x-show="selectedTab === '<?= $game->slug() ?>'" x-transition x-cloak>
                <section class="bg-linear-to-tl from-red-600 via-indigo-950 via-50% to-black">
                    <div class="container pt-20 lg:pt-24 xl:pt-28 flex flex-col lg:flex-row items-center gap-8 md:gap-16 lg:gap-32 lg:*:w-1/2">
                        <div>
                            <hgroup class="*:[h1]:text-4xl md:*:[h1]:text-7xl *:[h1]:font-bold *:[h1]:leading-none *:[h1]:text-balance">
                                <h1><?= $game->headerTitle() ?></h1>
                            </hgroup>

                            <div class="*:[p]:mt-8 *:[p]:text-xl md:*:[p]:text-2xl *:[p]:text-pretty *:[p]:text-white/80 **:[a]:inline-block **:[a]:text-fuchsia-300 **:hover:[a]:text-fuchsia-500">
                                <?= $game->description() ?>
                            </div>

                            <?php $buyLink = $game->buyButton()->toObject() ?>
                            <a href="<?= $buyLink->link()->toUrl() ?>"
                                class="mt-8 text-2xl inline-block min-w-2/3 border-2 uppercase whitespace-nowrap text-center rounded-full px-8 pt-4 pb-4.75 hover:border-fuchsia-500 hover:text-fuchsia-500">
                                <?= $buyLink->anchor() ?>
                            </a>
                        </div>

                        <div class="sticky top-36">
                            <?= $game->cover()->toFile() ?>
                        </div>
                    </div>
                </section>

                <section class="pt-28 md:pt-32 lg:pt-36 bg-linear-to-bl from-red-600 to-indigo-950 to-50%">
                    <div class="container flex flex-col gap-8 items-center xl:*:w-1/2 xl:flex-row xl:gap-32">
                        <h2 class="text-4xl font-bold text-shadow-sm text-shadow-black/50 md:text-7xl text-balance">
                            <?= $game->videoTitle() ?>
                        </h2>

                        <?php if ($video = $game->video()->toEmbed()): ?>
                            <div class="md:mt-6 lg:mt-8 xl:mt-0 w-full h-auto aspect-video rounded-xl p-1 bg-violet-900/20">
                                <?php snippet('video', ['video' => $video]) ?>
                            </div>
                        <?php endif ?>
                    </div>
                </section>
            </div>
        <?php endforeach ?>
    </div>
</div>

<?php endslot() ?>
