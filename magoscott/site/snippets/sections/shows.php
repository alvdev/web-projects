<section id="shows" class="<?= $class ?>">
    <div class="container">
        <div class="flex items-baseline gap-8">
            <h2 class="text-6xl whitespace-nowrap"><?= $site->showsTitle() ?></h2>
            <div class="w-full h-1 bg-red-500 rounded-full"></div>
        </div>

        <div class="mt-16 grid grid-cols-3 gap-12 *:flex *:flex-col *:border-2 *:border-white/30 *:p-8 *:rounded-3xl **:[img]:rounded-xl **:[img]:border-2 **:[img]:border-violet-800/20 **:[iframe]:mt-auto **:[iframe]:w-auto **:[iframe]:h-auto **:[iframe]:aspect-video **:[iframe]:border-2 **:[iframe]:border-violet-800/20 **:[iframe]:rounded-xl">
            <?php if ($shows = $site->shows()->toStructure()): ?>
                <?php foreach ($shows as $show): ?>
                    <div>
                        <?= $show->cover()->toFile() ?>
                        <h3 class="mt-8 text-3xl text-balance"><?= $show->title() ?></h3>
                        <p class="my-8 text-white/80"><?= $show->desc() ?></p>
                        <?= $show->video()->toEmbed()->code() ?>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>
    </div>
</section>
