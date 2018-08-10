<?php

// Bootstrap Kirby (from with the plugin's folder)
require '../../../kirby/bootstrap.php';
$kirbyPath = dirname(__FILE__) . "/../../../../";

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
bvdputte\kirbyQueue\queueworker::work();