<section>
    <div class="container flex items-center">
        <div class="w-1/2 pr-16 pt-8">
            <span class="font-bold text-4xl flex flex-wrap items-center mb-3">
                <span class="mr-2">
                    <svg width="65" height="2" viewBox="0 0 65 2" fill="none">
                        <path d="M0 1H65" stroke="#080808"></path>
                    </svg>
                </span>
                ¡Hola!😉, soy
            </span>
            <h1 class="text-9xl font-semibold">
                Alvaro<span class="block -ml-1.5">Devesa</span>
            </h1>
            <p class="text-lg mt-8 md:text-xl xl:text-2xl">
                Sysadmin y desarrollador de software,<br>
                principalmente para aplicaciones web<br>
                y móviles (JS, PHP, Dart, Go)
            </p>
            <h2 class="mt-8 text-2xl font-bold">Trabajando en Internet desde 2005</h2>

            <div class="flex mt-16 gap-8">
                <a href="#aboutme" class="link-yellow flex items-center flex-wrap group">
                    Sobre mí
                    <span class="arrow inline-block transition-all group-hover:rotate-45">
                        🡮
                    </span>
                </a>
                <a href="#contact" class="link-green-xl flex items-center flex-wrap group">
                    Contacto
                    <span class="arrow inline-block transition-all group-hover:rotate-45">
                        🡮
                    </span>
                </a>
                <a href="#projects" class="link-blue-2xl flex items-center flex-wrap group">
                    Projectos
                    <span class="arrow leading-0 items-center justify-center transition-all group-hover:rotate-45">
                        🡮
                    </span>
                </a>
            </div>
        </div>

        <div class="screen-overlay rounded-b w-1/2 [&>img]:max-h-[80vh] self-stretch bg-green-500 h-auto">
            <!-- <?= asset('assets/images/header-placeholder.webp') ?> -->
            <?php if ($asset = asset('assets/images/alvdev-ascii-face.svg')): ?>
                <img class="rounded-b-xs object-cover mask-b-from-0%"
                    src="<?= $asset->url() ?>" alt=""
                    srcset="<?= $asset->srcset([100, 200, 400, 800]) ?>"
                    height="<?= $asset->height() ?>"
                    width="<?= $asset->width() ?>">
            <?php endif ?>
        </div>
        <!-- <div class="bg-decoration-square"></div> -->
    </div>
</section>
