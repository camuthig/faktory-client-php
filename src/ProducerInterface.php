<?php

declare(strict_types=1);

namespace Camuthig\Faktory;

interface ProducerInterface
{
    /**
     * Enqueue jobs at the work server for later execution.
     *
     * @param WorkUnit $workUnit
     */
    public function push(WorkUnit $workUnit): void;
}
