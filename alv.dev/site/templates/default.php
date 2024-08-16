<!DOCTYPE html>
<html lang="<?= t('lang', 'es') ?>">

<?php snippet('head') ?>

<body class="debug-screens flex flex-col h-full font-mono">
    <div class="flex">
        <?php snippet('navbar') ?>

        <div class="w-full">
            <main class="flex-1">
                <?php snippet('header') ?>
    
                <div class="flex-1 min-h-screen">
                    <?php snippet('sections/skills-grid') ?>
                    <?php snippet('sections/projects') ?>
                    <?php snippet('sections/contact-form') ?>
                    <?php snippet('sections/about-me') ?>
                    <?php snippet('sections/latest-posts') ?>
                </div>
            </main>
    
            <?php snippet('footer') ?>
        </div>
    </div>
</body>

</html>
