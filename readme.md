# Kirby queue plugin

A simple queue utility plugin for the Kirby 3 cms. It enables workers in Kirby that can do tasks (in the background) at scheduled intervals (cron) by working through queues of jobs.

⚠️ This plugin is currently a playground for me to test the new Kirby plugin system. Do not use in production _yet_. ⚠️ 

## Installation

Put the `kirby-queue` folder in your `site/plugins` folder.
Run `composer install` from this directory.

## Usage

### Setup worker

#### 1. Via Cron

Add the worker file `site/plugins/kirby-queue/worker.php` to [cron](https://en.wikipedia.org/wiki/Cron) or similar at the desired interval (.e.g. each minute).

#### 2. Route

There's also a route available at `kqueueworker` that you can trigger to work the queues.

### Define queues

Queues are defined in the config file. Pass them as an associative array: `[name] => function handler($job) {}`. The handler is a closure that is being called to process job by the worker.

```

'bvdputte.kirbyqueue.queues' => [
    'queuename' => function($job) {

        // Get your data
        $propa = $job->get('propA');
        $propb = $job->get('propB');
    
        // Do something with your data, for example: send something to kirbylog
        try {
            kirbylog("test")->log($propa . " " . $propb);
        } catch (Exception $e) {
            // Throw an error to fail a job
            throw new Exception($e->getMessage());
            // or just return false, but setting a message for the user is better.
        }
    
        // No need to return or display anything else!
    }
],

```

You can define as many queues as you need, but each queue only has 1 handler.

### Add jobs

Use the `kqueue()` helper to add a job to the queue you'ld like to handle it. You can also pass data using an associative array.

```

$logQueue = kqueue("queuename");
$logQueue->addJob([
    'propA' => uniqid(),
    'propB' => uniqid()
]);

```

## Options and opinionated defaults

The default folder name for the queues is `queues`. This will be placed in the `/site/` folder. Its name can be changed with `kirby()->option("bvdputte.kirbyqueue.roots");`.

Each queue will get its own subfolder with its name as foldername.
