<section class="hero container flex gap-24 justify-center items-center bg-ellipse-b-blue pt-4 text-white">
    <hgroup class="text-balance text-right w-2/5">
        <h1 class="text-gradient-light"><?= $page->title() ?></h1>
        <?php if ($page->description()->isNotEmpty()): ?>
            <h2 class="text-gradient-light"><?= $page->description()->kirbytextinline() ?></h2>
        <?php endif ?>
    </hgroup>

    <div class="min-w-1/3 w-2/5">
        <form class="" action="#">
            <div class="ring-1 ring-black/5 bg-white border-4 rounded-full overflow-clip flex flex-col justify-around w-full sm:flex-row">
                <input class="w-full bg-white text-black font-medium px-6 py-4 text-2xl uppercase focus:outline-hidden placeholder:text-black focus:placeholder:text-black/20" type="text" placeholder="elnombredetunegocio.com" spellcheck="false" data-ms-editor="true">

                <button class="w-full p-4 text-white uppercase bg-black rounded-full shadow-md hover:shadow-inner">
                    Buscar
                </button>
            </div>

        </form>
    </div>
</section>
