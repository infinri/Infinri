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
namespace Tests\Integration;

use App\Core\Application;
use App\Core\Contracts\Http\RequestInterface;
use App\Core\Contracts\Http\ResponseInterface;
use App\Core\Contracts\Http\MiddlewareInterface;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Core\Http\Kernel;
use App\Core\Routing\Router;
use Closure;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class Phase2IntegrationTest extends TestCase
{
    private Application $app;
    private Router $router;
    private Kernel $kernel;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/phase2_test_' . uniqid();
        mkdir($this->tempDir);
        mkdir($this->tempDir . '/var/log', 0777, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=TestApp\nAPP_DEBUG=true\n");
        
        Application::resetInstance();
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
        
        $this->router = new Router($this->app);
        $this->kernel = new Kernel($this->app, $this->router);
    }

    protected function tearDown(): void
    {
        Application::resetInstance();
        $this->removeDirectory($this->tempDir);
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
    public function it_completes_full_http_request_lifecycle(): void
    {
        // Register a test route
        $this->router->get('/healthz', function() {
            return new JsonResponse([
                'status' => 'healthy',
                'timestamp' => date('c'),
            ]);
        });
        
        // Create request
        $request = Request::create('/healthz', 'GET');
        
        // Handle through kernel
        $response = $this->kernel->handle($request);
        
        // Verify response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('healthy', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
    }

    #[Test]
    public function it_handles_404_not_found(): void
    {
        $request = Request::create('/nonexistent', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function it_handles_405_method_not_allowed(): void
    {
        $this->router->get('/only-get', fn() => 'Get only');
        
        $request = Request::create('/only-get', 'POST');
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertNotNull($response->getHeader('allow'));
    }

    #[Test]
    public function it_registers_route_middleware(): void
    {
        // Register route with middleware
        $route = $this->router->get('/with-middleware', fn() => new Response('Done'))
            ->middleware(['test.middleware']);
        
        // Verify middleware was registered
        $this->assertContains('test.middleware', $route->getMiddleware());
    }

    #[Test]
    public function it_extracts_route_parameters(): void
    {
        $this->router->get('/users/{id}', function(Request $request) {
            return new JsonResponse([
                'user_id' => $request->route('id'),
            ]);
        });
        
        $request = Request::create('/users/42', 'GET');
        $response = $this->kernel->handle($request);
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('42', $data['user_id']);
    }

    #[Test]
    public function it_handles_json_request_body(): void
    {
        $this->router->post('/api/users', function(Request $request) {
            return new JsonResponse([
                'received' => [
                    'name' => $request->input('name'),
                    'email' => $request->input('email'),
                ],
            ], 201);
        });
        
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/api/users',
                'CONTENT_TYPE' => 'application/json',
            ],
            [],
            '{"name": "John Doe", "email": "john@example.com"}'
        );
        
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('John Doe', $data['received']['name']);
        $this->assertEquals('john@example.com', $data['received']['email']);
    }

    #[Test]
    public function it_adds_timing_headers(): void
    {
        $this->router->get('/timed', fn() => new Response('OK'));
        
        $request = Request::create('/timed', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertNotNull($response->getHeader('x-response-time'));
        $this->assertNotNull($response->getHeader('x-memory-usage'));
    }

    #[Test]
    public function it_handles_route_groups(): void
    {
        $this->router->group(['prefix' => 'api/v1'], function($router) {
            $router->get('/status', fn() => new JsonResponse(['api' => 'v1']));
        });
        
        $request = Request::create('/api/v1/status', 'GET');
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('v1', $data['api']);
    }

    #[Test]
    public function it_returns_json_for_json_requests(): void
    {
        // Request that expects JSON should get JSON error responses
        $request = new Request(
            [],
            [],
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/nonexistent',
                'HTTP_ACCEPT' => 'application/json',
            ]
        );
        
        $response = $this->kernel->handle($request);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Not Found', $data['error']);
    }

    #[Test]
    public function it_meets_performance_requirements(): void
    {
        $this->router->get('/perf-test', fn() => new Response('OK'));
        
        $request = Request::create('/perf-test', 'GET');
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $response = $this->kernel->handle($request);
        
        $duration = (microtime(true) - $startTime) * 1000;
        $memoryUsed = (memory_get_usage(true) - $startMemory) / 1024 / 1024;
        
        // Performance requirements from SCALABILITY-PLAN.md
        $this->assertLessThan(100, $duration, "Request should complete in <100ms");
        $this->assertLessThan(15, $memoryUsed, "Request should use <15MB additional memory");
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}
