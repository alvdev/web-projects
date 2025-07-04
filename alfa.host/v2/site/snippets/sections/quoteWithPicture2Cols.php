<?php

if ($imgBg = asset('assets/images/convertir-emprendedores.jpg')) {
    $imgBg = $imgBg->thumb(['format' => 'avif'])->url();
}

?>

<section class="py-24 mb-16 bg-center bg-no-repeat bg-cover border-t-4 border-b-4 border-gray-200 md:bg-contain md:bg-left" style="background-image: url('<?= $imgBg; ?>');">
    <div class="mb-12 ml-auto text-sm font-semibold text-right text-black md:text-2xl">
        <div class="table p-2 pr-8 mb-2 ml-auto bg-white/70">
            El combustible del emprendedor son las ideas
        </div>
        <div class="table p-2 pr-8 ml-auto bg-white/70">
            El combustible del empresario son los resultados
        </div>
    </div>
    <h2 class="flex flex-col w-full text-4xl text-right uppercase md:gap-0.5 md:text-4xl tracking-normal [-webkit-text-fill-color:unset]">
        <span class="relative z-0 ml-auto font-semibold md:pr-8 *:text-black! after:absolute after:-z-10 after:w-full after:h-full after:bg-black after:right-0">
            <b class="inline-block text-gradient-light py-2 pl-4 pr-8 font-bold bg-black md:pr-8">Convertir emprendedores</b>
        </span>
        <span class="relative z-0 ml-auto font-semibold md:pr-8 *:text-black! after:absolute after:-z-10 after:w-full after:h-full after:bg-black after:right-0">
            <b class="inline-block text-gradient-light py-2 pl-4 pr-8 font-bold bg-black md:pr-8">en empresarios digitales</b>
        </span>
        <span class="relative z-0 ml-auto font-semibold md:pr-8 *:text-black! after:absolute after:-z-10 after:w-full after:h-full after:bg-black after:right-0">
            <b class="inline-block text-gradient-light py-2 pl-4 pr-8 font-bold bg-black md:pr-8">es el principal objetivo</b>
        </span>
    </h2>
</section>
