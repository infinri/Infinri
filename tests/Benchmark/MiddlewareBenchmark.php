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
use App\Core\Http\Middleware\Pipeline;

/**
 * Middleware Pipeline Performance Benchmark
 * 
 * Benchmarks:
 * - 0 middleware
 * - 1 middleware
 * - 5 middleware
 * - 10 middleware
 * - 25 middleware
 * 
 * Measures:
 * - Cost per layer
 * - Pipeline overhead
 * - Memory churn
 * 
 * Goal: Keep middleware < 30µs per item.
 * 
 * Run with: php tests/Benchmark/MiddlewareBenchmark.php
 */
final class MiddlewareBenchmark
{
    private array $results = [];
    private const MIDDLEWARE_COUNTS = [0, 1, 5, 10, 25];
    private const ITERATIONS = 1000;
    private ?Application $app = null;
    private string $tempDir = '';

    public function run(): void
    {
        echo "========================================\n";
        echo "  Middleware Pipeline Benchmark\n";
        echo "========================================\n\n";

        try {
            foreach (self::MIDDLEWARE_COUNTS as $count) {
                $this->benchmarkMiddlewareCount($count);
            }

            $this->benchmarkRealMiddleware();
            $this->benchmarkMiddlewareTypes();
            $this->analyzePerLayerCost();
            $this->printResults();
        } finally {
            $this->cleanup();
        }
    }

    private function setupApp(): void
    {
        if ($this->app !== null) return;
        
        $this->tempDir = sys_get_temp_dir() . '/middleware_bench_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/log', 0755, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=MiddlewareBench\nAPP_ENV=testing\n");
        
        Application::resetInstance();
        $this->app = new Application($this->tempDir);
        $this->app->bootstrap();
    }

    private function benchmarkMiddlewareCount(int $middlewareCount): void
    {
        echo "Benchmarking {$middlewareCount} middleware... ";

        $this->setupApp();
        $request = Request::create('/', 'GET');

        // Create synthetic middleware array
        $middlewares = [];
        for ($i = 0; $i < $middlewareCount; $i++) {
            $middlewares[] = new class implements \App\Core\Contracts\Http\MiddlewareInterface {
                public function handle(\App\Core\Contracts\Http\RequestInterface $request, \Closure $next): \App\Core\Contracts\Http\ResponseInterface
                {
                    // Minimal work - just pass through
                    return $next($request);
                }
            };
        }

        // Final handler
        $handler = fn($r) => new Response('OK');

        $times = [];
        $memoryUsages = [];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            gc_collect_cycles();
            $startMemory = memory_get_usage();
            
            $start = hrtime(true);
            // Use the correct Pipeline API
            $pipeline = new Pipeline($this->app);
            $pipeline->send($request)->through($middlewares)->then($handler);
            $times[] = hrtime(true) - $start;
            
            $memoryUsages[] = memory_get_usage() - $startMemory;
        }

        $avgTime = array_sum($times) / count($times);
        $this->results["Pipeline ({$middlewareCount} middleware)"] = [
            'middleware_count' => $middlewareCount,
            'avg_us' => $avgTime / 1e3,
            'min_us' => min($times) / 1e3,
            'max_us' => max($times) / 1e3,
            'per_layer_us' => $middlewareCount > 0 ? ($avgTime / 1e3) / $middlewareCount : 0,
            'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
            'memory_churn_bytes' => array_sum($memoryUsages) / count($memoryUsages),
            'iterations' => self::ITERATIONS,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark real framework middleware
     */
    private function benchmarkRealMiddleware(): void
    {
        echo "Benchmarking Real Middleware... ";

        $realMiddleware = [
            'SecurityHeaders' => \App\Core\Http\Middleware\SecurityHeadersMiddleware::class,
            'RequestTiming' => \App\Core\Http\Middleware\RequestTimingMiddleware::class,
            'Metrics' => \App\Core\Http\Middleware\MetricsMiddleware::class,
        ];

        foreach ($realMiddleware as $name => $class) {
            if (!class_exists($class)) {
                $this->results["Real: {$name}"] = ['skipped' => true, 'reason' => 'Class not found'];
                continue;
            }

            try {
                $middleware = new $class();
                $request = Request::create('/', 'GET');
                $handler = fn($r) => new Response('OK');

                $times = [];
                for ($i = 0; $i < self::ITERATIONS; $i++) {
                    $start = hrtime(true);
                    $middleware->handle($request, $handler);
                    $times[] = hrtime(true) - $start;
                }

                $this->results["Real: {$name}"] = [
                    'avg_us' => (array_sum($times) / count($times)) / 1e3,
                    'min_us' => min($times) / 1e3,
                    'max_us' => max($times) / 1e3,
                    'ops_per_sec' => self::ITERATIONS / (array_sum($times) / 1e9),
                ];
            } catch (\Throwable $e) {
                $this->results["Real: {$name}"] = ['skipped' => true, 'reason' => $e->getMessage()];
            }
        }

        echo "Done\n";
    }

    /**
     * Benchmark different middleware operation types
     */
    private function benchmarkMiddlewareTypes(): void
    {
        echo "Benchmarking Middleware Types... ";

        $request = Request::create('/', 'GET');
        $handler = fn($r) => new Response('OK');

        // Pass-through middleware
        $passThroughMiddleware = new class implements \App\Core\Contracts\Http\MiddlewareInterface {
            public function handle(\App\Core\Contracts\Http\RequestInterface $request, \Closure $next): \App\Core\Contracts\Http\ResponseInterface
            {
                return $next($request);
            }
        };

        // Header modification middleware
        $headerMiddleware = new class implements \App\Core\Contracts\Http\MiddlewareInterface {
            public function handle(\App\Core\Contracts\Http\RequestInterface $request, \Closure $next): \App\Core\Contracts\Http\ResponseInterface
            {
                $response = $next($request);
                if (method_exists($response, 'header')) {
                    $response->header('X-Benchmark', 'true');
                }
                return $response;
            }
        };

        // Request inspection middleware
        $inspectionMiddleware = new class implements \App\Core\Contracts\Http\MiddlewareInterface {
            public function handle(\App\Core\Contracts\Http\RequestInterface $request, \Closure $next): \App\Core\Contracts\Http\ResponseInterface
            {
                $method = $request->method();
                $path = $request->path();
                $headers = $request->headers();
                return $next($request);
            }
        };

        // Array manipulation middleware
        $arrayMiddleware = new class implements \App\Core\Contracts\Http\MiddlewareInterface {
            public function handle(\App\Core\Contracts\Http\RequestInterface $request, \Closure $next): \App\Core\Contracts\Http\ResponseInterface
            {
                $data = [];
                for ($i = 0; $i < 10; $i++) {
                    $data["key_{$i}"] = "value_{$i}";
                }
                return $next($request);
            }
        };

        $types = [
            'Pass-through' => $passThroughMiddleware,
            'Header Modification' => $headerMiddleware,
            'Request Inspection' => $inspectionMiddleware,
            'Array Manipulation' => $arrayMiddleware,
        ];

        foreach ($types as $name => $middleware) {
            $times = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $start = hrtime(true);
                $middleware->handle($request, $handler);
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
     * Analyze per-layer cost
     */
    private function analyzePerLayerCost(): void
    {
        echo "Analyzing Per-Layer Cost... ";

        $points = [];
        foreach ($this->results as $name => $data) {
            if (isset($data['middleware_count'], $data['avg_us'])) {
                $points[] = [
                    'count' => $data['middleware_count'],
                    'time' => $data['avg_us'],
                ];
            }
        }

        if (count($points) < 2) {
            echo "Not enough data\n";
            return;
        }

        // Calculate baseline (0 middleware)
        $baseline = 0;
        foreach ($points as $point) {
            if ($point['count'] === 0) {
                $baseline = $point['time'];
                break;
            }
        }

        // Calculate incremental cost per layer
        $incrementalCosts = [];
        usort($points, fn($a, $b) => $a['count'] <=> $b['count']);
        
        for ($i = 1; $i < count($points); $i++) {
            $layerDiff = $points[$i]['count'] - $points[$i - 1]['count'];
            $timeDiff = $points[$i]['time'] - $points[$i - 1]['time'];
            if ($layerDiff > 0) {
                $incrementalCosts[] = $timeDiff / $layerDiff;
            }
        }

        $avgIncrementalCost = count($incrementalCosts) > 0 
            ? array_sum($incrementalCosts) / count($incrementalCosts) 
            : 0;

        $this->results['Per-Layer Analysis'] = [
            'baseline_us' => $baseline,
            'avg_incremental_cost_us' => $avgIncrementalCost,
            'meets_goal' => $avgIncrementalCost < 30, // Goal: < 30µs per layer
            'efficiency_rating' => match (true) {
                $avgIncrementalCost < 10 => 'Excellent',
                $avgIncrementalCost < 20 => 'Good',
                $avgIncrementalCost < 30 => 'Acceptable',
                $avgIncrementalCost < 50 => 'Needs Improvement',
                default => 'Poor',
            },
        ];

        echo "Done\n";
    }

    /**
     * Print benchmark results
     */
    private function printResults(): void
    {
        echo "\n========================================\n";
        echo "  Results\n";
        echo "========================================\n\n";

        // Pipeline results
        echo "Pipeline Scaling:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-30s %12s %12s %12s %15s\n", "Configuration", "Avg (µs)", "Per Layer", "Ops/sec", "Memory Churn");
        echo str_repeat('-', 80) . "\n";

        foreach ($this->results as $name => $data) {
            if (!str_starts_with($name, 'Pipeline')) {
                continue;
            }
            
            printf("%-30s %12.2f %12.2f %15s %12d bytes\n",
                $name,
                $data['avg_us'],
                $data['per_layer_us'],
                number_format($data['ops_per_sec'], 0),
                (int)$data['memory_churn_bytes']
            );
        }
        echo "\n";

        // Real middleware results
        echo "Real Middleware Performance:\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($this->results as $name => $data) {
            if (!str_starts_with($name, 'Real:')) {
                continue;
            }
            
            if (isset($data['skipped'])) {
                printf("  %s: Skipped (%s)\n", $name, $data['reason'] ?? 'unknown');
            } else {
                printf("  %s: %.2f µs (%s ops/sec)\n", 
                    $name, $data['avg_us'], number_format($data['ops_per_sec'], 0));
            }
        }
        echo "\n";

        // Middleware type results
        echo "Middleware Type Performance:\n";
        echo str_repeat('-', 60) . "\n";
        foreach ($this->results as $name => $data) {
            if (!str_starts_with($name, 'Type:')) {
                continue;
            }
            
            printf("  %s: %.2f µs (%s ops/sec)\n", 
                $name, $data['avg_us'], number_format($data['ops_per_sec'], 0));
        }
        echo "\n";

        // Per-layer analysis
        if (isset($this->results['Per-Layer Analysis'])) {
            $analysis = $this->results['Per-Layer Analysis'];
            echo "========================================\n";
            echo "  Per-Layer Analysis\n";
            echo "========================================\n";
            printf("  Pipeline Baseline: %.2f µs\n", $analysis['baseline_us']);
            printf("  Avg Cost Per Layer: %.2f µs\n", $analysis['avg_incremental_cost_us']);
            printf("  Meets Goal (<30µs): %s\n", $analysis['meets_goal'] ? 'YES' : 'NO');
            printf("  Rating: %s\n", $analysis['efficiency_rating']);
            echo "\n";
        }

        echo "========================================\n";
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        printf("  Goal: < 30µs per middleware layer\n");
        echo "\n";
    }

    private function cleanup(): void
    {
        Application::resetInstance();
        if ($this->tempDir && is_dir($this->tempDir)) {
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
    
    (new MiddlewareBenchmark())->run();
}
