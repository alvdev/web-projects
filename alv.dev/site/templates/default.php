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
                    <?php snippet('blocks/skills-grid') ?>
                    <?php snippet('blocks/projects') ?>
                    <?php snippet('blocks/contact-form') ?>
                    <?php snippet('blocks/about-me') ?>
                    <?php snippet('blocks/latest-posts') ?>
                </div>
            </main>
    
            <?php snippet('footer') ?>
        </div>
    </div>
</body>

</html>
