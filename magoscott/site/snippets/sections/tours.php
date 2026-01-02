<section id="tours" class="<?= $class ?>">
    <div class="container">
        <div class="flex items-end gap-8">
            <h2 class="text-4xl md:text-6xl whitespace-nowrap"><?= $site->tourTitle() ?></h2>
            <div class="w-full h-1 bg-red-500 rounded-full mb-3"></div>
        </div>

        <div class="mt-12 md:mt-16 flex flex-col lg:flex-row items-center gap-8 md:gap-12 lg:gap-18 lg:*:w-1/2">
            <div class="w-full *:w-full *:h-auto *:aspect-video *:rounded-2xl *:border-2 *:border-violet-900/30">
                <?php if ($video = $site->tourVideo()->toEmbed()): ?>
                    <?= str_replace('<iframe', '<iframe loading="lazy"', $video->code()) ?>
                <?php endif ?>
            </div>

            <div class="flex flex-col md:*:text-3xl lg:*:text-2xl text-white/80 *:[p]:not-first:mt-4">
                <?= $site->tourDesc()->kt() ?>
                <a href="<?= $site->tourButton()->toUrl() ?>" class="inline-block mx-auto font-semibold text-shadow-2xs text-shadow-black mt-8 md:mt-12 lg:mt-8 uppercase bg-red-600 rounded-4xl px-8 py-3">Formulario de contacto</a>
            </div>
        </div>
    </div>
</section>
