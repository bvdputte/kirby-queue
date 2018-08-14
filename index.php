<?php

require __DIR__ . DS . "src" . DS . "classes" . DS . "Job.php";
require __DIR__ . DS . "src" . DS . "classes" . DS . "Queue.php";
require __DIR__ . DS . "src" . DS . "classes" . DS . "Queueworker.php";

Kirby::plugin('bvdputte/kirbyqueue', [
    'options' => [
        'root' => 'queues',
        'route.pattern' => 'kqueueworker-supersecreturlkey',
        'queues' => []
    ],
    'routes' => function ($kirby) {
        return [
            [
                'pattern' => $kirby->option("bvdputte.kirbyqueue.route.pattern"),
                'action'  => function () {
                    bvdputte\kirbyQueue\Queueworker::work();
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