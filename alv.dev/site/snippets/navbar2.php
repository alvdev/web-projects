<?php
$posts = ['Lorem ipsum dolor sit', 'sit amet consecttur adispicing elit', 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.', 'Lorem ipsum dolor sit', 'sit amet consecttur adispicing elit', 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.', 'Lorem ipsum dolor sit', 'sit amet consecttur adispicing elit', 'Lorem ipsum dolor sit amet', 'Lorem ipsum dolor sit amet consectetur adipisicing elit.'];

$linkColors = ['link', 'link-red', 'link-blue', 'link-green', 'link-yellow', 'link-violet'];
$colorPool = $linkColors;
?>

<nav class="relative text-base w-2/12">
    <div class="fixed top-0 w-2/12 h-full">
        <div class="flex flex-col justify-between h-full gap-6">
            <h2 class="font-bold uppercase text-xl px-8 pt-8">Últimos artículos</h2>
            <div class="scrollbar scrollbar-sm overflow-y-scroll w-full">
                <div class="relative grid gap-6 px-8 -mt-4">
                    <?php
                    $usedColors = [];
                    foreach ($posts as $i => $post) :
                        // Reset color pool if empty
                        if (empty($colorPool)) {
                            $colorPool = $linkColors;
                        }
                        // Pick a random color and remove it from the pool
                        $randIndex = array_rand($colorPool);
                        $linkColor = $colorPool[$randIndex];
                        unset($colorPool[$randIndex]);
                    ?>
                        <a class="hover:text-black hover:uppercase transition-all hover:transition-all hover:cursor-pointer">
                            <?= $linkColor ?> <?= $post ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="w-full p-8 bg-green-500 **:w-full">
                <h2 class="font-bold uppercase text-xl mb-4">Suscríbete</h2>
                <div id="newsletter-message" class=" w-1/2" x-merge.transition>
                    <?php snippet('forms/newsletter') ?>
                </div>
            </div>
        </div>
    </div>
</nav>
