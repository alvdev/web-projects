<?php
$latestPosts = $latestPosts ?? null;
$spotlightPosts = ($latestPosts && $latestPosts->count() > 0) ? $latestPosts : null;

if (!$spotlightPosts):
    $allGames = $site->find('games')->children()->children()->children()->filterBy('intendedTemplate', 'game');
    $allPosts = $allGames->children()->filterBy('template', 'post');

    $rankBySlug = [];
    try {
        $stats = $site->steamStats();
        $mostPlayed = $stats->getMostPlayed(100);
        $db = new \Alv\SteamStats\SteamStatsDB();
        $rankByAppid = [];
        foreach ($mostPlayed as $g) {
            $rankByAppid[$g['appid']] = $g['rank'];
        }
        foreach ($db->getAllGames() as $sg) {
            if (isset($rankByAppid[$sg['appid']])) {
                $rankBySlug[$sg['slug']] = $rankByAppid[$sg['appid']];
            }
        }
    } catch (\Throwable $e) {}

    $allGames = $allGames->sort(
        function ($g) use ($rankBySlug) {
            return $rankBySlug[$g->slug()] ?? 999999;
        },
        'asc',
        'modified',
        'desc'
    );

    $topGame = $allGames->first();
    $latestPost = $topGame ? $topGame->children()->filterBy('template', 'post')->sortBy('date', 'desc')->first() : null;

    if ($latestPost):
        $featured = $latestPost;
        $isPost = true;
    else:
        $featured = $topGame;
        $isPost = false;
    endif;
endif;
?>
<?php if ($spotlightPosts): ?>
    <div data-spotlight class="relative aspect-video lg:aspect-auto lg:min-h-[545px] rounded-xl overflow-hidden border-4 border-border">
        <?php $index = 0; ?>
        <?php foreach ($spotlightPosts as $post): ?>
            <?php
            $postGame = $post->parentGame();
            $isGuide = $post->intendedTemplate()->name() === 'guide';
            $category = $isGuide ? 'Guía' : 'Noticia';
            $image = $post->headerImage();
            if (!$image && $postGame) {
                $image = $postGame->cover() ?? $postGame->hero();
            }
            $date = $isGuide ? $post->guideDate() : $post->newsDate();
            $summary = $post->summary();
            $coverUrl = $postGame ? (($c = $postGame->cover()) ? $c->url() : (($h = $postGame->hero()) ? $h->url() : '')) : '';
            ?>
            <div data-spotlight-card="<?= $index ?>" class="absolute inset-0 <?= $index === 0 ? '' : 'hidden' ?>">
                <?php if ($postGame): ?>
                    <button type="button"
                        class="site-fav absolute top-4 right-4 z-20 text-lg text-muted hover:text-yellow-400 transition bg-surface/50 backdrop-blur-md w-6 h-6 rounded-full leading-0"
                        data-slug="<?= $postGame->slug() ?>"
                        data-title="<?= htmlspecialchars($postGame->title()) ?>"
                        data-cover="<?= $coverUrl ?>">☆</button>
                <?php endif ?>
                <a href="<?= $post->url() ?>" class="block h-full">
                    <?php if ($image): ?>
                        <img src="<?= $image->url() ?>" alt="<?= htmlspecialchars($post->title()) ?>" class="absolute inset-0 w-full h-full object-cover">
                    <?php else: ?>
                        <div class="absolute inset-0 bg-linear-to-br from-neon-magenta/30 via-surface to-neon-cyan/20"></div>
                    <?php endif ?>
                    <div class="relative flex h-full items-end p-6 w-full overflow-hidden bg-linear-to-t from-black/80 via-black/50 to-transparent">
                        <div class="w-full">
                            <span class="text-xs uppercase tracking-widest <?= $isGuide ? 'text-neon-magenta' : 'text-neon-green' ?>">
                                <?= $category ?><?= $postGame ? ' • ' . htmlspecialchars($postGame->title()) : '' ?>
                            </span>
                            <h2 class="text-2xl font-bold text-text mt-1 truncate"><?= $post->title() ?></h2>
                            <?php if ($summary->isNotEmpty()): ?>
                                <p class="text-sm text-muted mt-1 line-clamp-2"><?= $summary->kti() ?></p>
                            <?php endif ?>
                            <?php if ($date): ?>
                                <span class="text-xs text-muted mt-2 block"><?= $date ?></span>
                            <?php endif ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php $index++; ?>
        <?php endforeach ?>
    </div>
<?php elseif ($featured): ?>
    <?php $heroGame = $isPost ? $featured->parent() : $featured ?>
    <div class="relative rounded-xl overflow-hidden group border-4 border-border hover:border-neon-cyan/50 transition lg:min-h-[545px]">
        <button type="button"
            class="site-fav absolute top-4 right-4 z-20 text-lg text-muted hover:text-yellow-400 transition bg-surface/50 backdrop-blur-md w-6 h-6 rounded-full leading-0"
            data-slug="<?= $heroGame->slug() ?>"
            data-title="<?= htmlspecialchars($heroGame->title()) ?>"
            data-cover="<?= ($cover = $heroGame->cover()) ? $cover->url() : (($hero = $heroGame->hero()) ? $hero->url() : '') ?>">☆</button>
        <a href="<?= $featured->url() ?>" class="block h-full">
            <?php $heroImg = $isPost ? ($featured->parent()->cover() ?? $featured->parent()->hero()) : ($featured->cover() ?? $featured->hero()) ?>
            <?php if ($heroImg): ?>
                <img src="<?= $heroImg->url() ?>" alt="<?= $featured->title() ?>" class="absolute inset-0 w-full h-full object-cover">
            <?php endif ?>
            <div class="relative aspect-21/9 flex h-full items-end p-6 w-full overflow-hidden bg-linear-to-t from-black/80 via-black/50 to-transparent">
                <div class="w-full">
                    <span class="text-xs uppercase tracking-widest text-neon-green"><?= $isPost ? 'Último post' : (isset($rankBySlug[$heroGame->slug()]) ? '#' . $rankBySlug[$heroGame->slug()] . ' en Steam' : ($featured->featured()->isTrue() ? 'Featured' : 'Último añadido')) ?></span>
                    <h2 class="text-2xl font-bold text-text mt-1 truncate"><?= $featured->title() ?></h2>
                    <p class="text-sm text-muted mt-1 line-clamp-2"><?= $isPost ? $featured->text()->kti() : $featured->summary()->kti() ?></p>
                </div>
            </div>
        </a>
    </div>
<?php endif ?>
