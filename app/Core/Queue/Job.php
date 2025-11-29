<?php

declare(strict_types=1);


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

/**
 * Base Job Class
 * 
 * Base class for all queueable jobs.
 */
abstract class Job
{
    /**
     * The number of times the job may be attempted
     */
    public int $tries = 1;

    /**
     * The number of seconds to wait before retrying
     */
    public int $retryAfter = 0;

    /**
     * The queue to push the job to
     */
    public ?string $queue = null;

    /**
     * The connection to use
     */
    public ?string $connection = null;

    /**
     * Execute the job
     */
    abstract public function handle(): void;

    /**
     * Handle a job failure
     */
    public function failed(?\Throwable $exception = null): void
    {
        // Override in child classes to handle failures
    }

    /**
     * Dispatch the job
     */
    public static function dispatch(...$arguments): PendingDispatch
    {
        return new PendingDispatch(new static(...$arguments));
    }

    /**
     * Set the queue to dispatch to
     */
    public function onQueue(string $queue): static
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Set the connection to dispatch to
     */
    public function onConnection(string $connection): static
    {
        $this->connection = $connection;
        return $this;
    }
}
