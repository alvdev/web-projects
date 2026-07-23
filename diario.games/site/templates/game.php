<?php snippet('header') ?>

<div class="relative flex items-center justify-center mt-8">
    <h1 class="text-balance">
        <?= $page->title() ?>
    </h1>
    <button type="button"
        class="site-fav -mr-8 ml-4 text-xl text-muted hover:text-yellow-400 transition shrink-0"
        data-slug="<?= $page->slug() ?>"
        data-title="<?= htmlspecialchars($page->title()) ?>"
        data-cover="<?= ($cover = $page->cover()) ? $cover->url() : (($hero = $page->hero()) ? $hero->url() : '') ?>">☆</button>
</div>

<div class="flex items-center justify-center gap-2 mt-4">
    <?php $genres = $page->genreList() ?>
    <?php if (!empty($genres)): ?>
        <div class="flex items-center gap-1">
            <h3 class="text-xs uppercase tracking-wider text-muted">Genres</h3>
            <div class="flex flex-wrap gap-1">
                <?php foreach ($genres as $g): ?>
                    <span class="px-2 py-0.5 rounded border border-neon-cyan/30 text-neon-cyan text-xs"><?= $g ?></span>
                <?php endforeach ?>
            </div>
        </div>
    <?php endif ?>

    <?php $tags = $page->tagList() ?>
    <?php if (!empty($tags)): ?>
        <div class="flex items-center gap-1">
            <h3 class="text-xs uppercase tracking-wider text-muted">Tags</h3>
            <div class="flex flex-wrap gap-1">
                <?php foreach ($tags as $t): ?>
                    <span class="px-2 py-0.5 rounded border border-neon-green/20 text-neon-green text-xs"><?= $t ?></span>
                <?php endforeach ?>
            </div>
        </div>
    <?php endif ?>
</div>

<div class="grid grid-cols-1 items-center lg:grid-cols-4 gap-6 mt-8 mb-8">
    <?php if ($cover = $page->cover()): ?>
        <div class="lg:col-span-1">
            <img src="<?= $cover->url() ?>" alt="" class="w-full rounded-lg">
        </div>
    <?php endif ?>

    <div class="lg:col-span-2">
        <?php $videos = $page->gameVideos() ?>
        <?php if (!empty($videos)):
            $thumbFiles = $page->videoThumbs();
            $thumbUrls = [];
            foreach ($thumbFiles as $f) {
                $idx = $f->filename();
                $idx = (int) preg_replace('/[^0-9]/', '', $idx);
                $thumbUrls[$idx] = $f->url();
            }
        ?>
            <div data-video-slideshow>
                <div class="aspect-video rounded-lg overflow-hidden bg-surface-alt relative group cursor-pointer" data-video-player>
                    <?php foreach ($videos as $i => $url):
                        preg_match('/\/embed\/(.+)/', $url, $m);
                        $ytId = $m[1];
                        $thumbUrl = $thumbUrls[$i] ?? 'https://img.youtube.com/vi/' . $ytId . '/hqdefault.jpg';
                    ?>
                        <div class="absolute inset-0 <?= $i === 0 ? '' : 'hidden' ?>" data-video-slide="<?= $i ?>" data-yt-id="<?= $ytId ?>" data-thumb-url="<?= $thumbUrl ?>">
                            <img src="<?= $thumbUrl ?>" alt="" class="w-full h-full object-cover" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/10 transition">
                                <svg class="w-16 h-16 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z" />
                                </svg>
                            </div>
                        </div>
                    <?php endforeach ?>
                    <?php if (count($videos) > 1): ?>
                        <button data-video-prev class="absolute left-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition opacity-0 group-hover:opacity-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button data-video-next class="absolute right-2 top-1/2 -translate-y-1/2 z-10 w-10 h-10 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition opacity-0 group-hover:opacity-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    <?php endif ?>
                </div>
            </div>
            <script>
                (function() {
                    var slideshow = document.querySelector('[data-video-slideshow]');
                    if (!slideshow) return;
                    var player = slideshow.querySelector('[data-video-player]');
                    var slides = slideshow.querySelectorAll('[data-video-slide]');
                    var prevBtn = slideshow.querySelector('[data-video-prev]');
                    var nextBtn = slideshow.querySelector('[data-video-next]');
                    var loaded = {};
                    var total = slides.length;

                    function thumbnailHtml(slide) {
                        var url = slide.dataset.thumbUrl || 'https://img.youtube.com/vi/' + slide.dataset.ytId + '/hqdefault.jpg';
                        return '<img src="' + url + '" alt="" class="w-full h-full object-cover">' +
                            '<div class="absolute inset-0 flex items-center justify-center bg-black/30 group-hover:bg-black/10 transition">' +
                            '<svg class="w-16 h-16 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>' +
                            '</div>';
                    }

                    function currentIndex() {
                        return Array.from(slides).findIndex(function(s) {
                            return !s.classList.contains('hidden');
                        });
                    }

                    function showSlide(i) {
                        slides.forEach(function(s, idx) {
                            s.classList.toggle('hidden', idx !== i);
                            if (idx !== i && loaded[idx]) {
                                s.innerHTML = thumbnailHtml(s);
                                loaded[idx] = false;
                            }
                        });
                    }

                    function loadVideo(i) {
                        if (loaded[i]) return;
                        var slide = slides[i];
                        var iframe = document.createElement('iframe');
                        iframe.src = 'https://www.youtube.com/embed/' + slide.dataset.ytId + '?autoplay=1';
                        iframe.className = 'w-full h-full';
                        iframe.setAttribute('allowfullscreen', '');
                        iframe.setAttribute('allow', 'autoplay; encrypted-media');
                        slide.innerHTML = '';
                        slide.appendChild(iframe);
                        loaded[i] = true;
                    }

                    player.addEventListener('click', function(e) {
                        if (e.target.closest('[data-video-prev]') || e.target.closest('[data-video-next]')) return;
                        loadVideo(currentIndex());
                    });

                    if (prevBtn) {
                        prevBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            showSlide((currentIndex() - 1 + total) % total);
                        });
                    }
                    if (nextBtn) {
                        nextBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            showSlide((currentIndex() + 1) % total);
                        });
                    }
                })();
            </script>
        <?php elseif ($hero = $page->hero()): ?>
            <img src="<?= $hero->url() ?>" alt="" class="w-full rounded-lg">
        <?php endif ?>
    </div>

    <div class="lg:col-span-1 space-y-4 text-sm">
        <?php if ($page->rating()->isNotEmpty()): ?>
            <div>
                <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Rating</h3>
                <p class="text-lg font-bold text-neon-magenta"><?= number_format((float)$page->rating()->value(), 1) ?> / 100</p>
            </div>
        <?php endif ?>

        <?php if ($page->platforms()->isNotEmpty()): ?>
            <div>
                <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Platforms</h3>
                <p><?= $page->platforms() ?></p>
            </div>
        <?php endif ?>

        <?php if ($page->releaseDate()): ?>
            <div>
                <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Released</h3>
                <p><?= $page->releaseDate() ?></p>
            </div>
        <?php endif ?>

        <?php if ($page->developer()->isNotEmpty()): ?>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Developer/Publisher</h3>
            <span><?= $page->developer() ?></span>
        <?php endif ?>
        <?php if ($page->publisher()->isNotEmpty()): ?>
            <span> • <?= $page->publisher() ?></span>
        <?php endif ?>

        <?php $links = $page->websites() ?>
        <?php if (!empty($links)): ?>
            <div class="mt-4">
                <ul class="grid grid-cols-5 gap-4 border-t-2 border-white/10 pt-4">
                    <?php foreach ($links as $link): ?>
                        <li>
                            <a href="<?= $link['url'] ?>" target="_blank" rel="noopener" class="block w-6 h-6 text-muted hover:text-neon-cyan transition *:[svg]:fill-white hover:*:[svg]:fill-neon-cyan" title="<?= $link['label'] ?>">
                                <?php if ($link['icon'] === 'globe'): ?>
                                    <?= svg('assets/svgs/globe.svg') ?>
                                <?php else: ?>
                                    <?= svg('assets/svgs/' . $link['icon'] . '.svg') ?>
                                <?php endif ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>
    </div>
</div>

<?php if ($page->summary()->isNotEmpty()): ?>
    <div class="prose lg:prose-xl max-w-full dark:prose-invert text-text leading-relaxed mt-16 mb-10 bg-surface/50 backdrop-blur-sm border-4 border-border rounded-xl p-8">
        <?= $page->summary()->kt() ?>
    </div>
<?php endif ?>

<?php $prices = $site->priceComparison($page->slug(), $page->title()->value()) ?>
<?php snippet('price-comparison', ['prices' => $prices]) ?>

<?php $shots = $page->screenshots() ?>
<?php if (!empty($shots)): ?>
    <div class="mt-8 pt-8 border-t border-border">
        <h2 class="text-lg font-bold text-neon-green mb-6">Capturas</h2>
        <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($shots as $shot): ?>
                <li>
                    <a href="<?= $shot['full'] ?>" data-lightbox="<?= $shot['full'] ?>" class="block aspect-video rounded-lg overflow-hidden bg-surface-alt">
                        <img src="<?= $shot['thumb'] ?>" alt="" loading="lazy" class="w-full h-full object-cover hover:opacity-80 transition">
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>

    <div id="lightbox" class="lightbox" role="dialog" aria-label="Screenshot lightbox">
        <button data-lightbox-close class="lightbox-close" aria-label="Close">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <button data-lightbox-prev class="lightbox-prev" aria-label="Previous">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <img src="" alt="">
        <span data-lightbox-counter class="lightbox-counter"></span>
        <button data-lightbox-next class="lightbox-next" aria-label="Next">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
<?php endif ?>

<?php $steamData = $site->steamChartData($page->slug()); ?>
<?php if ($steamData): ?>
    <?php snippet('steam-chart', ['data' => $steamData]) ?>
<?php endif ?>

<?php $posts = $page->posts() ?>
<?php if ($posts->count() > 0): ?>
    <div class="mt-8 pt-8 border-t border-border">
        <h2 class="text-lg font-bold text-neon-green mb-6">📰 Posts about <?= $page->title() ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($posts as $post): ?>
                <?php snippet('post-card', ['post' => $post]) ?>
            <?php endforeach ?>
        </div>
    </div>
<?php endif ?>

<?php
$guides = $page->guides()->sortBy('date', 'desc');
$news = $page->news()->sortBy('date', 'desc');
$guideSlides = $guides->chunk(2)->values(fn($p) => $p->values(fn($i) => $i));
$newsSlides = $news->chunk(2)->values(fn($p) => $p->values(fn($i) => $i));
?>
<?php if (count($guideSlides) > 0 || count($newsSlides) > 0): ?>
<div class="mt-8 pt-8 border-t border-border">
    <h2 class="text-lg font-bold text-neon-magenta mb-6">📖 Guias y noticias de <?= $page->title() ?></h2>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <?php if (count($newsSlides) > 0): ?>
        <div id="news-carousel" data-carousel="news" data-carousel-color="neon-green">
            <h3 class="text-sm font-bold text-neon-green mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-neon-green"></span> Noticias
            </h3>
            <div class="relative">
                <div class="overflow-hidden">
                    <div class="flex transition-transform duration-300 ease-out" data-carousel-slides>
                        <?php foreach ($newsSlides as $idx => $items): ?>
                        <div class="w-full shrink-0 grid grid-cols-1 sm:grid-cols-2 gap-4" data-carousel-slide="<?= $idx ?>">
                            <?php foreach ($items as $item): ?>
                                <?php snippet('news-card', ['news' => $item]) ?>
                            <?php endforeach ?>
                        </div>
                        <?php endforeach ?>
                    </div>
                </div>
                <?php if (count($newsSlides) > 1): ?>
                <button data-carousel-prev class="absolute -left-4 top-1/2 -translate-y-1/2 z-10 w-7 h-7 flex items-center justify-center rounded-full bg-surface border border-border text-muted hover:text-neon-green hover:border-neon-green/50 transition" aria-label="Previous">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button data-carousel-next class="absolute -right-4 top-1/2 -translate-y-1/2 z-10 w-7 h-7 flex items-center justify-center rounded-full bg-surface border border-border text-muted hover:text-neon-green hover:border-neon-green/50 transition" aria-label="Next">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div class="flex items-center justify-center gap-1.5 mt-3" data-carousel-dots>
                    <?php for ($i = 0; $i < count($newsSlides); $i++): ?>
                    <button data-carousel-dot="<?= $i ?>" class="w-1.5 h-1.5 rounded-full transition <?= $i === 0 ? 'bg-neon-green w-3.5' : 'bg-neon-green/30 hover:bg-neon-green/50' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
                    <?php endfor ?>
                </div>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>

        <?php if (count($guideSlides) > 0): ?>
        <div id="guide-carousel" data-carousel="guide" data-carousel-color="neon-magenta">
            <h3 class="text-sm font-bold text-neon-magenta mb-4 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-neon-magenta"></span> Guias
            </h3>
            <div class="relative">
                <div class="overflow-hidden">
                    <div class="flex transition-transform duration-300 ease-out" data-carousel-slides>
                        <?php foreach ($guideSlides as $idx => $items): ?>
                        <div class="w-full shrink-0 grid grid-cols-1 sm:grid-cols-2 gap-4" data-carousel-slide="<?= $idx ?>">
                            <?php foreach ($items as $item): ?>
                                <?php snippet('guide-card', ['guide' => $item]) ?>
                            <?php endforeach ?>
                        </div>
                        <?php endforeach ?>
                    </div>
                </div>
                <?php if (count($guideSlides) > 1): ?>
                <button data-carousel-prev class="absolute -left-4 top-1/2 -translate-y-1/2 z-10 w-7 h-7 flex items-center justify-center rounded-full bg-surface border border-border text-muted hover:text-neon-magenta hover:border-neon-magenta/50 transition" aria-label="Previous">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button data-carousel-next class="absolute -right-4 top-1/2 -translate-y-1/2 z-10 w-7 h-7 flex items-center justify-center rounded-full bg-surface border border-border text-muted hover:text-neon-magenta hover:border-neon-magenta/50 transition" aria-label="Next">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <div class="flex items-center justify-center gap-1.5 mt-3" data-carousel-dots>
                    <?php for ($i = 0; $i < count($guideSlides); $i++): ?>
                    <button data-carousel-dot="<?= $i ?>" class="w-1.5 h-1.5 rounded-full transition <?= $i === 0 ? 'bg-neon-magenta w-3.5' : 'bg-neon-magenta/30 hover:bg-neon-magenta/50' ?>" aria-label="Slide <?= $i + 1 ?>"></button>
                    <?php endfor ?>
                </div>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<script>
(function(){
    document.querySelectorAll('[data-carousel]').forEach(function(carousel){
        var track = carousel.querySelector('[data-carousel-slides]');
        var prev = carousel.querySelector('[data-carousel-prev]');
        var next = carousel.querySelector('[data-carousel-next]');
        var dots = carousel.querySelectorAll('[data-carousel-dot]');
        var slides = carousel.querySelectorAll('[data-carousel-slide]');
        var total = slides.length;
        var current = 0;
        var color = carousel.getAttribute('data-carousel-color') || 'neon-magenta';
        if (total <= 1) return;

        function goTo(idx) {
            current = (idx + total) % total;
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            dots.forEach(function(d, i) {
                d.className = 'w-1.5 h-1.5 rounded-full transition ' + (i === current
                    ? 'bg-' + color + ' w-3.5'
                    : 'bg-' + color + '/30 hover:bg-' + color + '/50');
            });
        }

        if (prev) prev.addEventListener('click', function(){ goTo(current - 1); });
        if (next) next.addEventListener('click', function(){ goTo(current + 1); });
        dots.forEach(function(dot){
            dot.addEventListener('click', function(){ goTo(parseInt(dot.getAttribute('data-carousel-dot'))); });
        });

        var startX = 0, dragging = false;
        track.addEventListener('touchstart', function(e){ startX = e.touches[0].clientX; dragging = true; });
        track.addEventListener('touchend', function(e){
            if (!dragging) return;
            dragging = false;
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) goTo(current + (diff > 0 ? 1 : -1));
        });
    });
})();
</script>
<?php endif ?>

<?php snippet('footer') ?>
