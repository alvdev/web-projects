<footer class="pt-48 bg-linear-to-b from-indigo-950 from-10% to-red-500 to-50% text-white">
    <div class="container pb-32 flex justify-between items-center gap-16 *:w-1/2">
        <div class="flex flex-col items-center gap-16">
            <h2 class="text-5xl font-bold text-shadow-xs text-shadow-black">Sigue a tu mago favorito</h2>

            <ul class="flex gap-16 **:[svg]:w-16 **:[svg]:h-16 **:[svg]:fill-indigo-950 **:[path]:fill-indigo-950">
                <?php if ($file = 'assets/svgs/instagram.svg'): ?>
                    <li>
                        <a href="#">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($file = 'assets/svgs/facebook.svg'): ?>
                    <li>
                        <a href="#">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($file = 'assets/svgs/x.svg'): ?>
                    <li>
                        <a href="#">
                            <?= svg($file) ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="w-2/5">
            <h2 class="text-5xl font-bold text-balance text-shadow-sm text-shadow-black">Una newsletter mágica</h2>
            <p class="mt-8 text-2xl text-shadow-2xs text-shadow-black">Mantente al corriente de todos los espectáculos, sorteos y entradas gratis para asistir en directo a los shows del Mago Scott.</p>
            <form action="" class="mt-16 flex">
                <div class="relative w-full flex items-center">
                    <input class="w-full mr-8 px-8 py-6 rounded-full bg-white/50 text-2xl text-indigo-900 font-semibold uppercase focus:outline-none focus:ring-2 focus:ring-indigo-900 placeholder:text-indigo-900/70 focus:placeholder:opacity-0 transition-all" type="email" placeholder="Tu correo electrónico" />

                    <button class="absolute right-0 bg-indigo-900 text-white font-semibold uppercase p-4 rounded-full aspect-square hover:bg-indigo-800" type="submit">Suscribirse</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-indigo-950 text-white">
        <div class="container py-8 flex justify-between items-center text-sm">
            <div>Copyright © <?= Date("Y") ?> Mago Scott</div>

            <ul class="flex gap-4">
                <li><a href="">Política de privacidad y cookies</a></li>
                <li><a href="">Aviso legal</a></li>
                <li><a href="">Gestionar consentimiento</a></li>
                <li><a href="">Mapa del sitio</a></li>
            </ul>
        </div>
    </div class="bg-indigo-950">
</footer>
