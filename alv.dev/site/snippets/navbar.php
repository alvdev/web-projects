<nav class="relative bg-gray-950 px-16 py-8 text-white text-base text-opacity-80">
    <div class="sticky z-10 top-8">
        <?php
        /* In the menu, we only fetch listed pages, i.e. the pages that have a prepended number in their foldername.  We do not want to display links to unlisted `error`, `home`, or `sandbox` pages.  More about page status: https://getkirby.com/docs/reference/panel/blueprints/page#statuses */
        ?>

        <a class="flex flex-col gap-8 [&>svg]:w-24 [&>svg]:h-24 [&>svg]:rounded-full [&>svg]:ring-1 [&>svg]:ring-white [&>svg]:mx-auto" href="<?= $site->url(); ?>">
            <?= svg('assets/images/alvdev-logo.svg'); ?>
        </a>

        <div class="flex flex-col items-center gap-6 mt-8">
            <div class="flex flex-col gap-6 text-center">
                <?php foreach ($site->children()->listed() as $item) : ?>
                    <a class="bg-black block" <?php e($item->isOpen(), 'aria-current="page"'); ?> href="<?= $item->url(); ?>">
                        <?= $item->title()->esc() ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <?php snippet('social'); ?>
                <?php if (true) : ?>
                <?php endif; ?>
            </div>

            <div x-data="{ show: false }" x-on:scroll.window="show = window.pageYOffset >= 1000" x-cloak class="fixed bottom-10">
                <button x-show="show" x-transition x-on:click="window.scrollTo({top: 0})" class="shadow-lg bg-black/60 ring-1 ring-gray-500  w-10 h-10 text-white rounded-full">
                    🡱
                </button>
            </div>
        </div>
    </div>

    <div class="absolute bottom-0 left-0 w-[25vw] h-[25vw] border-t-transparent border-l-[#00ff77] border-t-[25vw] border-l-[25vw]">
    </div>
</nav>
