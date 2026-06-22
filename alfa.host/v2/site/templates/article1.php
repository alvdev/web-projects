<?php snippet('layouts/base', slots: true) ?>

<main class="pt-56">
    <article class="main-content article">
        <div class="container flex gap-16">
            <div class="w-3/4">
                <div class="-mt-16 pt-4">
                    <?php if ($cover = $page->cover()->toFile()): ?>
                        <div class="relative p-2 bg-linear-to-b from-white/30 to-white/50 rounded-xl backdrop-blur-sm shadow-xl shadow-slate-300/50">
                            <img src="<?= $cover->url() ?>"
                                class="relative z-0 rounded-lg" alt="">

                            <h1 class="bg-gradient-to-t from-blue-700/80 px-12 pt-16 pb-8 absolute z-20 text-white inset-x-2 bottom-2 rounded-b-lg overflow-clip first-letter:uppercase text-6xl font-extrabold leading-[1.15]">
                                <?= $page->title() ?>
                            </h1>
                        </div>
                    <?php endif ?>

                    <?php if ($page->summary()->isNotEmpty()): ?>
                        <h2 class="mt-8 text-balance text-4xl font-semibold text-slate-500 [-webkit-text-fill-color:inherit]"><?= $page->summary()->kirbytextinline() ?></h2>
                    <?php endif ?>
                </div>

                <div class="mt-24">
                    <?= $page->body()->toBlocks() ?>
                </div>
            </div>

            <aside class="w-1/4 pt-16">osdhodhg</aside>
        </div>
    </article>
</main>

<?php endsnippet() ?>
