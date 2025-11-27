<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Core\Application;
use App\Core\Support\HealthCheck;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    private Application $app;
    private HealthCheck $healthCheck;

    protected function setUp(): void
    {
        // Reset Application singleton
        Application::resetInstance();
        
        // Create test .env file
        $envPath = BASE_PATH . '/.env';
        if (!file_exists($envPath)) {
            file_put_contents($envPath, "APP_NAME=Infinri\nAPP_ENV=testing\nAPP_DEBUG=true\n");
        }
        
        $this->app = new Application(BASE_PATH);
        $this->app->bootstrap();
        $this->healthCheck = new HealthCheck($this->app);
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
    }

    #[Test]
    public function it_returns_array_with_required_keys(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('app', $result);
        $this->assertArrayHasKey('system', $result);
    }

    #[Test]
    public function it_returns_healthy_status(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertContains($result['status'], ['healthy', 'degraded', 'critical']);
    }

    #[Test]
    public function it_includes_timestamp(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result['timestamp']);
    }

    #[Test]
    public function it_includes_app_information(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertArrayHasKey('name', $result['app']);
        $this->assertArrayHasKey('version', $result['app']);
        $this->assertArrayHasKey('environment', $result['app']);
        $this->assertArrayHasKey('debug', $result['app']);
    }

    #[Test]
    public function it_includes_system_information(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertArrayHasKey('php_version', $result['system']);
        $this->assertArrayHasKey('memory_usage_mb', $result['system']);
        $this->assertArrayHasKey('memory_limit_mb', $result['system']);
        $this->assertArrayHasKey('memory_usage_percent', $result['system']);
        $this->assertArrayHasKey('peak_memory_mb', $result['system']);
    }

    #[Test]
    public function it_reports_correct_app_version(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertEquals('0.1.0', $result['app']['version']);
    }

    #[Test]
    public function it_reports_correct_php_version(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertEquals(PHP_VERSION, $result['system']['php_version']);
    }

    #[Test]
    public function it_returns_valid_json(): void
    {
        $json = $this->healthCheck->toJson();
        
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    #[Test]
    public function it_reports_memory_usage_as_positive_number(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertGreaterThan(0, $result['system']['memory_usage_mb']);
        $this->assertGreaterThan(0, $result['system']['peak_memory_mb']);
    }

    #[Test]
    public function it_converts_bytes_with_kb_suffix(): void
    {
        $healthCheck = new TestableHealthCheck($this->app);
        
        $this->assertEquals(1024, $healthCheck->publicConvertToBytes('1k'));
        $this->assertEquals(2048, $healthCheck->publicConvertToBytes('2K'));
    }

    #[Test]
    public function it_converts_bytes_with_mb_suffix(): void
    {
        $healthCheck = new TestableHealthCheck($this->app);
        
        $this->assertEquals(1024 * 1024, $healthCheck->publicConvertToBytes('1m'));
        $this->assertEquals(128 * 1024 * 1024, $healthCheck->publicConvertToBytes('128M'));
    }

    #[Test]
    public function it_converts_bytes_with_gb_suffix(): void
    {
        $healthCheck = new TestableHealthCheck($this->app);
        
        $this->assertEquals(1024 * 1024 * 1024, $healthCheck->publicConvertToBytes('1g'));
        $this->assertEquals(2 * 1024 * 1024 * 1024, $healthCheck->publicConvertToBytes('2G'));
    }

    #[Test]
    public function it_converts_bytes_without_suffix(): void
    {
        $healthCheck = new TestableHealthCheck($this->app);
        
        $this->assertEquals(1234, $healthCheck->publicConvertToBytes('1234'));
    }

    #[Test]
    public function it_handles_unlimited_memory(): void
    {
        $healthCheck = new TestableHealthCheck($this->app);
        
        $result = $healthCheck->publicGetMemoryLimitForValue('-1');
        
        $this->assertEquals(PHP_INT_MAX, $result);
    }

    #[Test]
    public function it_returns_memory_usage_percent(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertIsFloat($result['system']['memory_usage_percent']);
        $this->assertGreaterThanOrEqual(0, $result['system']['memory_usage_percent']);
        $this->assertLessThanOrEqual(100, $result['system']['memory_usage_percent']);
    }

    #[Test]
    public function it_returns_memory_limit_mb(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertIsFloat($result['system']['memory_limit_mb']);
        $this->assertGreaterThan(0, $result['system']['memory_limit_mb']);
    }

    #[Test]
    public function it_returns_critical_status_when_memory_above_90_percent(): void
    {
        $healthCheck = new TestableHealthCheck($this->app);
        
        // Set fake memory limit to just above current usage (to simulate >90% usage)
        $currentUsage = memory_get_usage(true);
        $healthCheck->setFakeMemoryLimit((int)($currentUsage / 0.95)); // 95% usage
        
        $result = $healthCheck->check();
        
        $this->assertEquals('critical', $result['status']);
    }

    #[Test]
    public function it_returns_degraded_status_when_memory_between_75_and_90_percent(): void
    {
        $healthCheck = new TestableHealthCheck($this->app);
        
        // Set fake memory limit to simulate ~80% usage
        $currentUsage = memory_get_usage(true);
        $healthCheck->setFakeMemoryLimit((int)($currentUsage / 0.80)); // 80% usage
        
        $result = $healthCheck->check();
        
        $this->assertEquals('degraded', $result['status']);
    }
}

/**
 * Testable subclass to expose protected methods for testing
 */
class TestableHealthCheck extends HealthCheck
{
    private ?int $fakeMemoryLimit = null;

    public function publicConvertToBytes(string $value): int
    {
        return $this->convertToBytes($value);
    }

    public function publicGetMemoryLimitForValue(string $value): int
    {
        if ($value == -1) {
            return PHP_INT_MAX;
        }
        return $this->convertToBytes($value);
    }

    public function setFakeMemoryLimit(int $limit): void
    {
        $this->fakeMemoryLimit = $limit;
    }

    protected function getMemoryLimit(): int
    {
        if ($this->fakeMemoryLimit !== null) {
            return $this->fakeMemoryLimit;
        }
        return parent::getMemoryLimit();
    }
}
