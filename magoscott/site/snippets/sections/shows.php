<section id="shows" class="<?= $class ?>">
    <div class="container">
        <div class="flex items-baseline gap-8">
            <h2 class="text-6xl">Espectáculos</h2>
            <div class="w-full h-1 bg-red-500 rounded-full"></div>
        </div>

        <div class="mt-16 grid grid-cols-3 gap-12 *:flex *:flex-col *:border-2 *:border-white/30 *:p-8 *:rounded-3xl **:[img]:rounded-xl **:[img]:border-2 **:[img]:border-indigo-950 **:[img]:[box-shadow:0_0_30px_rgba(255,255,255,0.4)] **:[iframe]:mt-auto **:[iframe]:w-auto **:[iframe]:h-auto **:[iframe]:aspect-video **:[iframe]:rounded-xl">
            <div>
                <?= asset('assets/images/shows/oigo-voces.webp') ?>
                <h3 class="mt-8 text-3xl text-balance">Show familiar: En ocasiones… ¡Oigo voces! 2.0</h3>
                <p class="my-8 text-white/80">El Mago Scott presenta su nuevo espectáculo de la gira 2024 «En ocasiones…¡Oigo voces! 2.0» con las voces de Harry Potter, Morgan Freeman, Darth Vader y Dumbledore. (Show para toda la familia).</p>
                <iframe width="560" height="315" src="https://www.youtube.com/embed/jmP4YLts-PA?si=EFq_GFL0hhZsvmOM&amp;controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
            <div>
                <?= asset('assets/images/shows/restaurantes.webp') ?>
                <h3 class="mt-8 text-3xl text-balance">Show de magia de cerca: Entre tenedor y tenedor un buen entretenedor</h3>
                <p class="my-8 text-white/80">La magia ocurre delante de los ojos del espectador. Es esa magia que siempre se ve en televisión pero nunca en directo. Este espectáculo es para restaurantes, pubs, discotecas y cocktel donde no hace falta quitar la música.</p>
                <iframe width="560" height="315" src="https://www.youtube.com/embed/d7cNXf78DwA?si=0xpv4nSwL_6wg8iv&amp;controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
            <div>
                <?= asset('assets/images/shows/la-vida.webp') ?>
                <h3 class="mt-8 text-3xl text-balance">Show de magia y humor: La vida te debe un aplauso (mayores de 18)</h3>
                <p class="my-8 text-white/80">El Mago Scott presenta su nuevo espectáculo de la gira 2024 «La vida te debe un aplauso» con las voces de Mari Carmen y sus muñecos entre otros (mayores de 18 años).</p>
                <iframe width="560" height="315" src="https://www.youtube.com/embed/kp_q0O89YUs?si=QrwZC2ulIk0ae4M5&amp;controls=0" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</section>
