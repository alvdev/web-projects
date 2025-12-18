<section id="intro" class="bg-linear-to-bl from-red-600 to-indigo-950 to-50% <?= $class ?>">
    <div class="container">
        <!-- <?= dump($site) ?> -->
        <h2 class="text-6xl"><?= $site->introTitle() ?></h2>

        <div class="mt-8 flex items-center gap-28">
            <div class="w-2/3 mt-8 *:text-3xl *:not-first:mt-8 **:[strong]:text-violet-300 **:[strong]:font-semibold text-pretty">
                <?= $site->introDesc()->kt() ?>
            </div>

            <div class="sticky top-36 w-1/3 flex flex-col gap-8 text-2xl *:border *:whitespace-nowrap *:text-center *:rounded-full *:px-8 *:pt-4 *:pb-4.75">
                <?php if ($button = $site->introButton1()->toObject()): ?>
                    <a href="<?= $button->link()->toUrl() ?>">
                        <?= $button->anchor() ?>
                    </a>
                <?php endif ?>

                <?php if ($button = $site->introButton2()->toObject()): ?>
                    <a href="<?= $button->link()->toUrl() ?? 'https://entradas.com' ?>" target="_blank">
                        <?= $button->anchor() ?? 'Comprar entradas' ?>
                    </a>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>
