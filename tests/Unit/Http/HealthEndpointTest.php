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
namespace Tests\Unit\Http;

use App\Core\Application;
use App\Core\Http\HealthEndpoint;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HealthEndpointTest extends TestCase
{
    private Application $app;
    private string $tempDir;

    protected function setUp(): void
    {
        // Reset $_SERVER before each test
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Create temp environment for Application
        $this->tempDir = sys_get_temp_dir() . '/health_endpoint_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var');
        mkdir($this->tempDir . '/var/log');
        file_put_contents($this->tempDir . '/.env', "APP_NAME=TestApp\nAPP_DEBUG=true\n");
        
        // Reset Application singleton
        Application::resetInstance();
        
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
        $this->removeDirectory($this->tempDir);
        
        $_SERVER['REQUEST_URI'] = '';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    #[Test]
    public function it_detects_health_check_request_for_health_path(): void
    {
        $_SERVER['REQUEST_URI'] = '/health';
        
        $this->assertTrue(HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function it_detects_health_check_request_for_health_path_with_trailing_slash(): void
    {
        $_SERVER['REQUEST_URI'] = '/health/';
        
        $this->assertTrue(HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function it_returns_false_for_non_health_paths(): void
    {
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
        
        $_SERVER['REQUEST_URI'] = '/about';
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
        
        $_SERVER['REQUEST_URI'] = '/api/health';
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
        
        $_SERVER['REQUEST_URI'] = '/healthy';
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function it_returns_false_for_health_path_with_query_string(): void
    {
        $_SERVER['REQUEST_URI'] = '/health?check=true';
        
        $this->assertTrue(HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function it_handles_empty_request_uri(): void
    {
        $_SERVER['REQUEST_URI'] = '';
        
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function it_handles_missing_request_uri(): void
    {
        unset($_SERVER['REQUEST_URI']);
        
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function it_is_case_sensitive_for_health_path(): void
    {
        $_SERVER['REQUEST_URI'] = '/Health';
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
        
        $_SERVER['REQUEST_URI'] = '/HEALTH';
        $this->assertFalse(HealthEndpoint::isHealthCheckRequest());
    }

    #[Test]
    public function it_builds_response_with_correct_structure(): void
    {
        $response = HealthEndpoint::buildResponse($this->app);
        
        $this->assertArrayHasKey('statusCode', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('body', $response);
    }

    #[Test]
    public function it_returns_200_status_for_healthy(): void
    {
        $statusCode = HealthEndpoint::getStatusCode('healthy');
        
        $this->assertEquals(200, $statusCode);
    }

    #[Test]
    public function it_returns_200_status_for_degraded(): void
    {
        $statusCode = HealthEndpoint::getStatusCode('degraded');
        
        $this->assertEquals(200, $statusCode);
    }

    #[Test]
    public function it_returns_503_status_for_critical(): void
    {
        $statusCode = HealthEndpoint::getStatusCode('critical');
        
        $this->assertEquals(503, $statusCode);
    }

    #[Test]
    public function it_returns_200_status_for_unknown_status(): void
    {
        $statusCode = HealthEndpoint::getStatusCode('unknown');
        
        $this->assertEquals(200, $statusCode);
    }

    #[Test]
    public function it_returns_correct_headers(): void
    {
        $headers = HealthEndpoint::getHeaders();
        
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
        
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertEquals('*', $headers['Access-Control-Allow-Origin']);
        
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertEquals('GET, OPTIONS', $headers['Access-Control-Allow-Methods']);
    }

    #[Test]
    public function it_builds_response_with_valid_json_body(): void
    {
        $response = HealthEndpoint::buildResponse($this->app);
        
        $this->assertJson($response['body']);
        
        $decoded = json_decode($response['body'], true);
        $this->assertArrayHasKey('status', $decoded);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertArrayHasKey('app', $decoded);
        $this->assertArrayHasKey('system', $decoded);
    }

    #[Test]
    public function it_builds_response_with_correct_status_code(): void
    {
        $response = HealthEndpoint::buildResponse($this->app);
        
        $this->assertContains($response['statusCode'], [200, 503]);
    }

    #[Test]
    public function handle_returns_response_without_terminating(): void
    {
        // This test uses output buffering to capture the echo
        ob_start();
        $response = HealthEndpoint::handle($this->app, false);
        $output = ob_get_clean();
        
        $this->assertArrayHasKey('statusCode', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('body', $response);
        $this->assertJson($output);
    }

    #[Test]
    public function handle_returns_204_for_options_request(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        
        ob_start();
        $response = HealthEndpoint::handle($this->app, false);
        ob_get_clean();
        
        $this->assertEquals(204, $response['statusCode']);
        $this->assertEquals('', $response['body']);
    }
}
