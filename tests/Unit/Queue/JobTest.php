<?php

declare(strict_types=1);

namespace Tests\Unit\Queue;

use App\Core\Queue\Job;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    #[Test]
    public function job_has_default_tries(): void
    {
        $job = new TestJob();
        
        $this->assertSame(1, $job->tries);
    }

    #[Test]
    public function job_has_default_retry_after(): void
    {
        $job = new TestJob();
        
        $this->assertSame(0, $job->retryAfter);
    }

    #[Test]
    public function job_has_null_queue_by_default(): void
    {
        $job = new TestJob();
        
        $this->assertNull($job->queue);
    }

    #[Test]
    public function job_has_null_connection_by_default(): void
    {
        $job = new TestJob();
        
        $this->assertNull($job->connection);
    }

    #[Test]
    public function on_queue_sets_queue(): void
    {
        $job = new TestJob();
        
        $result = $job->onQueue('emails');
        
        $this->assertSame('emails', $job->queue);
        $this->assertSame($job, $result);
    }

    #[Test]
    public function on_connection_sets_connection(): void
    {
        $job = new TestJob();
        
        $result = $job->onConnection('redis');
        
        $this->assertSame('redis', $job->connection);
        $this->assertSame($job, $result);
    }

    #[Test]
    public function handle_is_callable(): void
    {
        $job = new TestJob();
        
        $job->handle();
        
        $this->assertTrue($job->handled);
    }

    #[Test]
    public function failed_is_callable(): void
    {
        $job = new TestJob();
        
        $job->failed(new \Exception('Test'));
        
        $this->assertTrue(true); // Just ensure it doesn't throw
    }

    #[Test]
    public function dispatch_returns_pending_dispatch(): void
    {
        $pending = DispatchableJob::dispatch('arg1', 'arg2');
        
        $this->assertInstanceOf(\App\Core\Queue\PendingDispatch::class, $pending);
    }
}

class DispatchableJob extends Job
{
    public function __construct(public string $arg1 = '', public string $arg2 = '')
    {
    }

    public function handle(): void
    {
    }
}

class TestJob extends Job
{
    public bool $handled = false;

    public function handle(): void
    {
        $this->handled = true;
    }
}
