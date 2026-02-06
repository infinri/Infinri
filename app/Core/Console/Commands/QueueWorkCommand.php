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
namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Contracts\Queue\QueueInterface;
use App\Core\Queue\QueueWorker;
use Throwable;

/**
 * Queue Work Command
 *
 * Starts a queue worker to process background jobs.
 */
class QueueWorkCommand extends Command
{
    protected string $name = 'queue:work';
    protected string $description = 'Start processing jobs on the queue';
    protected array $aliases = ['qw'];

    public function handle(array $args = []): int
    {
        // Parse arguments
        $queue = 'default';
        $once = false;
        $options = [
            'sleep' => 3,
            'tries' => 3,
            'timeout' => 60,
            'memory_limit' => 128,
            'max_jobs' => 0,
            'max_time' => 0,
        ];

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--queue=')) {
                $queue = substr($arg, 8);
            } elseif ($arg === '--once') {
                $once = true;
            } elseif (str_starts_with($arg, '--sleep=')) {
                $options['sleep'] = (int) substr($arg, 8);
            } elseif (str_starts_with($arg, '--tries=')) {
                $options['tries'] = (int) substr($arg, 8);
            } elseif (str_starts_with($arg, '--timeout=')) {
                $options['timeout'] = (int) substr($arg, 10);
            } elseif (str_starts_with($arg, '--memory=')) {
                $options['memory_limit'] = (int) substr($arg, 9);
            } elseif (str_starts_with($arg, '--max-jobs=')) {
                $options['max_jobs'] = (int) substr($arg, 11);
            } elseif (str_starts_with($arg, '--max-time=')) {
                $options['max_time'] = (int) substr($arg, 11);
            }
        }

        // Check if Redis queue is configured
        if (env('QUEUE_CONNECTION') !== 'redis') {
            $this->error("Queue connection is not set to 'redis'. Current: " . env('QUEUE_CONNECTION', 'sync'));
            $this->line("Set QUEUE_CONNECTION=redis in your .env file to use the queue worker.");

            return 1;
        }

        $this->line("🔄 Queue Worker");
        $this->line(str_repeat('─', 40));
        $this->line("Queue: {$queue}");
        $this->line("Mode: " . ($once ? 'Single job' : 'Daemon'));
        $this->line("Memory limit: {$options['memory_limit']}MB");
        $this->line("Timeout: {$options['timeout']}s");
        $this->line(str_repeat('─', 40));

        try {
            // Bootstrap application
            $app = \App\Core\Application::getInstance();

            // Get queue from container
            $queueInstance = $app->make(QueueInterface::class);

            // Create worker
            $worker = new QueueWorker($queueInstance, logger(), $options);

            if ($once) {
                // Process single job
                $this->info("Processing next job...");
                $processed = $worker->runNextJob($queue);

                if ($processed) {
                    $this->info("✓ Job processed successfully");
                } else {
                    $this->line("No jobs available");
                }
            } else {
                // Run daemon
                $this->info("Starting worker daemon (Ctrl+C to stop)...\n");
                $worker->daemon($queue);
            }

            return 0;

        } catch (Throwable $e) {
            $this->error("Worker error: " . $e->getMessage());

            return 1;
        }
    }

    public function getHelp(): string
    {
        return <<<HELP
            Usage: queue:work [options]

            Options:
              --queue=NAME       Queue to process (default: default)
              --once             Process a single job and exit
              --sleep=SECONDS    Seconds to sleep when no jobs (default: 3)
              --tries=NUMBER     Max retry attempts (default: 3)
              --timeout=SECONDS  Job timeout in seconds (default: 60)
              --memory=MB        Memory limit in MB (default: 128)
              --max-jobs=NUMBER  Stop after processing N jobs (0 = unlimited)
              --max-time=SECONDS Stop after running N seconds (0 = unlimited)

            Examples:
              php bin/console queue:work
              php bin/console queue:work --queue=emails
              php bin/console queue:work --once
              php bin/console queue:work --max-jobs=100 --max-time=3600
            HELP;
    }
}
