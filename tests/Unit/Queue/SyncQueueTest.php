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
namespace Tests\Unit\Queue;

use App\Core\Queue\SyncQueue;
use App\Core\Contracts\Queue\JobInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SyncQueueTest extends TestCase
{
    private SyncQueue $queue;

    protected function setUp(): void
    {
        $this->queue = new SyncQueue();
    }

    #[Test]
    public function push_executes_job_immediately(): void
    {
        $executed = false;
        $job = new class($executed) {
            private bool $executed;
            public function __construct(bool &$executed)
            {
                $this->executed = &$executed;
            }
            public function handle(): void
            {
                $this->executed = true;
            }
            public function fail(\Throwable $e): void {}
        };

        $this->queue->push($job);
        
        $this->assertTrue($executed);
    }

    #[Test]
    public function push_returns_unique_id(): void
    {
        $job = new class {
            public function handle(): void {}
            public function fail(\Throwable $e): void {}
        };

        $id = $this->queue->push($job);
        
        $this->assertStringStartsWith('sync_', $id);
    }

    #[Test]
    public function push_throws_on_job_exception(): void
    {
        $job = new class {
            public function handle(): void
            {
                throw new \RuntimeException('Job failed');
            }
            public function fail(\Throwable $e): void {}
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Job failed');
        
        $this->queue->push($job);
    }

    #[Test]
    public function later_executes_immediately(): void
    {
        $executed = false;
        $job = new class($executed) {
            private bool $executed;
            public function __construct(bool &$executed)
            {
                $this->executed = &$executed;
            }
            public function handle(): void
            {
                $this->executed = true;
            }
            public function fail(\Throwable $e): void {}
        };

        $this->queue->later(60, $job);
        
        $this->assertTrue($executed);
    }

    #[Test]
    public function pop_always_returns_null(): void
    {
        $this->assertNull($this->queue->pop());
        $this->assertNull($this->queue->pop('some_queue'));
    }

    #[Test]
    public function size_always_returns_zero(): void
    {
        $this->assertSame(0, $this->queue->size());
        $this->assertSame(0, $this->queue->size('some_queue'));
    }

    #[Test]
    public function clear_returns_true(): void
    {
        $this->assertTrue($this->queue->clear());
        $this->assertTrue($this->queue->clear('some_queue'));
    }

    #[Test]
    public function push_resolves_class_string(): void
    {
        // Create a concrete test job class
        $jobClass = new class {
            public static bool $executed = false;
            public function handle(): void
            {
                self::$executed = true;
            }
            public function fail(\Throwable $e): void {}
        };

        // We can't test class string resolution without a real class file
        // So we test object resolution
        $this->queue->push($jobClass);
        $this->assertTrue($jobClass::$executed);
    }

    #[Test]
    public function push_resolves_string_class_name(): void
    {
        StringTestJob::$executed = false;
        
        $this->queue->push(StringTestJob::class);
        
        $this->assertTrue(StringTestJob::$executed);
    }

    #[Test]
    public function push_passes_data_to_string_job(): void
    {
        StringTestJobWithData::$receivedData = null;
        
        $this->queue->push(StringTestJobWithData::class, ['test_value']);
        
        $this->assertSame('test_value', StringTestJobWithData::$receivedData);
    }
}

class StringTestJob
{
    public static bool $executed = false;

    public function handle(): void
    {
        self::$executed = true;
    }

    public function fail(\Throwable $e): void {}
}

class StringTestJobWithData
{
    public static ?string $receivedData = null;

    public function __construct(string $data)
    {
        self::$receivedData = $data;
    }

    public function handle(): void {}

    public function fail(\Throwable $e): void {}
}
