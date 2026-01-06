<!-- Set user var for profile links -->
<?php $user = $kirby->users()->findBy('username', 'Scott') ?>

<footer class="pt-28 md:pt-36 lg:pt-48 bg-linear-to-b from-indigo-950 from-10% via-violet-600 to-fuchsia-600 to-90% text-white">
    <div class="container flex flex-col lg:flex-row justify-between gap-20 lg:gap-28 lg:*:w-1/2">
        <div class="flex flex-col md:items-center justify-center gap-8">
            <h2 class="text-2xl md:text-4xl font-bold uppercase text-white md:whitespace-nowrap text-shadow-xs text-shadow-black">Tu publicidad aquí</h2>
            <?php if ($user?->sponsorsLogos()): ?>
                <ul class="flex flex-wrap gap-8 bg-violet-500/30 rounded-lg p-8 w-full">
                    <?php foreach ($user->sponsorsLogos()->toStructure() as $sponsor): ?>
                        <?php
                        $file = $sponsor->logo()->toFile();
                        if ($file) {
                            $filename = $file->filename();
                            $nameAttr = str_replace(['-', '_', '.svg', '.png', '.jpg', '.jpeg', '.webp'], ' ', $filename);
                        }
                        ?>
                        <li class="flex items-center bg-white rounded-md overflow-clip flex-1 min-w-0">
                            <a href="<?= $sponsor->link() ?>" target="_blank" class="block w-full py-2 px-4" aria-label="<?= $nameAttr ?>">
                                <?php if ($logo = $sponsor->logo()->toFile()): ?>
                                    <picture>
                                        <source srcset="<?= $logo->thumb(['width' => 175, 'format' => 'avif'])->url() ?>" type="image/avif">
                                        <source srcset="<?= $logo->thumb(['width' => 175, 'format' => 'webp'])->url() ?>" type="image/webp">
                                        <img src="<?= $logo->thumb(['width' => 175])->url() ?>" class="w-full h-auto object-contain" width="175" height="175" loading="lazy" alt="<?= $nameAttr ?>">
                                    </picture>
                                <?php endif ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="flex flex-col md:items-center gap-8">
            <h2 class="text-2xl md:text-4xl font-bold uppercase text-white xl:whitespace-nowrap text-shadow-xs text-shadow-black">Sigue al Mago Scott</h2>

            <ul class="flex md:justify-between gap-4 md:gap-8 **:[svg]:fill-fuchsia-400 **:[path]:fill-fuchsia-500">
                <?php if ($file = 'assets/svgs/instagram.svg'): ?>
                    <li class="bg-indigo-950 p-6 md:p-8 rounded-full w-min h-min **:[svg]:w-10 **:[svg]:h-10 md:**:[svg]:w-12 md:**:[svg]:h-12">
                        <a href="<?= $user?->instagram() ?? '#' ?>" target="_blank" aria-label="Instagram">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($file = 'assets/svgs/facebook.svg'): ?>
                    <li class="bg-indigo-950 p-6 md:p-8 rounded-full w-min h-min **:[svg]:w-10 **:[svg]:h-10 md:**:[svg]:w-12 md:**:[svg]:h-12">
                        <a href="<?= $user?->facebook() ?? '#' ?>" target="_blank" aria-label="Facebook">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($file = 'assets/svgs/youtube.svg'): ?>
                    <li class="bg-indigo-950 p-5 md:p-7 rounded-full w-min h-min **:[svg]:w-12 **:[svg]:h-12 md:**:[svg]:w-14 md:**:[svg]:h-14">
                        <a href="<?= $user?->youtube() ?? '#' ?>" target="_blank" aria-label="YouTube">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="container mt-16 md:mt-20 flex flex-col lg:flex-row justify-between gap-24 lg:gap-28 lg:*:w-1/2">
        <div class="flex flex-col">
            <h2 class="text-2xl md:text-4xl md:text-center font-bold uppercase text-white text-balance text-shadow-xs text-shadow-black">Artículos recientes</h2>
            <ul class="mt-8 grid md:grid-cols-2 gap-x-8 gap-y-16">
                <?php foreach ($site->find('blog')->children()->listed()->limit(2) as $article): ?>
                    <li class="group relative after:absolute after:left-0 after:right-0 after:-bottom-8 md:after:-bottom-4 after:h-0.5 md:after:h-1 after:bg-white/50 after:rounded-full group-hover:after:bg-red-700 md:after:hidden">
                        <a href="<?= $article->url() ?>">
                            <div class="w-2/5 mr-4 md:mr-0 md:w-full float-left md:float-none">
                                <?php if ($image = $article->cover()->toFile()): ?>
                                    <picture>
                                        <source
                                            srcset="<?= $image->srcset('avif') ?>"
                                            type="image/avif">
                                        <source
                                            srcset="<?= $image->srcset('webp') ?>"
                                            type="image/webp">
                                        <img src="<?= $image->crop(600, 338)->url() ?>" alt="<?= $article->title() ?>" width="600" height="338" loading="lazy"
                                            class="border-2 border-violet-500 rounded-xl bg-violet-700 aspect-video object-cover group-hover:rounded-3xl group-hover:transition-all transition-all w-full">
                                    </picture>
                                <?php else: ?>
                                    <?php if ($placeholder = asset('/assets/images/headers/post-placeholder.webp')): ?>
                                        <picture>
                                            <source
                                                srcset="<?= $placeholder->srcset('avif') ?>"
                                                type="image/avif">
                                            <source
                                                srcset="<?= $placeholder->srcset('webp') ?>"
                                                type="image/webp">
                                            <img src="<?= $placeholder->crop(600, 338)->url() ?>" alt="<?= $article->title() ?>" width="600" height="338" loading="lazy"
                                                class="border-2 border-violet-500 rounded-xl bg-violet-700 aspect-video object-cover group-hover:rounded-3xl group-hover:transition-all transition-all w-full">
                                        </picture>
                                    <?php endif ?>
                                <?php endif ?>
                            </div>
                            <h3 class="relative md:mt-4 text-2xl md:text-3xl lg:text-2xl group-hover:text-fuchsia-300 text-pretty">
                                <?= $article->title() ?>
                            </h3>
                            <p class="mt-2 text-white/90 text-lg font-sans">
                                <?= $article->summary()->excerpt(100) ?>
                            </p>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>

        <div>
            <h2 class="text-2xl md:text-4xl text-center font-bold uppercase text-white  text-shadow-xs text-shadow-black">Una newsletter mágica</h2>
            <p class="mt-4 md:mt-8 text-center text-balance text-xl md:text-3xl text-violet-100 text-shadow-black">Mantente al tanto de todos los espectáculos, sorteos y entradas gratis para asistir en directo a los shows del Mago Scott.</p>
            <form action="#" class="mt-8 md:mt-16">
                <div class="relative w-full flex flex-col md:flex-row gap-4 items-center">
                    <input class="w-full md:mr-8 px-8 py-6 rounded-full bg-white/50 text-2xl text-indigo-950 font-semibold uppercase focus:outline-none focus:ring-2 focus:ring-indigo-950 placeholder:text-indigo-950/70 focus:placeholder:opacity-0 transition-all" type="email" placeholder="Tu correo electrónico" />

                    <button class="w-min md:absolute md:right-0 bg-violet-800 text-white font-semibold uppercase p-4 rounded-full aspect-square hover:bg-indigo-800" type="submit">Suscribirse</button>
                </div>
            </form>
        </div>
    </div>

    <div class="pt-16 lg:pt-32 md:pt-24  text-white bg-linear-to-t from-black from-10% to-transparent">
        <div class="container py-8 flex flex-col-reverse md:flex-row gap-8 justify-between items-center flex-wrap">
            <div class="w-full md:w-auto text-sm text-white/80">Copyright © <?= Date("Y") ?> Mago Scott</div>

            <ul class="grid grid-cols-2 items-center md:flex gap-x-8 gap-y-4 **:[a]:hover:text-fuchsia-400 **:[a]:active:text-fuchsia-400 whitespace-nowrap">
                <li><a href="">Privacidad y cookies</a></li>
                <li><a href="">Aviso legal</a></li>
                <li><a href="">Gestionar consentimiento</a></li>
                <li><a href="">Mapa del sitio</a></li>
            </ul>
        </div>
    </div>
</footer>
