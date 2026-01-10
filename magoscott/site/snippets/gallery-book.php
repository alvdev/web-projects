        <?php
        $sizes = "(min-width: 1280px) 22vw, (min-width: 1024px) 30vw, (min-width: 768px) 30vw, 45vw";
        
        $avifSrcset = $image->staticSrcset('book-avif');
        $webpSrcset = $image->staticSrcset('book-webp');
        $defaultSrcset = $image->staticSrcset('book-default');
        ?>
        <div class="relative group" style="aspect-ratio: 2/3; contain: layout;">
            <picture>
                <source srcset="<?= $avifSrcset ?>" sizes="<?= $sizes ?>" type="image/avif">
                <source srcset="<?= $webpSrcset ?>" sizes="<?= $sizes ?>" type="image/webp">
                <img
                    src="<?= $image->staticUrl(['width' => 300, 'height' => 450, 'crop' => true]) ?>"
                    srcset="<?= $defaultSrcset ?>"
                    sizes="<?= $sizes ?>"
                    width="300"
                    height="450"
                    data-full="<?= $image->url() ?>"
                    @click="open($event)"
                    alt="<?= $image->alt()->or($image->filename()) ?>"
                    loading="lazy"
                    decoding="async"
                    class="w-full h-auto cursor-zoom-in rounded-xl ring-2 ring-indigo-950/50 object-cover saturate-110 contrast-110 group-hover:scale-[1.02] transition-transform duration-500">
            </picture>

        <a
            href="<?= $image->url() ?>"
            download
            class="absolute bottom-2 left-2 right-2 md:bottom-4 md:left-4 md:right-4 flex gap-2 justify-center items-center py-2 md:py-4 text-lg md:text-xl lg:text-xl xl:text-2xl uppercase bg-linear-to-t from-black/60 to-violet-600/60 rounded-xl hover:bg-violet-600/60 transition-all">
            Descargar
            <svg xmlns="http://www.w3.org/2000/svg" height="32px" viewBox="0 -960 960 960" width="32px" fill="#e3e3e3">
                <path d="m480-320 160-160-56-56-64 64v-168h-80v168l-64-64-56 56 160 160Zm0 240q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
            </svg>
        </a>
    </div>
