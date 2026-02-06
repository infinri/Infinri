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
namespace Tests\Benchmark;

use App\Core\Application;

/**
 * Long-Running & Memory Fragmentation Benchmark
 * 
 * Run the framework under:
 * - 10k requests
 * - 100k requests
 * - 1M requests
 * 
 * Measures:
 * - Memory growth
 * - Process stability
 * - Garbage collection overhead
 * - Leak detection
 * 
 * Useful for long-running processes (Swoole, RoadRunner).
 * 
 * Run with: php tests/Benchmark/LongRunningBenchmark.php [iterations]
 */
final class LongRunningBenchmark
{
    private array $results = [];
    private array $memorySnapshots = [];
    private array $gcStats = [];
    private string $tempDir;

    public function run(int $iterations = 10000): void
    {
        echo "========================================\n";
        echo "  Long-Running Benchmark\n";
        echo "========================================\n";
        printf("  Iterations: %s\n\n", number_format($iterations));

        $this->setupTempDir();

        try {
            $this->benchmarkMemoryGrowth($iterations);
            $this->benchmarkGarbageCollection($iterations);
            $this->benchmarkObjectChurn($iterations);
            $this->benchmarkStaticLeaks($iterations);
            $this->benchmarkContainerReuse($iterations);
            $this->analyzeMemoryPattern();
            $this->printResults();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Setup temporary directory
     */
    private function setupTempDir(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/longrun_bench_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/log', 0755, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=LongRunBench\nAPP_ENV=testing\n");
    }

    /**
     * Benchmark memory growth over iterations
     */
    private function benchmarkMemoryGrowth(int $iterations): void
    {
        echo "Benchmarking Memory Growth... ";

        gc_collect_cycles();
        $initialMemory = memory_get_usage(true);
        $checkpoints = [
            (int)($iterations * 0.1),
            (int)($iterations * 0.25),
            (int)($iterations * 0.5),
            (int)($iterations * 0.75),
            $iterations,
        ];

        Application::resetInstance();
        $app = new Application($this->tempDir);
        $app->bootstrap();

        $this->memorySnapshots = [
            0 => [
                'iteration' => 0,
                'memory_mb' => $initialMemory / 1024 / 1024,
                'objects' => 0,
            ],
        ];

        $startTime = hrtime(true);

        for ($i = 1; $i <= $iterations; $i++) {
            // Simulate request work
            $config = $app->make(\App\Core\Contracts\Config\ConfigInterface::class);
            $value = $config->get('app.name', 'default');
            
            // Create and discard objects
            $data = [
                'iteration' => $i,
                'timestamp' => microtime(true),
                'data' => str_repeat('x', 100),
            ];
            unset($data);

            // Snapshot at checkpoints
            if (in_array($i, $checkpoints)) {
                $this->memorySnapshots[$i] = [
                    'iteration' => $i,
                    'memory_mb' => memory_get_usage(true) / 1024 / 1024,
                    'memory_real_mb' => memory_get_usage(false) / 1024 / 1024,
                    'peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
                ];
            }

            // Progress indicator
            if ($i % 10000 === 0) {
                echo '.';
            }
        }

        $totalTime = hrtime(true) - $startTime;
        $finalMemory = memory_get_usage(true);

        $this->results['Memory Growth'] = [
            'initial_mb' => $initialMemory / 1024 / 1024,
            'final_mb' => $finalMemory / 1024 / 1024,
            'growth_mb' => ($finalMemory - $initialMemory) / 1024 / 1024,
            'growth_per_1k_iterations_kb' => (($finalMemory - $initialMemory) / ($iterations / 1000)) / 1024,
            'peak_mb' => memory_get_peak_usage(true) / 1024 / 1024,
            'total_time_sec' => $totalTime / 1e9,
            'iterations_per_sec' => $iterations / ($totalTime / 1e9),
        ];

        Application::resetInstance();

        echo " Done\n";
    }

    /**
     * Benchmark garbage collection overhead
     */
    private function benchmarkGarbageCollection(int $iterations): void
    {
        echo "Benchmarking Garbage Collection... ";

        // Reduce iterations for GC test
        $gcIterations = min($iterations, 50000);

        // With GC enabled
        gc_enable();
        gc_collect_cycles();

        $startMemory = memory_get_usage(true);
        $gcRuns = 0;
        $start = hrtime(true);

        for ($i = 0; $i < $gcIterations; $i++) {
            // Create circular references
            $obj1 = new \stdClass();
            $obj2 = new \stdClass();
            $obj1->ref = $obj2;
            $obj2->ref = $obj1;
            unset($obj1, $obj2);

            // Trigger GC periodically
            if ($i % 1000 === 0) {
                $collected = gc_collect_cycles();
                if ($collected > 0) $gcRuns++;
            }
        }

        $gcEnabledTime = hrtime(true) - $start;
        $gcEnabledMemory = memory_get_usage(true) - $startMemory;

        // Final collection
        $finalCollected = gc_collect_cycles();

        $this->results['GC Enabled'] = [
            'iterations' => $gcIterations,
            'time_ms' => $gcEnabledTime / 1e6,
            'memory_growth_mb' => $gcEnabledMemory / 1024 / 1024,
            'gc_runs' => $gcRuns,
            'final_collected' => $finalCollected,
            'overhead_per_iteration_ns' => $gcEnabledTime / $gcIterations,
        ];

        // With GC disabled (compare)
        gc_disable();
        $startMemory = memory_get_usage(true);
        $start = hrtime(true);

        for ($i = 0; $i < $gcIterations; $i++) {
            $obj1 = new \stdClass();
            $obj2 = new \stdClass();
            $obj1->ref = $obj2;
            $obj2->ref = $obj1;
            unset($obj1, $obj2);
        }

        $gcDisabledTime = hrtime(true) - $start;
        $gcDisabledMemory = memory_get_usage(true) - $startMemory;

        gc_enable();
        gc_collect_cycles();

        $this->results['GC Disabled'] = [
            'iterations' => $gcIterations,
            'time_ms' => $gcDisabledTime / 1e6,
            'memory_growth_mb' => $gcDisabledMemory / 1024 / 1024,
            'speedup' => $gcEnabledTime / max($gcDisabledTime, 1),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark object creation/destruction churn
     */
    private function benchmarkObjectChurn(int $iterations): void
    {
        echo "Benchmarking Object Churn... ";

        $churnIterations = min($iterations, 100000);
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        $start = hrtime(true);
        $pool = [];

        for ($i = 0; $i < $churnIterations; $i++) {
            // Create objects
            $obj = new class {
                public int $id;
                public string $data;
                public array $metadata = [];
                
                public function __construct() {
                    $this->id = random_int(1, 1000000);
                    $this->data = str_repeat('x', 50);
                    $this->metadata = ['created' => microtime(true)];
                }
            };

            // Pool management (keep max 100)
            $pool[] = $obj;
            if (count($pool) > 100) {
                array_shift($pool);
            }
        }

        $churnTime = hrtime(true) - $start;
        $churnMemory = memory_get_usage(true) - $startMemory;

        // Clear pool
        $pool = [];
        gc_collect_cycles();
        $afterGcMemory = memory_get_usage(true) - $startMemory;

        $this->results['Object Churn'] = [
            'iterations' => $churnIterations,
            'time_ms' => $churnTime / 1e6,
            'objects_per_sec' => $churnIterations / ($churnTime / 1e9),
            'memory_during_mb' => $churnMemory / 1024 / 1024,
            'memory_after_gc_mb' => $afterGcMemory / 1024 / 1024,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark static variable leaks
     */
    private function benchmarkStaticLeaks(int $iterations): void
    {
        echo "Benchmarking Static Leaks... ";

        $leakIterations = min($iterations, 10000);

        // Class with static storage
        $leakyClass = new class {
            public static array $cache = [];
            public static array $log = [];
            
            public static function process(int $id): void {
                self::$cache[$id] = ['data' => str_repeat('x', 100), 'time' => microtime(true)];
                self::$log[] = "Processed {$id}";
                
                // Simulate unbounded growth
                if (count(self::$cache) > 1000) {
                    // Bad: only trim occasionally
                }
            }
            
            public static function reset(): void {
                self::$cache = [];
                self::$log = [];
            }
        };

        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        // Without cleanup
        for ($i = 0; $i < $leakIterations; $i++) {
            $leakyClass::process($i);
        }

        $leakedMemory = memory_get_usage(true) - $startMemory;
        $cacheSize = count($leakyClass::$cache);
        $logSize = count($leakyClass::$log);

        $this->results['Static Leak (No Cleanup)'] = [
            'iterations' => $leakIterations,
            'memory_leaked_mb' => $leakedMemory / 1024 / 1024,
            'cache_entries' => $cacheSize,
            'log_entries' => $logSize,
            'bytes_per_iteration' => $leakedMemory / $leakIterations,
        ];

        // Reset and test with cleanup
        $leakyClass::reset();
        gc_collect_cycles();
        $startMemory = memory_get_usage(true);

        // With periodic cleanup
        for ($i = 0; $i < $leakIterations; $i++) {
            $leakyClass::process($i);
            
            // Clean up periodically
            if ($i % 100 === 0) {
                // Keep only last 100 entries
                if (count($leakyClass::$cache) > 100) {
                    $leakyClass::$cache = array_slice($leakyClass::$cache, -100, 100, true);
                }
                if (count($leakyClass::$log) > 100) {
                    $leakyClass::$log = array_slice($leakyClass::$log, -100);
                }
            }
        }

        $cleanedMemory = memory_get_usage(true) - $startMemory;

        $this->results['Static Leak (With Cleanup)'] = [
            'iterations' => $leakIterations,
            'memory_used_mb' => $cleanedMemory / 1024 / 1024,
            'memory_saved_mb' => ($leakedMemory - $cleanedMemory) / 1024 / 1024,
            'reduction_percent' => (($leakedMemory - $cleanedMemory) / max($leakedMemory, 1)) * 100,
        ];

        $leakyClass::reset();

        echo "Done\n";
    }

    /**
     * Benchmark container reuse behavior
     */
    private function benchmarkContainerReuse(int $iterations): void
    {
        echo "Benchmarking Container Reuse... ";

        $reuseIterations = min($iterations, 10000);

        Application::resetInstance();
        $app = new Application($this->tempDir);
        $app->bootstrap();

        gc_collect_cycles();
        $startMemory = memory_get_usage(true);
        $start = hrtime(true);

        // Simulate worker reusing container
        for ($i = 0; $i < $reuseIterations; $i++) {
            // Resolve services (should be singletons)
            $config = $app->make(\App\Core\Contracts\Config\ConfigInterface::class);
            $logger = $app->make(\App\Core\Contracts\Log\LoggerInterface::class);
            
            // Some work
            $config->get('app.name');
        }

        $reuseTime = hrtime(true) - $start;
        $reuseMemory = memory_get_usage(true) - $startMemory;

        $this->results['Container Reuse'] = [
            'iterations' => $reuseIterations,
            'time_ms' => $reuseTime / 1e6,
            'resolutions_per_sec' => ($reuseIterations * 2) / ($reuseTime / 1e9),
            'memory_growth_mb' => $reuseMemory / 1024 / 1024,
            'memory_per_iteration_bytes' => $reuseMemory / $reuseIterations,
        ];

        Application::resetInstance();

        echo "Done\n";
    }

    /**
     * Analyze memory pattern
     */
    private function analyzeMemoryPattern(): void
    {
        echo "Analyzing Memory Pattern... ";

        if (count($this->memorySnapshots) < 2) {
            echo "Not enough data\n";
            return;
        }

        $snapshots = array_values($this->memorySnapshots);
        $growthRates = [];

        for ($i = 1; $i < count($snapshots); $i++) {
            $prevSnapshot = $snapshots[$i - 1];
            $currSnapshot = $snapshots[$i];
            
            $iterDiff = $currSnapshot['iteration'] - $prevSnapshot['iteration'];
            $memDiff = $currSnapshot['memory_mb'] - $prevSnapshot['memory_mb'];
            
            if ($iterDiff > 0) {
                $growthRates[] = $memDiff / $iterDiff * 1000; // KB per 1000 iterations
            }
        }

        $avgGrowthRate = count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 0;
        $isLeaking = $avgGrowthRate > 10; // More than 10KB per 1000 iterations

        $this->results['Memory Analysis'] = [
            'snapshots' => count($this->memorySnapshots),
            'avg_growth_rate_kb_per_1k' => $avgGrowthRate,
            'pattern' => match (true) {
                $avgGrowthRate < 1 => 'Stable (Excellent)',
                $avgGrowthRate < 5 => 'Minimal Growth (Good)',
                $avgGrowthRate < 10 => 'Moderate Growth (Acceptable)',
                $avgGrowthRate < 50 => 'Significant Growth (Warning)',
                default => 'Memory Leak Detected (Critical)',
            },
            'leak_detected' => $isLeaking ? 'YES' : 'NO',
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

        // Memory snapshots
        if (!empty($this->memorySnapshots)) {
            echo "Memory Snapshots:\n";
            echo str_repeat('-', 60) . "\n";
            printf("%-15s %12s %12s %12s\n", "Iteration", "Memory (MB)", "Real (MB)", "Peak (MB)");
            echo str_repeat('-', 60) . "\n";
            
            foreach ($this->memorySnapshots as $snapshot) {
                printf("%-15s %12.2f %12.2f %12.2f\n",
                    number_format($snapshot['iteration']),
                    $snapshot['memory_mb'],
                    $snapshot['memory_real_mb'] ?? $snapshot['memory_mb'],
                    $snapshot['peak_mb'] ?? $snapshot['memory_mb']
                );
            }
            echo "\n";
        }

        // Other results
        foreach ($this->results as $name => $data) {
            echo "{$name}:\n";
            
            foreach ($data as $key => $value) {
                $label = str_replace('_', ' ', ucfirst($key));
                
                if (is_float($value)) {
                    if (str_contains($key, '_mb')) {
                        printf("  %s: %.2f MB\n", $label, $value);
                    } elseif (str_contains($key, '_kb')) {
                        printf("  %s: %.2f KB\n", $label, $value);
                    } elseif (str_contains($key, '_ms')) {
                        printf("  %s: %.2f ms\n", $label, $value);
                    } elseif (str_contains($key, '_sec') && !str_contains($key, 'per_sec')) {
                        printf("  %s: %.2f s\n", $label, $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", $label, number_format($value, 0));
                    } elseif (str_contains($key, 'percent')) {
                        printf("  %s: %.1f%%\n", $label, $value);
                    } elseif (str_contains($key, 'bytes')) {
                        printf("  %s: %.0f bytes\n", $label, $value);
                    } else {
                        printf("  %s: %.2f\n", $label, $value);
                    }
                } else {
                    printf("  %s: %s\n", $label, $value);
                }
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "  Long-Running Recommendations\n";
        echo "========================================\n";
        echo "  - Implement periodic GC in worker loops\n";
        echo "  - Bound static caches with LRU eviction\n";
        echo "  - Monitor memory between requests\n";
        echo "  - Use weak references where appropriate\n";
        echo "  - Reset application state between workers\n";
        echo "\n";

        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Final Memory: %.2f MB\n", memory_get_usage(true) / 1024 / 1024);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        echo "\n";
    }

    /**
     * Cleanup
     */
    private function cleanup(): void
    {
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
    
    $iterations = isset($argv[1]) ? (int)$argv[1] : 10000;
    (new LongRunningBenchmark())->run($iterations);
}
