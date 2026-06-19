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
        <?php if ($results->count() > 0): ?>
            Found <?= $results->count() ?> result<?= $results->count() !== 1 ? 's' : '' ?> for "<?= html($query) ?>"
        <?php else: ?>
            No results found for "<?= html($query) ?>"
        <?php endif ?>
    </p>

    <div class="space-y-4">
        <?php foreach ($results as $result): ?>
        <a href="<?= $result->url() ?>" class="block bg-surface border border-border rounded-lg p-4 hover:border-neon-cyan/50 transition">
            <h3 class="font-semibold text-text"><?= $result->title() ?></h3>
            <p class="text-sm text-muted mt-1"><?= $result->summary()->excerpt(120) ?></p>
        </a>
        <?php endforeach ?>
    </div>

    <?php if ($pagination && $pagination->pages() > 1): ?>
    <nav class="flex justify-center gap-2 mt-8">
        <?php if ($pagination->hasPrevPage()): ?>
        <a href="<?= $pagination->prevPageUrl() ?>" class="px-3 py-2 bg-surface border border-border rounded text-sm hover:border-neon-cyan/50 transition">&larr; Previous</a>
        <?php endif ?>
        <?php if ($pagination->hasNextPage()): ?>
        <a href="<?= $pagination->nextPageUrl() ?>" class="px-3 py-2 bg-surface border border-border rounded text-sm hover:border-neon-cyan/50 transition">Next &rarr;</a>
        <?php endif ?>
    </nav>
    <?php endif ?>
    <?php endif ?>
</div>

<?php snippet('footer') ?>
