<?php

declare(strict_types=1);

namespace Tests\Benchmark;

/**
 * Framework Benchmark Suite
 * 
 * Run with: php tests/Benchmark/FrameworkBenchmark.php
 * 
 * Measures key framework performance metrics:
 * - Bootstrap time
 * - Container resolution
 * - Router dispatch
 * - Cache operations
 * - Event dispatch
 */
class FrameworkBenchmark
{
    private array $results = [];
    private int $iterations = 1000;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Infinri Framework Benchmark Suite\n";
        echo "========================================\n\n";

        $this->benchmarkBootstrap();
        $this->benchmarkContainer();
        $this->benchmarkRouter();
        $this->benchmarkCache();
        $this->benchmarkEvents();
        $this->benchmarkModuleLoader();

        $this->printResults();
    }

    /**
     * Benchmark application bootstrap
     */
    private function benchmarkBootstrap(): void
    {
        echo "Benchmarking Bootstrap... ";

        $times = [];
        for ($i = 0; $i < 10; $i++) {
            // Reset application state
            \App\Core\Application::resetInstance();
            
            $start = hrtime(true);
            
            $app = new \App\Core\Application(dirname(__DIR__, 2));
            $app->bootstrap();
            
            $times[] = (hrtime(true) - $start) / 1e6; // Convert to ms
        }

        $this->results['Bootstrap'] = [
            'avg_ms' => array_sum($times) / count($times),
            'min_ms' => min($times),
            'max_ms' => max($times),
            'iterations' => 10,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark container resolution
     */
    private function benchmarkContainer(): void
    {
        echo "Benchmarking Container... ";

        $app = \App\Core\Application::getInstance();

        // Singleton resolution
        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            $app->make(\App\Core\Contracts\Config\ConfigInterface::class);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Container (Singleton)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => $this->iterations / (array_sum($times) / 1e9),
            'iterations' => $this->iterations,
        ];

        // Fresh instance resolution
        $app->bind('benchmark.test', fn() => new \stdClass());
        
        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            $app->make('benchmark.test');
            $times[] = hrtime(true) - $start;
        }

        $this->results['Container (Factory)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => $this->iterations / (array_sum($times) / 1e9),
            'iterations' => $this->iterations,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark router dispatch
     */
    private function benchmarkRouter(): void
    {
        echo "Benchmarking Router... ";

        $app = \App\Core\Application::getInstance();
        
        if (!$app->has(\App\Core\Contracts\Routing\RouterInterface::class)) {
            $this->results['Router'] = ['skipped' => true];
            echo "Skipped (not registered)\n";
            return;
        }

        $router = $app->make(\App\Core\Contracts\Routing\RouterInterface::class);

        // Route matching
        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            try {
                $router->dispatch('GET', '/');
            } catch (\Throwable) {
                // Ignore dispatch errors, we're measuring match time
            }
            $times[] = hrtime(true) - $start;
        }

        $this->results['Router (Dispatch)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => $this->iterations / (array_sum($times) / 1e9),
            'iterations' => $this->iterations,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark cache operations
     */
    private function benchmarkCache(): void
    {
        echo "Benchmarking Cache... ";

        $cache = new \App\Core\Cache\ArrayStore();
        
        // Write operations
        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            $cache->put("key_{$i}", "value_{$i}", 3600);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cache (Write)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => $this->iterations / (array_sum($times) / 1e9),
            'iterations' => $this->iterations,
        ];

        // Read operations
        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            $cache->get("key_{$i}");
            $times[] = hrtime(true) - $start;
        }

        $this->results['Cache (Read)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => $this->iterations / (array_sum($times) / 1e9),
            'iterations' => $this->iterations,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark event dispatch
     */
    private function benchmarkEvents(): void
    {
        echo "Benchmarking Events... ";

        $dispatcher = new \App\Core\Events\EventDispatcher();
        
        // Register listeners
        for ($i = 0; $i < 10; $i++) {
            $dispatcher->listen('benchmark.event', fn($e) => null);
        }

        // Create a simple event object
        $event = new class extends \App\Core\Events\Event {};

        // Dispatch with listeners
        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            $dispatcher->dispatch($event, 'benchmark.event');
            $times[] = hrtime(true) - $start;
        }

        $this->results['Events (10 listeners)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => $this->iterations / (array_sum($times) / 1e9),
            'iterations' => $this->iterations,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark module loader
     */
    private function benchmarkModuleLoader(): void
    {
        echo "Benchmarking ModuleLoader... ";

        $app = \App\Core\Application::getInstance();

        // Module registry load (from cache)
        $times = [];
        for ($i = 0; $i < 100; $i++) {
            $registry = new \App\Core\Module\ModuleRegistry();
            
            $start = hrtime(true);
            $registry->load();
            $times[] = hrtime(true) - $start;
        }

        $this->results['ModuleLoader (Registry)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'iterations' => 100,
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

        foreach ($this->results as $name => $data) {
            echo "{$name}:\n";
            
            if (isset($data['skipped'])) {
                echo "  Skipped\n";
                continue;
            }

            if (isset($data['avg_ms'])) {
                printf("  Average: %.3f ms\n", $data['avg_ms']);
                printf("  Min: %.3f ms, Max: %.3f ms\n", $data['min_ms'], $data['max_ms']);
            }

            if (isset($data['avg_us'])) {
                printf("  Average: %.2f µs\n", $data['avg_us']);
            }

            if (isset($data['ops_per_sec'])) {
                printf("  Throughput: %s ops/sec\n", number_format($data['ops_per_sec'], 0));
            }

            printf("  Iterations: %d\n", $data['iterations']);
            echo "\n";
        }

        echo "========================================\n";
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        printf("  OPcache: %s\n", function_exists('opcache_get_status') && opcache_get_status() ? 'Enabled' : 'Disabled');
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    
    // Bootstrap for benchmarks
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__, 2));
    }
    
    (new FrameworkBenchmark())->run();
}
