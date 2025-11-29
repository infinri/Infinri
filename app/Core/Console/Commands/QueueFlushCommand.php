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
namespace App\Core\Console\Commands;

use App\Core\Console\Command;
use App\Core\Queue\RedisQueue;
use App\Core\Contracts\Queue\QueueInterface;

/**
 * Queue Flush Command
 * 
 * Clears queue jobs.
 */
class QueueFlushCommand extends Command
{
    protected string $name = 'queue:flush';
    protected string $description = 'Flush queue jobs';
    protected array $aliases = ['qf'];

    public function handle(array $args = []): int
    {
        $queue = 'default';
        $failed = false;
        $all = false;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--queue=')) {
                $queue = substr($arg, 8);
            } elseif ($arg === '--failed' || $arg === '-f') {
                $failed = true;
            } elseif ($arg === '--all' || $arg === '-a') {
                $all = true;
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

            $this->line("🗑️  Flushing queue: {$queue}");
            $this->line(str_repeat('─', 40));

            if ($failed) {
                // Flush only failed jobs
                $queueInstance->flushFailed($queue);
                $this->info("✓ Failed jobs flushed");

            } elseif ($all) {
                // Flush all (pending + delayed + reserved + failed)
                $queueInstance->clear($queue);
                $queueInstance->flushFailed($queue);
                $this->info("✓ All jobs flushed (pending, delayed, reserved, failed)");

            } else {
                // Flush pending only
                $queueInstance->clear($queue);
                $this->info("✓ Pending jobs flushed");
            }

            return 0;

        } catch (\Throwable $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
