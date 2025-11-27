<?php

declare(strict_types=1);

namespace App\Core\Contracts\Queue;

/**
 * Queue Contract
 * 
 * Defines the queue interface for background job processing.
 */
interface QueueInterface
{
    /**
     * Push a new job onto the queue
     */
    public function push(string|object $job, array $data = [], ?string $queue = null): string|int;

    /**
     * Push a new job onto the queue after a delay
     */
    public function later(int $delay, string|object $job, array $data = [], ?string $queue = null): string|int;

    /**
     * Pop the next job off of the queue
     */
    public function pop(?string $queue = null): ?JobInterface;

    /**
     * Get the size of the queue
     */
    public function size(?string $queue = null): int;

    /**
     * Clear all jobs from the queue
     */
    public function clear(?string $queue = null): bool;
}
