<section class="bg-linear-to-bl from-red-600/30 to-indigo-950/30 to-50% <?= $class ?>">
    <div class="container">
        <div class="relative lg:ml-[max(calc(9.5rem+1px),calc(100%-80rem))]">
            <div
                class="hidden absolute top-3 bottom-0 right-full mr-7 md:mr-13 w-px bg-zinc-200 lg:block"></div>
            <div class="mt-36 *:not-first:mt-16">

                <?php if ($page->hasChildren()): {
                        $items = $page->children()->listed()->sortBy('yearTo', 'desc', 'yearFrom', 'desc');
                    } ?>
                    <?php foreach ($items as $item): ?>
                        <article class="relative group">
                            <div class="relative hidden mr-10 lg:flex md:mx-auto">
                                <div class="flex items-center justify-center w-3 h-full">
                                    <div class="h-full w-[0.05rem] bg-zinc-200 pointer-events-none"></div>
                                </div>
                                <div class="absolute right-full mr-6 top-2 text-blue-500 md:mr-12 w-[calc(0.5rem+1px)] h-[calc(0.5rem+1px)] overflow-visible sm:block">
                                    <div
                                        class="rounded-full size-full outline-2 outline-violet-100 bg-zinc-100"></div>
                                </div>
                            </div>

                            <div class="relative -top-2 flex justify-between gap-24">
                                <div>
                                    <h3 class="relative text-xl md:text-2xl mt-4 font-semibold lg:mt-0 first-letter:uppercase after:absolute after:-bottom-4 after:left-0 after:bg-red-600 after:w-36 after:h-1 after:rounded-full">
                                        <?= $item->title() ?>
                                    </h3>
    
                                    <div class="mt-8 text-pretty text-white/80">
                                        <?= $item->description()->kt() ?>
                                    </div>
                                </div>

                                <?php if ($item->content()->videos()->isNotEmpty()): ?>
                                    <div class="flex gap-8 *:rounded-xl *:ring-2 *:ring-indigo-900/50 *:aspect-video">
                                        <?php foreach ($item->content()->videos()->toEntries() as $entry): ?>
                                            <?= youtube($entry) ?>
                                        <?php endforeach ?>
                                    </div>
                                <?php endif ?>

                                <div class="lg:absolute order-first lg:top-0 tracking-tight text-lg lg:left-auto lg:right-full lg:mr-[calc(6.5rem+1px)]">
                                    <p class="relative text-2xl font-semibold uppercase tracking-wide text-violet-300">
                                        <span class="sr-only">Año</span>
                                        <time class="leading-none">
                                            <?php if ($item->yearFrom()->isNotEmpty() && $item->yearTo()->isNotEmpty()): ?>
                                                <?= $item->yearTo() ?>
                                                <span class="relative -top-2 block text-center">↑<span>
                                                        <?= $item->yearFrom() ?>
                                                    <?php else: ?>
                                                        <?= $item->yearFrom() ?>
                                                    <?php endif ?>
                                        </time>
                                    </p>
                                </div>
                            </div>
                        </article>
                    <?php endforeach ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>
