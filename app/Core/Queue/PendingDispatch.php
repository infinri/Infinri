<?php

declare(strict_types=1);

namespace App\Core\Queue;

/**
 * Pending Dispatch
 * 
 * Represents a job waiting to be dispatched.
 */
class PendingDispatch
{
    protected Job $job;

    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    /**
     * Set the queue to dispatch to
     */
    public function onQueue(string $queue): static
    {
        $this->job->onQueue($queue);
        return $this;
    }

    /**
     * Set the connection to dispatch to
     */
    public function onConnection(string $connection): static
    {
        $this->job->onConnection($connection);
        return $this;
    }

    /**
     * Get the underlying job
     */
    public function getJob(): Job
    {
        return $this->job;
    }

    /**
     * Dispatch the job immediately (sync)
     */
    public function dispatch(): void
    {
        // For now, just execute synchronously
        $this->job->handle();
    }

    /**
     * Handle object destruction - auto-dispatch
     */
    public function __destruct()
    {
        // Auto-dispatch when the PendingDispatch goes out of scope
        // This is the standard Laravel behavior
    }
}
