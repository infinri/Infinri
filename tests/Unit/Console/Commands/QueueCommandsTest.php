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
namespace Tests\Unit\Console\Commands;

use App\Core\Console\Commands\QueueStatusCommand;
use App\Core\Console\Commands\QueueFlushCommand;
use App\Core\Console\Commands\QueueRetryCommand;
use App\Core\Console\Commands\QueueWorkCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class QueueCommandsTest extends TestCase
{
    #[Test]
    public function queue_status_has_correct_name(): void
    {
        $command = new QueueStatusCommand();
        
        $this->assertEquals('queue:status', $command->getName());
    }

    #[Test]
    public function queue_status_has_aliases(): void
    {
        $command = new QueueStatusCommand();
        
        $this->assertContains('qs', $command->getAliases());
    }

    #[Test]
    public function queue_status_has_description(): void
    {
        $command = new QueueStatusCommand();
        
        $this->assertEquals('Show queue statistics', $command->getDescription());
    }

    #[Test]
    public function queue_status_returns_error_for_non_redis_connection(): void
    {
        // Ensure QUEUE_CONNECTION is not redis
        $originalValue = $_ENV['QUEUE_CONNECTION'] ?? null;
        $_ENV['QUEUE_CONNECTION'] = 'sync';
        putenv('QUEUE_CONNECTION=sync');
        
        $command = new QueueStatusCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        // Restore
        if ($originalValue !== null) {
            $_ENV['QUEUE_CONNECTION'] = $originalValue;
            putenv("QUEUE_CONNECTION={$originalValue}");
        } else {
            unset($_ENV['QUEUE_CONNECTION']);
            putenv('QUEUE_CONNECTION');
        }
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('not set to', $output);
    }

    #[Test]
    public function queue_flush_has_correct_name(): void
    {
        $command = new QueueFlushCommand();
        
        $this->assertEquals('queue:flush', $command->getName());
    }

    #[Test]
    public function queue_flush_has_aliases(): void
    {
        $command = new QueueFlushCommand();
        
        $this->assertContains('qf', $command->getAliases());
    }

    #[Test]
    public function queue_flush_has_description(): void
    {
        $command = new QueueFlushCommand();
        
        $this->assertEquals('Flush queue jobs', $command->getDescription());
    }

    #[Test]
    public function queue_flush_returns_error_for_non_redis_connection(): void
    {
        $originalValue = $_ENV['QUEUE_CONNECTION'] ?? null;
        $_ENV['QUEUE_CONNECTION'] = 'sync';
        putenv('QUEUE_CONNECTION=sync');
        
        $command = new QueueFlushCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        if ($originalValue !== null) {
            $_ENV['QUEUE_CONNECTION'] = $originalValue;
            putenv("QUEUE_CONNECTION={$originalValue}");
        } else {
            unset($_ENV['QUEUE_CONNECTION']);
            putenv('QUEUE_CONNECTION');
        }
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('not set to', $output);
    }

    #[Test]
    public function queue_retry_has_correct_name(): void
    {
        $command = new QueueRetryCommand();
        
        $this->assertEquals('queue:retry', $command->getName());
    }

    #[Test]
    public function queue_retry_has_aliases(): void
    {
        $command = new QueueRetryCommand();
        
        $this->assertContains('qr', $command->getAliases());
    }

    #[Test]
    public function queue_retry_has_description(): void
    {
        $command = new QueueRetryCommand();
        
        $this->assertEquals('Retry failed jobs', $command->getDescription());
    }

    #[Test]
    public function queue_retry_returns_error_for_non_redis_connection(): void
    {
        $originalValue = $_ENV['QUEUE_CONNECTION'] ?? null;
        $_ENV['QUEUE_CONNECTION'] = 'sync';
        putenv('QUEUE_CONNECTION=sync');
        
        $command = new QueueRetryCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        if ($originalValue !== null) {
            $_ENV['QUEUE_CONNECTION'] = $originalValue;
            putenv("QUEUE_CONNECTION={$originalValue}");
        } else {
            unset($_ENV['QUEUE_CONNECTION']);
            putenv('QUEUE_CONNECTION');
        }
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('not set to', $output);
    }

    #[Test]
    public function queue_work_has_correct_name(): void
    {
        $command = new QueueWorkCommand();
        
        $this->assertEquals('queue:work', $command->getName());
    }

    #[Test]
    public function queue_work_has_aliases(): void
    {
        $command = new QueueWorkCommand();
        
        $this->assertContains('qw', $command->getAliases());
    }

    #[Test]
    public function queue_work_has_description(): void
    {
        $command = new QueueWorkCommand();
        
        $this->assertEquals('Start processing jobs on the queue', $command->getDescription());
    }

    #[Test]
    public function queue_work_returns_error_for_non_redis_connection(): void
    {
        $originalValue = $_ENV['QUEUE_CONNECTION'] ?? null;
        $_ENV['QUEUE_CONNECTION'] = 'sync';
        putenv('QUEUE_CONNECTION=sync');
        
        $command = new QueueWorkCommand();
        
        ob_start();
        $result = $command->handle([]);
        $output = ob_get_clean();
        
        if ($originalValue !== null) {
            $_ENV['QUEUE_CONNECTION'] = $originalValue;
            putenv("QUEUE_CONNECTION={$originalValue}");
        } else {
            unset($_ENV['QUEUE_CONNECTION']);
            putenv('QUEUE_CONNECTION');
        }
        
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('not set to', $output);
    }

    #[Test]
    public function queue_status_parses_queue_argument(): void
    {
        $command = new QueueStatusCommand();
        
        // Use reflection to test argument parsing indirectly
        // by checking it doesn't crash with valid args
        $originalValue = $_ENV['QUEUE_CONNECTION'] ?? null;
        $_ENV['QUEUE_CONNECTION'] = 'sync';
        putenv('QUEUE_CONNECTION=sync');
        
        ob_start();
        $result = $command->handle(['--queue=high', '--failed']);
        ob_get_clean();
        
        if ($originalValue !== null) {
            $_ENV['QUEUE_CONNECTION'] = $originalValue;
            putenv("QUEUE_CONNECTION={$originalValue}");
        } else {
            unset($_ENV['QUEUE_CONNECTION']);
            putenv('QUEUE_CONNECTION');
        }
        
        // Should return 1 (error) because not redis, but shouldn't crash
        $this->assertEquals(1, $result);
    }

    #[Test]
    public function queue_flush_parses_flags(): void
    {
        $originalValue = $_ENV['QUEUE_CONNECTION'] ?? null;
        $_ENV['QUEUE_CONNECTION'] = 'sync';
        putenv('QUEUE_CONNECTION=sync');
        
        $command = new QueueFlushCommand();
        
        ob_start();
        $result1 = $command->handle(['--failed']);
        ob_get_clean();
        
        ob_start();
        $result2 = $command->handle(['-f']);
        ob_get_clean();
        
        ob_start();
        $result3 = $command->handle(['--all']);
        ob_get_clean();
        
        ob_start();
        $result4 = $command->handle(['-a', '--queue=high']);
        ob_get_clean();
        
        if ($originalValue !== null) {
            $_ENV['QUEUE_CONNECTION'] = $originalValue;
            putenv("QUEUE_CONNECTION={$originalValue}");
        } else {
            unset($_ENV['QUEUE_CONNECTION']);
            putenv('QUEUE_CONNECTION');
        }
        
        // All should return 1 (error) because not redis, but shouldn't crash
        $this->assertEquals(1, $result1);
        $this->assertEquals(1, $result2);
        $this->assertEquals(1, $result3);
        $this->assertEquals(1, $result4);
    }
}
