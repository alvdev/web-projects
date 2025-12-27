<!-- Set user var for profile links -->
<?php $user = $kirby->users()->findBy('username', 'Scott') ?>

<footer class="pt-28 md:pt-36 lg:pt-48 bg-linear-to-b from-indigo-950 from-10% to-violet-600 to-50% text-white">
    <div class="container mt-16 flex flex-col lg:flex-row justify-between gap-24 lg:gap-28 lg:*:w-1/2">
        <div class="flex flex-col md:items-center justify-center gap-8">
            <h2 class="text-4xl md:text-6xl font-semibold text-white md:whitespace-nowrap text-shadow-xs text-shadow-black">Tu publicidad aquí</h2>
            <?php if ($user?->sponsorsLogos()): ?>
                <ul class="flex flex-wrap gap-8 bg-violet-500/30 rounded-lg p-8 w-full">
                    <?php foreach ($user->sponsorsLogos()->toStructure() as $sponsor): ?>
                        <li class="flex items-center justify-between">
                            <a href="<?= $sponsor->link() ?>" target="_blank">
                                <?= $sponsor->logo()->toFile()->thumb(['width' => 175])->html() ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="flex flex-col md:items-center gap-8">
            <h2 class="text-4xl md:text-6xl font-semibold text-white md:whitespace-nowrap text-shadow-xs text-shadow-black">Sigue al Mago Scott</h2>

            <ul class="flex md:justify-between gap-4 md:gap-8 **:[svg]:fill-fuchsia-400 **:[path]:fill-fuchsia-500">
                <?php if ($file = 'assets/svgs/instagram.svg'): ?>
                    <li class="bg-indigo-950 p-6 md:p-8 rounded-full **:[svg]:w-10 **:[svg]:h-10 md:**:[svg]:w-12 md:**:[svg]:h-12">
                        <a href="<?= $user?->instagram() ?? '#' ?>" target="_blank">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($file = 'assets/svgs/facebook.svg'): ?>
                    <li class="bg-indigo-950 p-6 md:p-8 rounded-full **:[svg]:w-10 **:[svg]:h-10 md:**:[svg]:w-12 md:**:[svg]:h-12">
                        <a href="<?= $user?->facebook() ?? '#' ?>" target="_blank">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($file = 'assets/svgs/youtube.svg'): ?>
                    <li class="bg-indigo-950 p-6 md:p-7 rounded-full **:[svg]:w-12 **:[svg]:h-12 md:**:[svg]:w-14 md:**:[svg]:h-14">
                        <a href="<?= $user?->youtube() ?? '#' ?>" target="_blank">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="container mt-16 pb-16 md:pb-24 lg:pb-32 flex flex-col lg:flex-row justify-between gap-24 lg:gap-28 lg:*:w-1/2">
        <div class="flex flex-col">
            <h2 class="text-4xl md:text-6xl md:text-center font-semibold text-white text-balance text-shadow-xs text-shadow-black">Artículos recientes</h2>
            <ul class="mt-8 grid md:grid-cols-3 gap-x-8 gap-y-16">
                <?php foreach ($site->find('blog')->children()->listed()->limit(3) as $article): ?>
                    <li class="group relative after:absolute after:left-0 after:right-0 after:-bottom-8 md:after:-bottom-4 after:h-0.5 md:after:h-1 after:bg-white/50 after:rounded-full group-hover:after:bg-red-700">
                        <a href="<?= $article->url() ?>" class="grid gap-2">
                            <div class="grid grid-cols-2 md:grid-cols-1 items-center gap-4">
                                <div class="*:[img]:border-2 *:[img]:border-violet-500 *:[img]:rounded-xl *:[img]:bg-violet-700 *:[img]:aspect-video *:[img]:object-cover group-hover:*:[img]:rounded-3xl group-hover:*:[img]:transition-all *:transition-all">
                                    <?= $article->cover()->toFile()->crop(300, 200) ?>
                                </div>
                                <h3 class="relative text-2xl md:text-xl group-hover:text-fuchsia-300">
                                    <?= $article->title() ?>
                                </h3>
                            </div>
                            <div class="text-white/90 text-lg font-sans">
                                <?= $article->summary()->excerpt(100) ?>
                            </div>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>

        <div>
            <h2 class="text-4xl md:text-6xl text-center font-semibold text-white  text-shadow-xs text-shadow-black">Una newsletter mágica</h2>
            <p class="mt-4 md:mt-8 text-center text-balance text-xl md:text-3xl text-violet-100 text-shadow-black">Mantente al tanto de todos los espectáculos, sorteos y entradas gratis para asistir en directo a los shows del Mago Scott.</p>
            <form action="#" class="mt-8 md:mt-16">
                <div class="relative w-full flex flex-col md:flex-row gap-4 items-center">
                    <input class="w-full md:mr-8 px-8 py-6 rounded-full bg-white/50 text-2xl text-indigo-950 font-semibold uppercase focus:outline-none focus:ring-2 focus:ring-indigo-950 placeholder:text-indigo-950/70 focus:placeholder:opacity-0 transition-all" type="email" placeholder="Tu correo electrónico" />

                    <button class="w-min md:absolute md:right-0 bg-indigo-950 text-white font-semibold uppercase p-4 rounded-full aspect-square hover:bg-indigo-800" type="submit">Suscribirse</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-indigo-950 text-white">
        <div class="container py-8 flex flex-col-reverse md:flex-row gap-8 justify-between items-center flex-wrap">
            <div class="w-full md:w-auto text-sm text-white/80">Copyright © <?= Date("Y") ?> Mago Scott</div>

            <ul class="grid grid-cols-2 items-center md:flex gap-x-8 gap-y-4 **:[a]:hover:text-fuchsia-400 **:[a]:active:text-fuchsia-400 whitespace-nowrap">
                <li><a href="">Privacidad y cookies</a></li>
                <li><a href="">Aviso legal</a></li>
                <li><a href="">Gestionar consentimiento</a></li>
                <li><a href="">Mapa del sitio</a></li>
            </ul>
        </div>
    </div class="bg-indigo-950">
</footer>
