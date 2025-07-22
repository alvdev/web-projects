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
        <div class="container mt-24 flex gap-8">
            <div class="w-2/3">
                <section id="latest-posts" class="container grid grid-cols-2 gap-4">
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

            <aside id="aside-right" class="self-start sticky top-24 flex flex-col gap-8">
                <div>
                    <h2 class="border-l-4 border-blue-600 mb-1 pl-4 py-2 uppercase text-xl">Productos y servicios</h2>
                    <ul class="border-l-4 border-gray-200 mb-1 pl-4 py-2 grid grid-cols-2 gap-4">
                        <li class="text-blue-600">Web hosting</li>
                        <li class="text-blue-600">Servidores</li>
                        <li class="text-blue-600">Marketing</li>
                        <li class="text-blue-600">Alfa mailer</li>
                        <li class="text-blue-600">Alfa CMS</li>
                        <li class="text-blue-600">Alfa Page</li>
                    </ul>
                </div>

                <div>
                    <h2 class="border-l-4 border-blue-600 mb-1 pl-4 py-2 uppercase text-xl">Gestores de contenido</h2>
                    <ul class="border-l-4 border-gray-200 mb-1 pl-4 py-2 grid grid-cols-2 gap-4">
                        <li class="text-blue-600">Drupal</li>
                        <li class="text-blue-600">Wordpress</li>
                        <li class="text-blue-600">Moodle</li>
                        <li class="text-blue-600">Prestashop</li>
                        <li class="text-blue-600">Magento</li>
                        <li class="text-blue-600">WooCommerce</li>
                    </ul>
                </div>

                <div>
                    <h2 class="border-l-4 border-blue-600 mb-1 pl-4 py-2 uppercase text-xl">Negocios digitales</h2>
                    <ul class="border-l-4 border-gray-200 mb-1 pl-4 py-2 grid grid-cols-2 gap-4">
                        <li class="text-blue-600">Portales verticales</li>
                        <li class="text-blue-600">Programas de afiliados</li>
                        <li class="text-blue-600">Comercio electrónico</li>
                        <li class="text-blue-600">Servicios por suscripción</li>
                    </ul>
                </div>

                <div>
                    <h2 class="border-l-4 border-blue-600 mb-1 pl-4 py-2 uppercase text-xl">Marketing en internet</h2>
                    <ul class="border-l-4 border-gray-200 mb-1 pl-4 py-2 grid grid-cols-2 gap-4">
                        <li class="text-blue-600">Email marketing</li>
                        <li class="text-blue-600">Posicionamiento web</li>
                        <li class="text-blue-600">Social Media Marketing</li>
                        <li class="text-blue-600">Creación de contenidos</li>
                    </ul>
                </div>
            </aside>
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
