<?php

namespace bvdputte\kirbyQueue;

class Queueworker {

    public static function work() {
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
    }

}