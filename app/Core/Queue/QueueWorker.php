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

use App\Core\Contracts\Log\LoggerInterface;
use App\Core\Contracts\Queue\JobInterface;
use App\Core\Contracts\Queue\QueueInterface;
use Throwable;

/**
 * Queue Worker
 *
 * Long-running worker that processes jobs from the queue.
 * Supports graceful shutdown, memory limits, and job timeouts.
 */
class QueueWorker
{
    /**
     * The queue connection
     */
    protected QueueInterface $queue;

    /**
     * Logger instance
     */
    protected ?LoggerInterface $logger;

    /**
     * Whether the worker should stop
     */
    protected bool $shouldQuit = false;

    /**
     * Whether the worker is paused
     */
    protected bool $paused = false;

    /**
     * Worker options
     */
    protected array $options = [
        'sleep' => 3,           // Seconds to sleep when no jobs
        'max_jobs' => 0,        // Max jobs to process (0 = unlimited)
        'max_time' => 0,        // Max time to run in seconds (0 = unlimited)
        'memory_limit' => 128,  // Memory limit in MB
        'timeout' => 60,        // Job timeout in seconds
        'tries' => 3,           // Max retry attempts
        'retry_delay' => 60,    // Delay between retries in seconds
    ];

    /**
     * Number of jobs processed
     */
    protected int $jobsProcessed = 0;

    /**
     * Worker start time
     */
    protected float $startTime = 0.0;

    public function __construct(
        QueueInterface $queue,
        ?LoggerInterface $logger = null,
        array $options = []
    ) {
        $this->queue = $queue;
        $this->logger = $logger;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Run the worker daemon
     */
    public function daemon(string $queue = 'default'): void
    {
        $this->startTime = microtime(true);
        $this->registerSignalHandlers();

        $this->log('info', "Worker started for queue: {$queue}");

        while (! $this->shouldQuit) {
            // Check if paused
            if ($this->paused) {
                $this->sleep($this->options['sleep']);
                continue;
            }

            // Migrate timed-out jobs back to queue
            if ($this->queue instanceof RedisQueue) {
                $this->queue->migrateTimedOutJobs($queue);
            }

            // Try to get a job
            $job = $this->getNextJob($queue);

            if ($job) {
                $this->processJob($job);
                $this->jobsProcessed++;
            } else {
                $this->sleep($this->options['sleep']);
            }

            // Check limits
            if ($this->shouldStop()) {
                break;
            }
        }

        $this->log('info', "Worker stopped. Processed {$this->jobsProcessed} jobs.");
    }

    /**
     * Process a single job from the queue
     */
    public function runNextJob(string $queue = 'default'): bool
    {
        $job = $this->getNextJob($queue);

        if (! $job) {
            return false;
        }

        $this->processJob($job);

        return true;
    }

    /**
     * Get the next job from the queue
     */
    protected function getNextJob(string $queue): ?JobInterface
    {
        try {
            return $this->queue->pop($queue);
        } catch (Throwable $e) {
            $this->log('error', "Failed to get job: " . $e->getMessage());

            return null;
        }
    }

    /**
     * Process a job
     */
    protected function processJob(JobInterface $job): void
    {
        $jobName = $job->getName();
        $jobId = $job->getId();
        $startTime = microtime(true);

        $this->log('info', "Processing job: {$jobName}", ['job_id' => $jobId]);

        try {
            // Set up timeout if supported
            $this->setJobTimeout();

            $job->handle();

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->log('info', "Job completed: {$jobName}", [
                'job_id' => $jobId,
                'duration_ms' => $duration,
            ]);

        } catch (Throwable $e) {
            $this->handleJobException($job, $e);
        } finally {
            $this->clearJobTimeout();
        }
    }

    /**
     * Handle job exception
     */
    protected function handleJobException(JobInterface $job, Throwable $e): void
    {
        $jobName = $job->getName();
        $jobId = $job->getId();
        $attempts = $job->getAttempts();

        $this->log('error', "Job failed: {$jobName}", [
            'job_id' => $jobId,
            'attempts' => $attempts,
            'error' => $e->getMessage(),
        ]);

        if ($attempts < $this->options['tries']) {
            // Release for retry
            $job->release($this->options['retry_delay']);
            $this->log('info', "Job released for retry: {$jobName}", [
                'job_id' => $jobId,
                'next_attempt' => $attempts + 1,
            ]);
        } else {
            // Move to failed queue
            $job->fail($e);
            $this->log('warning', "Job moved to failed queue: {$jobName}", [
                'job_id' => $jobId,
            ]);
        }
    }

    /**
     * Check if the worker should stop
     */
    protected function shouldStop(): bool
    {
        // Check job limit
        if ($this->options['max_jobs'] > 0 && $this->jobsProcessed >= $this->options['max_jobs']) {
            $this->log('info', "Stopping: max jobs reached ({$this->options['max_jobs']})");

            return true;
        }

        // Check time limit
        if ($this->options['max_time'] > 0) {
            $elapsed = microtime(true) - $this->startTime;
            if ($elapsed >= $this->options['max_time']) {
                $this->log('info', "Stopping: max time reached ({$this->options['max_time']}s)");

                return true;
            }
        }

        // Check memory limit
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        if ($memoryUsage >= $this->options['memory_limit']) {
            $this->log('info', "Stopping: memory limit reached ({$memoryUsage}MB)");

            return true;
        }

        return false;
    }

    /**
     * Register signal handlers for graceful shutdown
     */
    protected function registerSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, fn () => $this->stop());
        pcntl_signal(SIGINT, fn () => $this->stop());
        pcntl_signal(SIGUSR2, fn () => $this->pause());
        pcntl_signal(SIGCONT, fn () => $this->resume());
    }

    /**
     * Set job timeout
     */
    protected function setJobTimeout(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_alarm($this->options['timeout']);
    }

    /**
     * Clear job timeout
     */
    protected function clearJobTimeout(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_alarm(0);
    }

    /**
     * Sleep for the given number of seconds
     */
    protected function sleep(int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        // Use usleep for sub-second precision and signal handling
        $microseconds = $seconds * 1000000;
        while ($microseconds > 0 && ! $this->shouldQuit) {
            $sleepTime = min($microseconds, 100000); // 100ms chunks
            usleep($sleepTime);
            $microseconds -= $sleepTime;
        }
    }

    /**
     * Stop the worker gracefully
     */
    public function stop(): void
    {
        $this->shouldQuit = true;
    }

    /**
     * Pause the worker
     */
    public function pause(): void
    {
        $this->paused = true;
        $this->log('info', 'Worker paused');
    }

    /**
     * Resume the worker
     */
    public function resume(): void
    {
        $this->paused = false;
        $this->log('info', 'Worker resumed');
    }

    /**
     * Log a message
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->$level($message, $context);
        }
    }

    /**
     * Get the number of jobs processed
     */
    public function getJobsProcessed(): int
    {
        return $this->jobsProcessed;
    }

    /**
     * Get worker statistics
     */
    public function getStats(): array
    {
        return [
            'jobs_processed' => $this->jobsProcessed,
            'uptime' => round(microtime(true) - $this->startTime, 2),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'paused' => $this->paused,
        ];
    }
}
