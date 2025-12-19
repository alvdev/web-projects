<header id="home-header" class="bg-linear-to-tl from-red-600/30 via-indigo-950/30 via-50% to-black/30">
    <div class="absolute top-0 -z-10 w-full opacity-90 *:mask-b-to-90% *:[img]:w-full *:[img]:object-cover *:[img]:aspect-video">
        <?= $page->cover()->toFile() ?>
    </div>

    <div class="container relative pt-28 md:pt-36 lg:pt-64">
        <hgroup class="text-center text-balance lg:max-w-2/3 mx-auto text-shadow-sm text-shadow-black">
            <h1 class="text-5xl md:text-7xl font-bold leading-none">
                <?= $page->headerTitle() ?>
            </h1>
            <p class="mt-8 text-2xl md:text-4xl">
                <?= $page->description() ?>
            </p>
        </hgroup>
    </div>
</header>
