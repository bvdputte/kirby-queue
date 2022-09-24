<?php

use bvdputte\kirbyQueue\Queueworker;

// Bootstrap Kirby (from with the plugin's folder)
$kirbyPath = realpath(__DIR__ . "/../../../");
require $kirbyPath . '/kirby/bootstrap.php';

// Instantiate Kirby
$kirby = new Kirby([
    'options' => [
        'debug' => true,
    ],
    'roots' => [
        'kirby' => $kirbyPath
    ],
]);

// Work the queue
Queueworker::work();
exit();