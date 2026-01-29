<div class="container mt-16 md:mt-28 lg:mt-36 grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
    <?php foreach ($page->videoList()->toStructure() as $item): ?>
        <div class="flex gap-8 *:w-full *:h-auto *:rounded-xl *:ring-2 *:ring-indigo-900/50 *:aspect-video">
            <?php snippet('video', ['video' => $item->video()->toEmbed()]) ?>
        </div>
    <?php endforeach ?>
</div>
