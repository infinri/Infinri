<?php

declare(strict_types=1);

namespace App\Core\Queue;

use App\Core\Contracts\Queue\JobInterface;
use Throwable;

/**
 * Redis Job
 * 
 * Represents a job pulled from a Redis queue with support for
 * releasing, retrying, and failure handling.
 */
class RedisJob implements JobInterface
{
    /**
     * The Redis queue instance
     */
    protected RedisQueue $queue;

    /**
     * The raw job payload
     */
    protected string $payload;

    /**
     * The decoded job data
     */
    protected array $decoded;

    /**
     * The queue name
     */
    protected ?string $queueName;

    /**
     * Whether the job has been released
     */
    protected bool $released = false;

    /**
     * Whether the job has been deleted
     */
    protected bool $deleted = false;

    /**
     * Whether the job has failed
     */
    protected bool $failed = false;

    public function __construct(RedisQueue $queue, string $payload, ?string $queueName = null)
    {
        $this->queue = $queue;
        $this->payload = $payload;
        $this->queueName = $queueName;
        $this->decoded = json_decode($payload, true) ?? [];
    }

    /**
     * Get the job ID
     */
    public function getId(): string
    {
        return $this->decoded['id'] ?? '';
    }

    /**
     * Get the job class name
     */
    public function getName(): string
    {
        return $this->decoded['job'] ?? '';
    }

    /**
     * Get the job data
     */
    public function getData(): mixed
    {
        return $this->decoded['data'] ?? [];
    }

    /**
     * Get the number of attempts
     */
    public function getAttempts(): int
    {
        return $this->decoded['attempts'] ?? 0;
    }

    /**
     * Get the number of times the job has been attempted (interface method)
     */
    public function attempts(): int
    {
        return $this->getAttempts();
    }

    /**
     * Handle the job
     */
    public function handle(): void
    {
        $jobClass = $this->getName();
        $data = $this->getData();

        if (!class_exists($jobClass)) {
            throw new QueueException("Job class does not exist: {$jobClass}");
        }

        // If data is already an instance of the job class
        if (is_object($data) && $data instanceof $jobClass) {
            $job = $data;
        } else {
            // Create new instance with data
            $job = new $jobClass(...(is_array($data) ? $data : [$data]));
        }

        if (method_exists($job, 'handle')) {
            $job->handle();
        }

        // Mark as completed
        $this->delete();
    }

    /**
     * Delete the job from the queue
     */
    public function delete(): void
    {
        if ($this->deleted) {
            return;
        }

        $this->queue->delete($this->payload, $this->queueName);
        $this->deleted = true;
    }

    /**
     * Release the job back onto the queue
     */
    public function release(int $delay = 0): void
    {
        if ($this->released || $this->deleted) {
            return;
        }

        $this->queue->release($this->payload, $delay, $this->queueName);
        $this->released = true;
    }

    /**
     * Mark the job as failed
     */
    public function fail(?\Throwable $e = null): void
    {
        if ($this->failed || $this->deleted) {
            return;
        }

        $message = $e ? $e->getMessage() . "\n" . $e->getTraceAsString() : 'Unknown error';

        $this->queue->fail(
            $this->payload,
            $message,
            $this->queueName
        );

        $this->failed = true;
    }

    /**
     * Get the raw payload
     */
    public function getRawPayload(): string
    {
        return $this->payload;
    }

    /**
     * Get the raw body of the job (interface method)
     */
    public function getRawBody(): string
    {
        return $this->payload;
    }

    /**
     * Determine if the job has been deleted or released
     */
    public function isDeletedOrReleased(): bool
    {
        return $this->deleted || $this->released;
    }

    /**
     * Get the queue name
     */
    public function getQueue(): ?string
    {
        return $this->queueName;
    }

    /**
     * Check if the job has been deleted
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Check if the job has been released
     */
    public function isReleased(): bool
    {
        return $this->released;
    }

    /**
     * Check if the job has failed
     */
    public function hasFailed(): bool
    {
        return $this->failed;
    }

    /**
     * Get the time the job was created
     */
    public function getCreatedAt(): int
    {
        return $this->decoded['created_at'] ?? 0;
    }
}
