<?php if ($pagination->hasPages()) : ?>
    <nav id="pagination" class="absolute z-10 left-1/2 -translate-x-1/2 mt-2" x-data="{page: 1}">
        <!-- Load more -->
        <a href="#"
            class="flex w-28 rounded-full bg-white p-4 uppercase text-[#00ff77] hover:[&>svg]:animate-[spin_3s_linear_infinite] hover:[svg>svg]:origin-center"
            :href="'/blog/page:' + page + '#results'" x-on:click="page++"
            :class="page >= <?= $pagination->pages() ?> ? 'invisible' : ''" x-init x-target="results">
            <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <path id="circlePath" fill="white" stroke-width="30" stroke="#030712" d="
          M 15, 50
          a 35,35 0 1,1 70,0
          a 35,35 0 1,1 -70,0
        " />
                <text id="text" font-family="monospace" font-size="18" font-weight="bold" fill="#00ff77" letter-spacing="0.3em">
                    <textPath id="textPath" href="#circlePath" startOffset="0%" textLength="208" lengthAdjust="spacingAndGlyphs">
                        <tspan dy="5"><?= t('loadMore', 'Cargar más artículos') ?></tspan>
                    </textPath>
                </text>
            </svg>
        </a>
    </nav>
<?php endif ?>
