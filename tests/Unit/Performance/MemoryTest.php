<?php declare(strict_types=1);


/**
 * Infinri Framework
 *
 * @copyright Copyright (c) 2024-2025 Lucio Saldivar / Infinri
 * @license   Proprietary - All Rights Reserved
 * 
 * This source code is proprietary and confidential. Unauthorized copying,
 * modification, distribution, or use is strictly prohibited. See LICENSE.
 */
namespace Tests\Unit\Performance;

use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Router;
use App\Core\Log\LogManager;
use App\Core\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Memory Usage Tests
 * 
 * These tests monitor memory consumption to catch resource hogs early.
 * Thresholds should be adjusted based on your application's requirements.
 */
class MemoryTest extends TestCase
{
    private const MB = 1024 * 1024;
    
    // Memory thresholds (in MB)
    private const MAX_CONTAINER_MEMORY = 5;
    private const MAX_REQUEST_MEMORY = 2;
    private const MAX_ROUTER_MEMORY = 5;
    private const MAX_LOGGER_MEMORY = 2;
    private const MAX_100_REQUESTS_MEMORY = 20;

    #[Test]
    public function container_uses_reasonable_memory(): void
    {
        $startMemory = memory_get_usage(true);
        
        $container = new Container();
        
        // Bind 100 services
        for ($i = 0; $i < 100; $i++) {
            $container->bind("service.{$i}", fn() => new \stdClass());
        }
        
        // Resolve them
        for ($i = 0; $i < 100; $i++) {
            $container->make("service.{$i}");
        }
        
        $endMemory = memory_get_usage(true);
        $usedMB = ($endMemory - $startMemory) / self::MB;
        
        $this->assertLessThan(
            self::MAX_CONTAINER_MEMORY,
            $usedMB,
            "Container used {$usedMB}MB for 100 bindings (max: " . self::MAX_CONTAINER_MEMORY . "MB)"
        );
    }

    #[Test]
    public function request_uses_reasonable_memory(): void
    {
        $startMemory = memory_get_usage(true);
        
        // Create request with typical data
        $request = new Request(
            ['key1' => 'value1', 'key2' => 'value2'], // query
            ['field1' => 'data1', 'field2' => str_repeat('x', 1000)], // post
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/api/users/123',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
            ],
            ['session' => 'abc123'],
            json_encode(['data' => str_repeat('y', 5000)])
        );
        
        // Access various properties
        $request->method();
        $request->path();
        $request->all();
        $request->headers();
        
        $endMemory = memory_get_usage(true);
        $usedMB = ($endMemory - $startMemory) / self::MB;
        
        $this->assertLessThan(
            self::MAX_REQUEST_MEMORY,
            $usedMB,
            "Request used {$usedMB}MB (max: " . self::MAX_REQUEST_MEMORY . "MB)"
        );
    }

    #[Test]
    public function router_with_many_routes_uses_reasonable_memory(): void
    {
        $tempDir = sys_get_temp_dir() . '/router_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        file_put_contents($tempDir . '/.env', "APP_NAME=Test\n");
        
        try {
            Application::resetInstance();
            $app = new Application($tempDir);
            $app->bootstrap();
            
            $startMemory = memory_get_usage(true);
            
            $router = new Router($app);
            
            // Register 200 routes (simulating a medium-sized app)
            for ($i = 0; $i < 200; $i++) {
                $router->get("/route/{$i}/{id}", fn() => "Route {$i}");
                $router->post("/route/{$i}", fn() => "Route {$i}");
            }
            
            $endMemory = memory_get_usage(true);
            $usedMB = ($endMemory - $startMemory) / self::MB;
            
            $this->assertLessThanOrEqual(
                self::MAX_ROUTER_MEMORY,
                $usedMB,
                "Router with 400 routes used {$usedMB}MB (max: " . self::MAX_ROUTER_MEMORY . "MB)"
            );
        } finally {
            Application::resetInstance();
            $this->removeDirectory($tempDir);
        }
    }

    #[Test]
    public function logger_uses_reasonable_memory(): void
    {
        $tempDir = sys_get_temp_dir() . '/memory_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            $startMemory = memory_get_usage(true);
            
            $logger = new LogManager($tempDir);
            
            // Log 1000 entries
            for ($i = 0; $i < 1000; $i++) {
                $logger->info("Log message {$i}", ['index' => $i, 'data' => str_repeat('x', 100)]);
            }
            
            $endMemory = memory_get_usage(true);
            $usedMB = ($endMemory - $startMemory) / self::MB;
            
            $this->assertLessThan(
                self::MAX_LOGGER_MEMORY,
                $usedMB,
                "Logger used {$usedMB}MB for 1000 entries (max: " . self::MAX_LOGGER_MEMORY . "MB)"
            );
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    #[Test]
    public function multiple_requests_do_not_leak_memory(): void
    {
        $tempDir = sys_get_temp_dir() . '/leak_test_' . uniqid();
        mkdir($tempDir, 0755, true);
        mkdir($tempDir . '/var/log', 0755, true);
        file_put_contents($tempDir . '/.env', "APP_NAME=Test\n");
        
        try {
            Application::resetInstance();
            $app = new Application($tempDir);
            $app->bootstrap();
            
            $router = new Router($app);
            
            // Setup some routes
            $router->get('/test/{id}', fn(Request $r) => new Response('OK'));
            $router->post('/data', fn() => new Response('Created'));
            
            $startMemory = memory_get_usage(true);
            
            // Simulate 100 requests
            for ($i = 0; $i < 100; $i++) {
                $request = Request::create("/test/{$i}", 'GET');
                $response = $router->dispatch($request);
                
                // Clear references
                unset($request, $response);
            }
            
            // Force garbage collection
            gc_collect_cycles();
            
            $endMemory = memory_get_usage(true);
            $usedMB = ($endMemory - $startMemory) / self::MB;
            
            $this->assertLessThan(
                self::MAX_100_REQUESTS_MEMORY,
                $usedMB,
                "100 requests used {$usedMB}MB (max: " . self::MAX_100_REQUESTS_MEMORY . "MB) - possible memory leak"
            );
        } finally {
            Application::resetInstance();
            $this->removeDirectory($tempDir);
        }
    }

    #[Test]
    public function reports_current_memory_usage(): void
    {
        $peakMemory = memory_get_peak_usage(true) / self::MB;
        $currentMemory = memory_get_usage(true) / self::MB;
        
        // This test always passes but outputs memory stats
        $this->addToAssertionCount(1);
        
        // Log memory stats (visible in verbose mode)
        fwrite(STDERR, sprintf(
            "\n[Memory Stats] Current: %.2fMB | Peak: %.2fMB\n",
            $currentMemory,
            $peakMemory
        ));
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
}
