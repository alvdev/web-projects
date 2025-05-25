<?php snippet('layouts/base', slots: true) ?>

<main class="container mx-auto">
    <h1><?= $page->title() ?></h1>
    <p>This is a simple template using Vite for CSS.</p>
    
    <div class="content">
        <p>Content goes here...</p>
    </div>
</main>

<?php endsnippet() ?>
