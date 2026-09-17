<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

require dirname(__DIR__) . '/vendor/autoload.php';

$tmp = __DIR__ . '/.tmp';
if (!is_dir($tmp) && !mkdir($tmp, 0775, true) && !is_dir($tmp)) {
    throw new RuntimeException('Unable to create tests/.tmp');
}
