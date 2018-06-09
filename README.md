# Faktory Worker PHP

**This project is a work in progress and open to suggestions and pull requests.**

This is a client implementation for the [Faktory](http://contribsys.com/faktory/) worker server. 

## Installation

This project has not yet been pushed to Packagist, so you will need to add an additional repository to your
Composer settings.

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/camuthig/faktory_worker_php"
    }
  ]
}
```

You can then require the library: 

```bash
composer require camuthig/faktory-worker
```

## Usage

At this time, this library makes no assumption as to how the worker itself should be implemented. This means
that all that is provided are the basic building blocks for sending messages to the Faktory server and 
receiving them again.

Producing messages is simple. Given an instance of a `ProducerInterface`, you can call the `push` function.

```php
<?php

$connection = new \Camuthig\Faktory\Connection('tcp://127.0.0.1', 7419);

$producer = new \Camuthig\Faktory\Producer($connection);

$producer->push(new \Camuthig\Faktory\WorkUnit(uniqid(), 'example', []));
```

Consuming messages is a little more complicated. This library provides the minimum of what is needed to connect
to the server and build out the required functionality. *It is planned to better support consuming WorkUnits
as this project matures.*

```php
<?php

// Configure the connection with worker properties. At least the `wid` should be provided.
$connection = new \Camuthig\Faktory\Connection('127.0.0.1', 7419, [
    'wid' => uniqid(),
    'labels' => ['php'],
]);

$consumer = new \Camuthig\Faktory\Consumer($connection);

$status = null;
$interrupt = null;

pcntl_signal(SIGINT, function ($signo, $signinfo) use (&$interrupt) {
    $interrupt = true;
});

while (true) {
    if ($interrupt) {
        // Interruption should gracefully shutdown the process
        echo "Stopping consumer...\n";
        $consumer->end();
        exit(0);
    }

    if ($status === \Camuthig\Faktory\ConsumerInterface::TERMINATE) {
        // The server can send a signal to stop processing at any time.
        echo "Server requested consumer termination.\n";
        exit(0);
    } elseif ($status === \Camuthig\Faktory\ConsumerInterface::QUIET) {
        // If the server requests to worker to be "quiet" we must stop processing.
        echo "Server requested consumer to go quiet.\n";
    } else {
        $workUnit = $consumer->fetch();

        if (!$workUnit) {
            sleep(5);
            continue;
        }

        echo "Received work unit " . $workUnit->getJobId() . "\n";
        $consumer->ack($workUnit);
    }

    sleep(1);

    $status = $consumer->beat();
}
```
## Demo

A basic demo of the tooling can be quickly run on a locally environment with PHP and the necessary extensions
installed.

First, start the Faktory server:

```bash
docker-compose up -d
```

Prepare the local environment:

```bash
composer dump
```

Then, in one terminal, start the producer:

```bash
php example/producer.php
```

This should begin outputting lines like:

```text
Pushing job with ID 5b1b202b6b5c4
Pushing job with ID 5b1b202c6be70
Pushing job with ID 5b1b202d6d780
Pushing job with ID 5b1b202e6f020
Pushing job with ID 5b1b202f6ff2f
Pushing job with ID 5b1b20307132a
Pushing job with ID 5b1b203172be3
Pushing job with ID 5b1b2032740bf
Pushing job with ID 5b1b203374ad7
```

Finally, in a second terminal, start the consumer:

```bash
php example/consumer.php
```

And you should begin to see jobs processed:

```text
Received work unit 5b1b1f3a6fa7c
Received work unit 5b1b1f3b7120b
Received work unit 5b1b1f3c72601
Received work unit 5b1b1f3d7356d
Received work unit 5b1b1f3e74ca6
Received work unit 5b1b1f3f7668b
Received work unit 5b1b1f407758d
Received work unit 5b1b1f4178bd6
Received work unit 5b1b1f427a57e
Received work unit 5b1b1f437bebe
```

