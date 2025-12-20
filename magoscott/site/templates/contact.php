<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/contact') ?>

<section class="infinite-scroll-h pt-16 pb-26 md:pt-24 md:pb-34 lg:pt-32 lg:pb-42 **:border-0 **:gap-2 text-5xl md:text-7xl lg:text-9xl uppercase font-extrabold text-violet-400/40">
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

<div class="container flex flex-col lg:flex-row items-center lg:gap-32 w-full *:w-full lg:*:w-1/2">
    <hgroup>
        <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold leading-none text-balance">¿Hablamos?</h1>
        <p class="mt-8 text-2xl md:text-3xl lg:text-4xl text-pretty">Envíanos un email si necesitas información para la contratación de los espectáculos. Resolveremos todas tus dudas en el menor tiempo posible.</p>
    </hgroup>

    <?php snippet('forms/contact') ?>
</div>
<?php endslot() ?>
