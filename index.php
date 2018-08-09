<?php

require __DIR__ . DS . "src" . DS . "classes" . DS . "Job.php";
require __DIR__ . DS . "src" . DS . "classes" . DS . "Queue.php";

/*
    A little Kirby helper function
*/
if (! function_exists("queue")) {
    function queue() {
        $kirbyQueue = new bvdputte\kirbyQueue\queue();
        
        return $kirbyQueue;
    }
}
