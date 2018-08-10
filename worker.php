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

// Get defined queues
$queues = kirby()->option("bvdputte.kirbyqueue.queues");
// Work them
foreach ($queues as $queue => $handler) {
    $kq = kqueue($queue);
    // Check for jobs in the queue
    if (!$kq->hasJobs()) continue;

    while ($kq->hasJobs()) {
        $kq->work();
    }
}

exit();