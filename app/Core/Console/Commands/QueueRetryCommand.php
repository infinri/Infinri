<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Queue\RedisQueue;
use App\Core\Contracts\Queue\QueueInterface;

/**
 * Queue Retry Command
 * 
 * Retries failed jobs.
 */
class QueueRetryCommand extends Command
{
    protected string $name = 'queue:retry';
    protected string $description = 'Retry failed jobs';
    protected array $aliases = ['qr'];

    public function handle(array $args = []): int
    {
        $queue = 'default';
        $all = false;
        $index = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--queue=')) {
                $queue = substr($arg, 8);
            } elseif ($arg === '--all' || $arg === '-a') {
                $all = true;
            } elseif (is_numeric($arg)) {
                $index = (int) $arg;
            }
        }

        if (env('QUEUE_CONNECTION') !== 'redis') {
            $this->error("Queue connection is not set to 'redis'");
            return 1;
        }

        try {
            $app = \App\Core\Application::getInstance();
            $queueInstance = $app->make(QueueInterface::class);

            if (!$queueInstance instanceof RedisQueue) {
                $this->error("Queue is not a Redis queue");
                return 1;
            }

            $this->line("🔄 Retrying failed jobs: {$queue}");
            $this->line(str_repeat('─', 40));

            if ($all) {
                // Retry all failed jobs
                $failed = $queueInstance->failed($queue);
                $count = 0;

                for ($i = count($failed) - 1; $i >= 0; $i--) {
                    if ($queueInstance->retryFailed($i, $queue)) {
                        $count++;
                    }
                }

                $this->info("✓ Retried {$count} jobs");

            } elseif ($index !== null) {
                // Retry specific job
                if ($queueInstance->retryFailed($index, $queue)) {
                    $this->info("✓ Job at index {$index} queued for retry");
                } else {
                    $this->error("Failed to retry job at index {$index}");
                    return 1;
                }

            } else {
                $this->error("Please specify --all or a job index");
                $this->line("\nUsage:");
                $this->line("  queue:retry --all           Retry all failed jobs");
                $this->line("  queue:retry 0               Retry job at index 0");
                $this->line("  queue:retry 0 --queue=mail  Retry from specific queue");
                return 1;
            }

            return 0;

        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
