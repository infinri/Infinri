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
namespace Tests\Unit\Log;

use App\Core\Log\LogManager;
use App\Core\Log\LogChannel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LogManagerTest extends TestCase
{
    private string $logDir;
    private LogManager $logger;

    protected function setUp(): void
    {
        $this->logDir = sys_get_temp_dir() . '/log_manager_test_' . uniqid();
        mkdir($this->logDir, 0755, true);
        $this->logger = new LogManager($this->logDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->logDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    public function it_creates_log_channels(): void
    {
        $channel = $this->logger->channel('exception');
        
        $this->assertInstanceOf(LogChannel::class, $channel);
        $this->assertEquals('exception', $channel->getName());
    }

    #[Test]
    public function it_logs_to_info_channel(): void
    {
        $this->logger->info('Test info message');
        
        $logFile = $this->logDir . '/info.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Test info message', $content);
        $this->assertStringContainsString('INFO', $content);
    }

    #[Test]
    public function it_logs_to_error_channel(): void
    {
        $this->logger->error('Test error message');
        
        $logFile = $this->logDir . '/error.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Test error message', $content);
        $this->assertStringContainsString('ERROR', $content);
    }

    #[Test]
    public function it_logs_to_debug_channel(): void
    {
        $this->logger->debug('Test debug message');
        
        $logFile = $this->logDir . '/debug.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Test debug message', $content);
    }

    #[Test]
    public function it_logs_exceptions_with_full_details(): void
    {
        $exception = new \RuntimeException('Test exception message', 42);
        $this->logger->exception($exception, ['user_id' => 123]);
        
        $logFile = $this->logDir . '/exception.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        
        $this->assertStringContainsString('Test exception message', $content);
        $this->assertStringContainsString('RuntimeException', $content);
        $this->assertStringContainsString('trace', $content);
        $this->assertStringContainsString('user_id', $content);
    }

    #[Test]
    public function it_logs_security_events(): void
    {
        $this->logger->security('Failed login attempt', ['ip' => '192.168.1.1']);
        
        $logFile = $this->logDir . '/security.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Failed login attempt', $content);
        $this->assertStringContainsString('192.168.1.1', $content);
    }

    #[Test]
    public function it_logs_system_events(): void
    {
        $this->logger->system('Application started');
        
        $logFile = $this->logDir . '/system.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Application started', $content);
    }

    #[Test]
    public function it_logs_database_queries(): void
    {
        $this->logger->query('SELECT * FROM users WHERE id = ?', [1], 0.0234);
        
        $logFile = $this->logDir . '/query.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('SELECT * FROM users', $content);
        $this->assertStringContainsString('time_ms', $content);
    }

    #[Test]
    public function it_includes_correlation_id(): void
    {
        $this->logger->setCorrelationId('test-correlation-123');
        $this->logger->info('Test message');
        
        $content = file_get_contents($this->logDir . '/info.log');
        $this->assertStringContainsString('test-correlation-123', $content);
    }

    #[Test]
    public function it_generates_correlation_id_if_not_set(): void
    {
        $this->logger->info('Test message');
        
        $content = file_get_contents($this->logDir . '/info.log');
        $this->assertStringContainsString('req_', $content);
    }

    #[Test]
    public function it_includes_global_context(): void
    {
        $this->logger->setGlobalContext(['app_version' => '1.0.0']);
        $this->logger->info('Test message');
        
        $content = file_get_contents($this->logDir . '/info.log');
        $this->assertStringContainsString('1.0.0', $content);
    }

    #[Test]
    public function it_adds_to_global_context(): void
    {
        $this->logger->addGlobalContext('environment', 'testing');
        $this->logger->info('Test message');
        
        $content = file_get_contents($this->logDir . '/info.log');
        $this->assertStringContainsString('testing', $content);
    }

    #[Test]
    public function it_routes_critical_to_exception_channel(): void
    {
        $this->logger->critical('Critical error occurred');
        
        $logFile = $this->logDir . '/exception.log';
        $this->assertFileExists($logFile);
    }

    #[Test]
    public function it_routes_warning_to_error_channel(): void
    {
        $this->logger->warning('Warning message');
        
        $logFile = $this->logDir . '/error.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Warning message', $content);
    }

    #[Test]
    public function it_creates_custom_channels_on_demand(): void
    {
        $channel = $this->logger->channel('custom');
        
        $this->assertInstanceOf(LogChannel::class, $channel);
        $this->assertEquals('custom', $channel->getName());
    }

    #[Test]
    public function it_logs_previous_exception(): void
    {
        $previous = new \InvalidArgumentException('Previous error');
        $exception = new \RuntimeException('Main error', 0, $previous);
        
        $this->logger->exception($exception);
        
        $content = file_get_contents($this->logDir . '/exception.log');
        $this->assertStringContainsString('previous_exception', $content);
        $this->assertStringContainsString('Previous error', $content);
    }

    #[Test]
    public function it_logs_emergency_level(): void
    {
        $this->logger->emergency('Emergency situation');
        
        $logFile = $this->logDir . '/exception.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Emergency situation', $content);
    }

    #[Test]
    public function it_logs_alert_level(): void
    {
        $this->logger->alert('Alert message');
        
        $logFile = $this->logDir . '/exception.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Alert message', $content);
    }

    #[Test]
    public function it_logs_debug_level(): void
    {
        $this->logger->debug('Debug message');
        
        $logFile = $this->logDir . '/debug.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Debug message', $content);
    }

    #[Test]
    public function it_logs_notice_level(): void
    {
        $this->logger->notice('Notice message');
        
        $logFile = $this->logDir . '/info.log';
        $this->assertFileExists($logFile);
        $content = file_get_contents($logFile);
        $this->assertStringContainsString('Notice message', $content);
    }

    #[Test]
    public function it_rotates_all_channels(): void
    {
        // Create some log entries
        $this->logger->info('Info message');
        $this->logger->error('Error message');
        
        // Rotate all channels
        $this->logger->rotateAll();
        
        // Should not throw
        $this->assertTrue(true);
    }

    #[Test]
    public function it_rotates_specific_channel(): void
    {
        // Create a log entry
        $this->logger->info('Test message');
        
        // Rotate specific channel
        $this->logger->rotate('info');
        
        // Should not throw
        $this->assertTrue(true);
    }
}
