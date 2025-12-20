<div class="fixed top-0 z-10 left-0 right-0 backdrop-blur-md shadow-md">
    <nav
        x-data="{ open: false }"
        class="container px-4 md:px-8 py-2 md:py-8">
        <div class="flex h-16 items-center justify-between">

            <!-- Logo -->
            <a href="<?= $site->url(); ?>" class="*:h-18 md:*:h-auto">
                <?php if ($asset = asset('assets/images/logo_scott.png')): ?>
                    <img src="<?= $asset->url() ?>" alt="Logo de Mago Scott" />
                <?php endif ?>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex md:items-center md:space-x-8 font-semibold text-2xl tracking-wide uppercase *:last:border-2 *:last:rounded-full *:last:px-4 *:last:py-2">
                <?php foreach ($site->children()->listed() as $item): ?>
                    <a class="text-shadow-md aria-current:text-fuchsia-400 aria-current:font-extrabold" <?php e($item->isOpen(), 'aria-current="true"'); ?> href="<?= $item->url(); ?>">
                        <?= $item->title()->esc() ?>
                    </a>
                <?php endforeach ?>
            </div>

            <!-- Mobile Button -->
            <div class="lg:hidden">
                <button
                    @click="open = !open"
                    aria-label="Toggle menu"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-white hover:bg-white-500 focus:outline-none">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div
            x-show="open"
            x-transition
            @click.outside="open = false"
            class="lg:hidden">
            <div class="space-y-1 pb-4 pt-8 font-semibold text-2xl tracking-wide uppercase">
                <?php foreach ($site->children()->listed() as $item): ?>
                    <a class="block text-shadow-md px-3 py-6 aria-current:text-fuchsia-500" <?php e($item->isOpen(), 'aria-current="true"'); ?> href="<?= $item->url(); ?>">
                        <?= $item->title()->esc() ?>
                    </a>
                <?php endforeach ?>
            </div>
        </div>
    </nav>
</div>

<div x-data="{ show: false }" x-on:scroll.window="show = window.pageYOffset >= 1000" x-cloak class="fixed left-5 bottom-5">
    <button x-show="show" x-transition x-on:click="window.scrollTo({top: 0})" class="shadow-lg bg-white/40 ring-2 ring-white/60 w-10 h-10 text-white rounded-full">
        🡱
    </button>
</div>
