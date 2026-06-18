<?php

require_once __DIR__ . '/vendor/autoload.php';

$kirby = new \Kirby\Cms\App(__DIR__);

echo $kirby->render();
