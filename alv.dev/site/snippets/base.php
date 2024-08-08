<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page->title()->esc() ?> | <?= $site->title()->esc() ?></title>
    <?= vite()->css('assets/css/main.css') ?>
    <?= vite()->js('assets/js/main.js') ?>
    <link rel="shortcut icon" type="image/x-icon" href="<?= url('favicon.ico') ?>">
</head>

<body class="debug-screens flex flex-col h-full font-mono">
    <div class="flex">
        <?php snippet('navbar') ?>

        <div class="flex-1">
            <?php snippet('header') ?>

            <main class="flex-1 min-h-screen">
                <?php snippet('blocks/skills') ?>
                <?php snippet('blocks/projects') ?>
                <?php snippet('blocks/contact-form') ?>
                <?php snippet('blocks/about-me') ?>
                <?php snippet('blocks/latest-posts') ?>
            </main>

            <?php snippet('footer') ?>
        </div>
    </div>
</body>

</html>
