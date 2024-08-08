<?php if ($headlines->count() >= 3) : ?>
    <nav class="toc">
        <h2>Table of Contents</h2>
        <ol>
            <?php foreach ($headlines as $headline) : ?>
                <li><a href="<?= $headline->url() ?>"><?= $headline->text() ?></a></li>
            <?php endforeach ?>
        </ol>
    </nav>
<?php endif ?>
