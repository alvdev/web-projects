<?php snippet('header') ?>

<?php snippet('hero') ?>

<?php snippet('marquee', ['phrase' => 'Últimos posts', 'color' => 'pink', 'bg' => 'black']) ?>

<?php
$genreChunks = array_chunk(array_keys($genreGames), 2);
$bandColors = ['purple', 'pink'];
?>
<?php foreach ($genreChunks as $bandIdx => $chunk): ?>
    <?php $bandColor = $bandColors[$bandIdx % 2]; ?>
    <section class="band-<?= $bandColor ?>">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($chunk as $genre):
                    $games = $genreGames[$genre];
                    $phrase = \GamePage::GENRE_PHRASES[$genre] ?? 'descubre';
                ?>
                    <div>
                        <div class="flex items-end justify-between mb-4">
                            <h2 class="font-display text-4xl md:text-6xl uppercase text-bg leading-none"><?= htmlspecialchars($genre) ?></h2>
                            <?php snippet('script-accent', ['text' => $phrase, 'color' => 'yellow', 'size' => 'md']) ?>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <?php foreach ($games as $game): ?>
                                <?php snippet('game-card', ['game' => $game]) ?>
                            <?php endforeach ?>
                        </div>
                        <div class="mt-4">
                            <a href="<?= url('genre/' . urlencode($genre)) ?>" class="inline-block border-2 border-bg text-bg font-display text-sm uppercase tracking-widest px-5 py-2 hover:bg-bg hover:text-pink transition">► Ver <?= htmlspecialchars($genre) ?></a>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </section>
<?php endforeach ?>

<?php snippet('marquee', ['phrase' => 'Explora el catálogo', 'color' => 'yellow', 'bg' => 'pink', 'speed' => 'slow']) ?>

<?php snippet('footer') ?>
