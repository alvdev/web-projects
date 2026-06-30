<?php snippet('header') ?>

<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold text-text mb-6">Search</h1>

    <form class="mb-8">
        <div class="flex gap-2">
            <input type="search" name="q" value="<?= html($query) ?>" placeholder="Search games, articles..." class="flex-1 bg-surface border border-border rounded-lg px-4 py-3 text-text placeholder-muted focus:outline-none focus:border-neon-cyan">
            <button type="submit" class="bg-neon-cyan text-dark font-semibold px-6 py-3 rounded-lg hover:bg-neon-cyan/90 transition cursor-pointer">Search</button>
        </div>
    </form>

    <?php if ($query): ?>
    <p class="text-sm text-muted mb-4">
        <?php if (count($results) > 0): ?>
            Found <?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> for "<?= html($query) ?>"
        <?php else: ?>
            No results found for "<?= html($query) ?>"
        <?php endif ?>
    </p>

    <div class="space-y-4">
        <?php foreach ($results as $result): ?>
        <div class="relative bg-surface border border-border rounded-lg p-4 hover:border-neon-cyan/50 transition">
            <button type="button"
                class="site-fav absolute top-3 right-3 text-sm text-muted hover:text-yellow-400 transition"
                data-slug="<?= $result['slug'] ?>"
                data-title="<?= htmlspecialchars($result['name']) ?>"
                data-cover="">☆</button>
            <a href="<?= $result['url'] ?>" class="block">
                <h3 class="font-semibold text-text"><?= html($result['name']) ?></h3>
                <?php if ($result['platforms']): ?>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-xs">
                    <span class="text-neon-cyan"><?= $result['platforms'] ?></span>
                    <?php if ($result['year']): ?>
                    <span class="text-muted">• <?= $result['year'] ?></span>
                    <?php endif ?>
                </div>
                <?php endif ?>
                <p class="text-sm text-muted mt-1"><?= $result['summary'] ?></p>
            </a>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</div>

<?php snippet('footer') ?>
