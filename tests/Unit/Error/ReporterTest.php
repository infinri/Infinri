<?php

declare(strict_types=1);

namespace Tests\Unit\Error;

use App\Core\Error\Reporter;
use App\Core\Contracts\Log\LoggerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReporterTest extends TestCase
{
    #[Test]
    public function constructor_accepts_null_logger(): void
    {
        $reporter = new Reporter();
        
        $this->assertNull($reporter->getLogger());
    }

    #[Test]
    public function constructor_accepts_logger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $reporter = new Reporter($logger);
        
        $this->assertSame($logger, $reporter->getLogger());
    }

    #[Test]
    public function set_logger_changes_logger(): void
    {
        $reporter = new Reporter();
        $logger = $this->createMock(LoggerInterface::class);
        
        $reporter->setLogger($logger);
        
        $this->assertSame($logger, $reporter->getLogger());
    }

    #[Test]
    public function add_context_stores_global_context(): void
    {
        $reporter = new Reporter();
        
        $result = $reporter->addContext('app_version', '1.0.0');
        
        $this->assertSame($reporter, $result);
    }

    #[Test]
    public function add_reporter_registers_custom_reporter(): void
    {
        $reporter = new Reporter();
        
        $result = $reporter->addReporter(fn($e, $ctx) => true);
        
        $this->assertSame($reporter, $result);
    }

    #[Test]
    public function clear_reporters_removes_all(): void
    {
        $reporter = new Reporter();
        $reporter->addReporter(fn($e, $ctx) => true);
        
        $result = $reporter->clearReporters();
        
        $this->assertSame($reporter, $result);
    }

    #[Test]
    public function report_logs_to_logger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');
        
        $reporter = new Reporter($logger);
        $reporter->report(new \Exception('Test error'));
    }

    #[Test]
    public function report_calls_custom_reporters(): void
    {
        $called = false;
        $reporter = new Reporter();
        $reporter->addReporter(function($e, $ctx) use (&$called) {
            $called = true;
            return true;
        });
        
        $reporter->report(new \Exception('Test'));
        
        $this->assertTrue($called);
    }

    #[Test]
    public function report_stops_when_reporter_returns_false(): void
    {
        $secondCalled = false;
        $reporter = new Reporter();
        
        $reporter->addReporter(fn($e, $ctx) => false);
        $reporter->addReporter(function($e, $ctx) use (&$secondCalled) {
            $secondCalled = true;
        });
        
        $reporter->report(new \Exception('Test'));
        
        $this->assertFalse($secondCalled);
    }

    #[Test]
    public function report_includes_global_context(): void
    {
        $receivedContext = [];
        $reporter = new Reporter();
        $reporter->addContext('env', 'testing');
        $reporter->addReporter(function($e, $ctx) use (&$receivedContext) {
            $receivedContext = $ctx;
            return false;
        });
        
        $reporter->report(new \Exception('Test'));
        
        $this->assertSame('testing', $receivedContext['env']);
    }

    #[Test]
    public function report_handles_reporter_exceptions(): void
    {
        $reporter = new Reporter();
        $reporter->addReporter(function($e, $ctx) {
            throw new \RuntimeException('Reporter failed');
        });
        
        // Should not throw
        $reporter->report(new \Exception('Test'));
        
        $this->assertTrue(true);
    }

    #[Test]
    public function report_falls_back_when_logger_throws(): void
    {
        // Create a mock logger that throws
        $logger = $this->createMock(\App\Core\Contracts\Log\LoggerInterface::class);
        $logger->method('error')->willThrowException(new \RuntimeException('Logger failed'));
        
        $reporter = new Reporter($logger);
        
        // Should not throw, should fall back to error_log
        $reporter->report(new \Exception('Test error'));
        
        $this->assertTrue(true);
    }
}
