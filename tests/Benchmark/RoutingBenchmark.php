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
namespace Tests\Benchmark;

use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Router;

/**
 * Routing Precision Benchmark
 * 
 * Measures route resolution time for:
 * - Static routes
 * - Dynamic routes (with parameters)
 * - Regex routes
 * - Grouped routes
 * - Nested groups
 * 
 * Scale tests:
 * - 100 routes
 * - 1,000 routes
 * - 10,000 routes
 * 
 * Should NOT degrade linearly.
 * 
 * Run with: php tests/Benchmark/RoutingBenchmark.php
 */
final class RoutingBenchmark
{
    private array $results = [];
    private const ITERATIONS = 500;
    private const ROUTE_COUNTS = [100, 500, 1000];
    private ?Application $app = null;
    private string $tempDir;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Routing Precision Benchmark\n";
        echo "========================================\n\n";

        $this->setupApp();

        try {
            $this->benchmarkRouteTypes();
            $this->benchmarkRouteScale();
            $this->benchmarkRoutePosition();
            $this->benchmarkHttpMethods();
            $this->analyzeScaling();
            $this->printResults();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Setup temporary application
     */
    private function setupApp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/routing_bench_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/log', 0755, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=RoutingBench\nAPP_ENV=testing\n");

        Application::resetInstance();
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
    }

    /**
     * Benchmark different route types
     */
    private function benchmarkRouteTypes(): void
    {
        echo "Benchmarking Route Types... ";

        $router = new Router($this->app);

        // Static routes
        $router->get('/static/path/to/resource', fn() => new Response('static'));
        
        // Dynamic routes with parameters
        $router->get('/users/{id}', fn() => new Response('user'));
        $router->get('/posts/{postId}/comments/{commentId}', fn() => new Response('comment'));
        
        // Regex routes
        $router->get('/articles/{id:\d+}', fn() => new Response('article'));
        $router->get('/files/{path:.+}', fn() => new Response('file'));
        
        // Grouped routes
        $router->group(['prefix' => '/api/v1'], function(Router $r) {
            $r->get('/users', fn() => new Response('api users'));
            $r->get('/posts', fn() => new Response('api posts'));
        });

        // Nested groups
        $router->group(['prefix' => '/admin'], function(Router $r) {
            $r->group(['prefix' => '/settings'], function(Router $r) {
                $r->get('/general', fn() => new Response('admin settings'));
            });
        });

        $routeTests = [
            'Static' => ['GET', '/static/path/to/resource'],
            'Dynamic (1 param)' => ['GET', '/users/123'],
            'Dynamic (2 params)' => ['GET', '/posts/456/comments/789'],
            'Regex (digits)' => ['GET', '/articles/12345'],
            'Regex (path)' => ['GET', '/files/path/to/document.pdf'],
            'Grouped' => ['GET', '/api/v1/users'],
            'Nested Group' => ['GET', '/admin/settings/general'],
        ];

        foreach ($routeTests as $name => $config) {
            [$method, $path] = $config;
            $request = Request::create($path, $method);

            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                try {
                    $router->dispatch($request);
                } catch (\Throwable) {
                    // Ignore
                }
                $times[] = hrtime(true) - $start;
            }

            $this->results["Type: {$name}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'min_us' => min($times) / 1e3,
                'max_us' => max($times) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark route scaling
     */
    private function benchmarkRouteScale(): void
    {
        echo "Benchmarking Route Scale... ";

        foreach (self::ROUTE_COUNTS as $count) {
            $router = new Router($this->app);

            // Register routes
            for ($i = 0; $i < $count; $i++) {
                $router->get("/route{$i}/{id}", fn() => new Response("route{$i}"));
            }

            // Test first route (best case)
            $firstRequest = Request::create('/route0/123', 'GET');
            $firstTimes = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                try {
                    $router->dispatch($firstRequest);
                } catch (\Throwable) {}
                $firstTimes[] = hrtime(true) - $start;
            }

            // Test middle route (average case)
            $middleIdx = (int)($count / 2);
            $middleRequest = Request::create("/route{$middleIdx}/123", 'GET');
            $middleTimes = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                try {
                    $router->dispatch($middleRequest);
                } catch (\Throwable) {}
                $middleTimes[] = hrtime(true) - $start;
            }

            // Test last route (worst case)
            $lastIdx = $count - 1;
            $lastRequest = Request::create("/route{$lastIdx}/123", 'GET');
            $lastTimes = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                try {
                    $router->dispatch($lastRequest);
                } catch (\Throwable) {}
                $lastTimes[] = hrtime(true) - $start;
            }

            // Test non-existent route (miss)
            $missRequest = Request::create('/nonexistent/route', 'GET');
            $missTimes = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                try {
                    $router->dispatch($missRequest);
                } catch (\Throwable) {}
                $missTimes[] = hrtime(true) - $start;
            }

            $this->results["Scale: {$count} routes"] = [
                'route_count' => $count,
                'first_route_us' => (array_sum($firstTimes) / count($firstTimes)) / 1e3,
                'middle_route_us' => (array_sum($middleTimes) / count($middleTimes)) / 1e3,
                'last_route_us' => (array_sum($lastTimes) / count($lastTimes)) / 1e3,
                'miss_us' => (array_sum($missTimes) / count($missTimes)) / 1e3,
                'avg_us' => ((array_sum($firstTimes) + array_sum($middleTimes) + array_sum($lastTimes)) / 
                            (count($firstTimes) * 3)) / 1e3,
            ];
        }

        echo "Done\n";
    }

    /**
     * Benchmark route position impact
     */
    private function benchmarkRoutePosition(): void
    {
        echo "Benchmarking Route Position... ";

        $router = new Router($this->app);
        $routeCount = 500;

        // Register routes
        for ($i = 0; $i < $routeCount; $i++) {
            $router->get("/position/route{$i}", fn() => new Response("route{$i}"));
        }

        $positions = [0, 50, 100, 250, 499]; // First, early, middle, late, last
        $positionResults = [];

        foreach ($positions as $pos) {
            $request = Request::create("/position/route{$pos}", 'GET');
            
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                try {
                    $router->dispatch($request);
                } catch (\Throwable) {}
                $times[] = hrtime(true) - $start;
            }

            $positionResults[$pos] = (array_sum($times) / count($times)) / 1e3;
        }

        $this->results['Position Impact'] = [
            'route_count' => $routeCount,
            'positions' => $positionResults,
            'position_sensitivity' => max($positionResults) / max(min($positionResults), 0.001),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark different HTTP methods
     */
    private function benchmarkHttpMethods(): void
    {
        echo "Benchmarking HTTP Methods... ";

        $router = new Router($this->app);
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            $methodLower = strtolower($method);
            $router->$methodLower('/method/test', fn() => new Response($method));
        }

        foreach ($methods as $method) {
            $request = Request::create('/method/test', $method);
            
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                try {
                    $router->dispatch($request);
                } catch (\Throwable) {}
                $times[] = hrtime(true) - $start;
            }

            $this->results["Method: {$method}"] = [
                'avg_us' => (array_sum($times) / count($times)) / 1e3,
                'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            ];
        }

        echo "Done\n";
    }

    /**
     * Analyze scaling behavior
     */
    private function analyzeScaling(): void
    {
        echo "Analyzing Scaling... ";

        $scaleData = [];
        foreach ($this->results as $name => $data) {
            if (str_starts_with($name, 'Scale:') && isset($data['route_count'], $data['avg_us'])) {
                $scaleData[] = [
                    'routes' => $data['route_count'],
                    'time' => $data['avg_us'],
                ];
            }
        }

        if (count($scaleData) < 2) {
            echo "Not enough data\n";
            return;
        }

        usort($scaleData, fn($a, $b) => $a['routes'] <=> $b['routes']);

        // Calculate scaling factor
        $first = $scaleData[0];
        $last = end($scaleData);
        $routeMultiplier = $last['routes'] / $first['routes'];
        $timeMultiplier = $last['time'] / max($first['time'], 0.001);

        // Determine scaling pattern
        $scalingPattern = match (true) {
            $timeMultiplier < 1.5 => 'O(1) - Constant (Excellent)',
            $timeMultiplier < log($routeMultiplier) * 2 => 'O(log n) - Logarithmic (Great)',
            $timeMultiplier < sqrt($routeMultiplier) * 2 => 'O(√n) - Square Root (Good)',
            $timeMultiplier < $routeMultiplier / 2 => 'O(n) - Linear (Acceptable)',
            default => 'O(n²) or worse - Needs Optimization',
        };

        $this->results['Scaling Analysis'] = [
            'route_multiplier' => $routeMultiplier,
            'time_multiplier' => $timeMultiplier,
            'scaling_pattern' => $scalingPattern,
            'efficiency' => $routeMultiplier / max($timeMultiplier, 0.001),
        ];

        echo "Done\n";
    }

    /**
     * Print results
     */
    private function printResults(): void
    {
        echo "\n========================================\n";
        echo "  Results\n";
        echo "========================================\n\n";

        // Route types
        echo "Route Type Performance:\n";
        echo str_repeat('-', 70) . "\n";
        printf("%-25s %12s %12s %15s\n", "Type", "Avg (µs)", "Max (µs)", "Ops/sec");
        echo str_repeat('-', 70) . "\n";

        foreach ($this->results as $name => $data) {
            if (!str_starts_with($name, 'Type:')) continue;
            printf("%-25s %12.2f %12.2f %15s\n",
                str_replace('Type: ', '', $name),
                $data['avg_us'],
                $data['max_us'],
                number_format($data['ops_per_sec'], 0)
            );
        }
        echo "\n";

        // Scale results
        echo "Route Scale Performance:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-15s %12s %12s %12s %12s %12s\n", 
            "Routes", "First (µs)", "Middle (µs)", "Last (µs)", "Miss (µs)", "Avg (µs)");
        echo str_repeat('-', 80) . "\n";

        foreach ($this->results as $name => $data) {
            if (!str_starts_with($name, 'Scale:')) continue;
            printf("%-15s %12.2f %12.2f %12.2f %12.2f %12.2f\n",
                number_format($data['route_count']),
                $data['first_route_us'],
                $data['middle_route_us'],
                $data['last_route_us'],
                $data['miss_us'],
                $data['avg_us']
            );
        }
        echo "\n";

        // HTTP Methods
        echo "HTTP Method Performance:\n";
        echo str_repeat('-', 50) . "\n";
        foreach ($this->results as $name => $data) {
            if (!str_starts_with($name, 'Method:')) continue;
            printf("  %s: %.2f µs (%s ops/sec)\n",
                str_replace('Method: ', '', $name),
                $data['avg_us'],
                number_format($data['ops_per_sec'], 0)
            );
        }
        echo "\n";

        // Position impact
        if (isset($this->results['Position Impact'])) {
            $pos = $this->results['Position Impact'];
            echo "Position Impact (500 routes):\n";
            echo str_repeat('-', 50) . "\n";
            foreach ($pos['positions'] as $position => $time) {
                printf("  Route #%d: %.2f µs\n", $position, $time);
            }
            printf("  Position Sensitivity: %.2fx\n", $pos['position_sensitivity']);
            echo "\n";
        }

        // Scaling analysis
        if (isset($this->results['Scaling Analysis'])) {
            $analysis = $this->results['Scaling Analysis'];
            echo "========================================\n";
            echo "  Scaling Analysis\n";
            echo "========================================\n";
            printf("  Route Multiplier: %.0fx\n", $analysis['route_multiplier']);
            printf("  Time Multiplier: %.2fx\n", $analysis['time_multiplier']);
            printf("  Pattern: %s\n", $analysis['scaling_pattern']);
            printf("  Efficiency Score: %.2f\n", $analysis['efficiency']);
            echo "\n";
        }

        echo "========================================\n";
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }

    /**
     * Cleanup
     */
    private function cleanup(): void
    {
        Application::resetInstance();
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
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

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__, 2));
    }
    
    (new RoutingBenchmark())->run();
}
