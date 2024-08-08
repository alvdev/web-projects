<nav class="relative rounded-md grid gap-x-20 gap-y-8 mt-8 bg-white pt-12 pb-14 pr-20 lg:grid-cols-2 xl:grid-cols-3 after:absolute after:right-0 after:-z-10 after:rounded-lg after:w-1/2 after:h-full after:shadow-[0_0_2rem_0_rgba(0,0,0,0.1)]">
    <?php
    $linkColors = ['link-2xl', 'link-red-2xl', 'link-blue-2xl', 'link-green-2xl', 'link-yellow-2xl', 'link-violet-2xl'];
    foreach ($site->blueprint()->field('categories')['options'] as $category) :
        $cat =  $category[$kirby->language()->code()] ?? $category;
        $linkColor = array_rand($linkColors); ?>
        <!-- <a class="<?= $linkColors[$linkColor] ?>" href="#">
            <?= $category[$kirby->language()->code()] ?? $category ?>
        </a> -->

        <a class="<?= $linkColors[$linkColor] ?>" href="<?= '/blog/' . urlencode(strtolower($cat)) . '#content' ?>">
            <?= $category[$kirby->language()->code()] ?? $category ?>
        </a>
    <?php unset($linkColors[$linkColor]);
    endforeach ?>
</nav>
