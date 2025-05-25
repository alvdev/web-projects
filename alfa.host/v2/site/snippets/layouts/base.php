<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page->description() ?>">
    <title><?= $page->title() ?></title>
    <?= vite()->css('assets/main.css') ?>
    <?= vite()->js('assets/main.js') ?>
</head>

<body>
    <?php snippet('site/header') ?>

    <?= $slot ?>

    <?php snippet('site/footer') ?>
</body>

</html>
