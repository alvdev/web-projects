<nav class="fixed top-0 z-10 w-full flex justify-between items-center text-white/90 backdrop-blur-md">
    <div class="container flex items-center justify-between py-8 ">
        <a class="flex flex-col gap-8 [&>svg]:w-24 [&>svg]:h-24 [&>svg]:rounded-full [&>svg]:ring-1 [&>svg]:ring-white [&>svg]:mx-auto" href="<?= $site->url(); ?>">
            <?php if ($asset = asset('assets/images/logo_scott.png')): ?>
                <img src="<?= $asset->url() ?>" alt="Logo de Mago Scott" />
            <?php endif ?>
        </a>

        <ul class="flex gap-8 font-semibold text-2xl tracking-wide uppercase *:last:*:border-2 *:last:*:rounded-full *:last:*:px-4 *:last:*:py-2">
            <?php foreach ($site->children()->listed() as $item) : ?>
                <li class="first-letter:uppercase">
                    <a class="text-shadow-md" <?php e($item->isOpen(), 'aria-current="page"'); ?> href="<?= $item->url(); ?>">
                        <?= $item->title()->esc() ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<div x-data="{ show: false }" x-on:scroll.window="show = window.pageYOffset >= 1000" x-cloak class="fixed left-5 bottom-5">
    <button x-show="show" x-transition x-on:click="window.scrollTo({top: 0})" class="shadow-lg bg-white/40 ring-2 ring-white/60 w-10 h-10 text-white rounded-full">
        🡱
    </button>
</div>
