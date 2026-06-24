<?php snippet('header') ?>

<h1 class="text-3xl font-bold text-text mb-6"><?= $page->title() ?></h1>

<div class="grid grid-cols-1 items-center lg:grid-cols-4 gap-6 mb-8">
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
        <?php $genres = $page->genreList() ?>
        <?php if (!empty($genres)): ?>
            <div>
                <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Genres</h3>
                <div class="flex flex-wrap gap-1">
                    <?php foreach ($genres as $g): ?>
                        <span class="px-2 py-0.5 rounded border border-neon-cyan/30 text-neon-cyan text-xs"><?= $g ?></span>
                    <?php endforeach ?>
                </div>
            </div>
        <?php endif ?>

        <?php $tags = $page->tagList() ?>
        <?php if (!empty($tags)): ?>
            <div>
                <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Tags</h3>
                <div class="flex flex-wrap gap-1">
                    <?php foreach ($tags as $t): ?>
                        <span class="px-2 py-0.5 rounded border border-neon-green/20 text-neon-green text-xs"><?= $t ?></span>
                    <?php endforeach ?>
                </div>
            </div>
        <?php endif ?>

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

        <?php $links = $page->websites() ?>
        <?php if (!empty($links)): ?>
            <div>
                <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Links</h3>
                <ul class="grid grid-cols-5 gap-4">
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="text-sm text-muted mb-4">
            <?php if ($page->developer()->isNotEmpty()): ?><span><?= $page->developer() ?></span><?php endif ?>
            <?php if ($page->publisher()->isNotEmpty()): ?><span> • <?= $page->publisher() ?></span><?php endif ?>
        </div>
        <?php if ($page->summary()->isNotEmpty()): ?>
            <div class="text-text leading-relaxed mb-8">
                <?= $page->summary()->kt() ?>
            </div>
        <?php endif ?>
    </div>
</div>

<?php $shots = $page->screenshots() ?>
<?php if (!empty($shots)): ?>
    <div class="mt-8 pt-8 border-t border-border">
        <h2 class="text-lg font-bold text-neon-green mb-6">Capturas</h2>
        <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach ($shots as $shot): ?>
                <li>
                    <a href="<?= $shot['full'] ?>" target="_blank" rel="noopener" class="block aspect-video rounded-lg overflow-hidden bg-surface-alt">
                        <img src="<?= $shot['thumb'] ?>" alt="" loading="lazy" class="w-full h-full object-cover hover:opacity-80 transition">
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
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

<?php snippet('footer') ?>
