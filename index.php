<?php

require __DIR__ . DS . "src" . DS . "classes" . DS . "Job.php";
require __DIR__ . DS . "src" . DS . "classes" . DS . "Queue.php";

Kirby::plugin('bvdputte/kirbyqueue', [
    'options' => [
        'root' => 'queue',
        'handlers' => []
    ],
]);

/*
    A little Kirby helper function
*/
if (! function_exists("kq")) {
    function kq() {
        $kirbyQueue = new bvdputte\kirbyQueue\queue();
        $kirbyQueue->addHandlers(kirby()->option("bvdputte.kirbyqueue.handlers"));
        
        return $kirbyQueue;
    }
}