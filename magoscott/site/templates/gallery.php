<?php snippet('base', slots: true) ?>

<?php slot('default') ?>

<?php snippet('sections/headers/gallery') ?>

<?php
$limit = 24;
$initialPictures = $page->find('pictures')?->images()->paginate($limit, ['page' => 1]);
?>

<div x-data="{ 
    selectedTab: 'pictures',
    opened: false,
    active: null,
    index: 0,
    tabs: {
        pictures: { 
            page: <?= $initialPictures ? 2 : 1 ?>, 
            more: <?= ($initialPictures && $initialPictures->pagination()->hasNextPage()) ? 'true' : 'false' ?>, 
            loading: false 
        },
        people: { page: 1, more: true, loading: false },
        book: { page: 1, more: true, loading: false },
        videos: { page: 1, more: false, loading: false }
    },
    async loadTab(tab, append = false) {
        const t = this.tabs[tab];
        if (t.loading || (!append && t.page > 1) || (append && !t.more)) return;
        
        t.loading = true;
        try {
            // Clean up pathname to avoid trailing slash issues with .json
            let path = window.location.pathname;
            if (path.endsWith('/')) path = path.slice(0, -1);
            
            const url = new URL(window.location.origin + path + '.json');
            url.searchParams.set('tab', tab);
            url.searchParams.set('page', t.page);
            
            const response = await fetch(url);
            const data = await response.json();
            
            const container = document.getElementById('tab-' + tab + '-content');
            if (container) {
                const shell = container.querySelector('ul') || container.querySelector('.grid') || container;
                
                if (append) {
                    const list = container.querySelector('ul') || container.querySelector('.grid') || container;
                    list.insertAdjacentHTML('beforeend', data.html);
                } else {
                    if (shell && shell !== container) {
                        shell.innerHTML = data.html;
                    } else {
                        container.innerHTML = data.html;
                    }
                }
            }
            
            t.more = data.more;
            t.page++;
        } catch (error) {
            console.error('Error loading tab:', error);
        } finally {
            t.loading = false;
        }
    },
    open(e) {
        const img = e.target;
        const container = document.getElementById('tab-' + this.selectedTab + '-content');
        const imgs = Array.from(container.querySelectorAll('img[data-full]'));
        this.index = imgs.indexOf(img);
        this.active = img.dataset.full;
        this.opened = true;
    },
    close() {
        this.opened = false;
        this.active = null;
    },
    next() {
        const container = document.getElementById('tab-' + this.selectedTab + '-content');
        const imgs = container.querySelectorAll('img[data-full]');
        if (imgs.length === 0) return;
        this.index = (this.index + 1) % imgs.length;
        this.active = imgs[this.index].dataset.full;
    },
    prev() {
        const container = document.getElementById('tab-' + this.selectedTab + '-content');
        const imgs = container.querySelectorAll('img[data-full]');
        if (imgs.length === 0) return;
        this.index = (this.index - 1 + imgs.length) % imgs.length;
        this.active = imgs[this.index].dataset.full;
    }
}" x-init="$watch('selectedTab', value => loadTab(value))">
    <section id="intro" class="relative pt-16 md:pt-28 lg:pt-36 bg-linear-to-bl from-red-600/30 to-indigo-950/30 to-50%">
        <div class="container relative w-full flex flex-col md:flex-row md:flex-wrap items-center gap-8 lg:gap-16 justify-center text-xl md:text-2xl lg:text-3xl">
            <button x-on:click="selectedTab = 'pictures'" x-bind:class="selectedTab === 'pictures' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Fotos</button>

            <button x-on:click="selectedTab = 'videos'" x-bind:class="selectedTab === 'videos' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Videos</button>

            <button x-on:click="selectedTab = 'people'" x-bind:class="selectedTab === 'people' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Famosos</button>

            <button x-on:click="selectedTab = 'book'" x-bind:class="selectedTab === 'book' ? 'bg-white text-black' : ''" class="min-w-2/3 md:min-w-1/3 inline-block font-semibold text-shadow-2xs text-shadow-black uppercase border-2 hover:bg-white hover:text-indigo-950 rounded-4xl px-8 py-3">Book</button>
        </div>
    </section>

    <!-- Dynamic Tab Content -->
    <div class="relative min-h-[60vh]">
        <!-- Initial Loader Overlay -->
        <div x-show="tabs[selectedTab].loading && tabs[selectedTab].page === 1" 
             class="absolute inset-0 flex items-center justify-center z-20 bg-indigo-950/80 backdrop-blur-xs"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex flex-col items-center gap-4">
                <div class="w-16 h-16 border-4 border-white/20 border-t-white rounded-full animate-spin"></div>
                <p class="text-white/60 font-medium animate-pulse">Cargando galería...</p>
            </div>
        </div>

        <!-- Pictures Tab -->
        <div x-show="selectedTab === 'pictures'" 
             id="tab-pictures-content" 
             x-transition.opacity.duration.500ms 
             x-cloak>
            <div class="container mt-16 md:mt-28 lg:mt-36">
                <ul class="mt-8 columns-2 md:columns-3 lg:columns-4 gap-4">
                    <?php if ($initialPictures): ?>
                        <?php foreach ($initialPictures as $image): ?>
                            <?= snippet('gallery-item', ['image' => $image]) ?>
                        <?php endforeach ?>
                    <?php endif ?>
                </ul>
            </div>
        </div>

        <!-- Videos Tab -->
        <div x-show="selectedTab === 'videos' && !tabs.videos.loading" id="tab-videos-content" x-transition.opacity.duration.500ms x-cloak></div>

        <!-- People Tab -->
        <div x-show="selectedTab === 'people' && (tabs.people.page > 1 || !tabs.people.loading)" 
             id="tab-people-content" 
             x-transition.opacity.duration.500ms 
             x-cloak>
            <div class="container mt-16 md:mt-28 lg:mt-36">
                <ul class="mt-8 columns-2 md:columns-3 lg:columns-4 gap-4"></ul>
            </div>
        </div>

        <!-- Book Tab -->
        <div x-show="selectedTab === 'book' && (tabs.book.page > 1 || !tabs.book.loading)" 
             id="tab-book-content" 
             x-transition.opacity.duration.500ms 
             x-cloak>
            <div class="container mt-16 md:mt-28 lg:mt-36 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 lg:gap-8"></div>
        </div>

        <!-- Infinite Scroll Loading & Sentinel -->
        <div class="container py-12 flex flex-col items-center">
            <!-- Show this spinner only when loading subsequent pages -->
            <div x-show="tabs[selectedTab].loading && tabs[selectedTab].page > 1" 
                 class="flex items-center justify-center p-8">
                <div class="w-10 h-10 border-4 border-white/20 border-t-white rounded-full animate-spin"></div>
            </div>
            
            <!-- Intersection Sentinel -->
            <div x-show="tabs[selectedTab].more && !tabs[selectedTab].loading" 
                 x-intersect:enter="loadTab(selectedTab, true)" 
                 class="h-20 w-full"></div>
        </div>

        <!-- Lightbox -->
        <template x-teleport="body">
            <div
                x-show="opened"
                x-cloak
                @click="close"
                @keyup.right.window="next"
                @keyup.left.window="prev"
                class="fixed inset-0 z-50 flex items-center justify-center bg-violet-500/70 cursor-zoom-out backdrop-blur-xs">
                <div class="relative">
                    <img
                        :src="active"
                        class="max-w-[90vw] max-h-[90vh] rounded-2xl border-4 border-white/50 shadow-xl">

                    <!-- Prev -->
                    <button
                        @click.stop="prev"
                        class="absolute -left-4 md:-left-8 top-1/2 -translate-y-1/2 w-8 h-8 md:w-16 md:h-16 bg-black/80 hover:bg-black text-white rounded-full flex items-center justify-center transition-colors shadow-lg group ring-2 md:ring-4 ring-white/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-6 md:h-6 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Next -->
                    <button
                        @click.stop="next"
                        class="absolute -right-4 md:-right-8 top-1/2 -translate-y-1/2 w-8 h-8 md:w-16 md:h-16 bg-black/80 hover:bg-black text-white rounded-full flex items-center justify-center transition-colors shadow-lg group ring-2 md:ring-4 ring-white/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-6 md:h-6 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

<?php endslot() ?>
