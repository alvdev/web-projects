<footer class="container relative mt-16" x-data="{ sent: false }">
    <div class="flex gap-8">
        <div class="pr-16 py-8 flex flex-col justify-center border-t-2 border-gray-300 flex-1">
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

        <div class="w-3/12 flex flex-col justify-center bg-black rounded-t justify-self-end">
            <div class="px-12 py-8 bg-black text-white">
                <h2 class="text-2xl font-bold text-white">Servicios</h2>
                <ul class="mt-8 flex flex-col gap-2 **:[a]:block **:[a]:text-green-500 **:[a]:py-2 **:[a]:hover:uppercase">
                    <li>
                        <a href="/#projects">Short links</a>
                    </li>
                    <li>
                        <a href="/#projects">Email marketing</a>
                    </li>
                    <li>
                        <a href="/#projects">Web hosting</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- TODO: is it dead code? -->
        <!-- <div id="message"></div> -->
    </div>

    <!-- <?php if ($pages->find('legal')->hasChildren()) : ?>
        <ul class="flex justify-between my-8 gap-4 text-sm [&>li>a]:text-black/70 [&>li>a]:hover:text-black/100">
            <?php foreach ($pages->find('legal')->children() as $page): ?>
                <li>
                    <a href="<?= $page->url() ?>"><?= $page->title() ?></a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?> -->
</footer>

<?=
js(
    [
        'assets/js/lightbox.js',
        'assets/js/index.js',
        '@auto',
    ]
) ?>
