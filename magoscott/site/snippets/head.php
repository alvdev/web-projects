<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page->title()->esc() ?> | <?= $site->title()->esc() ?></title>
    <meta name="description" content="<?= $page->description()->or($site->description())->esc() ?>">
    <link rel="shortcut icon" type="image/x-icon"
        href="<?= $site->favicon()->toFile() ? $site->favicon()->toFile()->url() : '/assets/images/favicons/favicon.ico' ?>">

    <?= vite()->css('assets/css/main.css') ?>
    <?= vite()->js('assets/js/main.js') ?>

    <?php if ($page->text()->toBlocks()->filterBy('type', 'code')->isNotEmpty() == 'true'): ?>
        <?= vite()->js('assets/js/prism.js') ?>
        <?= vite()->css('assets/css/prism.css') ?>
    <?php endif ?>
</head>
