<article>
    <h1><?= $article->title()->html() ?></h1>
    <time><?= $article->date()->toDate('d/m/Y') ?></time>
    <?= $article->intro()->kirbytext() ?>
    <a href="<?= $article->url() ?>">Read more…</a>
</article>
