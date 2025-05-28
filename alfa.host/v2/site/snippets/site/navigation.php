<div x-data="{ scrollNav: false, openSubmenu: null }" x-intersect:leave.margin.50%="scrollNav = true" x-intersect.enter="scrollNav = false" x-transition.duration.3000ms>
    <nav class="z-20 rounded-b-2xl backdrop-blur-sm transition-all duration-300" :class="scrollNav ? 'fixed inset-x-0 top-0 bg-black/90 py-0 **:[.logo_svg]:w-24' : 'relative py-6 [&>[id*=submenu]]:mt-8'">
        <div class="container grid items-center justify-between grid-cols-2 lg:flex lg:gap-4">
            <div class="logo">
                <a href="/" title="Alfa Host">
                    <?= svg('assets/logo.svg') ?>
                </a>
            </div>
            <div id="burger" class="block text-right lg:hidden">
                <span class="text-4xl font-bold text-white">
                    ☰
                </span>
            </div>

            <ul id="main-menu" class="grid items-center justify-end grid-cols-2 gap-12 py-12 text-xl text-center uppercase border-t-2 border-blue-600 col-span-full md:text-center md:grid-cols-4 lg:flex lg:gap-4 md:border-0 lg:py-4 lg:text-base lg:mt-0 lg:bg-transparent">
                <li>
                    <a href="/" id="inicio" class="p-2 border-b-2 border-teal-400 lg:text-white">
                        Inicio
                    </a>
                </li>
                <li>
                    <button id="hosting" class="uppercase p-2 lg:text-white"
                        x-on:click.stop="openSubmenu === 'hosting' ? openSubmenu = null : openSubmenu = 'hosting'">
                        Hosting
                    </button>
                </li>
                <li>
                    <button id="dominios" class="uppercase p-2 lg:text-white"
                        x-on:click.stop="openSubmenu === 'domains' ? openSubmenu = null : openSubmenu = 'domains'">
                        Dominios
                    </button>
                </li>
                <li>
                    <button id="marketing" class="uppercase p-2 lg:text-white"
                        x-on:click.stop="openSubmenu === 'marketing' ? openSubmenu = null : openSubmenu = 'marketing'">
                        Marketing
                    </button>
                </li>
                <li>
                    <button id="recursos" class="uppercase p-2 lg:text-white"
                        x-on:click.stop="openSubmenu === 'resources' ? openSubmenu = null : openSubmenu = 'resources'">
                        Recursos
                    </button>
                </li>
                <li>
                    <a href="/blog" id="blog" class="p-2 lg:text-white">
                        Blog
                    </a>
                </li>
                <li>
                    <a href="/soporte" id="soporte" class="p-2 lg:text-white">
                        soporte
                    </a>
                </li>
                <li>
                    <a href="/contacto" id="contacto" class="p-2 lg:text-white">
                        Contacto
                    </a>
                </li>

            </ul>

        </div>

        <div id="hosting-submenu" class="mx-4 z-10 bg-white backdrop-blur-xl rounded-t-md p-12 shadow-2xl border-b-[3px] border-b-gray-300" x-show="openSubmenu === 'hosting'" x-transition:enter.duration.250ms x-on:click.away="openSubmenu = null" x-cloak>
            <div class="close-submenu absolute inset-x-0 -bottom-8 mx-auto bg-gray-300/90 backdrop-blur-xl border-x-[3px] p-2 aspect-auto h-8 flex items-center justify-center shadow-xl hover:cursor-pointer uppercase text-xs font-semibold text-blue-700 hover:text-black hover:bg-opacity-100 border-none rounded-b-md">
                Cerrar menú
                <span class="rotate-90 ml-2  text-2xl">✕</span>
            </div>
            <div class="container flex">
                <div class="w-4/6">
                    <ul class="grid grid-cols-2 gap-8">
                        <li class="flex items-center divide-x-2 divide-gray-400">
                            <div class="pr-4">
                                <img src="<?= asset('assets/icons/navigation/hosting-web-emprendedor.svg')->url() ?>" alt="Hosting emprendedores" class="w-12">
                            </div>
                            <div class="pl-4">
                                <p class="text-gray-900 font-medium">
                                    Hosting emprendedores
                                </p>
                                <p class="text-base">
                                    Hosting description
                                </p>
                            </div>
                        </li>
                        <li class="flex items-center divide-x-2 divide-gray-400">
                            <div class="pr-4">
                                <img src="<?= asset('assets/icons/navigation/hosting-web-empresario.svg')->url() ?>" alt="Hosting empresarios" class="w-12">
                            </div>
                            <div class="pl-4">
                                <p class="text-gray-900 font-medium">
                                    Hosting empresarios
                                </p>
                                <p class="text-base">
                                    Hosting description
                                </p>
                            </div>
                        </li>
                        <li class="flex items-center divide-x-2 divide-gray-400">
                            <div class="pr-4">
                                <img src="<?= asset('assets/icons/navigation/hosting-web-ecommerce.svg')->url() ?>" alt="Hosting eCommerce" class="w-12">
                            </div>
                            <div class="pl-4">
                                <p class="text-gray-900 font-medium">
                                    Hosting eCommerce
                                </p>
                                <p class="text-base">
                                    Hosting description
                                </p>
                            </div>
                        </li>
                        <li class="flex items-center divide-x-2 divide-gray-400">
                            <div class="pr-4">
                                <img src="<?= asset('assets/icons/navigation/hosting-web-gratis.svg')->url() ?>" alt="Hosting gratis" class="w-12">
                            </div>
                            <div class="pl-4">
                                <p class="text-gray-900 font-medium">
                                    Hosting gratis
                                </p>
                                <p class="text-base">
                                    Hosting description
                                </p>
                            </div>
                        </li>
                        <li class="flex items-center divide-x-2 divide-gray-400">
                            <div class="pr-4">
                                <img src="<?= asset('assets/icons/navigation/servidor-dedicado.svg')->url() ?>" alt="Servidor dedicado" class="w-12">
                            </div>
                            <div class="pl-4">
                                <p class="text-gray-900 font-medium">
                                    Servidor dedicado
                                </p>
                                <p class="text-base">
                                    Hosting description
                                </p>
                            </div>
                        </li>
                        <li class="flex items-center divide-x-2 divide-gray-400">
                            <div class="pr-4">
                                <img src="<?= asset('assets/icons/navigation/servidores-virtuales.svg')->url() ?>" alt="Servidores virtuales" class="w-12">
                            </div>
                            <div class="pl-4">
                                <p class="text-gray-900 font-medium">
                                    Servidores virtuales
                                </p>
                                <p class="text-base">
                                    Hosting description
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="bg-cyan-400 rounded-xs p-8 flex-1">
                    Featured
                </div>
            </div>
        </div>

        <div id="domains-submenu" class="mx-4 z-10 bg-white backdrop-blur-xl rounded-t-md p-12 shadow-2xl border-b-[3px] border-b-gray-300" x-show="openSubmenu === 'domains'" x-transition:enter.duration.250ms x-on:click.away="openSubmenu = null" x-cloak>
            <div class="close-submenu absolute inset-x-0 -bottom-8 mx-auto bg-gray-300/90 backdrop-blur-xl border-x-[3px] p-2 aspect-auto h-8 flex items-center justify-center shadow-xl hover:cursor-pointer uppercase text-xs font-semibold text-blue-700 hover:text-black hover:bg-opacity-100 border-none rounded-b-md">
                Cerrar menú
                <span class="rotate-90 ml-2  text-2xl">✕</span>
            </div>
            <div class="container grid gap-8 ">
                <ul class="flex w-full place-content-between gap-8">
                    <li class="flex items-center divide-x-2 divide-gray-400">
                        <div class="pr-4">
                            <img src="<?= asset('assets/icons/navigation/registro-dominios.svg')->url() ?>" alt="Registro de dominio" class="w-12">
                        </div>
                        <div class="pl-4">
                            <p class="text-gray-900 font-medium">
                                Registro de dominio
                            </p>
                            <p class="text-base">
                                Registro de dominio
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center divide-x-2 divide-gray-400">
                        <div class="pr-4">
                            <img src="<?= asset('assets/icons/navigation/transferencia-dominios.svg')->url() ?>" alt="Transferencia de dominio" class="w-12">
                        </div>
                        <div class="pl-4">
                            <p class="text-gray-900 font-medium">
                                Transferencia de dominio
                            </p>
                            <p class="text-base">
                                Transferencia de dominio
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center divide-x-2 divide-gray-400">
                        <div class="pr-4">
                            <img src="<?= asset('assets/icons/navigation/precio-dominios.svg')->url() ?>" alt="Precios de dominios" class="w-12">
                        </div>
                        <div class="pl-4">
                            <p class="text-gray-900 font-medium">
                                Precios de dominios
                            </p>
                            <p class="text-base">
                                Precios de dominios
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center divide-x-2 divide-gray-400">
                        <div class="pr-4">
                            <img src="<?= asset('assets/icons/navigation/dominios-nivel-superior.svg')->url() ?>" alt="Dominios Top Level" class="w-12">
                        </div>
                        <div class="pl-4">
                            <p class="text-gray-900 font-medium">
                                Dominios Top Level
                            </p>
                            <p class="text-base">
                                Dominios Top Level
                            </p>
                        </div>
                    </li>
                </ul>

                <ul id="domains-offers" class="flex place-content-between gap-6 text-center">
                    <li class="bg-gray-300 p-6 rounded-md w-full">
                        <div class="text-2xl font-semibold">
                            .com
                        </div>
                        <div class="text-red-600">
                            <span class="line-through text-sm">6.95</span>
                            <span class="font-semibold">-30%</span>
                        </div>
                        <div class="font-bold text-xl text-gray-600">
                            4.95
                        </div>
                    </li>
                    <li class="bg-gray-300 p-6 rounded-md w-full">
                        <div class="text-2xl font-semibold">
                            .net
                        </div>
                        <div class="text-red-600">
                            <span class="line-through text-sm">6.95</span>
                            <span class="font-semibold">-30%</span>
                        </div>
                        <div class="font-bold text-xl text-gray-600">
                            4.95
                        </div>
                    </li>
                    <li class="bg-gray-300 p-6 rounded-md w-full">
                        <div class="text-2xl font-semibold">
                            .biz
                        </div>
                        <div class="text-red-600">
                            <span class="line-through text-sm">6.95</span>
                            <span class="font-semibold">-30%</span>
                        </div>
                        <div class="font-bold text-xl text-gray-600">
                            4.95
                        </div>
                    </li>
                    <li class="bg-gray-300 p-6 rounded-md w-full">
                        <div class="text-2xl font-semibold">
                            .org
                        </div>
                        <div class="text-red-600">
                            <span class="line-through text-sm">6.95</span>
                            <span class="font-semibold">-30%</span>
                        </div>
                        <div class="font-bold text-xl text-gray-600">
                            4.95
                        </div>
                    </li>
                    <li class="bg-gray-300 p-6 rounded-md w-full">
                        <div class="text-2xl font-semibold">
                            .club
                        </div>
                        <div class="text-red-600">
                            <span class="line-through text-sm">6.95</span>
                            <span class="font-semibold">-30%</span>
                        </div>
                        <div class="font-bold text-xl text-gray-600">
                            4.95
                        </div>
                    </li>
                    <li class="bg-gray-300 p-6 rounded-md w-full">
                        <div class="text-2xl font-semibold">
                            .live
                        </div>
                        <div class="text-red-600">
                            <span class="line-through text-sm">6.95</span>
                            <span class="font-semibold">-30%</span>
                        </div>
                        <div class="font-bold text-xl text-gray-600">
                            4.95
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div id="marketing-submenu" class="mx-4 z-10 bg-white backdrop-blur-xl rounded-t-md p-12 shadow-2xl border-b-[3px] border-b-gray-300" x-show="openSubmenu === 'marketing'" x-transition:enter.duration.250ms x-on:click.away="openSubmenu = null" x-cloak>
            <div class="close-submenu absolute inset-x-0 -bottom-8 mx-auto bg-gray-300/90 backdrop-blur-xl border-x-[3px] p-2 aspect-auto h-8 flex items-center justify-center shadow-xl hover:cursor-pointer uppercase text-xs font-semibold text-blue-700 hover:text-black hover:bg-opacity-100 border-none rounded-b-md">
                Cerrar menú
                <span class="rotate-90 ml-2  text-2xl">✕</span>
            </div>
            <div class="container grid gap-8 ">
                <ul class="grid grid-cols-3 gap-8 border-b-2 border-gray-400 pb-8">
                    <li class="flex items-center divide-x-2 divide-gray-400">
                        <div class="pr-4">
                            <img src="<?= asset('assets/icons/navigation/email-marketing.svg')->url() ?>" alt="Email marketing" class="w-12">
                        </div>
                        <div class="pl-4">
                            <p class="text-gray-900 font-medium">
                                Email marketing
                            </p>
                            <p class="text-base">
                                Email marketing
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center divide-x-2 divide-gray-400">
                        <div class="pr-4">
                            <img src="<?= asset('assets/icons/navigation/posicionamiento-web.svg')->url() ?>" alt="Posicionamiento web" class="w-12">
                        </div>
                        <div class="pl-4">
                            <p class="text-gray-900 font-medium">
                                Posicionamiento web
                            </p>
                            <p class="text-base">
                                Posicionamiento web
                            </p>
                        </div>
                    </li>
                    <li class="flex items-center divide-x-2 divide-gray-400">
                        <div class="pr-4">
                            <img src="<?= asset('assets/icons/navigation/social-media-marketing.svg')->url() ?>" alt="Social Media Marketing" class="w-12">
                        </div>
                        <div class="pl-4">
                            <p class="text-gray-900 font-medium">
                                Social Media Marketing
                            </p>
                            <p class="text-base">
                                Social Media Marketing
                            </p>
                        </div>
                    </li>
                </ul>
                <div class="flex place-content-between">
                    Servicios complementarios:
                    <a href="#">Diseño web</a>
                    <a href="#">Integraciones web</a>
                    <a href="#">Mantenimiento web</a>
                    <a href="#">Creación de contenidos</a>
                </div>
            </div>
        </div>

        <div id="resources-submenu" class="mx-4 z-10 bg-white backdrop-blur-xl rounded-t-md p-12 shadow-2xl border-b-[3px] border-b-gray-300" x-show="openSubmenu === 'resources'" x-transition:enter.duration.250ms x-on:click.away="openSubmenu = null" x-cloak>
            <div class="close-submenu absolute inset-x-0 -bottom-8 mx-auto bg-gray-300/90 backdrop-blur-xl border-x-[3px] p-2 aspect-auto h-8 flex items-center justify-center shadow-xl hover:cursor-pointer uppercase text-xs font-semibold text-blue-700 hover:text-black hover:bg-opacity-100 border-none rounded-b-md">
                Cerrar menú
                <span class="rotate-90 ml-2  text-2xl">✕</span>
            </div>
            <div class="container flex gap-12">
                <div class="flex gap-8 w-2/3 whitespace-nowrap border-r-2 border-gray-300">
                    <div class="flex-1">
                        <div class="mb-8">
                            <h2 class="text-xl font-medium mb-4">
                                Gestores de contenido
                            </h2>
                            <ul class="flex flex-col gap-4">
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Wordpress" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Wordpress
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Drupal" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Drupal
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Moodle" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Moodle
                                    </p>
                                </li>
                            </ul>
                        </div>
                        <div class="">
                            <h2 class="text-xl font-medium mb-4">
                                Tiendas eCommerce
                            </h2>
                            <ul class="flex flex-col gap-4">
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="WooCommerce" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        WooCommerce
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Prestashop" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Prestashop
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Magento" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Magento
                                    </p>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="mb-8">
                            <h2 class="text-xl font-medium mb-4">
                                Promoción web
                            </h2>
                            <ul class="flex flex-col gap-4">
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Redes sociales" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Redes sociales
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Páginas de captura" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Páginas de captura
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Plantillas de email" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Plantillas de email
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Plantillas HTML" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Plantillas HTML
                                    </p>
                                </li>
                            </ul>
                        </div>
                        <div class="">
                            <h2 class="text-xl font-medium mb-4">
                                Software y scripts
                            </h2>
                            <ul class="flex flex-col gap-4">
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Rotación de artículos" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Rotación de artículos
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Screaming from" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Screaming from
                                    </p>
                                </li>
                                <li class="flex items-center">
                                    <img src="<?= asset('assets/icons/navigation/www.svg')->url() ?>" alt="Keyword planner" class="w-6">
                                    <p class="pl-4 text-gray-900 font-medium text-base">
                                        Keyword planner
                                    </p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col flex-1 gap-4">
                    <div class="bg-gray-300 flex-1 p-8 rounded-xs flex place-content-center place-items-center">
                        CEED placeholder
                    </div>
                    <div class="flex gap-4 place-items-center">
                        <div class="text-center leading-tight text-sm border rounded-xs border-gray-500 p-2">
                            Web analizer
                        </div>
                        <div class="text-center leading-tight text-sm border rounded-xs border-gray-500 p-2">
                            Whois service
                        </div>
                        <div class="text-center leading-tight text-sm border rounded-xs border-gray-500 p-2">
                            Web page creator
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</div>
