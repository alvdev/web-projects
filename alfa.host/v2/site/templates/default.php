<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <?= vite()->css('assets/global.css') ?>
</head>

<body>
    <h1><?= $page->title() ?></h1>
    <p>This is a simple template using Vite for CSS.</p>

    <div class="content">
        <p>Content goes here...</p>
    </div>
</body>

</html>
