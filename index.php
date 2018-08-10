<?php

require __DIR__ . DS . "src" . DS . "classes" . DS . "Job.php";
require __DIR__ . DS . "src" . DS . "classes" . DS . "Queue.php";
require __DIR__ . DS . "src" . DS . "classes" . DS . "Queueworker.php";

Kirby::plugin('bvdputte/kirbyqueue', [
    'options' => [
        'root' => 'queues',
        'route.pattern' => 'kqueueworker',
        'queues' => []
    ],
    'routes' => function ($kirby) {
        return [
            [
                'pattern' => $kirby->option("bvdputte.kirbyqueue.route.pattern"),
                'action'  => function () {
                    bvdputte\kirbyQueue\queueworker::work();
                }
            ]
        ];
    }
]);

/*
    A little Kirby helper function
*/
if (! function_exists("kq")) {
    function kqueue($name) {
        $queues = kirby()->option("bvdputte.kirbyqueue.queues");
        if( array_key_exists($name, $queues) ) {
            $kirbyQueue = new bvdputte\kirbyQueue\queue($name, $queues[$name]);
        }

        return $kirbyQueue;
    }
}