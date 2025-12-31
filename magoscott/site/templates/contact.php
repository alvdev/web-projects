<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/contact') ?>

<?php $user = $kirby->users()->findBy('username', 'Scott') ?>

<section class="infinite-scroll-h pt-16 pb-26 md:pt-24 md:pb-34 lg:pt-32 lg:pb-42 **:border-0 **:gap-2 text-5xl md:text-7xl lg:text-9xl uppercase font-extrabold text-violet-400/40">
    <div class="relative overflow-x-clip">
        <div class="carousel flex items-center w-max will-change-transform *:whitespace-nowrap *:after:content-['/'] animate-infinite-scroll-r hover:animate-pause
        [animation-duration:20s]
         sm:[animation-duration:25s]
         md:[animation-duration:30s]
         lg:[animation-duration:35s]">
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

<section>
    <div class="container flex flex-col md:flex-row gap-8 md:gap-16 lg:gap-32 md:items-center">
        <h1 class="lg:w-1/2 text-5xl md:text-6xl lg:text-7xl font-bold leading-none text-balance">¿Hablamos?</h1>
        <div class="lg:w-1/2 md:w-full flex justify-center gap-4">
            <div class="flex md:flex-col xl:flex-row gap-4 w-full">
                <?php if ($user->whatsapp()->isNotEmpty()): ?>
                    <a href="https://wa.me/<?= $user->whatsapp() ?>" class="relative inline-flex items-center gap-2 md:gap-4 md:text-xl font-semibold border px-6 md:px-8 py-4 rounded-full min-w-1/3 w-full justify-center uppercase *:[svg]:w-6 md:*:[svg]:w-8 **:[path]:fill-white" aria-label="Escribir por Whatsapp">
                        <?= svg('assets/svgs/whatsapp.svg') ?> Whatsapp
                    </a>
                <?php endif ?>
                <?php if ($user->phone()->isNotEmpty()): ?>
                    <a href="tel:<?= $user->phone() ?>" class="relative inline-flex items-center gap-2 md:gap-4 md:text-xl font-semibold border px-6 md:px-8 py-4 rounded-full min-w-1/3 w-full justify-center uppercase *:[svg]:w-7 md:*:[svg]:w-9 **:[path]:fill-white" aria-label="Llamar por teléfono">
                        <?= svg('assets/svgs/phone.svg') ?> Llamada
                    </a>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>

<div class="container mt-8 md:mt-16 flex flex-col lg:flex-row items-center lg:gap-32 w-full *:w-full lg:*:w-1/2">
    <div>
        <?php $infoEmail = $user->infoEmail()->isNotEmpty() ? $user->infoEmail() : 'info@magoscott.com' ?>
        <p class="mt-8 text-2xl md:text-3xl lg:text-4xl text-pretty">También puedes enviar un email a <a href="mailto:<?= $infoEmail ?>" class="text-violet-300"><?= $infoEmail ?></a href="mailto"> o completar el formulario si necesitas información para la contratación de los espectáculos. Recibirás una respuesta muy rápidamente.</p>
        <p class="mt-8">* Recuerda revisar tu bandeja de correo no deseado (spam)</p>
    </div>

    <?php snippet('forms/contact') ?>
</div>
<?php endslot() ?>
