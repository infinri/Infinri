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
use App\Core\Queue\RedisQueue;
use Throwable;

/**
 * Queue Status Command
 *
 * Shows queue statistics and failed jobs.
 */
class QueueStatusCommand extends Command
{
    protected string $name = 'queue:status';
    protected string $description = 'Show queue statistics';
    protected array $aliases = ['qs'];

    public function handle(array $args = []): int
    {
        $queue = 'default';
        $showFailed = false;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--queue=')) {
                $queue = substr($arg, 8);
            } elseif ($arg === '--failed' || $arg === '-f') {
                $showFailed = true;
            }
        }

        if (env('QUEUE_CONNECTION') !== 'redis') {
            $this->error("Queue connection is not set to 'redis'. Current: " . env('QUEUE_CONNECTION', 'sync'));

            return 1;
        }

        try {
            $app = \App\Core\Application::getInstance();
            $queueInstance = $app->make(QueueInterface::class);

            if (! $queueInstance instanceof RedisQueue) {
                $this->error("Queue is not a Redis queue");

                return 1;
            }

            $this->line("📊 Queue Status: {$queue}");
            $this->line(str_repeat('─', 40));

            $stats = $queueInstance->stats($queue);

            $this->line("  Pending:  " . $this->colorize($stats['pending'], 'yellow'));
            $this->line("  Delayed:  " . $this->colorize($stats['delayed'], 'cyan'));
            $this->line("  Reserved: " . $this->colorize($stats['reserved'], 'blue'));
            $this->line("  Failed:   " . $this->colorize($stats['failed'], 'red'));

            if ($showFailed && $stats['failed'] > 0) {
                $this->line("\n📋 Failed Jobs:");
                $this->line(str_repeat('─', 40));

                $failed = $queueInstance->failed($queue, 10);
                foreach ($failed as $i => $job) {
                    $this->line("\n  [{$i}] " . ($job['job'] ?? 'Unknown'));
                    $this->line("      ID: " . ($job['id'] ?? 'N/A'));
                    $this->line("      Failed: " . date('Y-m-d H:i:s', $job['failed_at'] ?? 0));
                    if (isset($job['exception'])) {
                        $error = substr($job['exception'], 0, 100);
                        $this->line("      Error: {$error}...");
                    }
                }
            }

            return 0;

        } catch (Throwable $e) {
            $this->error("Error: " . $e->getMessage());

            return 1;
        }
    }

    protected function colorize(int $value, string $color): string
    {
        $colors = [
            'red' => "\033[31m",
            'green' => "\033[32m",
            'yellow' => "\033[33m",
            'blue' => "\033[34m",
            'cyan' => "\033[36m",
            'reset' => "\033[0m",
        ];

        return ($colors[$color] ?? '') . $value . $colors['reset'];
    }
}
