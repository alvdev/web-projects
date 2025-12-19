<section id="shows" class="<?= $class ?>">
    <div class="container">
        <div class="flex items-end gap-8">
            <h2 class="text-4xl md:text-6xl lg:whitespace-nowrap">
                <?= $site->showsTitle() ?>
            </h2>
            <div class="w-full h-1 bg-red-500 rounded-full mb-3"></div>
        </div>

        <div class="mt-12 md:mt-16 grid lg:grid-cols-3 gap-12">
            <?php if ($shows = $site->shows()->toStructure()): ?>
                <?php foreach ($shows as $show): ?>
                    <div class="flex flex-col border-2 border-white/30 p-4 md:p-8 rounded-3xl *:[img]:rounded-xl *:[img]:border-2 *:[img]:border-violet-800/20 *:[iframe]:mt-auto *:[iframe]:w-auto *:[iframe]:h-auto *:[iframe]:aspect-video *:[iframe]:border-2 *:[iframe]:border-violet-800/20 *:[iframe]:rounded-xl">
                        <?= $show->cover()->toFile() ?>
                        <h3 class="mt-8 text-3xl md:text-5xl lg:text-3xl text-pretty"><?= $show->title() ?></h3>
                        <p class="my-8 text-xl md:text-3xl lg:text-xl text-white/80"><?= $show->desc() ?></p>
                        <?= $show->video()->toEmbed()->code() ?>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>
    </div>
</section>
