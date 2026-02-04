<!DOCTYPE html>
<html lang="es" class="h-full">

<?php snippet('head') ?>

<body class="debug-screens font-sans bg-indigo-950 text-white">
    <div class="h-full flex flex-col min-h-screen bg-linear-to-tl from-indigo-950 via-indigo-950 via-50% to-black">
        <main class="relative md:w-sm md:mx-auto px-2">
            <div class="px-4 min-h-52 flex flex-col justify-center *:[a]:text-base *:[a]:text-fuchsia-300 *:[a]:focus:text-fuchsia-400 *:[a]:font-sans">
                <?php if ($page->website()): ?>
                    <img src="<?= asset('assets/images/logo_scott.png')->url() ?>" alt="Logo Mago Scott"
                    class="w-24 h-auto">
                <?php endif; ?>

                <a href="tel:+34630818123">+34 630 81 81 23</a>
                <a href="mailto:mail@magoscott.com">mail@magoscott.com</a>
                <a href="https://www.magoscott.com" target="_blank">www.magoscott.com</a>
            </div>

            <?php if ($image = ($site->formImage()->toFile() ?? asset('assets/images/headers/scott.png'))): ?>
                <picture class="absolute top-2 right-2">
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
                        class="w-full h-48 mask-b-from-50%"
                    >
                </picture>
            <?php endif ?>

            <section class="container grid divide-y divide-indigo-100 *:py-5 bg-white text-black rounded-xl">
                <?php if ($page->tickets()): ?>
                    <?php if ($button = $site->introButton2()->toObject()) {
                        $link = $button->link()->toUrl() ?? 'https://abonoteatro.com';
                    } ?>
                    <a href="<?= $link ?>" target="_blank" class="flex items-center gap-6 px-2 md:px-0 text-lg text-gray-600 active:bg-linear-to-r active:from-transparent active:via-cyan-200 active:to-transparent">
                        <div class="w-10 h-10 *:[svg]:fill-cyan-500">
                            <?= svg('assets/svgs/tickets.svg') ?>
                        </div>
                        <div>
                            <h2 class="font-bold text-xl text-black">¡Venta de entradas aquí!</h2>
                            <?= rtrim(str_replace(['https://www.', 'https://'], 'www.', $link), '/') ?>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($page->youtube()): ?>
                    <a href="<?= $page->youtube() ?>" target="_blank" class="flex items-center gap-6 px-2 md:px-0 text-lg text-gray-600 active:bg-linear-to-r active:from-transparent active:via-red-200 active:to-transparent">
                        <div class="w-10 h-10 *:[svg]:w-12 *:[svg]:fill-red-600 -ml-1 mr-1">
                            <?= svg('assets/svgs/youtube.svg') ?>
                        </div>
                        <div>
                            <h2 class="font-bold text-xl text-black">YouTube</h2>
                            El canal con mis vídeos 
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($page->whatsapp()): ?>
                    <a href="<?= $page->whatsapp() ?>" target="_blank" class="flex items-center gap-6 px-2 md:px-0 text-lg text-gray-600 active:bg-linear-to-r active:from-transparent active:via-green-200 active:to-transparent">
                        <div class="w-10 h-10">
                            <?= svg('assets/svgs/whatsapp2.svg') ?>
                        </div>
                        <div class="">
                            <h2 class="font-bold text-xl text-black">WhatsApp</h2>
                            Para charlar conmigo
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($page->instagram()): ?>
                    <a href="<?= $page->instagram() ?>" target="_blank" class="flex items-center gap-6 px-2 md:px-0 text-lg text-gray-600 active:bg-linear-to-r active:from-transparent active:via-pink-200 active:to-transparent">
                        <div class="w-10 h-10 *:[svg]:fill-pink-500">
                            <?= svg('assets/svgs/instagram.svg') ?>
                        </div>
                        <div>
                            <h2 class="font-bold text-xl text-black">Instagram</h2>
                            No te olvides de seguirme
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($page->facebook()): ?>
                    <a href="<?= $page->facebook() ?>" target="_blank" class="flex items-center gap-6 px-2 md:px-0 text-lg text-gray-600 active:bg-linear-to-r active:from-transparent active:via-blue-200 active:to-transparent">
                        <div class="w-10 h-10">
                            <?= svg('assets/svgs/facebook2.svg') ?>
                        </div>
                        <div>
                            <h2 class="font-bold text-xl text-black">Facebook</h2>
                            Visita mi página oficial
                        </div>
                    </a>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>

</html>
