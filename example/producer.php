<?php

require __DIR__ . '/../vendor/autoload.php';

$connection = new \Camuthig\Faktory\Connection('tcp://127.0.0.1', 7419);

$producer = new \Camuthig\Faktory\Producer($connection);

while (true) {
    $id = uniqid();
    echo "Pushing job with ID $id\n";
    $producer->push(new \Camuthig\Faktory\WorkUnit($id, 'example', []));
    sleep(1);
}
