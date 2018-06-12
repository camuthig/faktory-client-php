<?php

declare(strict_types=1);

namespace Camuthig\Faktory;

interface ConsumerInterface
{
    public const QUIET = 'quiet';
    public const TERMINATE = 'terminate';

    /**
     * Retrieve the first WorkUnit from the given queues.
     *
     * @param string[] $queues
     *
     * @return WorkUnit|null
     */
    public function fetch(string ...$queues): ?WorkUnit;

    /**
     * Acknowledge completion of the WorkUnit to the server.
     *
     * @param WorkUnit $workUnit
     */
    public function ack(WorkUnit $workUnit): void;

    /**
     * Inform the server that execution of a job has failed.
     *
     * @param WorkUnit $workUnit The WorkUnit that failed to execute properly.
     * @param string   $errorType The class of error that occurred during execution.
     * @param string   $message A short description of the error.
     * @param string   $backtrace A longer, multi-line backtrace of how the error occurred.
     */
    public function fail(WorkUnit $workUnit, string $errorType, string $message, string $backtrace): void;

    /**
     * Send a heartbeat to indicate liveness, and to get notified about server-initiated state changes.
     *
     * @return null|string The required state change. One of either ConsumerInterface::QUIET or ConsumerInterface::TERMINATE.
     */
    public function beat(): ?string;

    /**
     * Gracefully end the session with the server.
     */
    public function end(): void;
}
