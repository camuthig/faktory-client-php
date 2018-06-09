<?php

declare(strict_types=1);

namespace Camuthig\Faktory;

final class WorkUnit implements \JsonSerializable
{
    /**
     * @var string
     */
    private $jobId;

    /**
     * @var string
     */
    private $jobType;

    /**
     * @var array
     */
    private $args;

    /**
     * @var array
     */
    private $options;

    /**
     * Create a WorkUnit to send to the server.
     *
     * Option fields can be provided in `options` and must match one of the below:
     * | Field name    | Value type     | When omitted   | Description |
     * | ------------- | -------------- | -------------- | ----------- |
     * | `queue`       | string         | `default`      | which job queue to push this job onto.
     * | `priority`    | int [1-9]      | 5              | higher priority jobs are dequeued before lower priority jobs.
     * | `reserve_for` | int [60+]      | 1800           | number of seconds a job may be held by a worker before it is considered failed.
     * | `at`          | RFC3339 string | \<blank\>      | run the job at approximately this time; immediately if blank
     * | `retry`       | int            | 25             | number of times to retry this job if it fails. -1 prevents retries.
     * | `backtrace`   | int            | 0              | number of lines of FAIL information to preserve.
     * | `created_at`  | RFC3339 string | set by server  | used to indicate the creation time of this job.
     * | `custom`      | array          | `null`         | provides additional context to the worker executing the job.
     *
     * @param string $jobId
     * @param string $jobType
     * @param array  $args Args must for an indexed array of values. The server will reject an associative array.
     * @param array  $options An array of valid options
     */
    public function __construct(string $jobId, string $jobType, array $args, array $options = [])
    {

        $this->jobId   = $jobId;
        $this->jobType = $jobType;
        $this->args    = $args;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }

    /**
     * @return string
     */
    public function getJobType(): string
    {
        return $this->jobType;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function jsonSerialize()
    {
        $data =  [
            'jid' => $this->getJobId(),
            'jobtype' => $this->getJobType(),
            'args' => $this->getArgs(),
        ];

        return array_replace($this->getOptions(), $data);
    }

    public static function fromJson(string $json): WorkUnit
    {
        $data = json_decode($json, true);

        $jobId = $data['jid'];
        $jobType = $data['jobtype'];
        $args = $data['args'];

        unset($data['jid'], $data['jobtype'], $data['args']);

        return new WorkUnit($jobId, $jobType, $args, $data);
    }
}
