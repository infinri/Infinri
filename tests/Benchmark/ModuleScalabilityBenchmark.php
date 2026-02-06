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
 * Module Scalability Benchmark
 * 
 * Tests scalability of the module system with varying module counts:
 * - 1 module
 * - 10 modules
 * - 100 modules
 * - 250 modules
 * 
 * Measures:
 * - Overall boot time
 * - Dependency resolution time
 * - Provider registration cost
 * - Event subscription cost
 * - Memory growth
 * 
 * Ideal pattern: Boot time should grow logarithmically, not linearly.
 * 
 * Run with: php tests/Benchmark/ModuleScalabilityBenchmark.php
 */
final class ModuleScalabilityBenchmark
{
    private array $results = [];
    private string $tempDir;
    private const MODULE_COUNTS = [1, 10, 50, 100, 250];
    private const ITERATIONS = 5;

    public function run(): void
    {
        echo "========================================\n";
        echo "  Module Scalability Benchmark\n";
        echo "========================================\n\n";

        $this->tempDir = sys_get_temp_dir() . '/module_bench_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/var/log', 0755, true);
        mkdir($this->tempDir . '/var/cache', 0755, true);
        mkdir($this->tempDir . '/config', 0755, true);
        mkdir($this->tempDir . '/app/Modules', 0755, true);
        file_put_contents($this->tempDir . '/.env', "APP_NAME=ModuleBench\nAPP_ENV=testing\n");

        try {
            foreach (self::MODULE_COUNTS as $count) {
                $this->benchmarkModuleCount($count);
            }

            $this->analyzeScalability();
            $this->printResults();
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Benchmark boot time with specific module count
     */
    private function benchmarkModuleCount(int $moduleCount): void
    {
        echo "Benchmarking {$moduleCount} modules... ";

        // Generate synthetic modules
        $this->generateModules($moduleCount);

        $bootTimes = [];
        $memoryUsages = [];
        $resolutionTimes = [];
        $registrationTimes = [];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            gc_collect_cycles();
            $startMemory = memory_get_usage(true);
            
            Application::resetInstance();

            // Measure total boot time
            $bootStart = hrtime(true);
            
            $app = new Application($this->tempDir);
            
            // Measure provider registration
            $regStart = hrtime(true);
            $app->bootstrap();
            $registrationTimes[] = hrtime(true) - $regStart;
            
            $bootTimes[] = hrtime(true) - $bootStart;
            $memoryUsages[] = memory_get_usage(true) - $startMemory;

            // Measure dependency resolution
            $resStart = hrtime(true);
            for ($j = 0; $j < min($moduleCount, 50); $j++) {
                try {
                    $app->make("App\\Modules\\SyntheticModule{$j}\\SyntheticService{$j}");
                } catch (\Throwable) {
                    // Expected - synthetic classes don't exist
                }
            }
            $resolutionTimes[] = hrtime(true) - $resStart;
        }

        $this->results["Modules ({$moduleCount})"] = [
            'module_count' => $moduleCount,
            'boot_time_ms' => (array_sum($bootTimes) / count($bootTimes)) / 1e6,
            'boot_time_min_ms' => min($bootTimes) / 1e6,
            'boot_time_max_ms' => max($bootTimes) / 1e6,
            'registration_time_ms' => (array_sum($registrationTimes) / count($registrationTimes)) / 1e6,
            'resolution_time_ms' => (array_sum($resolutionTimes) / count($resolutionTimes)) / 1e6,
            'memory_growth_mb' => (array_sum($memoryUsages) / count($memoryUsages)) / 1024 / 1024,
            'memory_per_module_kb' => ((array_sum($memoryUsages) / count($memoryUsages)) / $moduleCount) / 1024,
            'iterations' => self::ITERATIONS,
        ];

        // Cleanup synthetic modules
        $this->cleanupModules($moduleCount);

        echo "Done\n";
    }

    /**
     * Generate synthetic modules for testing
     */
    private function generateModules(int $count): void
    {
        $modulesDir = $this->tempDir . '/app/Modules';

        for ($i = 0; $i < $count; $i++) {
            $moduleDir = $modulesDir . "/SyntheticModule{$i}";
            mkdir($moduleDir, 0755, true);

            // Create module.json
            $manifest = [
                'name' => "SyntheticModule{$i}",
                'version' => '1.0.0',
                'description' => "Synthetic module {$i} for benchmarking",
                'providers' => ["App\\Modules\\SyntheticModule{$i}\\SyntheticProvider{$i}"],
                'dependencies' => $i > 0 ? ["SyntheticModule" . ($i - 1)] : [],
            ];
            file_put_contents($moduleDir . '/module.json', json_encode($manifest, JSON_PRETTY_PRINT));

            // Create service provider
            $providerCode = <<<PHP
<?php declare(strict_types=1);
namespace App\Modules\SyntheticModule{$i};

use App\Core\Container\ServiceProvider;

class SyntheticProvider{$i} extends ServiceProvider
{
    public function register(): void
    {
        \$this->container->bind(SyntheticService{$i}::class, fn() => new SyntheticService{$i}());
    }

    public function boot(): void
    {
        // Simulate boot work
        usleep(10); // 10 microseconds
    }
}
PHP;
            file_put_contents($moduleDir . "/SyntheticProvider{$i}.php", $providerCode);

            // Create service class
            $serviceCode = <<<PHP
<?php declare(strict_types=1);
namespace App\Modules\SyntheticModule{$i};

class SyntheticService{$i}
{
    public function __construct()
    {
        // Simulate construction work
    }

    public function execute(): string
    {
        return 'Module {$i} executed';
    }
}
PHP;
            file_put_contents($moduleDir . "/SyntheticService{$i}.php", $serviceCode);
        }
    }

    /**
     * Cleanup synthetic modules
     */
    private function cleanupModules(int $count): void
    {
        $modulesDir = $this->tempDir . '/app/Modules';
        
        for ($i = 0; $i < $count; $i++) {
            $moduleDir = $modulesDir . "/SyntheticModule{$i}";
            if (is_dir($moduleDir)) {
                $this->removeDirectory($moduleDir);
            }
        }
    }

    /**
     * Analyze scalability patterns
     */
    private function analyzeScalability(): void
    {
        echo "\nAnalyzing scalability patterns... ";

        $points = [];
        foreach ($this->results as $data) {
            if (isset($data['module_count'], $data['boot_time_ms'])) {
                $points[] = [
                    'modules' => $data['module_count'],
                    'time' => $data['boot_time_ms'],
                ];
            }
        }

        if (count($points) < 2) {
            echo "Not enough data\n";
            return;
        }

        // Calculate growth pattern
        $growthRatios = [];
        for ($i = 1; $i < count($points); $i++) {
            $moduleRatio = $points[$i]['modules'] / $points[$i - 1]['modules'];
            $timeRatio = $points[$i]['time'] / max($points[$i - 1]['time'], 0.001);
            $growthRatios[] = [
                'from' => $points[$i - 1]['modules'],
                'to' => $points[$i]['modules'],
                'module_ratio' => $moduleRatio,
                'time_ratio' => $timeRatio,
                'efficiency' => $moduleRatio / max($timeRatio, 0.001),
            ];
        }

        // Determine pattern
        $avgEfficiency = array_sum(array_column($growthRatios, 'efficiency')) / count($growthRatios);
        
        $pattern = match (true) {
            $avgEfficiency > 2.0 => 'Excellent (Sub-linear / Logarithmic)',
            $avgEfficiency > 1.0 => 'Good (Near-linear with optimizations)',
            $avgEfficiency > 0.5 => 'Acceptable (Linear)',
            default => 'Poor (Super-linear - needs optimization)',
        };

        $this->results['Scalability Analysis'] = [
            'pattern' => $pattern,
            'avg_efficiency' => $avgEfficiency,
            'growth_ratios' => $growthRatios,
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
            if ($name === 'Scalability Analysis') {
                continue; // Print last
            }

            echo "{$name}:\n";
            printf("  Boot Time: %.3f ms (min: %.3f, max: %.3f)\n", 
                $data['boot_time_ms'], $data['boot_time_min_ms'], $data['boot_time_max_ms']);
            printf("  Registration Time: %.3f ms\n", $data['registration_time_ms']);
            printf("  Resolution Time: %.3f ms\n", $data['resolution_time_ms']);
            printf("  Memory Growth: %.2f MB\n", $data['memory_growth_mb']);
            printf("  Memory Per Module: %.2f KB\n", $data['memory_per_module_kb']);
            echo "\n";
        }

        // Print scalability analysis
        if (isset($this->results['Scalability Analysis'])) {
            $analysis = $this->results['Scalability Analysis'];
            echo "========================================\n";
            echo "  Scalability Analysis\n";
            echo "========================================\n";
            printf("  Pattern: %s\n", $analysis['pattern']);
            printf("  Average Efficiency: %.2f\n", $analysis['avg_efficiency']);
            echo "\n  Growth Ratios:\n";
            foreach ($analysis['growth_ratios'] as $ratio) {
                printf("    %d → %d modules: %.2fx modules, %.2fx time (efficiency: %.2f)\n",
                    $ratio['from'], $ratio['to'], $ratio['module_ratio'], $ratio['time_ratio'], $ratio['efficiency']);
            }
            echo "\n";
        }

        echo "========================================\n";
        echo "  Summary\n";
        echo "========================================\n";
        printf("  PHP Version: %s\n", PHP_VERSION);
        printf("  Peak Memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);
        
        // Calculate boot time per module for different scales
        $firstResult = $this->results['Modules (1)'] ?? null;
        $lastResult = end($this->results);
        if ($firstResult && is_array($lastResult) && isset($lastResult['boot_time_ms'])) {
            $scale = $lastResult['module_count'] / $firstResult['module_count'];
            $timeScale = $lastResult['boot_time_ms'] / max($firstResult['boot_time_ms'], 0.001);
            printf("  Scaling Factor: %dx modules = %.2fx boot time\n", (int)$scale, $timeScale);
        }
        echo "\n";
    }

    /**
     * Cleanup temporary directory
     */
    private function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    /**
     * Recursively remove directory
     */
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
}

// Run if executed directly
if (php_sapi_name() === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    
    if (!defined('BASE_PATH')) {
        define('BASE_PATH', dirname(__DIR__, 2));
    }
    
    (new ModuleScalabilityBenchmark())->run();
}
