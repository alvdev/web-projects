<section id="tours" class="<?= $class ?>">
    <div class="container">
        <div class="flex items-baseline gap-8">
        <h2 class="text-6xl whitespace-nowrap"><?= $site->tourTitle() ?></h2>
            <div class="w-full h-1 bg-red-500 rounded-full"></div>
        </div>

        <div class="mt-16 flex items-center gap-18 *:w-1/2">
            <div class="*:w-full *:h-auto *:aspect-video *:rounded-2xl *:border-2 *:border-violet-900/30">
                <?= $site->tourVideo()->toEmbed()->code() ?>
            </div>

            <div class="*:text-2xl *:[p]:not-first:mt-4">
                <?= $site->tourDesc()->kt() ?>
            <a href="<?= $site->tourButton()->toUrl() ?>" class="inline-block font-semibold text-shadow-2xs text-shadow-black mt-8 uppercase bg-red-600 rounded-4xl px-8 py-3">Formulario de contacto</a>
            </div>
        </div>
    </div>
</section>
