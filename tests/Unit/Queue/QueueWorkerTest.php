<?php declare(strict_types=1);

use App\Core\Queue\QueueWorker;
use App\Core\Queue\RedisQueue;
use App\Core\Queue\RedisJob;
use App\Core\Contracts\Queue\QueueInterface;
use App\Core\Contracts\Queue\JobInterface;
use App\Core\Contracts\Log\LoggerInterface;
use App\Core\Redis\RedisManager;

describe('QueueWorker', function () {
    beforeEach(function () {
        $this->mockQueue = Mockery::mock(QueueInterface::class);
        $this->mockLogger = Mockery::mock(LoggerInterface::class);
        // Set up permissive defaults - specific tests will override
        $this->mockLogger->shouldReceive('info')->byDefault();
        $this->mockLogger->shouldReceive('error')->byDefault();
        $this->mockLogger->shouldReceive('warning')->byDefault();
        $this->mockLogger->shouldReceive('debug')->byDefault();
    });
    
    afterEach(function () {
        Mockery::close();
    });
    
    // Constructor tests
    it('can be instantiated with queue only', function () {
        $worker = new QueueWorker($this->mockQueue);
        
        expect($worker)->toBeInstanceOf(QueueWorker::class);
    });
    
    it('can be instantiated with logger', function () {
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger);
        
        expect($worker)->toBeInstanceOf(QueueWorker::class);
    });
    
    it('accepts custom options', function () {
        $worker = new QueueWorker($this->mockQueue, null, [
            'sleep' => 5,
            'max_jobs' => 100,
            'max_time' => 3600,
            'memory_limit' => 256,
            'timeout' => 120,
            'tries' => 5,
            'retry_delay' => 120,
        ]);
        
        expect($worker)->toBeInstanceOf(QueueWorker::class);
    });
    
    // getJobsProcessed() tests
    it('returns zero jobs processed initially', function () {
        $worker = new QueueWorker($this->mockQueue);
        
        expect($worker->getJobsProcessed())->toBe(0);
    });
    
    // getStats() tests
    it('returns statistics array', function () {
        $worker = new QueueWorker($this->mockQueue);
        
        $stats = $worker->getStats();
        
        expect($stats)->toBeArray();
        expect($stats)->toHaveKey('jobs_processed');
        expect($stats)->toHaveKey('uptime');
        expect($stats)->toHaveKey('memory_usage');
        expect($stats)->toHaveKey('paused');
    });
    
    it('reports initial state in stats', function () {
        $worker = new QueueWorker($this->mockQueue);
        
        $stats = $worker->getStats();
        
        expect($stats['jobs_processed'])->toBe(0);
        expect($stats['paused'])->toBeFalse();
    });
    
    // stop() tests
    it('can be stopped', function () {
        $worker = new QueueWorker($this->mockQueue);
        
        $worker->stop();
        
        expect(true)->toBeTrue();
    });
    
    // pause() / resume() tests
    it('can be paused', function () {
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger);
        
        $worker->pause();
        
        $stats = $worker->getStats();
        expect($stats['paused'])->toBeTrue();
    });
    
    it('can be resumed', function () {
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger);
        
        $worker->pause();
        $worker->resume();
        
        $stats = $worker->getStats();
        expect($stats['paused'])->toBeFalse();
    });
    
    // runNextJob() tests
    it('returns false when no job available', function () {
        $this->mockQueue->shouldReceive('pop')->once()->andReturn(null);
        
        $worker = new QueueWorker($this->mockQueue);
        
        expect($worker->runNextJob())->toBeFalse();
    });
    
    it('returns true when job processed', function () {
        $mockJob = Mockery::mock(JobInterface::class);
        $mockJob->shouldReceive('getName')->andReturn('TestJob');
        $mockJob->shouldReceive('getId')->andReturn('job_123');
        $mockJob->shouldReceive('handle')->once();
        
        $this->mockQueue->shouldReceive('pop')->once()->andReturn($mockJob);
        
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger);
        
        expect($worker->runNextJob())->toBeTrue();
    });
    
    it('handles job exception and releases for retry', function () {
        $mockJob = Mockery::mock(JobInterface::class);
        $mockJob->shouldReceive('getName')->andReturn('TestJob');
        $mockJob->shouldReceive('getId')->andReturn('job_123');
        $mockJob->shouldReceive('getAttempts')->andReturn(0);
        $mockJob->shouldReceive('handle')
            ->andThrow(new Exception('Job failed'));
        $mockJob->shouldReceive('release')->once()->with(60);
        
        $this->mockQueue->shouldReceive('pop')->once()->andReturn($mockJob);
        
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger);
        
        expect($worker->runNextJob())->toBeTrue();
    });
    
    it('handles job exception and fails after max retries', function () {
        $mockJob = Mockery::mock(JobInterface::class);
        $mockJob->shouldReceive('getName')->andReturn('TestJob');
        $mockJob->shouldReceive('getId')->andReturn('job_123');
        $mockJob->shouldReceive('getAttempts')->andReturn(5);
        $mockJob->shouldReceive('handle')
            ->andThrow(new Exception('Job failed'));
        $mockJob->shouldReceive('fail')->once();
        
        $this->mockQueue->shouldReceive('pop')->once()->andReturn($mockJob);
        
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger, [
            'tries' => 3,
        ]);
        
        expect($worker->runNextJob())->toBeTrue();
    });
    
    it('handles exception when getting job', function () {
        $this->mockQueue->shouldReceive('pop')
            ->andThrow(new Exception('Connection lost'));
        
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger);
        
        expect($worker->runNextJob())->toBeFalse();
    });
    
    // daemon() with max_jobs limit
    it('stops daemon on max_jobs limit', function () {
        $mockJob = Mockery::mock(JobInterface::class);
        $mockJob->shouldReceive('getName')->andReturn('TestJob');
        $mockJob->shouldReceive('getId')->andReturn('job_123');
        $mockJob->shouldReceive('handle');
        
        $this->mockQueue->shouldReceive('pop')
            ->times(3)
            ->andReturn($mockJob);
        
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger, [
            'max_jobs' => 3,
            'sleep' => 0,
        ]);
        
        $worker->daemon('default');
        
        expect($worker->getJobsProcessed())->toBe(3);
    });
    
    // daemon() stops immediately when stopped
    it('daemon exits immediately when stopped before start', function () {
        $this->mockQueue->shouldReceive('pop')->never();
        
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger, [
            'sleep' => 0,
        ]);
        
        $worker->stop();
        $worker->daemon('default');
        
        expect($worker->getJobsProcessed())->toBe(0);
    });
    
    // Memory limit test
    it('stops daemon on memory limit', function () {
        $this->mockQueue->shouldReceive('pop')->andReturn(null);
        
        $worker = new QueueWorker($this->mockQueue, $this->mockLogger, [
            'memory_limit' => 0, // 0 MB limit - any memory usage > 0
            'sleep' => 0,
        ]);
        
        $worker->daemon('default');
        
        expect($worker->getJobsProcessed())->toBe(0);
    });
    
    // Works without logger
    it('works without logger', function () {
        $mockJob = Mockery::mock(JobInterface::class);
        $mockJob->shouldReceive('getName')->andReturn('TestJob');
        $mockJob->shouldReceive('getId')->andReturn('job_123');
        $mockJob->shouldReceive('handle');
        
        $this->mockQueue->shouldReceive('pop')->andReturn($mockJob);
        
        $worker = new QueueWorker($this->mockQueue, null, [
            'max_jobs' => 1,
        ]);
        
        $worker->daemon('default');
        
        expect($worker->getJobsProcessed())->toBe(1);
    });
    
    // Test daemon with RedisQueue migrates timed out jobs
    it('calls migrateTimedOutJobs for RedisQueue', function () {
        $mockJob = Mockery::mock(JobInterface::class);
        $mockJob->shouldReceive('getName')->andReturn('TestJob');
        $mockJob->shouldReceive('getId')->andReturn('job_123');
        $mockJob->shouldReceive('handle');
        
        $mockRedisQueue = Mockery::mock(RedisQueue::class);
        $mockRedisQueue->shouldReceive('pop')->andReturn($mockJob);
        $mockRedisQueue->shouldReceive('migrateTimedOutJobs')->once();
        
        $worker = new QueueWorker($mockRedisQueue, $this->mockLogger, [
            'max_jobs' => 1,
        ]);
        
        $worker->daemon('default');
        
        expect($worker->getJobsProcessed())->toBe(1);
    });
});
