<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Core\Application;
use App\Core\Support\HealthCheck;
use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    private Application $app;
    private HealthCheck $healthCheck;

    protected function setUp(): void
    {
        // Create test .env file
        $envPath = BASE_PATH . '/.env';
        if (!file_exists($envPath)) {
            file_put_contents($envPath, "APP_NAME=Infinri\nAPP_ENV=testing\nAPP_DEBUG=true\n");
        }
        
        $this->app = new Application(BASE_PATH);
        $this->app->bootstrap();
        $this->healthCheck = new HealthCheck($this->app);
    }

    /** @test */
    public function it_returns_array_with_required_keys(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('app', $result);
        $this->assertArrayHasKey('system', $result);
    }

    /** @test */
    public function it_returns_healthy_status(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertContains($result['status'], ['healthy', 'degraded', 'critical']);
    }

    /** @test */
    public function it_includes_timestamp(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result['timestamp']);
    }

    /** @test */
    public function it_includes_app_information(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertArrayHasKey('name', $result['app']);
        $this->assertArrayHasKey('version', $result['app']);
        $this->assertArrayHasKey('environment', $result['app']);
        $this->assertArrayHasKey('debug', $result['app']);
    }

    /** @test */
    public function it_includes_system_information(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertArrayHasKey('php_version', $result['system']);
        $this->assertArrayHasKey('memory_usage_mb', $result['system']);
        $this->assertArrayHasKey('memory_limit_mb', $result['system']);
        $this->assertArrayHasKey('memory_usage_percent', $result['system']);
        $this->assertArrayHasKey('peak_memory_mb', $result['system']);
    }

    /** @test */
    public function it_reports_correct_app_version(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertEquals('0.1.0', $result['app']['version']);
    }

    /** @test */
    public function it_reports_correct_php_version(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertEquals(PHP_VERSION, $result['system']['php_version']);
    }

    /** @test */
    public function it_returns_valid_json(): void
    {
        $json = $this->healthCheck->toJson();
        
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    /** @test */
    public function it_reports_memory_usage_as_positive_number(): void
    {
        $result = $this->healthCheck->check();
        
        $this->assertGreaterThan(0, $result['system']['memory_usage_mb']);
        $this->assertGreaterThan(0, $result['system']['peak_memory_mb']);
    }
}
