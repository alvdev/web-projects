<section id="home-header" class="bg-linear-to-tl from-red-600 via-indigo-950 via-50% to-black">
    <div class="container pt-64 flex items-center gap-32 *:w-1/2">
        <div>
            <hgroup class="*:[h1]:text-7xl *:[h1]:font-bold *:[h1]:leading-none *:[h1]:text-balance *:[p]:mt-8 *:[p]:text-4xl *:[p]:text-pretty">
                <?= $site->formText()->kt() ?>
            </hgroup>

            <?php snippet('forms/contact') ?>
        </div>

        <div>
            <?= $site->formImage()?->toFile() ?? asset('assets/images/headers/scott.png') ?>
        </div>
    </div>
</section>
