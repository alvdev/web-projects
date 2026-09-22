<?php
$posts = $posts ?? [];
$count = is_countable($posts) ? count($posts) : 0;
if ($count === 0) return;

$visible = min($count, 5);
$postList = [];
foreach ($posts as $post) {
    $postList[] = $post;
}
?>
<div data-home-ticker class="bg-surface border border-border rounded-xl p-4 flex flex-col">
    <h2 class="text-sm font-bold uppercase tracking-wider text-neon-magenta mb-4">Últimas noticias y guías</h2>
    <div class="relative overflow-hidden"
        data-ticker-viewport
        style="--ticker-card-h: 5.5rem; --ticker-gap: 0.75rem; height: calc(<?= $visible ?> * var(--ticker-card-h) + <?= max($visible - 1, 0) ?> * var(--ticker-gap));">
        <div class="flex flex-col gap-3" data-ticker-track>
            <?php foreach ($postList as $index => $post): ?>
                <?php
                $isGuide = $post->intendedTemplate()->name() === 'guide';
                $category = $isGuide ? 'Guía' : 'Noticia';
                $image = $post->headerImage();
                $active = $index === 0;
                ?>
                <button type="button"
                    data-ticker-card="<?= $index ?>"
                    <?= $active ? 'data-active' : '' ?>
                    aria-label="Ver <?= htmlspecialchars($post->title()) ?>"
                    class="group flex h-[var(--ticker-card-h)] w-full shrink-0 cursor-pointer items-center gap-3 overflow-hidden rounded-lg border text-left transition <?= $active ? 'border-neon-magenta/60 bg-surface-alt' : 'border-border hover:border-neon-magenta/40' ?>">
                    <span class="h-full w-24 shrink-0 overflow-hidden bg-surface-alt">
                        <?php if ($image): ?>
                            <img src="<?= $image->url() ?>" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                        <?php endif ?>
                    </span>
                    <span class="min-w-0 flex-1 pr-3 py-2">
                        <span class="inline-block rounded px-1.5 py-0.5 text-[10px] font-bold <?= $isGuide ? 'bg-neon-magenta/20 text-neon-magenta' : 'bg-neon-green/20 text-neon-green' ?>"><?= $category ?></span>
                        <span class="mt-1 block text-sm font-semibold leading-tight text-text line-clamp-2"><?= $post->title() ?></span>
                    </span>
                </button>
            <?php endforeach ?>
        </div>
    </div>
</div>

<script>
    (function() {
        var ticker = document.querySelector('[data-home-ticker]');
        if (!ticker || ticker.hasAttribute('data-ticker-ready')) return;
        ticker.setAttribute('data-ticker-ready', '1');

        var track = ticker.querySelector('[data-ticker-track]');
        var viewport = ticker.querySelector('[data-ticker-viewport]');
        if (!track || !viewport) return;

        var cards = Array.prototype.slice.call(track.querySelectorAll('[data-ticker-card]'));
        var count = cards.length;
        if (count === 0) return;

        var spotlight = Array.prototype.slice.call(document.querySelectorAll('[data-spotlight-card]'));

        function spotlightIndex(index) {
            spotlight.forEach(function(card) {
                card.classList.toggle('hidden', parseInt(card.getAttribute('data-spotlight-card'), 10) !== index);
            });
        }

        function highlight(index) {
            cards.forEach(function(card, i) {
                var active = i === index;
                card.classList.toggle('border-neon-magenta/60', active);
                card.classList.toggle('bg-surface-alt', active);
                card.classList.toggle('border-border', !active);
                card.classList.toggle('hover:border-neon-magenta/40', !active);
                if (active) {
                    card.setAttribute('data-active', '');
                } else {
                    card.removeAttribute('data-active');
                }
            });
        }

        function select(index) {
            highlight(index);
            spotlightIndex(index);
        }

        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var selected = 0;
        var timer = null;

        function advance() {
            if (document.hidden) return;

            selected = (selected + 1) % count;
            select(selected);
        }

        function start() {
            if (timer === null && count > 1 && !reducedMotion) {
                timer = window.setInterval(advance, 5000);
            }
        }

        function stop() {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        start();

        ticker.addEventListener('mouseenter', stop);
        ticker.addEventListener('mouseleave', start);
        ticker.addEventListener('focusin', stop);
        ticker.addEventListener('focusout', start);

        cards.forEach(function(card, index) {
            card.addEventListener('click', function() {
                selected = index;
                select(selected);
            });
        });
    })();
</script>
