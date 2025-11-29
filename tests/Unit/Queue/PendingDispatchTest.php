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

use App\Core\Queue\Job;
use App\Core\Queue\PendingDispatch;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PendingDispatchTest extends TestCase
{
    #[Test]
    public function constructor_accepts_job(): void
    {
        $job = new SimpleJob();
        $pending = new PendingDispatch($job);
        
        $this->assertInstanceOf(PendingDispatch::class, $pending);
    }

    #[Test]
    public function get_job_returns_job(): void
    {
        $job = new SimpleJob();
        $pending = new PendingDispatch($job);
        
        $this->assertSame($job, $pending->getJob());
    }

    #[Test]
    public function on_queue_sets_job_queue(): void
    {
        $job = new SimpleJob();
        $pending = new PendingDispatch($job);
        
        $result = $pending->onQueue('emails');
        
        $this->assertSame('emails', $job->queue);
        $this->assertSame($pending, $result);
    }

    #[Test]
    public function on_connection_sets_job_connection(): void
    {
        $job = new SimpleJob();
        $pending = new PendingDispatch($job);
        
        $result = $pending->onConnection('redis');
        
        $this->assertSame('redis', $job->connection);
        $this->assertSame($pending, $result);
    }

    #[Test]
    public function dispatch_executes_job(): void
    {
        $job = new SimpleJob();
        $pending = new PendingDispatch($job);
        
        $pending->dispatch();
        
        $this->assertTrue($job->wasHandled);
    }

    #[Test]
    public function fluent_chaining_works(): void
    {
        $job = new SimpleJob();
        $pending = new PendingDispatch($job);
        
        $pending
            ->onQueue('notifications')
            ->onConnection('sync');
        
        $this->assertSame('notifications', $job->queue);
        $this->assertSame('sync', $job->connection);
    }
}

class SimpleJob extends Job
{
    public bool $wasHandled = false;

    public function handle(): void
    {
        $this->wasHandled = true;
    }
}
