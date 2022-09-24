<?php

require __DIR__ . "/src/classes/Job.php";
require __DIR__ . "/src/classes/Queue.php";
require __DIR__ . "/src/classes/Queueworker.php";

// For composer
@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('bvdputte/kirbyqueue', [
    'options' => [
        'root' => 'queues',
        'worker.route' => 'kqueueworker-supersecreturlkey',
        'poormanscron' => false,
        'poormanscron.interval' => 60, // in seconds
        'queues' => []
    ],
    'routes' => function ($kirby) {
        return [
            [
                'pattern' => $kirby->option("bvdputte.kirbyqueue.worker.route"),
                'action'  => function () {
                    bvdputte\kirbyQueue\Queueworker::work();
                    exit();
                }
            ]
        ];
    }
]);

/*
    A little Kirby helper function to create a Queue and a Job
*/
if (! function_exists("kqQueue")) {
    function kqQueue($name) {
        $queues = kirby()->option("bvdputte.kirbyqueue.queues");
        if( array_key_exists($name, $queues) ) {
            $kirbyQueue = new bvdputte\kirbyQueue\Queue($name, $queues[$name]);
        }

        return $kirbyQueue;
    }
}
if (! function_exists("kqJob")) {
    function kqJob($data) {
        $job = new bvdputte\kirbyQueue\Job();
        $job->data($data);

        return $job;
    }
}

/*
    For servers without cron, enable "poormanscron"
*/
if (option("bvdputte.kirbyqueue.poormanscron")) {
    $root = kirby()->roots()->site() . '/' . option("bvdputte.kirbyqueue.root");
    $pmcFile = $root . "/.pmc";

    if (!f::exists($pmcFile)) f::write($pmcFile, time());
    $nextRun = f::read($pmcFile) + option("bvdputte.kirbyqueue.poormanscron.interval");

    if( $nextRun < time() ) {
        // Work the queue
        bvdputte\kirbyQueue\Queueworker::work();
        f::write($pmcFile, time());
    }
}
