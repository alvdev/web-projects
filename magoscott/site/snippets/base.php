<!DOCTYPE html>
<html lang="en" class="h-full">

<?php snippet('head') ?>

<body class="debug-screens font-sans bg-indigo-950 text-white">
    <div class="h-full flex flex-col min-h-screen">
        <?php snippet('header') ?>

        <main>
            <?= $slot ?>
        </main>

        <?php snippet('footer') ?>
    </div>
</body>

</html>
