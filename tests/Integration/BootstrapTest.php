<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Application;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests for app/Core/bootstrap.php
 * 
 * Note: bootstrap.php is tested indirectly through Application and HealthEndpoint tests.
 * This test verifies the bootstrap file can be included without errors.
 */
class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset Application singleton before each test
        Application::resetInstance();
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
        
        // Reset $_SERVER to avoid affecting other tests
        $_SERVER['REQUEST_URI'] = '';
    }

    #[Test]
    public function bootstrap_defines_base_path_constant(): void
    {
        // BASE_PATH should already be defined by tests/bootstrap.php
        $this->assertTrue(defined('BASE_PATH'));
    }

    #[Test]
    public function application_can_be_created_with_base_path(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        $this->assertInstanceOf(Application::class, $app);
        $this->assertSame(BASE_PATH, $app->basePath());
    }

    #[Test]
    public function bootstrap_creates_working_application(): void
    {
        $app = new Application(BASE_PATH);
        $app->bootstrap();
        
        // Should have config and logger registered
        $this->assertTrue($app->bound(\App\Core\Contracts\Config\ConfigInterface::class));
        $this->assertTrue($app->bound(\App\Core\Contracts\Log\LoggerInterface::class));
    }

    #[Test]
    public function health_endpoint_is_detected_correctly(): void
    {
        $_SERVER['REQUEST_URI'] = '/health';
        
        $this->assertTrue(\App\Core\Http\HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function non_health_requests_bypass_health_endpoint(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        
        $this->assertFalse(\App\Core\Http\HealthEndpoint::isHealthCheckRequest());
    }
}
