<?php

declare(strict_types=1);

namespace Camuthig\Faktory;

class Producer implements ProducerInterface
{

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Enqueue jobs at the work server for later execution.
     *
     * @param WorkUnit $workUnit
     */
    public function push(WorkUnit $workUnit): void
    {
        $this->connection->writeCommand('PUSH', json_encode($workUnit));
    }
}
