<?php

declare(strict_types=1);

namespace App\Core\Contracts\Queue;

/**
 * Job Contract
 * 
 * Defines the interface for queue jobs.
 */
interface JobInterface
{
    /**
     * Get the job identifier
     */
    public function getId(): string|int;

    /**
     * Get the raw body of the job
     */
    public function getRawBody(): string;

    /**
     * Get the number of times the job has been attempted
     */
    public function attempts(): int;

    /**
     * Process the job
     */
    public function handle(): void;

    /**
     * Delete the job from the queue
     */
    public function delete(): void;

    /**
     * Release the job back onto the queue
     */
    public function release(int $delay = 0): void;

    /**
     * Mark the job as failed
     */
    public function fail(?\Throwable $e = null): void;

    /**
     * Determine if the job has been deleted or released
     */
    public function isDeletedOrReleased(): bool;
}
