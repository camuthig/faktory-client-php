<?php

declare(strict_types=1);

namespace Camuthig\Faktory;

use Camuthig\Faktory\Exception\FaktoryException;

class Consumer implements ConsumerInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        if (empty($connection->getWorkerOptions())) {
            throw new FaktoryException('Consumer connections must include worker options');
        }

        if (!isset($connection->getWorkerOptions()['wid'])) {
            throw new FaktoryException('Consumer worker ID is required');
        }

        $this->connection = $connection;
    }

    /**
     * Retrieve the first WorkUnit from the given queues.
     *
     * @param string[] $queues
     *
     * @return WorkUnit|null
     */
    public function fetch(string ...$queues): ?WorkUnit
    {
        // TODO: explicitly naming the default queue should not be necessary to avoid blank names
        $response = $this->connection->writeCommand('FETCH', empty($queues) ? 'default' : implode(' ', $queues));

        if (empty($response)) {
            return null;
        }

        return WorkUnit::fromJson($response);
    }

    /**
     * Acknowledge completion of the WorkUnit to the server.
     *
     * @param WorkUnit $workUnit
     */
    public function ack(WorkUnit $workUnit): void
    {
        $this->connection->writeCommand('ACK', json_encode(['jid' => $workUnit->getJobId()]));
    }

    /**
     * Inform the server that execution of a job has failed.
     *
     * @param WorkUnit $workUnit  The WorkUnit that failed to execute properly.
     * @param string   $errorType The class of error that occurred during execution.
     * @param string   $message   A short description of the error.
     * @param string[] $backtrace A longer, multi-line backtrace of how the error occurred.
     */
    public function fail(WorkUnit $workUnit, string $errorType, string $message, array $backtrace): void
    {
        $this->connection->writeCommand('FAIL', json_encode([
            'jid' => $workUnit->getJobId(),
            'errType' => $errorType,
            'message' => $message,
            'backtrace' => $backtrace,
        ]));
    }

    /**
     * Send a heartbeat to indicate liveness, and to get notified about server-initiated state changes.
     *
     * @return null|string The required state change. One of either ConsumerInterface::QUIET or ConsumerInterface::TERMINATE.
     */
    public function beat(): ?string
    {
        $response = $this->connection->writeCommand(
            'BEAT',
            json_encode(['wid' => $this->connection->getWorkerOptions()['wid']])
        );

        if (strpos($response, 'OK') === 0) {
            return null;
        }

        $data = json_decode($response, true);

        return $data['state'];
    }

    public function end(): void
    {
        $this->connection->end();
    }
}
