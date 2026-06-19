<?php

require __DIR__ . '/kirby/bootstrap.php';

\Kirby\Filesystem\Dir::$numSeparator = '-';

$kirby = new \Kirby\Cms\App();

echo $kirby->render();
