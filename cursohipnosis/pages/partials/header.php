<header class="sticky z-20 top-0 w-full border-b py-2 bg-white/80 backdrop-blur-md shadow-xl shadow-blue-950/10 transition-all duration-300">
    <div class="container flex items-center justify-between mx-auto px-4">
        <a href="<?= BASE_URL ?>" id="logo" class="w-32 transition-all border-2 border-blue-800 **:[path]:fill-blue-800 rounded-lg" aria-label="Logo de curso de hipnosis terapeutica">
            <?= asset('images/hipnosis-logo.svg', true) ?>
        </a>

        <!-- Hidden checkbox for menu state -->
        <input type="checkbox" id="menu-toggle" class="hidden peer" />

        <!-- Hamburger Icon -->
        <label for="menu-toggle" class="md:hidden cursor-pointer">
            <div class="w-8 h-8 flex flex-col justify-between">
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
            </div>
        </label>

        <!-- Navigation Links -->
        <div class="absolute peer-checked:top-full peer-checked:opacity-100 peer-checked:visible md:relative md:top-0 md:opacity-100 md:visible left-0 w-full md:w-auto -top-[400px] opacity-0 invisible md:flex gap-12 bg-white md:bg-transparent transition-all duration-300 shadow-md md:shadow-none">
            <nav class="container mx-auto px-4 py-6 md:py-0 grid grid-cols-2 lg:flex lg:flex-row items-center gap-4 md:gap-x-12 uppercase text-xl font-semibold border-y md:border-y-0">
                <a href="#intro" class="flex items-center gap-2 group hover:text-blue-800">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">1</span>
                    <span class="md:hidden">Intro</span>
                    <span class="hidden md:block">Introducción</span>
                </a>
                <a href="#testimonials" class="flex items-center gap-2 group hover:text-blue-800">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">2</span>
                    Testimonios
                </a>
                <a href="#course" class="flex items-center gap-2 group hover:text-blue-800">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">3</span>
                    El curso
                </a>
                <a href="#contact" class="flex items-center gap-2 group hover:text-blue-800">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">4</span>
                    Contacto
                </a>
            </nav>
        </div>
    </div>
</header>
