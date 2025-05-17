<!DOCTYPE html>
<html lang="en" class="h-full">

<?php snippet('head') ?>

<body class="debug-screens font-mono">
    <div class="flex h-full">
        <?php snippet('navbar') ?>
        <?php snippet('navbar2') ?>

        <div class="flex-1 overflow-y-scroll">
            <main>
                <?= $slot ?>
            </main>

            <?php snippet('footer') ?>
        </div>
    </div>
</body>

</html>
