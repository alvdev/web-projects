<section id="course" class="relative text-5xl text-center">
    <div class="grid">
        <div class="justify-self-end relative top-20">
            <img src="<?= asset('images/hand-1.webp') ?>" alt="">
        </div>
        <h2 class="container text-5xl text-center">
            <span class="font-bold">El Curso de Hipnosis Terapeutica te ayudará</span> a complementar tus conocimientos y a mejorar en la forma de tratar los problemas de tus pacientes.
        </h2>
        <div class="mt-8">
            <img src="<?= asset('images/hand-2.webp') ?>" alt="">
        </div>
    </div>
</section>

<section>
    <div class="container grid grid-cols-4 gap-8 items-center">
        <div class="text-center bg-blue-950 text-white self-stretch flex flex-col items-center justify-center gap-2 rounded-lg">
            <div class="text-xl uppercase font-semibold">Duración</div>
            <div class="text-3xl font-light">3 días consecutivos</div>
        </div>
        <div class="border border-blue-950 rounded-lg px-6 py-4">
            Durante el curso, se entregará la teoría, práctica y aprendizaje necesarios para la comprensión total de un proceso de hipnosis, sus fases y cómo afrontar cada una de ellas.
        </div>
        <div class="text-center bg-blue-950 text-white self-stretch flex flex-col items-center justify-center gap-2 rounded-lg">
            <div class="text-xl uppercase font-semibold">Modalidad</div>
            <div class="text-3xl font-light">Presencial</div>
        </div>
        <div class="border border-blue-950 rounded-lg px-6 py-4">
            Después de superar esta formación, dispondrás de todas las herramientas para lograr la efectividad total en cada sesión de hipnosis terapéutca que realices con tus pacientes.
        </div>
    </div>

    <div class="container mt-8 grid grid-cols-4 gap-8 items-center">
        <div class="relative">
            <img src="<?= asset('images/fases-curso-hipnosis-1.webp') ?>" alt="" class="rounded-lg">
            <div class="absolute z-10 flex flex-col justify-between h-full w-full top-0 p-8 *:fill-white ">
                <?= asset('icons/check-note.svg', true) ?>
                <h3 class="text-white text-4xl flex items-center gap-4">
                    <span class="text-8xl">1</span>
                    Evaluación preliminar
                </h3>
            </div>
        </div>
        <div class="relative">
            <img src="<?= asset('images/fases-curso-hipnosis-2.webp') ?>" alt="" class="rounded-lg">
            <div class="absolute z-10 flex flex-col justify-between h-full w-full top-0 p-8 *:fill-white ">
                <?= asset('icons/road-plan.svg', true) ?>
                <h3 class="text-white text-4xl flex items-center gap-4">
                    <span class="text-8xl">2</span>
                    Planificación personalizada
                </h3>
            </div>
        </div>
        <div class="relative">
            <img src="<?= asset('images/fases-curso-hipnosis-3.webp') ?>" alt="" class="rounded-lg">
            <div class="absolute z-10 flex flex-col justify-between h-full w-full top-0 p-8 *:fill-white ">
                <?= asset('icons/road-indicators.svg', true) ?>
                <h3 class="text-white text-4xl flex items-center gap-4">
                    <span class="text-8xl">3</span>
                    Orientación constante
                </h3>
            </div>
        </div>
        <div class="relative">
            <img src="<?= asset('images/fases-curso-hipnosis-4.webp') ?>" alt="" class="rounded-lg">
            <div class="absolute z-10 flex flex-col justify-between h-full w-full top-0 p-8 *:fill-white ">
                <?= asset('icons/growth-graph.svg', true) ?>
                <h3 class="text-white text-4xl flex items-center gap-4">
                    <span class="text-8xl">4</span>
                    Seguimiento de resultados
                </h3>
            </div>
        </div>
    </div>
</section>
