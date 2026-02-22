<section class="bg-linear-to-bl from-red-600/30 to-indigo-950/30 to-50% <?= $class ?>">
    <div class="container">
        <?php 
        $items = $page->children()->listed()->sortBy('dateTo', 'desc', 'dateFrom', 'desc');
        $count = $items->count();
        ?>
        <?php if ($count > 0): ?>
        <div class="grid <?= $count < 3 ? 'lg:flex lg:justify-center' : 'lg:grid-cols-3' ?> gap-12">
            <?php foreach ($items as $item): ?>
            <div
                 class="flex flex-col border-2 border-white/30 p-4 md:p-8 rounded-3xl <?= $count < 3 ? 'lg:w-[calc((100%-6rem)/3)]' : '' ?>">
                <?php if ($cover = $item->cover()->toFile()): ?>
                <picture>
                    <source srcset="<?= $cover->srcset('avif') ?>"
                            type="image/avif">
                    <source srcset="<?= $cover->srcset('webp') ?>"
                            type="image/webp">
                    <img src="<?= $cover->resize(390)->url() ?>"
                         srcset="<?= $cover->srcset() ?>"
                         sizes="(min-width: 1024px) 33vw, 100vw"
                         alt="<?= $cover->alt() ?>"
                         loading="lazy"
                         width="<?= $cover->resize(390)->width() ?>"
                         height="<?= $cover->resize(550)->height() ?>"
                         class="w-full h-auto rounded-xl border-2 border-white/10">
                </picture>
                <?php endif ?>
                <h3 class="mt-8 text-3xl md:text-5xl lg:text-3xl text-pretty"><?= $item->title() ?></h3>
                <div class="mt-8 mb-12 md:mb-16 text-xl md:text-3xl lg:text-xl text-white/80">
                    <?= $item->description()->kt() ?>
                </div>
                <a href="<?= $item->link()->toUrl() ?: '#' ?>"
                   target="<?= $item->link()->isNotEmpty() ? '_blank' : '' ?>"
                   class="mt-auto border min-w-1/2 pb-4.75 pt-4 px-8 rounded-full text-center whitespace-nowrap text-2xl hover:bg-white hover:text-black transition-colors">
                    Comprar entradas
                </a>
            </div>
            <?php endforeach ?>
        </div>
        <?php else: ?>
        <div
             class="text-center text-4xl md:text-5xl lg:text-6xl pt-18 pb-0 md:pt-24 md:pb-36 lg:pt-36 lg:pb-54 text-fuchsia-300">
            No hay entradas disponibles
        </div>
        <?php endif ?>
    </div>
</section>
