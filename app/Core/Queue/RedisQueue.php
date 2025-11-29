<?php

declare(strict_types=1);

namespace App\Core\Queue;

use App\Core\Contracts\Queue\QueueInterface;
use App\Core\Contracts\Queue\JobInterface;
use App\Core\Redis\RedisManager;
use Redis;
use RedisException;

/**
 * Redis Queue
 * 
 * Reliable queue implementation using Redis with support for
 * delayed jobs, retries, and dead letter queues.
 */
class RedisQueue implements QueueInterface
{
    /**
     * Redis manager instance
     */
    protected RedisManager $redis;

    /**
     * The Redis connection to use
     */
    protected string $connection;

    /**
     * Default queue name
     */
    protected string $defaultQueue;

    /**
     * Key prefix for queue keys
     */
    protected string $prefix;

    /**
     * Maximum retry attempts
     */
    protected int $maxRetries;

    /**
     * Retry delay in seconds
     */
    protected int $retryDelay;

    public function __construct(
        RedisManager $redis,
        string $connection = 'queue',
        string $defaultQueue = 'default',
        string $prefix = 'queue:',
        int $maxRetries = 3,
        int $retryDelay = 60
    ) {
        $this->redis = $redis;
        $this->connection = $connection;
        $this->defaultQueue = $defaultQueue;
        $this->prefix = $prefix;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    /**
     * Get the Redis connection
     */
    protected function redis(): Redis
    {
        return $this->redis->connection($this->connection);
    }

    /**
     * Get the queue key
     */
    protected function queueKey(?string $queue): string
    {
        return $this->prefix . ($queue ?? $this->defaultQueue);
    }

    /**
     * Get the delayed queue key
     */
    protected function delayedKey(?string $queue): string
    {
        return $this->prefix . ($queue ?? $this->defaultQueue) . ':delayed';
    }

    /**
     * Get the reserved queue key (jobs being processed)
     */
    protected function reservedKey(?string $queue): string
    {
        return $this->prefix . ($queue ?? $this->defaultQueue) . ':reserved';
    }

    /**
     * Get the failed queue key
     */
    protected function failedKey(?string $queue): string
    {
        return $this->prefix . ($queue ?? $this->defaultQueue) . ':failed';
    }

    /**
     * Push a new job onto the queue
     */
    public function push(string|object $job, array $data = [], ?string $queue = null): string|int
    {
        $payload = $this->createPayload($job, $data);

        try {
            $this->redis()->rPush($this->queueKey($queue), $payload);
            return $this->getJobId($payload);
        } catch (RedisException $e) {
            throw new QueueException("Failed to push job to queue: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Push a new job onto the queue after a delay
     */
    public function later(int $delay, string|object $job, array $data = [], ?string $queue = null): string|int
    {
        $payload = $this->createPayload($job, $data);
        $availableAt = time() + $delay;

        try {
            $this->redis()->zAdd($this->delayedKey($queue), $availableAt, $payload);
            return $this->getJobId($payload);
        } catch (RedisException $e) {
            throw new QueueException("Failed to push delayed job to queue: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Pop the next job off of the queue
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        // First, migrate any delayed jobs that are ready
        $this->migrateDelayedJobs($queue);

        try {
            // Use BLPOP with timeout for blocking pop (more efficient than polling)
            // For non-blocking, use LPOP
            $payload = $this->redis()->lPop($this->queueKey($queue));

            if ($payload === false || $payload === null) {
                return null;
            }

            // Move to reserved queue while processing
            $this->redis()->zAdd(
                $this->reservedKey($queue),
                time() + 60, // Reserve for 60 seconds
                $payload
            );

            return $this->resolveJob($payload, $queue);
        } catch (RedisException $e) {
            throw new QueueException("Failed to pop job from queue: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the size of the queue
     */
    public function size(?string $queue = null): int
    {
        try {
            return (int) $this->redis()->lLen($this->queueKey($queue));
        } catch (RedisException $e) {
            logger()->warning('Queue size check failed', ['queue' => $queue, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Clear all jobs from the queue
     */
    public function clear(?string $queue = null): bool
    {
        try {
            $this->redis()->del($this->queueKey($queue));
            $this->redis()->del($this->delayedKey($queue));
            $this->redis()->del($this->reservedKey($queue));
            return true;
        } catch (RedisException $e) {
            logger()->error('Queue clear failed', ['queue' => $queue, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Delete a job from the reserved queue (job completed)
     */
    public function delete(string $payload, ?string $queue = null): void
    {
        try {
            $this->redis()->zRem($this->reservedKey($queue), $payload);
        } catch (RedisException $e) {
            logger()->warning('Queue job delete failed', ['queue' => $queue, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Release a job back onto the queue
     */
    public function release(string $payload, int $delay = 0, ?string $queue = null): void
    {
        // Remove from reserved
        $this->delete($payload, $queue);

        // Update attempts count
        $data = json_decode($payload, true);
        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        $newPayload = json_encode($data);

        try {
            if ($delay > 0) {
                $this->redis()->zAdd($this->delayedKey($queue), time() + $delay, $newPayload);
            } else {
                $this->redis()->rPush($this->queueKey($queue), $newPayload);
            }
        } catch (RedisException $e) {
            throw new QueueException("Failed to release job: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Move job to failed queue
     */
    public function fail(string $payload, string $exception, ?string $queue = null): void
    {
        // Remove from reserved
        $this->delete($payload, $queue);

        // Add failure info
        $data = json_decode($payload, true);
        $data['failed_at'] = time();
        $data['exception'] = $exception;
        $failedPayload = json_encode($data);

        try {
            $this->redis()->rPush($this->failedKey($queue), $failedPayload);
        } catch (RedisException $e) {
            logger()->error('Queue fail recording failed', ['queue' => $queue, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Migrate delayed jobs that are ready to the main queue
     */
    protected function migrateDelayedJobs(?string $queue): void
    {
        $now = time();

        try {
            // Get all delayed jobs that are ready
            $jobs = $this->redis()->zRangeByScore(
                $this->delayedKey($queue),
                '-inf',
                (string) $now
            );

            foreach ($jobs as $job) {
                // Remove from delayed queue
                $this->redis()->zRem($this->delayedKey($queue), $job);
                // Add to main queue
                $this->redis()->rPush($this->queueKey($queue), $job);
            }
        } catch (RedisException $e) {
            logger()->warning('Queue delayed job migration failed', ['queue' => $queue, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Migrate timed-out reserved jobs back to the queue
     */
    public function migrateTimedOutJobs(?string $queue = null): int
    {
        $now = time();
        $count = 0;

        try {
            // Get all reserved jobs that have timed out
            $jobs = $this->redis()->zRangeByScore(
                $this->reservedKey($queue),
                '-inf',
                (string) $now
            );

            foreach ($jobs as $job) {
                $data = json_decode($job, true);
                $attempts = $data['attempts'] ?? 0;

                // Remove from reserved
                $this->redis()->zRem($this->reservedKey($queue), $job);

                if ($attempts < $this->maxRetries) {
                    // Retry with delay
                    $this->release($job, $this->retryDelay, $queue);
                } else {
                    // Move to failed queue
                    $this->fail($job, 'Max retry attempts exceeded', $queue);
                }

                $count++;
            }
        } catch (RedisException $e) {
            logger()->warning('Queue timed-out job migration failed', ['queue' => $queue, 'error' => $e->getMessage()]);
        }

        return $count;
    }

    /**
     * Create the job payload
     */
    protected function createPayload(string|object $job, array $data): string
    {
        if (is_object($job)) {
            $payload = [
                'id' => $this->generateJobId(),
                'job' => get_class($job),
                'data' => $job,
                'attempts' => 0,
                'created_at' => time(),
            ];
        } else {
            $payload = [
                'id' => $this->generateJobId(),
                'job' => $job,
                'data' => $data,
                'attempts' => 0,
                'created_at' => time(),
            ];
        }

        return json_encode($payload);
    }

    /**
     * Resolve a job from its payload
     */
    protected function resolveJob(string $payload, ?string $queue): RedisJob
    {
        return new RedisJob($this, $payload, $queue);
    }

    /**
     * Generate a unique job ID
     */
    protected function generateJobId(): string
    {
        return 'job_' . bin2hex(random_bytes(16));
    }

    /**
     * Get the job ID from a payload
     */
    protected function getJobId(string $payload): string
    {
        $data = json_decode($payload, true);
        return $data['id'] ?? '';
    }

    /**
     * Get failed jobs
     */
    public function failed(?string $queue = null, int $limit = 100): array
    {
        try {
            $jobs = $this->redis()->lRange($this->failedKey($queue), 0, $limit - 1);
            return array_map(fn($j) => json_decode($j, true), $jobs);
        } catch (RedisException $e) {
            logger()->warning('Queue failed jobs retrieval failed', ['queue' => $queue, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Retry a failed job
     */
    public function retryFailed(int $index, ?string $queue = null): bool
    {
        try {
            $job = $this->redis()->lIndex($this->failedKey($queue), $index);

            if ($job === false) {
                return false;
            }

            // Reset attempts and remove failure info
            $data = json_decode($job, true);
            $data['attempts'] = 0;
            unset($data['failed_at'], $data['exception']);
            $newPayload = json_encode($data);

            // Remove from failed queue
            $this->redis()->lRem($this->failedKey($queue), 1, $job);

            // Push to main queue
            $this->redis()->rPush($this->queueKey($queue), $newPayload);

            return true;
        } catch (RedisException $e) {
            logger()->error('Queue retry failed job failed', ['queue' => $queue, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Flush failed jobs
     */
    public function flushFailed(?string $queue = null): bool
    {
        try {
            $this->redis()->del($this->failedKey($queue));
            return true;
        } catch (RedisException $e) {
            logger()->error('Queue flush failed jobs failed', ['queue' => $queue, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    public function stats(?string $queue = null): array
    {
        try {
            return [
                'pending' => $this->redis()->lLen($this->queueKey($queue)),
                'delayed' => $this->redis()->zCard($this->delayedKey($queue)),
                'reserved' => $this->redis()->zCard($this->reservedKey($queue)),
                'failed' => $this->redis()->lLen($this->failedKey($queue)),
            ];
        } catch (RedisException $e) {
            logger()->warning('Queue stats retrieval failed', ['queue' => $queue, 'error' => $e->getMessage()]);
            return ['pending' => 0, 'delayed' => 0, 'reserved' => 0, 'failed' => 0];
        }
    }
}
