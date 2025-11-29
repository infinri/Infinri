<?php

declare(strict_types=1);

namespace Tests\Benchmark;

/**
 * Autoloader Performance Benchmark
 * 
 * Measures:
 * - Classmap load time
 * - PSR-4 resolution speed
 * - file_exists penalties
 * - OPcache impact
 * - Autoload hit ratio
 * 
 * Run with: php tests/Benchmark/AutoloaderBenchmark.php
 */
final class AutoloaderBenchmark
{
    private array $results = [];
    private int $iterations = 100;
    private array $testClasses = [];

    public function run(): void
    {
        echo "========================================\n";
        echo "  Autoloader Performance Benchmark\n";
        echo "========================================\n\n";

        $this->discoverTestClasses();
        
        $this->benchmarkClassmapLoad();
        $this->benchmarkPsr4Resolution();
        $this->benchmarkFileExistsPenalties();
        $this->benchmarkOpcacheImpact();
        $this->benchmarkAutoloadHitRatio();
        $this->benchmarkColdVsWarmLoad();

        $this->printResults();
    }

    /**
     * Discover available classes for testing
     */
    private function discoverTestClasses(): void
    {
        $appDir = dirname(__DIR__, 2) . '/app';
        
        // Find all PHP files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
                    if (preg_match('/class\s+(\w+)/', $content, $classMatch)) {
                        $this->testClasses[] = $nsMatch[1] . '\\' . $classMatch[1];
                    }
                }
            }
        }

        $this->testClasses = array_unique(array_slice($this->testClasses, 0, 200));
        echo "Discovered " . count($this->testClasses) . " classes for testing\n\n";
    }

    /**
     * Benchmark classmap lookup performance
     */
    private function benchmarkClassmapLoad(): void
    {
        echo "Benchmarking Classmap Load... ";

        $composerAutoload = dirname(__DIR__, 2) . '/vendor/composer/autoload_classmap.php';
        
        if (!file_exists($composerAutoload)) {
            $this->results['Classmap Load'] = ['skipped' => true, 'reason' => 'No classmap'];
            echo "Skipped\n";
            return;
        }

        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            $classmap = include $composerAutoload;
            $times[] = hrtime(true) - $start;
            
            // Clear OPcache for realistic measurement
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($composerAutoload, true);
            }
        }

        $this->results['Classmap Load'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'min_us' => min($times) / 1e3,
            'max_us' => max($times) / 1e3,
            'entries' => count($classmap ?? []),
            'iterations' => $this->iterations,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark PSR-4 autoload resolution speed
     */
    private function benchmarkPsr4Resolution(): void
    {
        echo "Benchmarking PSR-4 Resolution... ";

        if (empty($this->testClasses)) {
            $this->results['PSR-4 Resolution'] = ['skipped' => true];
            echo "Skipped\n";
            return;
        }

        // Classes already loaded - measure class_exists lookup
        $times = [];
        foreach ($this->testClasses as $class) {
            $start = hrtime(true);
            class_exists($class, false); // Don't trigger autoload
            $times[] = hrtime(true) - $start;
        }

        $this->results['PSR-4 Resolution (Cached)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => count($times) / (array_sum($times) / 1e9),
            'classes_tested' => count($times),
        ];

        // Test autoload trigger on unloaded class
        $syntheticClasses = [];
        for ($i = 0; $i < 100; $i++) {
            $syntheticClasses[] = 'Synthetic\\Benchmark\\Class' . $i . '_' . uniqid();
        }

        $times = [];
        foreach ($syntheticClasses as $class) {
            $start = hrtime(true);
            class_exists($class, true); // Trigger autoload (will fail)
            $times[] = hrtime(true) - $start;
        }

        $this->results['PSR-4 Resolution (Miss)'] = [
            'avg_us' => (array_sum($times) / count($times)) / 1e3,
            'ops_per_sec' => count($times) / (array_sum($times) / 1e9),
            'classes_tested' => count($times),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark file_exists penalties in autoloading
     */
    private function benchmarkFileExistsPenalties(): void
    {
        echo "Benchmarking file_exists Penalties... ";

        $appDir = dirname(__DIR__, 2) . '/app';
        $existingFiles = [];
        $nonExistingFiles = [];

        // Collect real files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($appDir, \FilesystemIterator::SKIP_DOTS)
        );
        $count = 0;
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php' && $count++ < 100) {
                $existingFiles[] = $file->getPathname();
            }
        }

        // Generate non-existing paths
        for ($i = 0; $i < 100; $i++) {
            $nonExistingFiles[] = $appDir . '/NonExistent/File' . $i . '_' . uniqid() . '.php';
        }

        // Benchmark existing files
        $times = [];
        foreach ($existingFiles as $file) {
            $start = hrtime(true);
            file_exists($file);
            $times[] = hrtime(true) - $start;
        }

        $this->results['file_exists (Hit)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => count($times) / (array_sum($times) / 1e9),
            'files_tested' => count($times),
        ];

        // Benchmark non-existing files
        $times = [];
        foreach ($nonExistingFiles as $file) {
            $start = hrtime(true);
            file_exists($file);
            $times[] = hrtime(true) - $start;
        }

        $this->results['file_exists (Miss)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => count($times) / (array_sum($times) / 1e9),
            'files_tested' => count($times),
        ];

        echo "Done\n";
    }

    /**
     * Benchmark OPcache impact on autoloading
     */
    private function benchmarkOpcacheImpact(): void
    {
        echo "Benchmarking OPcache Impact... ";

        $opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status() !== false;

        if (!$opcacheEnabled) {
            $this->results['OPcache Impact'] = ['skipped' => true, 'reason' => 'OPcache disabled'];
            echo "Skipped (OPcache disabled)\n";
            return;
        }

        $status = opcache_get_status();
        $stats = $status['opcache_statistics'] ?? [];

        $this->results['OPcache Status'] = [
            'enabled' => true,
            'cached_scripts' => $stats['num_cached_scripts'] ?? 0,
            'cache_hits' => $stats['hits'] ?? 0,
            'cache_misses' => $stats['misses'] ?? 0,
            'hit_ratio' => isset($stats['hits'], $stats['misses']) && ($stats['hits'] + $stats['misses']) > 0
                ? ($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100
                : 0,
            'memory_used_mb' => ($status['memory_usage']['used_memory'] ?? 0) / 1024 / 1024,
            'memory_free_mb' => ($status['memory_usage']['free_memory'] ?? 0) / 1024 / 1024,
        ];

        // Benchmark include with OPcache
        $testFile = dirname(__DIR__, 2) . '/app/Core/Application.php';
        if (!file_exists($testFile)) {
            echo "Skipped (test file not found)\n";
            return;
        }

        // With OPcache (warm cache)
        $times = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = hrtime(true);
            include_once $testFile;
            $times[] = hrtime(true) - $start;
        }

        $this->results['Include (OPcache Warm)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'iterations' => $this->iterations,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark autoload hit ratio
     */
    private function benchmarkAutoloadHitRatio(): void
    {
        echo "Benchmarking Autoload Hit Ratio... ";

        $declaredBefore = get_declared_classes();

        // Load some classes (suppress autoload warnings for missing files)
        $loaded = 0;
        $failed = 0;
        $oldErrorReporting = error_reporting(E_ERROR);
        foreach ($this->testClasses as $class) {
            if (@class_exists($class)) {
                $loaded++;
            } else {
                $failed++;
            }
        }
        error_reporting($oldErrorReporting);

        $declaredAfter = get_declared_classes();
        $newlyLoaded = count($declaredAfter) - count($declaredBefore);

        $this->results['Autoload Hit Ratio'] = [
            'classes_tested' => count($this->testClasses),
            'successfully_loaded' => $loaded,
            'failed_to_load' => $failed,
            'newly_autoloaded' => $newlyLoaded,
            'hit_ratio' => count($this->testClasses) > 0 
                ? ($loaded / count($this->testClasses)) * 100 
                : 0,
        ];

        echo "Done\n";
    }

    /**
     * Benchmark cold vs warm class loading
     */
    private function benchmarkColdVsWarmLoad(): void
    {
        echo "Benchmarking Cold vs Warm Load... ";

        // Warm load (classes already in memory)
        $times = [];
        foreach (array_slice($this->testClasses, 0, 50) as $class) {
            $start = hrtime(true);
            class_exists($class);
            $times[] = hrtime(true) - $start;
        }

        $this->results['Class Load (Warm)'] = [
            'avg_ns' => array_sum($times) / count($times),
            'ops_per_sec' => count($times) / (array_sum($times) / 1e9),
        ];

        // Measure get_class performance
        $objects = [];
        foreach (array_slice($this->testClasses, 0, 20) as $class) {
            try {
                $reflection = new \ReflectionClass($class);
                if (!$reflection->isAbstract() && !$reflection->isInterface() && !$reflection->isTrait()) {
                    $constructor = $reflection->getConstructor();
                    if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
                        $objects[] = $reflection->newInstance();
                    }
                }
            } catch (\Throwable) {
                // Skip classes that can't be instantiated
            }
        }

        if (!empty($objects)) {
            $times = [];
            foreach ($objects as $object) {
                for ($i = 0; $i < 100; $i++) {
                    $start = hrtime(true);
                    get_class($object);
                    $times[] = hrtime(true) - $start;
                }
            }

            $this->results['get_class()'] = [
                'avg_ns' => array_sum($times) / count($times),
                'ops_per_sec' => count($times) / (array_sum($times) / 1e9),
            ];
        }

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
                echo "  Skipped" . (isset($data['reason']) ? " ({$data['reason']})" : "") . "\n\n";
                continue;
            }

            foreach ($data as $key => $value) {
                if (is_float($value)) {
                    if (str_contains($key, '_us')) {
                        printf("  %s: %.2f µs\n", str_replace('_', ' ', ucfirst($key)), $value);
                    } elseif (str_contains($key, '_ns')) {
                        printf("  %s: %.0f ns\n", str_replace('_', ' ', ucfirst($key)), $value);
                    } elseif (str_contains($key, '_mb')) {
                        printf("  %s: %.2f MB\n", str_replace('_', ' ', ucfirst($key)), $value);
                    } elseif (str_contains($key, 'ratio')) {
                        printf("  %s: %.2f%%\n", str_replace('_', ' ', ucfirst($key)), $value);
                    } elseif (str_contains($key, 'per_sec')) {
                        printf("  %s: %s\n", str_replace('_', ' ', ucfirst($key)), number_format($value, 0));
                    } else {
                        printf("  %s: %.2f\n", str_replace('_', ' ', ucfirst($key)), $value);
                    }
                } else {
                    printf("  %s: %s\n", str_replace('_', ' ', ucfirst($key)), is_bool($value) ? ($value ? 'Yes' : 'No') : $value);
                }
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        $opcache = function_exists('opcache_get_status') && opcache_get_status();
        printf("  OPcache: %s\n", $opcache ? 'Enabled' : 'Disabled');
        echo "\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    (new AutoloaderBenchmark())->run();
}
