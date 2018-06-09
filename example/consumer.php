<?php

declare(ticks=1);

require __DIR__ . '/../vendor/autoload.php';

$connection = new \Camuthig\Faktory\Connection('127.0.0.1', 7419, [
    'wid' => uniqid(),
    'labels' => ['php'],
]);

$consumer = new \Camuthig\Faktory\Consumer($connection);

$status = null;
$interrupt = null;

function signalHandler($signo)
{
    global $interrupt;
    $interrupt = true;
}
pcntl_signal(SIGINT, function ($signo, $signinfo) use (&$interrupt) {
    $interrupt = true;
});

while (true) {
    if ($interrupt) {
        echo "Stopping consumer...\n";
        $consumer->end();
        exit(0);
    }

    if ($status === \Camuthig\Faktory\ConsumerInterface::TERMINATE) {
        echo "Server requested consumer termination.\n";
        exit(0);
    } elseif ($status === \Camuthig\Faktory\ConsumerInterface::QUIET) {
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
