<?php

declare(strict_types=1);

namespace Tests\Unit\Error;

use App\Core\Error\Handler;
use App\Core\Error\Reporter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    #[Test]
    public function constructor_creates_reporter_if_not_provided(): void
    {
        $handler = new Handler();
        
        $this->assertInstanceOf(Reporter::class, $handler->getReporter());
    }

    #[Test]
    public function constructor_accepts_reporter(): void
    {
        $reporter = new Reporter();
        $handler = new Handler($reporter);
        
        $this->assertSame($reporter, $handler->getReporter());
    }

    #[Test]
    public function constructor_sets_debug_mode(): void
    {
        $handler = new Handler(null, true);
        
        $this->assertTrue($handler->isDebug());
    }

    #[Test]
    public function set_debug_changes_mode(): void
    {
        $handler = new Handler();
        
        $handler->setDebug(true);
        
        $this->assertTrue($handler->isDebug());
    }

    #[Test]
    public function should_report_returns_true_by_default(): void
    {
        $handler = new Handler();
        
        $this->assertTrue($handler->shouldReport(new \Exception('Test')));
    }

    #[Test]
    public function dont_report_excludes_exception_types(): void
    {
        $handler = new Handler();
        $handler->dontReport([\InvalidArgumentException::class]);
        
        $this->assertFalse($handler->shouldReport(new \InvalidArgumentException('Test')));
        $this->assertTrue($handler->shouldReport(new \RuntimeException('Test')));
    }

    #[Test]
    public function register_adds_custom_handler(): void
    {
        $handler = new Handler();
        $handler->getReporter()->suppressFallback(); // Suppress error_log in tests
        $called = false;
        
        $handler->register(\InvalidArgumentException::class, function($e) use (&$called) {
            $called = true;
        });
        
        $handler->handle(new \InvalidArgumentException('Test'));
        
        $this->assertTrue($called);
    }

    #[Test]
    public function to_array_converts_exception(): void
    {
        $handler = new Handler();
        $exception = new \Exception('Test message', 123);
        
        $result = $handler->toArray($exception);
        
        $this->assertSame(\Exception::class, $result['type']);
        $this->assertSame('Test message', $result['message']);
        $this->assertSame(123, $result['code']);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('line', $result);
    }

    #[Test]
    public function to_array_includes_trace_when_requested(): void
    {
        $handler = new Handler();
        $exception = new \Exception('Test');
        
        $result = $handler->toArray($exception, true);
        
        $this->assertArrayHasKey('trace', $result);
    }

    #[Test]
    public function to_array_includes_previous_exception(): void
    {
        $handler = new Handler();
        $previous = new \Exception('Previous');
        $exception = new \Exception('Current', 0, $previous);
        
        $result = $handler->toArray($exception);
        
        $this->assertArrayHasKey('previous', $result);
        $this->assertSame('Previous', $result['previous']['message']);
    }

    #[Test]
    public function handle_reports_exception(): void
    {
        $reporter = $this->createMock(Reporter::class);
        $reporter->expects($this->once())->method('report');
        
        $handler = new Handler($reporter);
        $handler->handle(new \Exception('Test'));
    }

    #[Test]
    public function report_skips_dont_report_exceptions(): void
    {
        $reporter = $this->createMock(Reporter::class);
        $reporter->expects($this->never())->method('report');
        
        $handler = new Handler($reporter);
        $handler->dontReport([\InvalidArgumentException::class]);
        
        // This should not call reporter->report()
        $handler->report(new \InvalidArgumentException('Test'));
    }
}
