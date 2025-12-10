<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/contact') ?>

<section class="infinite-scroll-h py-8 **:border-0 **:gap-2 text-9xl uppercase font-extrabold text-violet-400/40">
    <div class="relative overflow-x-clip">
        <div class="carousel flex items-center w-max *:whitespace-nowrap *:after:content-['/'] animate-infinite-scroll-r hover:animate-pause" style="animation-duration: 20s">
            <div class="w-full">
                Presentador
            </div>
            <div class="w-full">
                Mago
            </div>
            <div class="w-full">
                Actor de doblaje
            </div>
            <div class="w-full">
                Showman
            </div>
            <div class="w-full">
                Presentador
            </div>
            <div class="w-full">
                Mago
            </div>
            <div class="w-full">
                Actor de doblaje
            </div>
            <div class="w-full">
                Showman
            </div>
        </div>
    </div>
</section>

<div class="container mt-48 flex items-center gap-32 *:w-1/2">
    <hgroup>
        <h1 class="text-7xl font-bold leading-none text-balance">¿Hablamos?</h1>
        <p class="mt-8 text-4xl text-pretty">Envíanos un email si necesitas información para la contratación de los espectáculos. Resolveremos todas tus dudas en el menor tiempo posible.</p>
    </hgroup>

    <div>
        <form action="" class="flex flex-col gap-8 **:[input,textarea]:text-2xl **:[input,textarea]:text-black **:[input,textarea]:bg-violet-300 **:[input,textarea]:rounded-4xl **:[input,textarea]:px-6 **:[input,textarea]:py-4 **:[input,textarea]:pb-5.5">
            <input type="text" placeholder="Introduce tu nombre">
            <input type="email" placeholder="y un email al que poder responderte">
            <input type="tel" placeholder="o tu teléfono si prefieres una llamada">

            <div class="relative flex items-center">
                <textarea name="" id="" class="w-full mr-12 h-32 resize-none" placeholder="Escribe tu pregunta o consulta aquí"></textarea>
                <button type="submit" class="absolute right-0 bg-violet-700 ring-4 ring-violet-400 uppercase font-semibold text-xl aspect-square rounded-full p-4">Enviar<br> consulta</button>
            </div>
        </form>
    </div>
</div>
<?php endslot() ?>
