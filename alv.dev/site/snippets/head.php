<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page->description()->or($site->description())->esc() ?>">
    <title><?= $page->title()->esc() ?> | <?= $site->title()->esc() ?></title>
    <link rel="shortcut icon" type="image/x-icon" href="<?= $site->files()->findBy('template', 'favicon')->url() ?? '/favicon.ico' ?>">
    <?= vite()->css('assets/css/main.css') ?>
    <?= vite()->js('assets/js/main.js') ?>
</head>
