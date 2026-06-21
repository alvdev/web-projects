<?php snippet('header') ?>

<div class="relative bg-bg border-2 border-pink mb-8 overflow-hidden" style="box-shadow: 0 0 20px rgba(255,43,214,0.4);">
<?php $heroImg = $page->cover() ?? $page->hero() ?>
    <div class="aspect-[21/9] bg-cover bg-center flex items-end p-6 md:p-10 relative" style="<?= $heroImg ? 'background-image: url(' . $heroImg->url() . ')' : 'background: linear-gradient(135deg, var(--color-pink), var(--color-purple))' ?>">
        <div class="absolute inset-0 bg-gradient-to-t from-bg via-bg/60 to-transparent"></div>
        <div class="relative z-10 w-full">
            <h1 class="font-display text-4xl md:text-6xl lg:text-7xl uppercase text-text leading-none neon-glow-pink"><?= $page->title() ?></h1>
            <div class="flex flex-wrap gap-2 mt-4 font-body text-sm">
                <?php foreach ($page->genreList() as $genre): ?>
                <span class="px-3 py-1 border border-pink text-pink uppercase tracking-wider"><?= htmlspecialchars($genre) ?></span>
                <?php endforeach ?>
                <?php foreach ($page->tagList() as $tag): ?>
                <span class="px-3 py-1 border border-yellow text-yellow uppercase tracking-wider"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach ?>
                <?php if ($page->releaseDate()): ?>
                <span class="px-3 py-1 text-muted uppercase tracking-wider">Release: <span class="text-cyan"><?= $page->releaseDate() ?></span></span>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <div class="lg:col-span-2">
        <?php if ($page->summary()->isNotEmpty()): ?>
        <div class="font-body text-lg text-text leading-relaxed drop-cap">
            <?= $page->summary()->kt() ?>
        </div>
        <?php endif ?>
    </div>
    <aside class="space-y-4">
        <div class="bg-bg border-2 border-pink p-4" style="box-shadow: 0 0 12px rgba(255,43,214,0.3);">
            <h3 class="font-display text-xs uppercase tracking-widest text-yellow mb-2">Ficha</h3>
            <dl class="font-body text-sm space-y-1">
                <?php if ($page->developer()->isNotEmpty()): ?>
                <div><dt class="text-pink uppercase text-xs">Developer</dt><dd class="text-text"><?= $page->developer() ?></dd></div>
                <?php endif ?>
                <?php if ($page->publisher()->isNotEmpty()): ?>
                <div><dt class="text-pink uppercase text-xs">Publisher</dt><dd class="text-text"><?= $page->publisher() ?></dd></div>
                <?php endif ?>
                <?php if ($page->releaseDate()): ?>
                <div><dt class="text-pink uppercase text-xs">Release</dt><dd class="text-cyan"><?= $page->releaseDate() ?></dd></div>
                <?php endif ?>
            </dl>
        </div>
        <?php if (!empty($page->genreList()) || !empty($page->tagList())): ?>
        <div class="bg-bg border-2 border-pink p-4">
            <h3 class="font-display text-xs uppercase tracking-widest text-yellow mb-2">Tags</h3>
            <div class="flex flex-wrap gap-2 font-body text-xs">
                <?php foreach ($page->genreList() as $g): ?>
                <span class="text-pink uppercase">#<?= htmlspecialchars($g) ?></span>
                <?php endforeach ?>
                <?php foreach ($page->tagList() as $t): ?>
                <span class="text-yellow uppercase">#<?= htmlspecialchars($t) ?></span>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>
    </aside>
</div>

<?php $shots = $page->screenshots() ?>
<?php if (!empty($shots)): ?>
<div class="mt-8 pt-8 border-t border-border">
    <h2 class="font-display text-2xl md:text-3xl uppercase text-pink mb-2">Capturas</h2>
    <?php snippet('script-accent', ['text' => 'mira antes de jugar', 'color' => 'yellow', 'size' => 'md']) ?>
    <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mt-4">
        <?php foreach ($shots as $shot): ?>
        <li>
            <a href="<?= $shot['full'] ?>" target="_blank" rel="noopener" class="block aspect-video bg-surface-alt border-2 border-transparent hover:border-pink transition overflow-hidden">
                <img src="<?= $shot['thumb'] ?>" alt="" loading="lazy" class="w-full h-full object-cover">
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<?php $posts = $page->posts() ?>
<?php if ($posts->count() > 0): ?>
<div class="mt-8 pt-8 border-t border-border">
    <div class="flex items-end justify-between mb-6">
        <h2 class="font-display text-2xl md:text-3xl uppercase text-pink">Posts sobre <?= $page->title() ?></h2>
        <?php snippet('script-accent', ['text' => 'léelos todos', 'color' => 'yellow', 'size' => 'md']) ?>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($posts as $post): ?>
            <?php snippet('post-card', ['post' => $post]) ?>
        <?php endforeach ?>
    </div>
</div>
<?php endif ?>

<?php snippet('footer') ?>
