<?php snippet('layouts/base', slots: true) ?>

<main class="pt-48">
    <?php snippet('sections/heroBlog') ?>

    <section class="pt-16 container bg-ellipse-t-blue">
        <div class="relative -mb-8 z-10 grid grid-cols-3 gap-1 p-1 bg-linear-to-b from-blue-300/90 to-white/50 rounded-xl backdrop-blur-sm shadow-slate-300/50 shadow-sm">
            <article class="relative rounded-lg overflow-clip">
                <img src="https://picsum.photos/800/500?random=1" class="aspect-video" alt="">
                <h2 class="text-gradient-none text-white text-shadow-xs absolute bottom-0 z-0 w-full text-3xl font-semibold px-4 pt-8 pb-3 after:absolute after:left-0 after:bottom-0 after:-z-10 after:w-full after:h-full after:bg-gradient-to-t after:from-black/80 after:to-transparent">Lorem ipsum</h2>
            </article>

            <article class="relative rounded-lg overflow-clip">
                <img src="https://picsum.photos/800/500?random=2" class="aspect-video" alt="">
                <h2 class="text-gradient-none text-white text-shadow-xs absolute bottom-0 z-0 w-full text-3xl font-semibold px-4 pt-8 pb-3 after:absolute after:left-0 after:bottom-0 after:-z-10 after:w-full after:h-full after:bg-gradient-to-t after:from-black/80 after:to-transparent">Lorem ipsum</h2>
            </article>

            <article class="relative rounded-lg overflow-clip">
                <img src="https://picsum.photos/800/500?random=3" class="aspect-video" alt="">
                <h2 class="text-gradient-none text-white text-shadow-xs absolute bottom-0 z-0 w-full text-3xl font-semibold px-4 pt-8 pb-3 after:absolute after:left-0 after:bottom-0 after:-z-10 after:w-full after:h-full after:bg-gradient-to-t after:from-black/80 after:to-transparent">Lorem ipsum</h2>
            </article>
        </div>
    </section>

    <section class="main-content">
        <div class="container flex gap-8">
            <div class="w-2/3">
                <section id="latest-posts" class="container mt-24 grid grid-cols-2 gap-4">
                    <article>
                        <img src="https://picsum.photos/600/400?random=4" class="aspect-video rounded-lg" alt="">
                        <h2 class="mt-6 text-3xl">Lorem ipsum dolor sit amet consectetur adipisicing elit</h2>
                        <p class="mt-2">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Vel, eligendi commodi mollitia fugiat doloribus, molestias hic labore dolores expedita sint repellat, adipisci debitis blanditiis laborum? Pariatur nam mollitia nihil blanditiis!</p>
                    </article>

                    <article>
                        <img src="https://picsum.photos/600/400?random=5" class="aspect-video rounded-lg" alt="">
                        <h2 class="mt-6 text-3xl">Lorem ipsum dolor sit amet consectetur adipisicing elit</h2>
                        <p class="mt-2">Lorem ipsum dolor sit amet consectetur, adipisicing elit. Vel, eligendi commodi mollitia fugiat doloribus, molestias hic labore dolores expedita sint repellat, adipisci debitis blanditiis laborum? Pariatur nam mollitia nihil blanditiis!</p>
                    </article>
                </section>
            </div>
        </div>
        <div class="container mt-16">
            <div class="border-2 border-red-500">
                <?= $page->categories() ?>
            </div>
            <div class="border-2 border-blue-500">
                <?= $site->children()->template('blog')->categories() ?>
            </div>
            <?php foreach (page('blog')->children()->listed()->sortBy('date', 'desc') as $post): ?>
                <article>
                    <a href="<?= $post->url() ?>" class="text-emerald-600 font-bold">
                        <?= $post->title() ?>
                    </a>

                    <?= $post->body()->toBlocks() ?>
                </article>
            <?php endforeach ?>
        </div>
    </section>
</main>

<?php endsnippet() ?>
