<?php
$allGames = $site->find('games')->children();
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
$latestPost = $topGame->children()->filterBy('template', 'post')->sortBy('date', 'desc')->first();

if ($latestPost):
    $featured = $latestPost;
    $isPost = true;
else:
    $featured = $topGame;
    $isPost = false;
endif;
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <?php if ($featured): ?>
        <?php $heroGame = $isPost ? $featured->parent() : $featured ?>
        <div class="lg:col-span-2 relative rounded-xl overflow-hidden group border-4 border-border hover:border-neon-cyan/50 transition lg:min-h-[545px]">
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
    <?php snippet('steam-stats-tabs') ?>
</div>
