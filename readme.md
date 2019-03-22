# Kirby queue plugin

A simple queue utility plugin for Kirby 3. It enables workers in Kirby that can do tasks (in the background) at scheduled intervals (cron) by working through queues of jobs.

## Installation

- unzip [master.zip](https://github.com/bvdputte/kirby-queue/archive/master.zip) as folder `site/plugins/kirby-queue` or
- `git submodule add https://github.com/bvdputte/kirby-queue.git site/plugins/kirby-fingerprint` or
- `composer require bvdputte/kirby-queue`

## Usage

### Setup worker

#### 1. Via Cron

Add the worker file `site/plugins/kirby-queue/worker.php` to [cron](https://en.wikipedia.org/wiki/Cron) or similar at the desired interval (.e.g. each minute).

💡 This is the preferred method for setting up kirby-queue.

#### 2. Route

There's also a route available at `kqueueworker-supersecreturlkey` that you can trigger to work the queues. The URL can be adjusted [via the options](#options-and-opinionated-defaults).

#### 3. Poor man's cron

When cron is not installed on your server, you can also _fake_ cron by enabling `option("bvdputte.kirbyqueue.poormanscron", true);`.

The default interval for _poor man's cron_ is 60sec. You can change this with `option("bvdputte.kirbyqueue.poormanscron.interval", 60*60);` to hourly e.g.

### Custom worker(s)

If you need your own worker logic (_e.g. workers that need to run at different intervals_), you can create your custom worker by extending the `Queueworker` class.

### Define queues

Queues are defined in the config file. Pass them as an associative array: `[name] => function handler($job) {}`. The handler is a closure that is being called to process job by the worker.

```php
'bvdputte.kirbyqueue.queues' => [
    'queuename' => function($job) {

        // Get your data
        $foo = $job->get('foo');
        $bar = $job->get('bar');
    
        // Do something with your data, for example: send something to kirbylog
        try {
            kirbylog("test")->log($foo . " " . $bar);
        } catch (Exception $e) {
            // Throw an error to fail a job
            throw new Exception($e->getMessage());
            // or just return false, but setting a message for the user is better.
        }
    
        // No need to return or display anything else!
    }
],
```

- 💡 You can define as many queues as you need, but each queue only has 1 handler.
- 💡 The queues will be worked through in the order as defined in the `queues` option.

### Add jobs

```php
$myQueue = kqQueue("queuename"); // "queuename must be the same as set in the options
$myJob = kqJob([ // Pass the variables needed in the handler
    'foo' => "foo",
    'bar' => "bar"
]);
$myQueue->addJob($myJob);
```

### Schedule jobs

```php
$tomorrow = new DateTime('tomorrow');
$myJob->setDueDate($tomorrow->getTimestamp());
```

You can also define a "due date" (UNIX Timestamp) for your job. Your job will be ignored until then.

💡 Take into account the interval you've defined to trigger your worker, as due dates only get be checked when the worker is working through the queue.

## Options and opinionated defaults

```php
option("bvdputte.kirbyqueue.roots");
```

The default folder name for the queues is `queues`. This will be placed in the `/site/` folder.
Each queue will get its own subfolder with its name as foldername.

```php
option("bvdputte.kirbyqueue.worker.route");
```

The URL for the route to trigger the built in worker. Might be  useful if you want to trigger the worker via an URL. Be sure to add a secret hash to it so it can't be used as an attack vector.

## Disclaimer

This plugin is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it in a production environment. If you find any issues, please [create a new issue](https://github.com/bvdputte/kirby-queue/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this plugin in any project that promotes racism, sexism, homophobia, animal abuse, violence or any other form of hate speech.