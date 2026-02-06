<?php declare(strict_types=1);

/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 *
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace App\Core\Queue;

use App\Core\Contracts\Queue\JobInterface;
use App\Core\Contracts\Queue\QueueInterface;
use Throwable;

/**
 * Synchronous Queue
 *
 * Executes jobs immediately without queuing.
 * Useful for development and testing.
 */
class SyncQueue implements QueueInterface
{
    /**
     * Push a new job onto the queue (executes immediately)
     */
    public function push(string|object $job, array $data = [], ?string $queue = null): string|int
    {
        $job = $this->resolveJob($job, $data);

        try {
            $job->handle();
        } catch (Throwable $e) {
            $job->fail($e);
            throw $e;
        }

        return uniqid('sync_', true);
    }

    /**
     * Push a new job onto the queue after a delay (executes immediately, delay ignored)
     */
    public function later(int $delay, string|object $job, array $data = [], ?string $queue = null): string|int
    {
        // Sync queue ignores delay
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue (always null for sync)
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        return null;
    }

    /**
     * Get the size of the queue (always 0 for sync)
     */
    public function size(?string $queue = null): int
    {
        return 0;
    }

    /**
     * Clear all jobs from the queue
     */
    public function clear(?string $queue = null): bool
    {
        return true;
    }

    /**
     * Resolve the job instance
     */
    protected function resolveJob(string|object $job, array $data): object
    {
        if (is_string($job)) {
            $job = new $job(...$data);
        }

        return $job;
    }
}
