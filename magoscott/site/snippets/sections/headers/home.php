<section id="home-header" class="bg-linear-to-tl from-red-600 via-indigo-950 via-50% to-black">
    <div class="container pt-28 md:pt-36 lg:pt-64 flex flex-col lg:flex-row items-center gap-8 md:gap-16 lg:gap-32 lg:*:w-1/2">
        <div>
            <hgroup class="*:[h1]:text-4xl md:*:[h1]:text-7xl *:[h1]:font-bold *:[h1]:leading-none *:[h1]:text-balance *:[p]:mt-8 *:[p]:text-xl md:*:[p]:text-4xl *:[p]:text-pretty *:[p]:text-white/80">
                <?= $site->formText()->kt() ?>
            </hgroup>

            <?php snippet('forms/contact') ?>
        </div>

        <div>
            <?php if ($image = ($site->formImage()->toFile() ?? asset('assets/images/headers/scott.png'))): ?>
                <picture>
                    <source srcset="<?= $image->srcset('avif') ?>" type="image/avif" sizes="(min-width: 1024px) 50vw, 100vw">
                    <source srcset="<?= $image->srcset('webp') ?>" type="image/webp" sizes="(min-width: 1024px) 50vw, 100vw">
                    <img
                        srcset="<?= $image->srcset('default') ?>"
                        sizes="(min-width: 1024px) 50vw, 100vw"
                        src="<?= $image->resize(1200)->url() ?>"
                        width="<?= $image->width() ?>"
                        height="<?= $image->height() ?>"
                        alt="Scott - Magoscott"
                        fetchpriority="high"
                        class="w-full h-auto mask-b-from-50%"
                    >
                </picture>
            <?php endif ?>
        </div>
    </div>
</section>
