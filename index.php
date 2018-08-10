<?php

require __DIR__ . DS . "src" . DS . "classes" . DS . "Job.php";
require __DIR__ . DS . "src" . DS . "classes" . DS . "Queue.php";

Kirby::plugin('bvdputte/kirbyqueue', [
    'options' => [
        'root' => 'queues',
        'queues' => []
    ],
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