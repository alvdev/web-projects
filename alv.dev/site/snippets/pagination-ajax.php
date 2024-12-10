<?php if ($pagination->hasPages()) : ?>
    <nav id="pagination" class="relative z-10 text-xl font-bold text-center flex justify-center -my-12" x-data="{page: 1}">
        <!-- Load more -->
        <a href="#"
            class="flex w-24 rounded-full ring-[1rem] ring-white uppercase text-[#00ff77] hover:[&>svg]:animate-[spin_3s_linear_infinite] hover:[svg>svg]:origin-center"
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
