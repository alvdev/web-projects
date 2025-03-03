<?php
$shadowPages = ['404'];
$shadow = 'shadow-slate-200 border-white';
foreach ($shadowPages as $page) {
    if (str_ends_with($request_uri, $page) || str_ends_with($request_uri, $page . '/')) {
        $shadow = 'shadow-slate-800 border-slate-600';
    }
}
?>

<footer class="relative bg-blue-600 bg-[url('../images/free-wolf.webp')] bg-bottom bg-cover md:bg-auto 2xl:bg-cover shadow-[0_-50px_50px] <?= $shadow ?> border-t
before:absolute before:w-6 before:h-6 before:left-0 before:-top-6 before:shadow-[-0.25rem_0.25rem_0_0.25rem_#0155e3] before:rounded-bl-[1.5rem] after:absolute after:w-6 after:h-6 after:right-0 after:-top-6 after:shadow-[0.25rem_0.25rem_0_0.25rem_#2971e8] after:rounded-br-[3rem]">
    <div class="alv-linear-gradient-white">
        <div class="container items-center justify-center gap-8 py-8 news md:flex ">
            <h2 class="text-6xl font-normal text-white uppercase">
                Alfa News
                <span class="hidden md:inline">
                    &rsaquo;&rsaquo;
                </span>
            </h2>
            <div class="">
                <p class="mb-4 text-center text-white">
                    Recibe gratis por email trucos y consejos para hacer crecer tu negocio digital
                </p>
                <form class="inline-flex justify-around w-full gap-2" action="./">
                    <input class="w-full p-3 rounded-xs" type="text" placeholder="Introduce tu email" />
                    <button class="w-full p-3 text-white uppercase bg-black rounded-xs ">
                        suscribirse
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="container py-12 md:flex">
        <div class="grid grid-cols-1 gap-8 text-white md:grid-cols-3 md:w-5/6">
            <section id="hosting">
                <h3 class="mb-2 text-lg font-semibold">
                    <a href="#hosting">
                        Alojamiento web
                    </a>
                </h3>
                <ul class="dropdown">
                    <li>
                        <a class="text-sm leading-8" href="#hosting">
                            Hosting Alfa 1
                        </a>
                    </li>
                    <li>
                        <a class="text-sm leading-8" href="#hosting">
                            Hosting Alfa 2
                        </a>
                    </li>
                    <li>
                        <a class="text-sm leading-8" href="#hosting">
                            Hosting Alfa 3
                        </a>
                    </li>
                    <li>
                        <a class="text-sm leading-8" href="#hosting">
                            Hosting Alfa 1 Pro
                        </a>
                    </li>
                    <li>
                        <a class="text-sm leading-8" href="#hosting">
                            Hosting Alfa 2 Pro
                        </a>
                    </li>
                    <li>
                        <a class="text-sm leading-8" href="#hosting">
                            Hosting Alfa 3 Pro
                        </a>
                    </li>
                    <li>
                        <a class="text-sm leading-8" href="#hosting">
                            Tiendas eCommerce
                        </a>
                    </li>
                </ul>
            </section>
            <section id="marketing">
                <h3 class="mb-2 text-lg font-semibold">
                    <a href="#marketing">
                        Marketing y desarrollo
                    </a>
                </h3>
                <ul class="dropdown">
                    <li class="text-sm leading-8">
                        <a href="./">
                            Diseño web
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Email marketing
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Integraciones web
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Mantenimiento web
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Posicionamiento web
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Creación de contenidos
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Social Media Marketing
                        </a>
                    </li>
                </ul>
            </section>
            <section id="company">
                <h3 class="mb-2 text-lg font-semibold">
                    <a href="#company">
                        Nuestra empresa
                    </a>
                </h3>
                <ul class="dropdown">
                    <li class="text-sm leading-8">
                        <a href="./">
                            Sobre nosotros
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Prensa y medios
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Trabaja con nosotros
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Alianzas estratégicas
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Programa de afiliados
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Testimonios de clientes
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Noticias, consejos y blogs
                        </a>
                    </li>
                </ul>
            </section>
            <section id="social">
                <h3 class="mb-2 text-lg font-semibold">
                    Sociales y sociables
                </h3>
                <p class="mb-6 text-sm text-white">
                    Mantente al día de la actualidad de iWolf Host siguiéndonos en las redes sociales y uniéndote a
                    nuestra comunidad.
                </p>
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-black rounded-full">
                        <?php include(dirname(__DIR__, 1) . '/images/footer/facebook.svg') ?>
                    </div>

                    <div class="p-3 bg-black rounded-full">
                        <?php include(dirname(__DIR__, 1) . '/images/footer/twitter.svg') ?>
                    </div>

                    <div class="p-3 bg-black rounded-full">
                        <?php include(dirname(__DIR__, 1) . '/images/footer/youtube.svg') ?>
                    </div>

                    <div class="p-3 bg-black rounded-full">
                        <?php include(dirname(__DIR__, 1) . '/images/footer/linkedin.svg') ?>
                    </div>
                </div>
            </section>
            <section id="resources">
                <h3 class="mb-2 text-lg font-semibold">
                    <a href="#resources">
                        Recursos recomendados
                    </a>
                </h3>
                <ul class="dropdown">
                    <li class="text-sm leading-8">
                        <a href="./">
                            Cómo crear un sitio web
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Cómo transferir un sitio web
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Comienza tu propio negocio online
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            De emprendedor a empresario digital
                        </a>
                    </li>
                </ul>
            </section>
            <section id="support">
                <h3 class="mb-2 text-lg font-semibold">
                    <a href="#support">
                        Soporte y ayuda
                    </a>
                </h3>
                <ul class="dropdown">
                    <li class="text-sm leading-8">
                        <a href="./">
                            Asistencia técnica
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Videos y tutoriales
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Base de conocimientos
                        </a>
                    </li>
                    <li class="text-sm leading-8">
                        <a href="./">
                            Contacta con nosotros
                        </a>
                    </li>
                </ul>
            </section>
        </div>
        <div class="flex flex-col items-center justify-between md:w-1/6">
            <div>
                LOGO
            </div>
            <div class="[&_path]:stroke-white [&_g]:fill-white [&_path+path]:fill-white flex gap-2 w-full">
                <?php include(dirname(__DIR__, 1) . "/images/footer/paypal.svg") ?>
                <?php include(dirname(__DIR__, 1) . "/images/footer/visa.svg") ?>
                <?php include(dirname(__DIR__, 1) . "/images/footer/mastercard.svg") ?>
                <?php include(dirname(__DIR__, 1) . "/images/footer/amex.svg") ?>
            </div>
        </div>
    </div>

    <div class="alv-linear-gradient-white">
        <?php include_once("copyright.php") ?>
    </div>
</footer>


<div id="totop" class="w-24 h-24 fixed bottom-4 right-4 opacity-30 hover:opacity-50 hover:cursor-pointer z-50" rel="nofollow">
    <?php include(dirname(__DIR__, 1) . "/images/footer/totop.svg") ?>
</div>

<script src="<?= $this->view_dir ?>js/totop.js"></script>
