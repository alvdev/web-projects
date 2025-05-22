<section class="bg-sky-100/90 backdrop-blur-lg border-t-2 border-white py-28" id="projects">
    <div class="container flex gap-24" x-data="{ tab: 1 }">
        <div class="w-2/5">
            <div class="sticky top-8 ">
                <div class="font-bold leading-none flex flex-wrap flex-col gap-y-2 mb-4">
                    <span class="text-sky-800 text-xl">Proyectos</span>
                    <h3 class="text-black-800 text-4xl lg:text-5xl xl:text-[64px] tracking-[-1.5px] relative before:rounded-full before:bg-primary before:block before:absolute before:top-[2px] before:left-0 before:-z-1 before:w-[36px] lg:before:w-[48px] xl:before:w-[64px] before:h-[36px] lg:before:h-[48px] xl:before:h-[64px]">
                        Se admiten usuarios
                    </h3>
                </div>

                <div class="mt-8 flex flex-wrap gap-4 lg:flex-col">
                    <button class="p-4 rounded-lg justify-between items-center inline-flex group" x-on:click="tab = 1" :class="tab === 1 ? 'bg-gray-950 text-white' : 'bg-white'">
                        Short links
                        <span class="inline-block ml-3 group-hover:animate-arrow-move-up">
                            🡪
                        </span>
                    </button>

                    <button class="p-4 rounded-lg justify-between items-center inline-flex group" x-on:click="tab = 2" :class="tab === 2 ? 'bg-gray-950 text-white' : 'bg-white'">
                        Email marketing
                        <span class="inline-block ml-3 group-hover:animate-arrow-move-up">
                            🡪
                        </span>
                    </button>

                    <button class="p-4 rounded-lg justify-between items-center inline-flex group" x-on:click="tab = 3" :class="tab === 3 ? 'bg-gray-950 text-white' : 'bg-white'">
                        Web hosting
                        <span class="inline-block ml-3 group-hover:animate-arrow-move-up">
                            🡪
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="w-3/5" x-show="tab === 1" x-transition:enter>
            <div id="about_me_tab" class="tab-content relative active">
                <div class="grid grid-cols-1">
                    <h4 class="text-black-800 text-2xl font-bold mb-6">Based in German</h4>
                    <p class="paragraph mb-7">Para evitar el abuso indiscriminado se piden cafés mensuales. That is
                        where I come in. A
                        lover of words, a wrangler of copy. Here to create copy that not only reflects who you
                        are
                        and what you stand for,</p>
                    <p class="paragraph mb-14">but words that truly land with those that read them, calling your
                        audience
                        in and making them want more.</p>

                    <ul class="flex-col gap-3 inline-flex">
                        <li class="gap-10 inline-flex items-center">
                            <span class="w-[110px] text-black-text-800 text-lg font-normal leading-none">
                                Estado</span>
                            <span class="text-black-800 text-2xl font-bold font-Syne leading-8">
                                Estable / en desarrollo</span>
                        </li>
                        <li class="gap-10 inline-flex items-center">
                            <span class="w-[110px] text-black-text-800 text-lg font-normal leading-none">
                                Cafés</span>
                            <span class="text-black-800 text-2xl font-bold font-Syne leading-8">
                                1 al mes</span>
                        </li>
                        <li class="gap-10 inline-flex items-center">
                            <span class="w-[110px] text-black-text-800 text-lg font-normal leading-none">
                                Phone</span>
                            <span class="text-black-800 text-2xl font-bold font-Syne leading-8">
                                +(2) 870 174 302</span>
                        </li>
                        <li class="gap-10 inline-flex items-center">
                            <span class="w-[110px] text-black-text-800 text-lg font-normal leading-none">
                                Enlace</span>
                            <a class="link-blue-xl" href=" http://lnk.alv.dev">lnk.alv.dev</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="w-3/5" x-show="tab === 2" x-transition:enter>Tab 2</div>
        <div class="w-3/5" x-show="tab === 3" x-transition:enter>Tab 3</div>
    </div>
</section>
