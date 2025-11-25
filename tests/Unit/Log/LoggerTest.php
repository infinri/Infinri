<?php

declare(strict_types=1);

namespace Tests\Unit\Log;

use App\Core\Log\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    private string $logPath;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logPath = BASE_PATH . '/var/log/test.log';
        $this->logger = new Logger($this->logPath);
        
        // Clean up any existing log file
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /** @test */
    public function it_writes_log_entry_to_file(): void
    {
        $this->logger->info('Test message');
        
        $this->assertFileExists($this->logPath);
    }

    /** @test */
    public function it_writes_log_as_json(): void
    {
        $this->logger->info('Test message');
        
        $content = file_get_contents($this->logPath);
        $entry = json_decode(trim($content), true);
        
        $this->assertIsArray($entry);
        $this->assertArrayHasKey('message', $entry);
        $this->assertEquals('Test message', $entry['message']);
    }

    /** @test */
    public function it_includes_log_level(): void
    {
        $this->logger->warning('Test warning');
        
        $entry = $this->getLastLogEntry();
        
        $this->assertEquals('warning', $entry['level']);
    }

    /** @test */
    public function it_includes_correlation_id(): void
    {
        $this->logger->info('Test message');
        
        $entry = $this->getLastLogEntry();
        
        $this->assertArrayHasKey('correlation_id', $entry);
        $this->assertStringStartsWith('req_', $entry['correlation_id']);
    }

    /** @test */
    public function it_uses_same_correlation_id_for_multiple_logs(): void
    {
        $this->logger->info('First message');
        $this->logger->error('Second message');
        
        $content = file_get_contents($this->logPath);
        $lines = explode("\n", trim($content));
        
        $first = json_decode($lines[0], true);
        $second = json_decode($lines[1], true);
        
        $this->assertEquals($first['correlation_id'], $second['correlation_id']);
    }

    /** @test */
    public function it_can_set_custom_correlation_id(): void
    {
        $this->logger->setCorrelationId('custom_id_123');
        $this->logger->info('Test message');
        
        $entry = $this->getLastLogEntry();
        
        $this->assertEquals('custom_id_123', $entry['correlation_id']);
    }

    /** @test */
    public function it_includes_timestamp(): void
    {
        $this->logger->info('Test message');
        
        $entry = $this->getLastLogEntry();
        
        $this->assertArrayHasKey('timestamp', $entry);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $entry['timestamp']);
    }

    /** @test */
    public function it_includes_memory_usage(): void
    {
        $this->logger->info('Test message');
        
        $entry = $this->getLastLogEntry();
        
        $this->assertArrayHasKey('memory', $entry);
        $this->assertIsInt($entry['memory']);
        $this->assertGreaterThan(0, $entry['memory']);
    }

    /** @test */
    public function it_includes_context_data(): void
    {
        $this->logger->info('Test message', ['user_id' => 123, 'action' => 'login']);
        
        $entry = $this->getLastLogEntry();
        
        $this->assertArrayHasKey('context', $entry);
        $this->assertEquals(123, $entry['context']['user_id']);
        $this->assertEquals('login', $entry['context']['action']);
    }

    /** @test */
    public function it_merges_global_context_with_log_context(): void
    {
        $this->logger->setGlobalContext(['app' => 'infinri']);
        $this->logger->info('Test message', ['user_id' => 123]);
        
        $entry = $this->getLastLogEntry();
        
        $this->assertEquals('infinri', $entry['context']['app']);
        $this->assertEquals(123, $entry['context']['user_id']);
    }

    /** @test */
    public function it_can_add_to_global_context(): void
    {
        $this->logger->addGlobalContext('version', '1.0.0');
        $this->logger->info('Test message');
        
        $entry = $this->getLastLogEntry();
        
        $this->assertEquals('1.0.0', $entry['context']['version']);
    }

    /** @test */
    public function it_supports_all_log_levels(): void
    {
        $this->logger->emergency('emergency message');
        $this->logger->alert('alert message');
        $this->logger->critical('critical message');
        $this->logger->error('error message');
        $this->logger->warning('warning message');
        $this->logger->notice('notice message');
        $this->logger->info('info message');
        $this->logger->debug('debug message');
        
        $content = file_get_contents($this->logPath);
        $lines = explode("\n", trim($content));
        
        $this->assertCount(8, $lines);
        
        $levels = array_map(function($line) {
            return json_decode($line, true)['level'];
        }, $lines);
        
        $this->assertEquals([
            'emergency', 'alert', 'critical', 'error',
            'warning', 'notice', 'info', 'debug'
        ], $levels);
    }

    /** @test */
    public function it_creates_log_directory_if_not_exists(): void
    {
        $newLogPath = BASE_PATH . '/var/log/nested/test.log';
        $newLogger = new Logger($newLogPath);
        
        $newLogger->info('Test message');
        
        $this->assertFileExists($newLogPath);
        
        // Cleanup
        unlink($newLogPath);
        rmdir(dirname($newLogPath));
    }

    private function getLastLogEntry(): array
    {
        $content = file_get_contents($this->logPath);
        $lines = explode("\n", trim($content));
        $lastLine = end($lines);
        
        return json_decode($lastLine, true);
    }
}
