<?php snippet('base', slots: true) ?>
<?php slot('default') ?>

<main class="bg-linear-to-tl from-indigo-950 via-indigo-950 via-50% to-black">
    <div class="container pt-64 *:[p]:text-3xl *:[p]:text-white/80 *:first-of-type:[p]:mt-8">
        <h1 class="font-bold text-8xl">
            Error 404
        </h1>

        <p>:( Esta página no se ha encontrado o se ha movido de localización.</p>
        <p>Puedes ir a la <a href="<?= $site->homePage() ?>" class="text-red-500 hover:text-red-400">portada</a> o visitar alguna página del menú de navegación</p>

        <?= $page->text()->kirbytext() ?>
    </div>
</main>

<?php endslot() ?>
