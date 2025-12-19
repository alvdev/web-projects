<section id="home-header" class="bg-linear-to-tl from-red-600 via-indigo-950 via-50% to-black">
    <div class="container pt-28 md:pt-36 lg:pt-64 flex flex-col lg:flex-row items-center gap-8 md:gap-16 lg:gap-32 lg:*:w-1/2">
        <div>
            <hgroup class="*:[h1]:text-4xl md:*:[h1]:text-7xl *:[h1]:font-bold *:[h1]:leading-none *:[h1]:text-balance *:[p]:mt-8 *:[p]:text-xl md:*:[p]:text-4xl *:[p]:text-pretty *:[p]:text-white/80">
                <?= $site->formText()->kt() ?>
            </hgroup>

            <?php snippet('forms/contact') ?>
        </div>

        <div>
            <?= $site->formImage()?->toFile() ?? asset('assets/images/headers/scott.png') ?>
        </div>
    </div>
</section>
