<section id="intro" class="bg-linear-to-bl from-red-600 to-indigo-950 to-50% <?= $class ?>">
    <div class="container">
        <!-- <?= dump($site) ?> -->
        <h2 class="text-4xl md:text-6xl text-balance"><?= $site->introTitle() ?></h2>

        <div class="mt-8 md:mt-12 lg:mt-16 flex flex-col lg:flex-row items-center gap-12 md:gap-20 lg:gap-28">
            <div class="lg:w-2/3 md:*:text-3xl *:not-first:mt-4 md:*:not-first:mt-8 **:[strong]:text-violet-300 **:[strong]:font-semibold text-pretty">
                <?= $site->introDesc()->kt() ?>
            </div>

            <div class="sticky top-36 lg:w-1/3 flex flex-col md:flex-row lg:flex-col gap-8 text-2xl *:min-w-1/2 *:border *:whitespace-nowrap *:text-center *:rounded-full *:px-8 *:pt-4 *:pb-4.75">
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
