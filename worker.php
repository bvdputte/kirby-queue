<?php

use bvdputte\kirbyQueue\kirbyQueue;

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

// Check for jobs in the queue
if (!queue::hasJobs()) exit();
while (queue::hasJobs()) {
    queue::work();
}