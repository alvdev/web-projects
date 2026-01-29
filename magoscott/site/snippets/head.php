<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page->title()->esc() ?> | <?= $site->title()->esc() ?></title>
    <meta name="description" content="<?= $page->description()->or($site->description())->esc()->or('El Mago Scott es un artista polifacético, mago, humorista, presentador y showman internacional. Espectáculos de magia y humor para eventos y televisión.') ?>">
    <link rel="shortcut icon" type="image/x-icon"
        href="<?= $site->favicon()->toFile() ? $site->favicon()->toFile()->url() : '/assets/images/favicons/favicon.ico' ?>">

    <link rel="preload" href="<?= vite()->file('assets/fonts/ysabeau.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <?php if ($cover = $page->cover()->toFile()): ?>
        <link rel="preload" as="image" imagesrcset="<?= $cover->staticSrcset('avif') ?>" imagesizes="(min-width: 1024px) 50vw, 100vw">
    <?php endif ?>
    <?= vite()->css('assets/css/main.css', options:['defer' => true]) ?>
    <?= vite()->js('assets/js/main.js', ['defer' => true]) ?>

    <?php if ($page->text()->toBlocks()->filterBy('type', 'code')->isNotEmpty() == 'true'): ?>
        <?= vite()->js('assets/js/prism.js') ?>
        <?= vite()->css('assets/css/prism.css') ?>
    <?php endif ?>
</head>
