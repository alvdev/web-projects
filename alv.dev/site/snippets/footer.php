<footer class="container relative mt-28" x-data="{ sent: false }">
    <div class="relative -z-50 w-full border-6 border-gray-950 rounded-xs"></div>

    <div class="border border-gray-900 rounded my-20 px-0 flex">
        <div class="glass-dark flex items-center gap-8 w-3/6 rounded-l-none rounded-r-sm">
            <div class="w-1/2">
                <h2 class="uppercase font-bold text-2xl">Newsletter</h2>
                <p class="mt-4 font-light text-base">Lorem ipsum dolor sit amet consectetur adipisicing elit.
                    Facilis, totam vero culpa consequatur, porro incidunt officia.</p>
            </div>

            <div id="newsletter-message" class=" w-1/2" x-merge.transition>
                <?php snippet('forms/newsletter') ?>
            </div>
        </div>

        <div class="glass rounded-none flex flex-col justify-center w-3/6">
            <h2 class="flex flex-col font-bold text-2xl">
                <span>Escribo para aprender.</span>
                <span>Aprendo para compartir.</span>
                <span>Comparto para emprender.</span>
            </h2>

            <ul class="grid grid-cols-2 gap-x-10 gap-y-6 mt-8 min-w-max">
                <?php
                $linkColors = ['link', 'link-red', 'link-blue', 'link-green', 'link-yellow', 'link-violet'];
                foreach ($site->blueprint()->field('categories')['options'] as $category) :
                    $linkColor = array_rand($linkColors); ?>
                    <li>
                        <a class="<?= $linkColors[$linkColor] ?> block" href="#">
                            <?= $category[$kirby->language()->code()] ?? $category ?>
                        </a>
                    </li>
                <?php unset($linkColors[$linkColor]);
                endforeach ?>
            </ul>
        </div>

        <div class="glass rounded-l-none pl-0 w-2/6 flex flex-col justify-center">
            <div class="border-l-8 border-dotted border-gray-600 pl-16">
                <h2 class="text-2xl font-bold">Servicios</h2>
                <ul class="mt-8 flex flex-col gap-1">
                    <li><a href="/#projects">Short links</a></li>
                    <li><a href="/#projects">Email marketing</a></li>
                    <li><a href="/#projects">Web hosting</a></li>
                </ul>
            </div>
        </div>

        <!-- TODO: is it dead code? -->
        <!-- <div id="message"></div> -->

    </div>
</footer>

<?=
js(
    [
        'assets/js/lightbox.js',
        'assets/js/index.js',
        '@auto',
    ]
) ?>
