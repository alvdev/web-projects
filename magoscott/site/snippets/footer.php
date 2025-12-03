<footer class="mt-32 pt-64 bg-linear-to-b from-indigo-950 from-10% to-red-500 to-50% text-white">
    <div class="container pb-32 flex justify-between items-center">
        <div class="flex flex-col items-center gap-16">
            <h2 class="text-5xl font-bold">Sigue a tu mago favorito</h2>

            <ul class="flex **:[svg]:w-16 **:[svg]:h-16 gap-16">
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
            <h2 class="text-5xl font-bold text-pretty">Una newsletter mágica</h2>
            <p class="mt-8 text-2xl">Mantente al corriente de todos los espectáculos, sorteos y entradas gratis para asistir en directo a los shows del Mago Scott.</p>
            <form action="" class="mt-16 flex">
                <div class="relative w-full flex items-center">
                    <input class="w-full mr-8 p-8 rounded-full border-2 text-2xl uppercase focus:outline-none focus:ring-2 placeholder:text-white focus:placeholder:opacity-0 transition-all" type="email" placeholder="Tu correo electrónico" />

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
