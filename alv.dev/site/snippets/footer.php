<footer class="container relative mt-28" x-data="{ sent: false }">
    <div class="w-full border-2 border-gray-950 ring-2 ring-white ring-opacity-95 rounded"></div>

    <div class="border border-gray-900 rounded my-28 px-0 flex items-stretch justify-between [contain:paint]">
        <div id="message"></div>

        <div class="glass-dark flex items-center gap-8 rounded-none w-1/2">
            <div class="w-1/2">
                <h2 class="uppercase font-bold text-2xl">Newsletter</h2>
                <p class="mt-4 font-light text-base">Lorem ipsum dolor sit amet consectetur adipisicing elit.
                    Facilis, totam vero culpa consequatur, porro incidunt officia.</p>
            </div>

            <div class="w-1/2">
                <?php snippet('forms/newsletter') ?>
            </div>
        </div>

        <!-- <form class="glass-dark flex items-center rounded-none w-1/2" action="/message" method="post" x-on:submit.prevent x-init x-target="message">
            <div class="flex items-center gap-8">
                <div class="w-1/2">
                    <h2 class="uppercase font-bold text-2xl">Newsletter</h2>
                    <p class="mt-4 font-light text-base">Lorem ipsum dolor sit amet consectetur adipisicing elit.
                        Facilis, totam vero culpa consequatur, porro incidunt officia.</p>
                </div>

                <div class="flex flex-col gap-8 w-1/2">
                    <input class="w-full bg-transparent border-b-2 border-green-500 text-green-500 placeholder:text-green-100 placeholder:font-light pb-2 outline-none" name="name" type="text" placeholder="<?= t('newletter-name', 'name') ?>" style="input:-webkit-autofill: background: red">

                    <input class="w-full bg-transparent border-b-2 border-green-500 text-green-500 placeholder:text-green-100 placeholder:font-light pb-2 outline-none" name="email" type="email" placeholder="<?= t('newsletter-email', 'email') ?>">

                    <button class="bg-green-500 rounded text-black inline-block w-fit min-w-[50%] px-8 py-2 mt-4 hover:animate-wiggle" x-init x-target="message" x-on:click="sent = true" x-text:="sent ? 'Sent' : 'Send'">
                        <?= t('newsletter-submit', 'submit') ?>
                    </button>
                </div>
            </div>
        </form> -->

        <div class="glass rounded-sm w-1/2 flex flex-col justify-center">
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
